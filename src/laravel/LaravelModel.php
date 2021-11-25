<?php


namespace vpashkov\represent\laravel;


use Illuminate\Database\Eloquent\Model;
use vpashkov\represent\core\ModelInterface;
use vpashkov\represent\core\Schema;
use vpashkov\represent\helpers\H;
use yii\db\ActiveRecord;

class LaravelModel extends Model implements ModelInterface
{

    static public function represent($schema, $parameters = [])
    {
        $schema['#model'] = static::class;
        $schema['#rights'] = 'crud';
        $schema[] = '*';
        return LaravelRepresent::bySchema($schema, $parameters);
    }

    static public function representFind($class, $where)
    {
        $query = $class::query();
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return $query->first();
    }

    public function representSetAttributes(array $structure, Schema $schema)
    {
        foreach ($schema->fields as $fieldName => $field) {
            if (array_key_exists($fieldName, $structure)) {
                if ($this->$fieldName !== $structure [$fieldName]) {
                    $this->$fieldName = $structure[$fieldName];
                }
            }
        }
    }

    public function representSave(Schema $schema)
    {
//        $pk = $this->primaryKey;
//        if (!$this->exists && $this->$pk === null) {
//            $this->$pk = H::uuid();
//        }
        if (!$this->exists) {
            if (count($schema->tableSchema['pks']) === 1 && array_key_exists('id', $schema->tableSchema['pks'])) {
                if ($this->id === null) {
                    $this->id = H::uuid();
                }
            }
        }

        $result = $this->save();
        $this->refresh();

        return $result;
    }

    public function representIsLinked($model, $relation)
    {

        $relationName = $relation->relationName;
        $query = $this->$relationName()->getQuery();
        $where = [];
        foreach ($relation->tableSchema['pks'] as $key) {
            $query->where($key, $model->$key);
        }
        $result = $query->first();

        return $result !== null;
    }

    public function representLink($relationName, $model, $parentRelation, $childRelation)
    {
//        var_dump($parentRelation);
//        var_dump($childRelation);die;
        $childRelation->relationSchema['via'];
        $table = $childRelation->config->w($childRelation->relationSchema['via']['viaTable']);
        $selfLink = $childRelation->relationSchema['via']['selfLink'];
        $selfValue = $parentRelation->wrapValue($selfLink, $this->$selfLink);
        $foreignLink = $childRelation->relationSchema['via']['foreignLink'];
        $foreignValue = $childRelation->wrapValue($foreignLink, $model->$selfLink);
        $selfInViaLink = $childRelation->config->w($childRelation->relationSchema['via']['selfInViaLink']);
        $foreignInViaLink = $childRelation->config->w($childRelation->relationSchema['via']['foreignInViaLink']);

        $sql = "INSERT INTO $table ($selfInViaLink, $foreignInViaLink) VALUES ({$selfValue}, {$foreignValue})";
        LaravelRepresent::execSql($sql);
    }


    public function representUnlink($relationName, $model, $deleteFlag, $parentRelation, $childRelation)
    {
        if ($childRelation->relationSchema['type'] === 'via') {
            $childRelation->relationSchema['via'];
            $table = $childRelation->config->w($childRelation->relationSchema['via']['viaTable']);
            $selfLink = $childRelation->relationSchema['via']['selfLink'];
            $selfValue = $parentRelation->wrapValue($selfLink, $this->$selfLink);
            $foreignLink = $childRelation->relationSchema['via']['foreignLink'];
            $foreignValue = $childRelation->wrapValue($foreignLink, $model->$selfLink);
            $selfInViaLink = $childRelation->config->w($childRelation->relationSchema['via']['selfInViaLink']);
            $foreignInViaLink = $childRelation->config->w($childRelation->relationSchema['via']['foreignInViaLink']);

            $sql = "DELETE FROM $table WHERE $selfInViaLink = $selfValue AND $foreignInViaLink = $foreignValue";
            LaravelRepresent::execSql($sql);

            if ($deleteFlag === true) {
                $table = $childRelation->config->w($childRelation->relationSchema['table']);
                $foreignLink = $childRelation->relationSchema['via']['foreignLink'];
                $foreignValue = $parentRelation->wrapValue($foreignLink, $model->$foreignLink);
                $foreignLink = $childRelation->config->w($foreignLink);
                $sql = "DELETE FROM $table WHERE $foreignLink = $foreignValue";
                LaravelRepresent::execSql($sql);
            }
        }
        if ($childRelation->relationSchema['type'] === 'parent') {
            $selfLink = $childRelation->relationSchema['selfLink'];
            $model->$selfLink = null;
            $model->save();

            if ($deleteFlag === true) {
                $table = $childRelation->config->w($childRelation->relationSchema['table']);
                $foreignLink = $childRelation->relationSchema['foreignLink'];
                $foreignValue = $parentRelation->wrapValue($foreignLink, $model->$foreignLink);
                $foreignLink = $childRelation->config->w($foreignLink);
                $sql = "DELETE FROM $table WHERE $foreignLink = $foreignValue";
                LaravelRepresent::execSql($sql);
            }
        }
        if ($childRelation->relationSchema['type'] === 'child') {
            $selfLink = $childRelation->relationSchema['selfLink'];
            $this->$selfLink = null;
            $this->save();
            if ($deleteFlag === true) {
                $table = $childRelation->config->w($childRelation->relationSchema['table']);
                $foreignLink = $childRelation->relationSchema['foreignLink'];
                $foreignValue = $parentRelation->wrapValue($foreignLink, $model->$foreignLink);
                $foreignLink = $childRelation->config->w($foreignLink);
                $sql = "DELETE FROM $table WHERE $foreignLink = $foreignValue";
                LaravelRepresent::execSql($sql);
            }
        }
    }


    public function representDelete()
    {
        $this->delete();
    }

    public function representGetRelated($relationName)
    {
        return $this->$relationName;
    }

    public function representGetValue($field)
    {
        return $this->$field;
    }

    public function representSetValue($field, $value)
    {
        $this->$field = $value;
    }

    public function representIsNewRecord()
    {
        return !$this->exists;
    }
}
