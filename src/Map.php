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
	public $tableSchema;

	/** @var string|ActiveRecord $modelClass */
	public $modelClass;
	public $tableName = '';
	public $pks = [];
	public $multiple = false;
	public $link = [];
	public $via = null;
	public $relType = '';
	public $rights = ['r' => [Represent::ALLOW_ALL_RIGHT]];
	public $mapBy = null;

	public $fields = [];
	public $where = [];
	public $limit = false;
	public $maxLimit;
	public $offset = false;
	public $order = "";
	public $relations = [];
	public $shortRelations = [];
	public $group = [];
	public $counable = false;

	public $parent = null;
	public $root = null;
	public $model;

	protected $rawMap;
	protected $fieldIndex = 1;
	public $includeInfo = true;

	/**
	 * Map constructor.
	 * @param array $rawMap
	 * @param Represent $represent
	 * @param string $optionsMapName
	 * @param Map $parent
	 * @param string $relationName
	 */
	function __construct($rawMap, $represent, $optionsMapName = 'map', $parent = null, $relationName = 'root', $root = null)
		{
		$this->rawMap = $rawMap;
		$this->represent = $represent;
		$this->relationName = $relationName;
		if ($root == null)
			{
			$this->root = $this;
			}
		else
			{
			$this->root = $root;
			}

		if ($parent == null)
			{
			$this->maxLimit = $this->represent->maxLimit;
			$represent->relationIndex = 1;
			$this->shortName = 'r' . $represent->relationIndex;

			$this->modelClass = $rawMap['#model'];
			$this->model = new $this->modelClass();

			$this->tableSchema = $this->model->getTableSchema();
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

			$this->tableSchema = $this->model->getTableSchema();
			$this->aliasPath = $parent->aliasPath . Represent::ALIAS_TABLE_SEP . $this->shortName;
			$this->relationPath = static::appendPath($parent->relationPath, Represent::YII_AR_RELATION_SEP, $relationName);
			$this->representPath = static::appendPath($parent->representPath, Represent::RELATION_SEP, $relationName);

			$this->multiple = $activeQuery->multiple;
			$this->link = $activeQuery->link;
			$this->via = $activeQuery->via;

			if ($this->via == null)
				{
				if (!(array_key_exists('#count', $rawMap) && $rawMap['#count'] === true))
					{
					foreach ($this->link as $thisKey => $parentKey)
						{
						$parent->addField($parentKey);
						$this->addField($thisKey);
						}
					}

				$isPk = $this->modelClass::isPrimaryKey(array_keys($this->link));
				$this->relType = $isPk ? 'parent' : 'depend';
				}
			else
				{
				$this->relType = 'via';
				}

			}


//		print_r($this->modelClass::getTableSchema()); die;
		$this->tableName = $this->modelClass::getTableSchema()->fullName;//$this->modelClass::tableName();
		$this->pks = $this->modelClass::getTableSchema()->primaryKey;

		$this->collectFields($rawMap);
		$this->collectRelations($rawMap);
		$this->collectConfig($rawMap);

		if ($parent == null)
			{
			$this->collectOptionMap($optionsMapName);
			$this->collectCustomFields($rawMap);
			}

		unset($this->model);
		}

	protected function collectRelations(&$rawMap)
		{
		foreach ($rawMap as $relationName => $relationRawMap)
			{
			if (is_string($relationName) && is_array($rawMap) && mb_strpos($relationName, '#') === false)
				{
				$relation = new Map($relationRawMap, $this->represent, null, $this, $relationName, $this->root);
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
				if ($key == "#rights")
					{
					$this->rights = $this->normalizeRights($value);
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
				if ($key == '#group')
					{
					$this->group = $value;
					}
				if ($key == "#mapBy")
					{
					$this->mapBy = $value;
					}
				if ($key == "#includeInfo")
					{
					$this->includeInfo = $value;
					}
				if ($key == "#count")
					{
					$this->counable = $value;
					}

				unset($rawMap[ $key ]);
				}
			}
		}

	protected function normalizeRights($value)
		{
		if (is_string($value))
			{
			$actions = str_split($value);
			$rights = [];
			foreach ($actions as $action)
				{
				$rights[ $action ] = [Represent::ALLOW_ALL_RIGHT];
				}
			return $rights;
			}
		if (is_array($value))
			{
			$rights = [];
			foreach ($value as $action => $rawRight)
				{
				$right = $rawRight;
				if (is_string($rawRight))
					{
					$right = [$rawRight];
					}
				$actions = str_split($action);
				foreach ($actions as $action)
					{
					if (array_key_exists($action, $rights))
						{
//						echo "$action \n";
//						print_r($right);
//						print_r($rights);
						$rights[ $action ] = array_merge($rights[ $action ], $right);
//						print_r($rights);
//						die;
						}
					else
						{

						$rights[ $action ] = $right;

						}
					}
				}
			return $rights;
			}
		}

	/**
	 * @param array $rawMap
	 */
	protected function collectFields(&$rawMap)
		{
		if (!(array_key_exists('#count', $rawMap) && $rawMap["#count"] === true))
			{
			foreach ($this->pks as $key)
				{
				$this->addField($key);
				}
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
						unset($rawMap[ $key ]);
						}
					}
				else
					{
					if (strpos($value, ' AS ') === false)
						{
						$countable = (array_key_exists('#count', $rawMap) && $rawMap["#count"] === true);
						$this->addField($value, $countable);
						unset($rawMap[ $key ]);
						}
					}
				}
			}
		}

	protected function collectCustomFields()
		{
		foreach ($this->rawMap as $key => $value)
			{
			if (is_numeric($key))
				{
				if (strpos($value, ' AS ') !== false)
					{
					$this->addCustomField($value);
					unset($this->rawMap[ $key ]);
					}
				}
			}
		foreach ($this->relations as $relation)
			{
			$relation->collectCustomFields();
			}
		}


	/**
	 * @param $optionsMapName
	 * @throws \Exception
	 */
	protected function collectOptionMap($optionsMapName)
		{
		if (isset($this->represent->options[ $optionsMapName ]))
			{
//			print_r($this->represent->options[ $optionsMapName ]);die;
			if (is_string($this->represent->options[ $optionsMapName ]))
				{
				$optionMap = json_decode($this->represent->options[ $optionsMapName ], true);
				}
			if (is_array($this->represent->options[ $optionsMapName ]))
				{
				$optionMap = $this->represent->options[ $optionsMapName ];
				}

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
//				$order = [$optionMap['order'], $this->order];
//				$this->order = implode(' , ', $order);
//				$this->order = [$optionMap['order'], ...$this->order];
//				if (is_array($this->order))
//					{
//					array_unshift($this->order, $optionMap['order']);
//					}
//				if (is_string($this->order))
//					{
				$order = [$optionMap['order'], $this->order];
				$order = array_diff($order, ['']);

				$this->order = implode(' , ', $order);
//					}
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
	protected function addField($field, $countable = false)
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
				"dbAlias" => $this->shortName . Represent::DB_FIELD_SEP . $field,
				"type" => 'normal',
				'inSelect' => !$countable,
				'inGroupBy' => !$countable,
				'schema' => $this->tableSchema->columns[$field],
			];
			$this->shortFields[ $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short ] = $field;
			$this->fieldIndex++;
			}
		}

	protected function addCustomField($field)
		{
		if (!array_key_exists($field, $this->fields))
			{
			$short = 'f' . $this->fieldIndex;
			list($field, $aliace) = explode(' AS ', $field);
			$this->fields[ $aliace ] = [
				"name" => $aliace,
				"short" => $short,
				"alias" => $this->shortString($field, 'dbAlias', 'alias'),
				"fullAlias" => $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short,
				"fullName" => static::appendPath($this->representPath, Represent::RELATION_SEP, $field),
				'dbAlias' => $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short,
//				"dbAlias" => $this->shortName . Represent::DB_FIELD_SEP . $aliace,
				"type" => 'custom',
				"value" => $field,
				'inSelect' => true,
				'inGroupBy' => strpos('count(', $field) !== false,
			];
			$this->shortFields[ $this->aliasPath . Represent::ALIAS_FIELD_SEP . $short ] = $aliace;
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
	public function shortString($str, $normalType = 'dbAlias', $customType = 'alias')
		{
		$parts = explode(' ', $str);
		foreach ($parts as &$part)
			{
			if (strpos($part, Represent::RELATION_SEP) !== false)
				{
				list($fullRelationName, $fieldName) = $this->splitField($part);
				$relation = $this->findRelation($fullRelationName);

				if ($relation == null)
					{
					continue;
					}

				if ($relation->fields[ $fieldName ]['type'] == 'custom')
					{
					$part = $relation->fields[ $fieldName ][ $customType ];
					}
				elseif ($relation->fields[ $fieldName ]['type'] == 'normal')
					{
					$part = $relation->fields[ $fieldName ][ $normalType ];
					}
				}
			else
				{
				if ($this->isSelectedField($part))
					{
					$fieldName = $part;
//					$part = $this->shortName . Represent::DB_FIELD_SEP . $part;
//					print_r($this->fields[ $fieldName ]);
					if ($this->fields[ $fieldName ]['type'] == 'custom')
						{
						$part = $this->fields[ $fieldName ][ $customType ];
						}
					elseif ($this->fields[ $fieldName ]['type'] == 'normal')
						{
						$part = $this->fields[ $fieldName ][ $normalType ];
						}
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
			$map = &$this->root;
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
		$nullRef = null;
		return $nullRef;
//		print_r($map);die;
		throw new RepresentQueryException("Unknown relation $fullKey in " . print_r(array_keys($map->relations), true), $this->represent, $fullKey);
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