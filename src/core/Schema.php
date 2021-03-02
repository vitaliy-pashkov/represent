<?php

namespace vpashkov\represent\core;


use vpashkov\represent\helpers\H;

class Schema
	{
	public array $allTableSchema;
	public SchemaRoot $root;

	public Config $config;

	public array $rawSchema;
	public array $tableSchema;

	public array $rights = ['guest' => ['r' => ['fields' => ['*'], 'conditions' => []]]];


	public array $fieldsAlias = [];

	public string $shortName;

	public string $modelName;
	public string $modelClass;

	public string $where = '';
	public array $conditions = [];
	public string $order = "";

	public array $fields = [];
	public array $relations = [];


	public array $usedRelations = [];
	public array $path = [];

	public $mapBy = null;
	public $serialize = 'arrays';
	public bool $includeInfo = true;


	function __construct($rawSchema, $config)
		{
		$this->rawSchema = $rawSchema;
		$this->config = $config;
		}

	public function getTableSchema()
		{
		$allTableSchema = require($this->config->schemaFilePath);
		return $allTableSchema[ $this->modelName ];
		}

	public function collectRawSchema()
		{
		$this->collectConfig();
		$this->collectFields();
		$this->collectRelations();
		}

	protected function postCollect()
		{
		$this->where = $this->combineWhere();
		$this->order = $this->parseString($this->order);


		foreach ($this->relations as $relation)
			{
			$relation->postCollect();
			}
		}

	protected function collectRelations()
		{
		foreach ($this->rawSchema as $relationName => $relationRawSchema)
			{
			if (is_string($relationName) && is_array($this->rawSchema) && mb_strpos($relationName, '#') === false)
				{
				$relation = new SchemaRelation($relationRawSchema, $this->config, $this, $relationName);
				$this->relations[ $relationName ] = $relation;
				}
			}
		}

	protected function collectConfig()
		{
		foreach ($this->rawSchema as $key => $value)
			{
			if (is_string($key) && mb_strpos($key, '#') === 0)
				{
				if ($key == "#rights")
					{
					$this->rights = $this->normalizeRights($value);
					}
				if ($key == "#limit")
					{
					$this->limit = intval($value);
					}
				if ($key == "#offset")
					{
					$this->offset = intval($value);
					}
				if (strpos($key, "#where") === 0)
					{
					if ($key === "#where")
						{
						if (is_string($value))
							{
							$this->where = $value;
							}
						elseif (is_array($value))
							{
							$conditionName = H::randomString(10);
							$this->where = $conditionName;
							$this->conditions[ $conditionName ] = $value;
							}
						}
					else
						{
						$conditionName = lcfirst(substr($key, strlen('#where')));
						$this->conditions[ $conditionName ] = $value;
						}
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
				if ($key == '#serialize')
					{
					$this->serialize = $value;
					}
				if ($key == "#count")
					{
					$this->countable = $value;
					}
				unset($this->rawSchema[ $key ]);
				}
			}
		}


	protected function collectFields()
		{
		if (!(array_key_exists('#count', $this->rawSchema) && $this->rawSchema["#count"] === true))
			{
			foreach ($this->tableSchema['pks'] as $key)
				{
				$this->addField($key);
				}
			}

		foreach ($this->rawSchema as $key => $value)
			{
			if (is_numeric($key))
				{
				if ($value == '*')
					{
					foreach ($this->tableSchema['fields'] as $fieldName => $val)
						{
						$this->addField($fieldName);
						unset($this->rawSchema[ $key ]);
						}
					}
				else
					{
					if (strpos($value, ' AS ') === false && strpos($value, ' as ') === false)
						{
						$countable = (array_key_exists('#count', $this->rawSchema) && $this->rawSchema["#count"] === true);
						$this->addField($value, $countable);
						unset($this->rawSchema[ $key ]);
						}
					}
				}
			}
		}

	protected function collectCustomFields()
		{
		foreach ($this->rawSchema as $key => $value)
			{
			if (is_numeric($key))
				{
				if (strpos($value, ' AS ') !== false || strpos($value, ' as ') !== false)
					{
					$this->addCustomField($value);
					unset($this->rawSchema[ $key ]);
					}
				}
			}
		foreach ($this->relations as $relation)
			{
			$relation->collectCustomFields();
			}
		}


	protected function collectSchemaParameters($schemaParameters)
		{
		if (isset($schemaParameters['where']))
			{
			$this->conditions['#params'] = $schemaParameters['where'];
			}
		if (isset($schemaParameters['filters']))
			{
			$filter = new Filter($schemaParameters['filters']);
			$this->conditions['#filters'] = $filter->generateSql();
			}
		if (isset($schemaParameters['limit']))
			{
			$this->limit = intval($schemaParameters['limit']);
			}
		if (isset($schemaParameters['offset']))
			{
			$this->offset = intval($schemaParameters['offset']);
			}
		if (isset($schemaParameters['order']))
			{
			$order = [$schemaParameters['order'], $this->order];
			$order = array_diff($order, ['']);
			$this->order = implode(' , ', $order);
			}

		}

	protected function addField($field, $countable = false)
		{
		if (!array_key_exists($field, $this->fields))
			{
			if (!array_key_exists($field, $this->tableSchema['fields']))
				{
				throw new \Exception("Field '$field' not found in model {$this->modelName}. Forgot it in select section?");
				}

			$this->fields[ $field ] = [
				'name' => $field,
				'body' => $this->w($this->shortName) . '.' . $this->w($field),
				'alias' => $this->w($this->shortName . $this->config->aliasFieldSep . $field),
				'type' => 'normal',
				'inSelect' => !$countable,
				'inGroupBy' => !$countable,
				'dataType' => $this->tableSchema['fields'][ $field ],
			];
			$this->fieldsAlias[ $this->shortName . $this->config->aliasFieldSep . $field ] = $field;
			}
		}

	protected function addCustomField($field)
		{
		if (!array_key_exists($field, $this->fields))
			{
			[$body, $name] = $this->explodeCustomField($field);
			$this->fields[ $name ] = [
				'name' => $name,
				'body' => $this->parseString($body),
				'alias' => $this->w($name),
				'type' => 'custom',
				'inSelect' => true,
				'inGroupBy' => strpos('count(', $field) !== false,
				'dataType' => 'custom',
			];
			$this->fieldsAlias[ $name ] = $name;
			}
		}


	public function explodeCustomField($field)
		{
		$parts = preg_split('/ as | AS /', $field,);
		$name = $parts[ count($parts) - 1 ];
		unset($parts[ count($parts) - 1 ]);
		$body = implode(' AS ', $parts);
		return [$body, $name];
		}


	public function parseString($string)
		{
		$string = str_replace('(', "( ", $string);
		$string = str_replace(')', " )", $string);
		$string = str_replace('[', "[ ", $string);
		$string = str_replace(']', " ]", $string);
		$string = str_replace(',', " , ", $string);

		$parts = preg_split('/[ ]/', $string);
		foreach ($parts as &$part)
			{
			if (strpos($part, $this->config->relationSep) !== false)
				{
				[$fullRelationName, $fieldName] = $this->splitField($part);

				if (array_key_exists($fullRelationName, $this->root->fullRelationsMap))
					{
					$relation = $this->root->fullRelationsMap[ $fullRelationName ];
					$this->addUsedRelation($relation);
					$part = $relation->fields[ $fieldName ]['body'];
					}
				}
//			if (strpos($part, $this->config->methodSep) !== false)
//				{
//				[$fullRelationName, $methodName, ] = $this->splitMethod($part);
//				}
			elseif (array_key_exists($part, $this->root->fields))
				{
				$part = $this->root->fields[ $part ]['body'];
				$this->addUsedRelation($this->root);
				}
			}
		$string = implode(' ', $parts);
		return $string;
		}

	public function addUsedRelation($relation)
		{
		if ($relation !== $this)
			{
			$this->usedRelations[ $relation->shortName ] = $relation;
			}
		}

	public function combineWhere()
		{
		$conditions = [];
		foreach ($this->conditions as $conditionName => $condition)
			{
			if (is_array($condition))
				{
				$conditionParts = [];
				foreach ($condition as $key => $value)
					{
					if (is_array($value))
						{
						$onEmpty = isset ($value['onEmpty']) ? $value['onEmpty'] : ' FALSE ';
						unset($value['onEmpty']);
						if (count($value) > 0)
							{
							$possibleValues = H::implodeWrap(', ', $value, $this->getFieldValueWrapper($key));
							$conditionParts [] = $this->parseString($key) . ' IN (' . $possibleValues . ') ';
							}
						else
							{
							$conditionParts [] = $onEmpty;
							}
						}
					else
						{
						$wrapper = $this->getFieldValueWrapper($key);
						$conditionParts [] = $this->parseString($key) . " = $wrapper" . $value . "$wrapper";
						}
					}
				$conditions[ $conditionName ] = implode(' AND ', $conditionParts);
				}
			if (is_string($condition))
				{
				$conditions[ $conditionName ] = $this->parseString($condition);
				}
			}

		$where = $this->where;
		$and = strlen(trim($where)) > 0 ? ' AND ' : '';
		if (array_key_exists('#params', $conditions) && strlen(trim($conditions['#params'])) > 0)
			{
			if (strpos($where, '#params') === false)
				{
				$where .= " $and #params";
				}
			}
		else
			{
			if (strpos($where, '#params') !== false)
				{
				$conditions['#params'] = ' TRUE ';
				}
			}

		$and = strlen(trim($where)) > 0 ? ' AND ' : '';
		if (array_key_exists('#filters', $conditions) && strlen(trim($conditions['#filters'])) > 0)
			{
			if (strpos($where, '#filters') === false)
				{
				$where .= " $and #filters";
				}
			}
		else
			{
			if (strpos($where, '#filters') !== false)
				{
				$conditions['#filters'] = ' TRUE ';
				}
			}

		$whereParts = preg_split('/[ ]/', $where);
		foreach ($whereParts as &$wherePart)
			{
			if (array_key_exists($wherePart, $conditions) || array_key_exists(lcfirst($wherePart), $conditions))
				{
				$wherePart = $conditions[ lcfirst($wherePart) ];
				}
			else
				{
				$wherePart = $this->parseString($wherePart);
				}
			}
		$where = implode(' ', $whereParts);
		return $where;
		}

	public function getFieldValueWrapper($field)
		{
		$wrapper = '"';

		[$fullRelationName, $fieldName] = $this->splitField($field);
		if (array_key_exists($fullRelationName, $this->root->fullRelationsMap))
			{
			$relation = $this->root->fullRelationsMap[ $fullRelationName ];
			$type = $relation->fields[ $fieldName ]['dataType'];
			if ($type === 'integer' || $type === 'float' || $type === 'double')
				{
				$wrapper = '';
				}
			}
		return $wrapper;
		}

	protected function splitField($field)
		{
		$parts = explode($this->config->relationSep, $field);
		$fieldName = $parts[ count($parts) - 1 ];
		unset($parts[ count($parts) - 1 ]);
		$fullRelationName = implode($this->config->relationSep, $parts);
		return [$fullRelationName, $fieldName];
		}

	protected function normalizeRights($value)
		{
		if (is_string($value))
			{
			$actions = str_split($value);
			$rights = [];
			foreach ($actions as $action)
				{
				$rights[ $action ] = [$this->config->allowAllRights];
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
						$rights[ $action ] = array_merge($rights[ $action ], $right);
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

	public function printSchema()
		{
		$schema = [
			'model' => $this->model,
			'table' => $this->tableSchema['table'],
			'name' => $this->name,
			'shortName' => $this->shortName,
			'path' => $this->path,
			'where' => $this->where,
			'order' => $this->order,
			'limit' => $this->limit,
			'fields' => $this->fields,
			'relationSchema' => $this->relationSchema,
			'relations' => [],
		];
		foreach ($this->relations as $relationName => $relation)
			{
			$schema['relations'][ $relationName ] = $relation->printMap();
			}
		return $schema;
		}

	public function w($name)
		{
		return $this->config->w($name);
		}

	}
