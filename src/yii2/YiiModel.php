<?php


namespace vpashkov\represent\yii2;


use vpashkov\represent\core\ModelInterface;
use vpashkov\represent\core\Schema;
use yii\db\ActiveRecord;

class YiiModel extends ActiveRecord implements ModelInterface
	{


	static public function representFind($class, $where)
		{
		return $class::find()->where($where)->one();
		}

	public function representSetAttributes(array $structure, Schema $schema)
		{
		foreach ($schema->fields as $fieldName => $field)
			{
			if (array_key_exists($fieldName, $structure))
				{
				if ($this->$fieldName !== $structure [ $fieldName ])
					{
					$this->$fieldName = $structure[ $fieldName ];
					}
				}
			}
		}

	public function representSave()
		{
		return $this->save();
		}

	public function representIsLinked($model, $relation)
		{
		$where = [];
		foreach ($relation->tableSchema['pks'] as $key)
			{
			$where[ $key ] = $model->$key;
			}
		$result = $this->getRelation($relation->relationName)->where($where)->one();
		return $result !== null;
		}

	public function representLink($relationName, $model)
		{
		$this->link($relationName, $model);
		}


	public function representUnlink($relationName, $model, $deleteFlag)
		{
		$this->unlink($relationName, $model, $deleteFlag);
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
		return $this->isNewRecord;
		}
	}
