<?php

namespace vpashkov\represent\core;


use vpashkov\represent\helpers\H;
use yii\db\ActiveRecord;

class Structure
{
    public $action;

    /** @var Structure[] $deleteQueue */
    public array $deleteQueue = [];

    public ?array $structure;
    private Schema $schema;

    private ?ModelInterface $model;

    public Represent $represent;

    function __construct($structure, $map, $represent)
    {
        $this->structure = $structure;
        $this->schema = $map;
        $this->represent = $represent;
        $this->model = $this->findModel();
    }

    protected function findModel()
    {
        $whereCondition = [];
        foreach ($this->schema->tableSchema['pks'] as $key) {
            if (isset($this->structure[$key])) {
                $whereCondition[$key] = $this->structure[$key];
            } else {
                $whereCondition[$key] = null;
            }
        }

        $model = $this->represent->config->modelClass::representFind($this->schema->modelClass, $whereCondition);
        if ($model == null) {
            $model = new $this->schema->modelClass();
        }

        return $model;
    }

    public function save(Structure $parent = null)
    {
        if (H::get($this->structure, $this->represent->config->deleteFlag) === true) {
            if ($parent == null) {
                $this->representDelete();
            } else {
                if ($this->schema->relationSchema['type'] === 'parent') {
                    $selfLink = $this->schema->relationSchema['selfLink'];
                    $parent->structure[$selfLink] = null;
                    $parent->model->$selfLink = null;
                    $parent->model->representSave($this->schema);
//                    $parent->model->representUnlink($this->schema->relationName, $this->model, false);
                }
                $parent->deleteQueue [] = $this;
            }

            $this->action = 'd';

            return null;
        }

        $this->model->representSetAttributes($this->structure, $this->schema);

        $this->saveRelations('parent');

        $this->action = $this->model->representIsNewRecord() ? 'c' : 'u';
        if (static::checkRights($this->action, $this->schema->rights, ['model' => $this->model, 'row' => $this->structure])) {
            if (!$this->model->representSave($this->schema)) {
                $tableName = $this->model->tableName();
                throw new \Exception(print_r([
                    'model attributes' => $this->model->attributes,
                    'model errors' => $this->model->getErrors(),
                    'structure' => $this->structure,
                    'tableName' => $tableName,
                ], true));
            }
        }

        $this->saveRelations('depend');
        $this->saveRelations('via');

        foreach ($this->deleteQueue as $structure) {
            if ($structure->schema->relationSchema['type'] === 'via') {
                $this->model->representUnlink($structure->schema->relationName, $structure->model, true, $this->schema, $structure->schema);
            }
            $structure->delete();
        }

        return $this->model;
    }

    protected function saveRelations($relType)
    {
        foreach ($this->schema->relations as $relationName => $relation) {
            if ($relation->relationSchema['type'] == $relType) {
                if (array_key_exists($relationName, $this->structure) && $this->structure[$relationName] != null) {
                    if ($relation->relationSchema['multiple'] === true) {
                        foreach ($this->structure[$relationName] as $item) {
                            $this->saveRelationModel($item, $relation);
                        }
                    } else {
                        $this->saveRelationModel($this->structure[$relationName], $relation);
                    }
                }
            }
        }
    }

    protected function saveRelationModel(array $relationRow, SchemaRelation $relation)
    {

        if ($relation->relationSchema['type'] == 'depend') {
            $relationRow[$relation->relationSchema['foreignLink']] = $this->model->representGetValue($relation->relationSchema['selfLink']);
        }

        $relatedStructure = new Structure($relationRow, $relation, $this->represent);
        $relatedModel = $relatedStructure->save($this);

        if ($relatedModel == null) {
            return;
        }

        if ($relation->relationSchema['type'] == 'parent') {
            $this->model->representSetValue($relation->relationSchema['selfLink'],
                $relatedModel->representGetValue($relation->relationSchema['foreignLink']));
        }
        if ($relation->relationSchema['type'] == 'via') {
            if (!$this->model->representIsLinked($relatedModel, $relation)) {
                $this->model->representLink($relation->relationName, $relatedModel, $this->schema, $relation);
            }
        }

        if (H::get($relationRow, $this->represent->config->unlinkFlag) === true) {
            $delete = $relation->relationSchema['type'] == 'via' ? true : false;
            $this->model->representUnlink($relation->relationName, $relatedModel, $delete, $this->schema, $relation);
        }
    }


    public function delete()
    {
        if ($this->model->representIsNewRecord() === false) {
            $this->deleteWithRelations($this->model, $this->schema);
        }
    }

    protected function deleteWithRelations($model, Schema $schema)
    {
        if (!static::checkRights("d", $schema->rights, ['model' => $this->model, 'row' => $this->structure])) {
            return;
        }

        $this->deleteRelations($model, $schema, 'depend');
        $this->deleteRelations($model, $schema, 'via');

//		var_dump($schema->modelName);
//		if ($schema->modelName === 'Category')
//			{
//			die;
//			}
        $model->representDelete();

        $this->deleteRelations($model, $schema, 'parent');
    }


    private function deleteRelations($model, $map, $relType)
    {
        foreach ($map->relations as $relationName => $relation) {

            if ($relation->relationSchema['type'] == $relType) {
                if ($relation->relationSchema['multiple'] === true) {
                    $relModels = $model->representGetRelated($relationName);
                    foreach ($relModels as $relModel) {
                        if (static::checkRights("d", $relation->rights, ['model' => $relModel])) {
                            if ($relType === 'via') {
                                $this->model->representUnlink($relationName, $relModel, true);
                            }
                            $this->deleteWithRelations($relModel, $relation);
                        }
                    }
                } else {
                    $relModel = $model->representGetRelated($relationName);
                    if ($relModel != null) {
                        if (static::checkRights("d", $relation->rights, ['model' => $relModel])) {
                            $this->deleteWithRelations($relModel, $relation);
                        }

                    }
                }
            }
        }
    }

    public function minifyRow()
    {
        if ($this->structure === null) {
            return null;

        }
        $row = [];
        foreach ($this->schema->tableSchema['pks'] as $pk) {
            if (isset($this->structure[$pk])) {
                $row[$pk] = $this->structure[$pk];
            }
        }

        return $row;
    }


    public static function checkRights($action, $rights, $params = [])
    {
        if (array_key_exists($action, $rights)) {
            $actionRights = $rights[$action];
            $rightsMap = array_flip($actionRights);
            if (array_key_exists('#all', $rightsMap)) {
                return true;
            } else {
                return true;
//				$params['action'] = $action;
//				foreach ($actionRights as $right)
//					{
//					if (\yii::$app->user->can($right, $params))
//						{
//						return true;
//						}
//					}
            }
        }

//		throw new \yii\web\ForbiddenHttpException("Access denied: $action, " . print_r($rights, true));
        return false;
    }
}
