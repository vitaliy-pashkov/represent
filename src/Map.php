<?php

namespace vpashkov\represent;

use yii\db\ActiveRecord;

class Map
	{
	public $represent;

	public $relationName = '';
	public $shortName = '';
	public $representPath = '';
	public $aliasPath = '';
	public $relationPath = '';

	public $shortFields = [];

	/** @var string|ActiveRecord $modelClass */
	public $modelClass;
	public $tableName = '';
	public $pks = [];
	public $multiple = false;
	public $link = [];
	public $via = null;
	public $relType = '';
	public $actions = 'r';
	public $mapBy = null;

	public $fields = [];
	public $where = [];
	public $limit = false;
	public $maxLimit;
	public $offset = false;
	public $order = [];
	public $relations = [];
	public $shortRelations = [];

	public $parent = null;
	public $model;

	protected $fieldIndex = 1;

	/**
	 * Map constructor.
	 * @param array $rawMap
	 * @param Represent $represent
	 * @param string $optionsMapName
	 * @param Map $parent
	 * @param string $relationName
	 */
	function __construct($rawMap, $represent, $optionsMapName = 'map', $parent = null, $relationName = 'root')
		{
		$this->represent = $represent;
		$this->relationName = $relationName;
		if ($parent == null)
			{
			$this->maxLimit = $this->represent->maxLimit;
			$represent->relationIndex = 1;
			$this->shortName = 'r' . $represent->relationIndex;

			$this->modelClass = $rawMap['#model'];
			$this->model = new $this->modelClass();

			$this->aliasPath = $this->shortName;
			$this->relationPath = '';
			$this->representPath = '';
			}
		else
			{
			$represent->relationIndex++;
			$this->shortName = 'r' . $represent->relationIndex;
			$activeQuery = $parent->getActiveQuery($relationName);

			$this->modelClass = $activeQuery->modelClass;
			$this->model = new $this->modelClass();

			$this->aliasPath = $parent->aliasPath . Represent::ALIAS_TABLE_SEP . $this->shortName;
			$this->relationPath = static::appendPath($parent->relationPath, Represent::YII_AR_RELATION_SEP, $relationName);
			$this->representPath = static::appendPath($parent->representPath, Represent::RELATION_SEP, $relationName);

			$this->multiple = $activeQuery->multiple;
			$this->link = $activeQuery->link;
			$this->via = $activeQuery->via;

			if ($this->via == null)
				{
				foreach ($this->link as $thisKey => $parentKey)
					{
					$parent->addField($parentKey);
					$this->addField($thisKey);
					}

				$isPk = $this->modelClass::isPrimaryKey(array_keys($this->link));
				$this->relType = $isPk ? 'parent' : 'depend';
				}
			else
				{
				$this->relType = 'via';
				}

			}


		$this->tableName = $this->modelClass::tableName();
		$this->pks = $this->modelClass::getTableSchema()->primaryKey;

		$this->collectConfig($rawMap);
		$this->collectFields($rawMap);
		$this->collectRelations($rawMap);
		if ($parent == null)
			{
			$this->collectOptionMap($optionsMapName);
			}

		unset($this->model);
		}

	protected function collectRelations(&$rawMap)
		{
		foreach ($rawMap as $relationName => $relationRawMap)
			{
			if (is_string($relationName) && is_array($rawMap) && mb_strpos($relationName, '#') === false)
				{
				$relation = new Map($relationRawMap, $this->represent, null, $this, $relationName);
				$this->relations[ $relationName ] = $relation;
				$this->shortRelations[ $relation->shortName ] = $relationName;
				}
			}
		}

	/**
	 * @param array $rawMap
	 */
	protected function collectConfig(&$rawMap)
		{
		foreach ($rawMap as $key => $value)
			{
			if (is_string($key) && mb_strpos($key, '#') === 0)
				{
				if ($key == "#actions")
					{
					$this->actions = $value;
					}
				if ($key == "#limit")
					{
					$this->limit = min(intval($value), $this->maxLimit);
					}
				if ($key == "#offset")
					{
					$this->offset = intval($value);
					}
				if (strpos($key, "#where") === 0)
					{
					$this->where[] = $value;
					}
				if ($key == "#order")
					{
					$this->order = $value;
					}
				if($key == "#mapBy")
					{
					$this->mapBy = $value;
					}
				unset($rawMap[ $key ]);
				}
			}
		}

	/**
	 * @param array $rawMap
	 */
	protected function collectFields(&$rawMap)
		{
		foreach ($this->pks as $key)
			{
			$this->addField($key);
			}
		foreach ($rawMap as $key => $value)
			{
			if (is_numeric($key))
				{
				if ($value == '*')
					{
					foreach ($this->model->attributes as $field => $val)
						{
						$this->addField($field);
						}
					}
				else
					{
					$this->addField($value);
					}
				unset($rawMap[ $key ]);
				}
			}
		}

	/**
	 * @param $optionsMapName
	 * @throws \Exception
	 */
	protected function collectOptionMap($optionsMapName)
		{
		if (array_key_exists($optionsMapName, $this->represent->options))
			{
			$optionMap = json_decode($this->represent->options[ $optionsMapName ], true);
			if (isset($optionMap['filters']))
				{
				$filter = new Filter($optionMap['filters']);
				$this->where[] = $filter->generateSql();
				}
			if (isset($optionMap['limit']))
				{
				$optionLimit = intval($optionMap['limit']);
				if ($optionLimit > $this->maxLimit)
					{
					throw new \Exception("Optional limit $optionLimit is higher that represent max limit " . $this->maxLimit);
					}
				$this->limit = $optionLimit;
				}
			if (isset($optionMap['offset']))
				{
				$this->offset = intval($optionMap['offset']);
				}
			if (isset($optionMap['order']))
				{
				$this->order = $optionMap['order'];
				}
			if (isset($optionMap['where']))
				{
				$this->where[] = $optionMap['where'];
				}
			}
		}

	/**
	 * @param string $field
	 * @throws RepresentQueryException
	 */
	protected function addField($field)
		{
		if (!array_key_exists($field, $this->fields))
			{
			if (!array_key_exists($field, $this->model->attributes))
				{
				throw new RepresentQueryException("Field '$field' not found in model $this->modelClass", $this->represent, $field);
				}

			$short = 'f' . $this->fieldIndex;
			$this->fields[ $field ] = [
				"name" => $field,
				"short" => $short,
				"alias" => $this->shortName . Represent::RELATION_SEP . $field,
				"fullAlias" => $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short,
				"fullName" => static::appendPath($this->representPath, Represent::RELATION_SEP, $field),
			];
			$this->shortFields[ $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short ] = $field;
			$this->fieldIndex++;
			}
		}

	/**
	 * @param string $relationName
	 * @return \yii\db\ActiveQuery
	 * @throws \Exception
	 */
	protected function getActiveQuery($relationName)
		{
		$activeQueryFunction = 'get' . ucfirst($relationName);
		if (!method_exists($this->model, $activeQueryFunction))
			{
			throw new RepresentQueryException("Relation '$relationName' not found in model $this->modelClass", $this->represent, $relationName);
//			throw new \Exception('Represent error. Relation not found: "' . $relationName . '" in model "' . $this->modelClass . '"');
			}
		$activeQuery = $this->model->$activeQueryFunction();
		return $activeQuery;
		}


	/**
	 * @param string $str
	 * @return string
	 */
	public function shortString($str)
		{
		$parts = explode(' ', $str);
		foreach ($parts as &$part)
			{
			if (strpos($part, Represent::RELATION_SEP) !== false)
				{
				list($fullRelationName, $fieldName) = $this->splitField($part);
				$relation = $this->findRelation($fullRelationName);
				$part = $relation->shortName . Represent::DB_FIELD_SEP . $fieldName;
				}
			else
				{
				if ($this->isSelectedField($part))
					{
					$part = $this->shortName . Represent::DB_FIELD_SEP . $part;
					}
				}
			}
		$str = implode(' ', $parts);
		return $str;
		}

	/**
	 * @param array $array
	 * @return array
	 * @throws RepresentQueryException
	 */
	public function shortArray($array)
		{
		$shortArray = [];
		foreach ($array as $key => $value)
			{
			if (strpos($key, Represent::RELATION_SEP) !== false)
				{
				list($fullRelationName, $fieldName) = $this->splitField($key);
				$relation = $this->findRelation($fullRelationName);
				}
			else
				{
				$relation = $this;
				$fieldName = $key;
				}
			if (!$relation->isSelectedField($fieldName))
				{
				throw new RepresentQueryException("Unknown column '$fieldName' in $relation->modelClass", $this->represent, $fieldName);
				}
			$shortRelationName = $relation->shortName;
			$shortArray [ $shortRelationName . Represent::DB_FIELD_SEP . $fieldName ] = $value;
			}
		return $shortArray;
		}

	/**
	 * @param $key
	 * @param Map $map
	 * @param null $fullKey
	 * @return null|Map
	 * @throws RepresentQueryException
	 */
	public function &findRelation($key, $map = null, $fullKey = null)
		{
		if ($fullKey == null)
			{
			$fullKey = $key;
			}
		if ($map == null)
			{
			$map = &$this;
			}

		if ($key == '')
			{
			return $map;
			}

		$parts = explode(Represent::RELATION_SEP, $key);
		$part = $parts[0];
		unset($parts[0]);
		$lessParts = implode(Represent::RELATION_SEP, $parts);

		if (isset($map->relations[ $part ]))
			{
			return $this->findRelation($lessParts, $map->relations[ $part ], $fullKey);
			}
		throw new RepresentQueryException("Unknown relation $fullKey", $this->represent, $fullKey);
		}

	/**
	 * @param string $field
	 * @return array
	 */
	protected function splitField($field)
		{
		$parts = explode(Represent::RELATION_SEP, $field);
		$fieldName = $parts[ count($parts) - 1 ];
		unset($parts[ count($parts) - 1 ]);
		$fullRelationName = implode(Represent::RELATION_SEP, $parts);
		return [$fullRelationName, $fieldName];
		}

	public function isSelectedField($field, $map = null)
		{
		if ($map == null)
			{
			$map = $this;
			}
		return isset($map->fields[ $field ]);
		}

	/**
	 * @param string $path
	 * @param string $sep
	 * @param string $part
	 * @return string
	 */
	public static function appendPath($path, $sep, $part)
		{
		return trim($path . $sep . $part, $sep);
		}

	}