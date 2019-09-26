<?php

namespace vpashkov\represent;


use yii\db\ActiveRecord;

class RepresentModel
	{
	public $action;

	/** @var RepresentModel[] $deleteQueue */
	public $deleteQueue = [];

	public $row;
	/** @var Map $map */
	private $map;
	/** @var ActiveRecord $model */
	private $model;

	function __construct($row, $map)
		{
		$this->row = $row;
		$this->map = $map;
		$this->model = $this->findModel();
		}

	/**
	 * @return ActiveRecord
	 */
	protected function findModel()
		{
		$whereCondition = [];
		foreach ($this->map->pks as $key)
			{
			if (!array_key_exists($key, $this->row))
				{
				$this->row[ $key ] = null;
				}
			$whereCondition[ $key ] = $this->row[ $key ];
			}

		$model = $this->map->modelClass::find()->where($whereCondition)->one();
		if ($model == null)
			{
			$model = new $this->map->modelClass();
			}
		return $model;
		}

	/**
	 * @param RepresentModel $parent
	 * @return ActiveRecord
	 * @throws RepresentModelException
	 */
	public function representSave($parent = null)
		{
		if (array_key_exists(Represent::DELETE_FLAG, $this->row) && $this->row[ Represent::DELETE_FLAG ] != false)
			{
			if ($parent == null)
				{
				$this->representDelete();
				}
			else
				{
				$parent->deleteQueue [] = $this;
				}
			$this->action = 'd';
			return null;
			}

		$this->setAttributes();

		$this->saveRelations('parent');

		$this->action = $this->model->getIsNewRecord() ? 'c' : 'u';
		if (static::checkRights($this->action, $this->map->rights, ['model' => $this->model, 'row' => $this->row]))
			{
			if (!$this->model->save())
				{
				$tableName = $this->model->tableName();
				throw new RepresentModelException($this->model, $this->model->getErrors(), $this->row, $tableName);
				}
			}

		static::saveRelations('depend');
		static::saveRelations('via');

		foreach ($this->deleteQueue as $representModel)
			{
			if ($representModel->map->via != null)
				{
				$this->model->unlink($representModel->map->relationName, $representModel->model, true);
				}
			$representModel->representDelete();
			}

		return $this->model;
		}

	/**
	 * @param string $relType
	 */
	protected function saveRelations($relType)
		{
		foreach ($this->map->relations as $relationName => $relation)
			{
			if ($relation->relType == $relType)
				{
				if (array_key_exists($relationName, $this->row) && $this->row[ $relationName ] != null)
					{
					if ($relation->multiple)
						{
						foreach ($this->row[ $relationName ] as $item)
							{
							$this->saveRelationModel($item, $relation);
							}
						}
					else
						{
						$this->saveRelationModel($this->row[ $relationName ], $relation);
						}
					}
				}
			}
		}

	/**
	 * @param array $relationRow
	 * @param Map $relation
	 */
	protected function saveRelationModel($relationRow, $relation)
		{
		if ($relation->relType == 'depend')
			{
			foreach ($relation->link as $thisKey => $parentKey)
				{
				$relationRow[ $thisKey ] = $this->model->$parentKey;
				}
			}

		$relatedRepresentModel = new RepresentModel($relationRow, $relation);
		$relatedModel = $relatedRepresentModel->representSave($this);

		if ($relatedModel == null)
			{
			return;
			}

		if ($relation->relType == 'parent')
			{
			foreach ($relation->link as $thisKey => $parentKey)
				{
				$this->model->$parentKey = $relatedModel->$thisKey;
				}
			}
		if ($relation->relType == 'via')
			{
			if (!$this->isLinked($relatedModel, $relation))
				{
				$this->model->link($relation->relationName, $relatedModel);
				}
			}

		if (array_key_exists(Represent::UNLINK_FLAG, $relationRow) && $relationRow[ Represent::UNLINK_FLAG ] != false)
			{
			$delete = $relation->via != null ? true : false;
			$this->model->unlink($relation->relationName, $relatedModel, $delete);
			}
		}

	/**
	 * @param ActiveRecord $model
	 * @param Map $relation
	 * @return bool
	 */
	public function isLinked($model, $relation)
		{
		$where = [];
		foreach ($relation->pks as $key)
			{
			$where[ $key ] = $model->$key;
			}
		$result = $this->model->getRelation($relation->relationName)->where($where)->one();
		return $result !== null;
		}

	/**
	 *
	 */
	protected function setAttributes()
		{
		foreach ($this->map->fields as $fieldName => $field)
			{
			if (array_key_exists($fieldName, $this->row))
				{
				if ($this->model->$fieldName !== $this->row [ $fieldName ])
					{
					$this->model->$fieldName = $this->row [ $fieldName ];
					}
				}
			}
		}

	/**
	 *
	 */
	public function representDelete()
		{
		if ($this->model->isNewRecord === false)
			{
			$this->representDeleteWithRelations($this->model, $this->map);
			}
		}

	/**
	 * @param ActiveRecord $model
	 * @param Map $map
	 */
	protected function representDeleteWithRelations($model, $map)
		{
		if (!static::checkRights("d", $map->rights, ['model' => $this->model, 'row' => $this->row]))
			{
			return;
			}

		$this->deleteRelations($model, $map, 'depend');
		$this->deleteRelations($model, $map, 'via');

		$model->delete();

		$this->deleteRelations($model, $map, 'parent');
		}

	/**
	 * @param ActiveRecord $model
	 * @param Map $map
	 * @param string $relType
	 */
	private function deleteRelations($model, $map, $relType)
		{
		foreach ($map->relations as $relationName => $relation)
			{
			if ($relation->relType == $relType)
				{
				if ($relation->multiple)
					{
					/** @var ActiveRecord[] $relModels */
					$relModels = $model->$relationName;
					foreach ($relModels as $relModel)
						{
						if (static::checkRights("d", $relation->rights, ['model' => $relModel]))
							{
							$this->representDeleteWithRelations($relModel, $relation);
							}
						}
					}
				else
					{
					/** @var ActiveRecord $relModel */
					$relModel = $model->$relationName;
					if ($relModel != null)
						{
						if (static::checkRights("d", $relation->rights, ['model' => $relModel]))
							{
							$this->representDeleteWithRelations($relModel, $relation);
							}

						}
					}
				}
			}
		}

	public function minifyRow()
		{
		$row = [];
		foreach ($this->map->pks as $pk)
			{
			$row[ $pk ] = $this->row[ $pk ];
			}
		return $row;
		}


	/**
	 * @param string $action
	 * @param array $rights
	 * @param array $params
	 * @return bool
	 */
	public static function checkRights($action, $rights, $params = [])
		{
//		return strpos($actions, $action) === false ? false : true;
		if (array_key_exists($action, $rights))
			{
			$actionRights = $rights[ $action ];
			$rightsMap = array_flip($actionRights);
			if (array_key_exists(Represent::ALLOW_ALL_RIGHT, $rightsMap))
				{
				return true;
				}
			else
				{
				$params['action'] = $action;
				foreach ($actionRights as $right)
					{
					if (\yii::$app->user->can($right, $params))
						{
						return true;
						}
					}
				}
			}
//		throw new \yii\web\ForbiddenHttpException("Access denied: $action, " . print_r($rights, true));
		return false;
		}
	}