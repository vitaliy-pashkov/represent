<?php

namespace app\vpashkov\represent\core;

use app\vpashkov\represent\helpers\H;

class Loader
	{

	public Schema $schema;
	public Represent $represent;

	public function __construct($schema, $represent)
		{
		$this->schema = $schema;
		$this->represent = $represent;
		}

	public function execute($query)
		{
		$result = $this->represent->execSql($query);
		return $result;
		}

	public function byModel(ModelInterface $model)
		{
		$where = [];
		foreach ($this->schema->tableSchema['pks'] as $key)
			{
			$where[] = $this->w($this->schema->shortName) . '.' . $this->w($key) . ' = ' . '"' . $model->representGetValue($key) . '"';
			}
		$this->schema->where = implode(' AND ', $where);
		}

	public function all()
		{
		$trees = $this->selectRelation($this->schema, [], $this->schema->limit);
		$trees = Serializer::serialize($trees, $this->schema);

		return $trees;
		}

	public function one()
		{
		$trees = $this->selectRelation($this->schema, [], 1);
		$trees = Serializer::serialize($trees, $this->schema);
		if (count($trees) > 0)
			{
			return $trees[0];
			}
		return null;
		}

	public function count($schema = null)
		{
		if ($schema === null)
			{
			$schema = $this->schema;
			}
		$joinArray = $this->generateRelationArray($schema);
		$from = $schema->tableSchema['table'] . ' AS ' . $schema->shortName;
		$select = 'count(*) as count';
		$join = implode(' ', $joinArray);
		$where = strlen($schema->where) > 0 ? 'WHERE ' . $schema->where : '';
		$sql = "SELECT $select FROM $from $join $where";
		$row = $this->execute($sql);
		return $row[0]['count'];
		}

	public function meta($field)
		{
		return [];
		}


	public function selectRelation($schema, $parentRows = [], $schemaLimit = null)
		{
		$selectArray = $this->generateSelectArray($schema);
		$joinArray = $this->generateRelationArray($schema);
		$limit = '';
		$offset = '';
		$additionFields = [];

		$whereString = $schema->where;
		if ($schema instanceof SchemaRoot)
			{
			$limit = $schemaLimit !== null ? 'LIMIT ' . $schemaLimit : '';
			$offset = $schema->offset !== null ? 'OFFSET ' . $schema->offset : '';
			}
		else
			{
			if (count($parentRows) === 0)
				{
				return [];
				}
			if ($schema->relationSchema['type'] === 'parent' || $schema->relationSchema['type'] === 'depend')
				{
				$parentIds = H::linerizeArray($parentRows, $schema->relationSchema['selfLink'], '"');
				$parentIds = array_unique($parentIds, SORT_STRING);
				$parentIds = implode(', ', $parentIds);

				$relationQuoted = $this->w($schema->shortName);
				$fieldQuoted = $this->w($schema->relationSchema['foreignLink']);

				$and = strlen(trim($whereString)) > 0 ? ' AND ' : '';
				$whereString .= " $and $relationQuoted.$fieldQuoted IN ( $parentIds ) ";
				}
			elseif ($schema->relationSchema['type'] === 'via')
				{
				$parentIds = H::linerizeArray($parentRows, $schema->relationSchema['via']['selfLink'], '"');
				$parentIds = array_unique($parentIds, SORT_STRING);
				$parentIds = implode(', ', $parentIds);

				$via = $schema->relationSchema['via'];

				$viaJoin = $this->joinSql([
					"{$via['viaTable']} AS via" => $schema->shortName,
					$via['foreignInViaLink'] => $via['foreignLink'],
				]);
				$viaJoin .= " AND {$this->w('via')}.{$this->w($via['selfInViaLink'])} IN ($parentIds)";
				array_unshift($joinArray, $viaJoin);

				$selectArray[] = "{$this->w('via')}.{$this->w($via['selfInViaLink'])} as {$this->w('#via_link')}";
				$additionFields[] = '#via_link';
				}
			}

		$from = $this->w($schema->tableSchema['table']) . ' AS ' . $this->w($schema->shortName);
		$select = implode(', ', $selectArray);
		$join = implode(' ', $joinArray);
		$where = strlen(trim($whereString)) > 0 ? 'WHERE ' . $whereString : '';
		$order = strlen(trim($schema->order)) > 0 ? 'ORDER BY ' . $schema->order : '';
		$sql = "SELECT $select FROM $from $join $where $order $limit $offset";
//        echo $sql . "\n";
		$rows = $this->execute($sql);

		$rows = $this->toLocalNames($rows, $schema, $additionFields);

		foreach ($schema->relations as $relation)
			{
			$relationRows = $this->selectRelation($relation, $rows);
			$rows = $this->combine($rows, $relationRows, $relation);
			}
		return $rows;
		}

	public function generateSelectArray($schema)
		{
		$selectArray = [];
		foreach ($schema->fields as $fieldName => $fieldConfig)
			{
			if ($fieldConfig['inSelect'] == true)
				{
				$selectArray[] = $fieldConfig['body'] . ' AS ' . $fieldConfig['alias'];
				}
			}
		return $selectArray;
		}

	protected function generateRelationArray($schema)
		{
		$relationArray = [];
		foreach ($schema->usedRelations as $usedRelation)
			{
			$pathDown = [];
			$pathUp = [];
			for ($i = count($schema->path) - 1; $i >= 0; $i--)
				{
				$pathUp = [];
				for ($j = count($usedRelation->path) - 1; $j >= 0; $j--)
					{
					$pathUp[] = $usedRelation->path[ $j ];
					if ($schema->path[ $i ] === $usedRelation->path[ $j ])
						{
						break;
						}
					}
				$pathDown [] = $schema->path[ $i ];
				}
			$pathUp = array_reverse($pathUp);


			for ($i = 1; $i < count($pathDown); $i++)
				{
				$childRelation = $schema->root->shortRelationsMap[ $pathDown[ $i - 1 ] ];
				$parentRelation = $schema->root->shortRelationsMap[ $pathDown[ $i ] ];

				if ($childRelation->relationSchema['type'] === 'parent' || $childRelation->relationSchema['type'] === 'depend')
					{
					$relationArray[] = $this->joinSql([
						"{$parentRelation->tableSchema['table']} AS {$parentRelation->shortName}" => $childRelation->shortName,
						$childRelation->relationSchema['selfLink'] => $childRelation->relationSchema['foreignLink'],
					]);
					}
				elseif ($childRelation->relationSchema['type'] === 'via')
					{
					$via = $childRelation->relationSchema['via'];
					$relationArray[] = $this->joinSql([
						"{$via['viaTable']} AS {$childRelation->shortName}_via" => $childRelation->shortName,
						$via['foreignInViaLink'] => $via['foreignLink'],
					]);
					$relationArray[] = $this->joinSql([
						"{$parentRelation->tableSchema['table']} AS {$parentRelation->shortName}" => $childRelation->shortName . '_via',
						$via['selfLink'] => $via['selfInViaLink'],
					]);
					}
				}

			for ($i = 1; $i < count($pathUp); $i++)
				{
				$childRelation = $schema->root->shortRelationsMap[ $pathUp[ $i ] ];
				$parentRelation = $schema->root->shortRelationsMap[ $pathUp[ $i - 1 ] ];
				if ($childRelation->relationSchema['type'] === 'parent' || $childRelation->relationSchema['type'] === 'depend')
					{

					$relationArray[] = $this->joinSql([
						"{$childRelation->tableSchema['table']} AS {$childRelation->shortName}" => $parentRelation->shortName,
						$childRelation->relationSchema['foreignLink'] => $childRelation->relationSchema['selfLink'],
					]);
					}
				elseif ($childRelation->relationSchema['type'] === 'via')
					{
					$via = $childRelation->relationSchema['via'];
					$relationArray[] = $this->joinSql([
						"{$via['viaTable']} AS {$childRelation->shortName}_via" => $parentRelation->shortName,
						$via['selfInViaLink'] => $via['selfLink'],
					]);
					$relationArray[] = $this->joinSql([
						"{$childRelation->relationSchema['table']} AS {$childRelation->shortName}" => $childRelation->shortName . '_via',
						$via['foreignLink'] => $via['foreignInViaLink'],
					]);
					}
				}
			}
		return $relationArray;
		}

	public function joinSql($config)
		{
		$table = '';
		$alias = '';
		$foreignAlias = '';
		$field = '';
		$foreignField = '';

		foreach ($config as $key => $value)
			{
			if (strpos($key, ' AS ') !== false)
				{
				[$table, $alias] = explode(' AS ', $key);
				$foreignAlias = $value;
				}
			else
				{
				$field = $key;
				$foreignField = $value;
				}
			}
		$on = $this->w($alias) . '.' . $this->w($field) . ' = ';
		$on .= $this->w($foreignAlias) . '.' . $this->w($foreignField);
		return "LEFT OUTER JOIN " . $this->w($table) . " " . $this->w($alias) . " ON " . $on;
		}

	public function toLocalNames($rows, $schema, $additionFields = [])
		{
		$resultRows = [];

		foreach ($rows as $row)
			{
			$resultRow = [];
			foreach ($row as $key => $value)
				{
				if (array_key_exists($key, $schema->fieldsAlias))
					{
					$resultRow [ $schema->fieldsAlias[ $key ] ] = $value;
					}
				}

			foreach ($additionFields as $addField)
				{
				$resultRow [ $addField ] = $row[ $addField ];
				}
			$resultRows [] = $resultRow;
			}
		return $resultRows;
		}

	public function combine($parentRows, $relationRows, $relation)
		{
		if ($relation->relationSchema['multiple'] === true)
			{
			foreach ($parentRows as &$parentRow)
				{
				$parentRow[ $relation->relationName ] = [];
				foreach ($relationRows as $relationRow)
					{
					if ($this->isParent($parentRow, $relationRow, $relation->relationSchema))
						{
						$parentRow[ $relation->relationName ][] = $relationRow;
						}
					}
				}
			}
		else
			{
			foreach ($parentRows as &$parentRow)
				{
				$parentRow[ $relation->relationName ] = null;
				foreach ($relationRows as $relationRow)
					{
					if ($this->isParent($parentRow, $relationRow, $relation->relationSchema))
						{
						$parentRow[ $relation->relationName ] = $relationRow;
						}
					}
				}
			}
		return $parentRows;
		}

	public function isParent($parentRow, $relationRow, $schema)
		{
		if ($schema['type'] === 'parent' || $schema['type'] === 'depend')
			{
			if ($parentRow[ $schema['selfLink'] ] === $relationRow[ $schema['foreignLink'] ])
				{
				return true;
				}
			}
		elseif ($schema['type'] === 'via')
			{
			if ($parentRow[ $schema['via']['selfLink'] ] === $relationRow['#via_link'])
				{
				return true;
				}
			}
		return false;
		}

	public function w($name)
		{
		return $this->represent->config->w($name);
		}
	}
