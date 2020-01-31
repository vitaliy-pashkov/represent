<?php

namespace vpashkov\represent;

class Builder
	{
	/** @var Map map */
	private $map;

	/**
	 * Builder constructor.
	 * @param Map $map
	 */
	public function __construct($map)
		{
		$this->map = $map;
		}

	/**
	 * @param array $rows
	 * @return array
	 */
	public function build($rows)
		{
		$trees = $this->buildTrees($rows);
		$trees = $this->combineTrees($trees);
		$trees = $this->castTrees($trees, $this->map);

		$trees = $this->toMap($trees, $this->map, true);
//		print_r ($this->map); die;
//die;
		return $trees;
		}


	protected function castTrees($trees, $map)
		{
		foreach ($trees as &$tree)
			{
			$tree = $this->castTree($tree, $map);
			}
		return $trees;
		}

	protected function castTree($tree, $map)
		{
		if ($tree === null)
			{
			return null;
			}
		foreach ($map->fields as $fieldName => $field)
			{
			if (array_key_exists($fieldName, $tree) && array_key_exists('schema', $field))
				{
				$tree[ $fieldName ] = $this->castField($tree[ $fieldName ], $field['schema']);
				}
			}
		foreach ($map->relations as $relationName => $relation)
			{
			if (array_key_exists($relationName, $tree))
				{
				if ($relation->multiple === true)
					{
					$tree[ $relationName ] = $this->castTrees($tree[ $relationName ], $relation);
					}
				else
					{
					$tree[ $relationName ] = $this->castTree($tree[ $relationName ], $relation);
					}
				}
			}
		return $tree;
		}

	protected function castField($value, $schema)
		{
		return $schema->phpTypecast($value);
		}

	protected function toMap($trees, $map, $isMultiply)
		{
		foreach ($map->relations as $relationName => $relation)
			{
			if ($isMultiply == true)
				{
//				echo "multiple $relationName \n";
				foreach ($trees as &$tree)
					{
					if (array_key_exists($relationName, $tree) && $tree[ $relationName ] != null)
						{
						$tree[ $relationName ] = $this->toMap($tree[ $relationName ], $relation, $relation->multiple);
						}
					}
				}
			else
				{
//				echo "single $relationName \n";
//				print_r($trees);
//				echo "____________________";
				if (array_key_exists($relationName, $trees) && $trees[ $relationName ] != null)
					{
					$trees[ $relationName ] = $this->toMap($trees[ $relationName ], $relation, $relation->multiple);
					}
				}
			}

//		print_r($trees);


		$mapTrees = [];
		if ($map->mapBy != null)
			{
			foreach ($trees as $tree1)
				{
				$mapTrees[ $tree1[ $map->mapBy ] ] = $tree1;
				}
			}
		else
			{
			$mapTrees = $trees;
			}
//		print_r($mapTrees);

		return $mapTrees;
		}


	protected function isAssoc(array $arr)
		{
		if ([] === $arr)
			{
			return false;
			}
		return array_keys($arr) !== range(0, count($arr) - 1);
		}

	protected function buildTrees(&$rows)
		{
		$trees = [];
		foreach ($rows as &$row)
			{
			$trees [] = $this->buildTree($row, $this->map);
			}
		unset($rows);
		return $trees;
		}

	/**
	 * @param array $row
	 * @param Map $map
	 * @return array|null
	 */
	protected function buildTree($row, $map)
		{
		$tree = [];
		if ($map->includeInfo === true)
			{
			$tree = [
				"#table" => $map->tableName,
				"#pks" => $map->pks,
			];
			}


		foreach ($row as $field => $value)
			{
			if (array_key_exists($field, $map->shortFields))
				{
				$tree [ $map->shortFields[ $field ] ] = $value;
				}
			}

		if ($this->isNullObject($tree, $map->pks))
			{
			return null;
			}

		foreach ($map->relations as $relationName => $relation)
			{
			$subtree = $this->buildTree($row, $relation);

			if ($relation->multiple)
				{
				if ($subtree == null)
					{
					$tree[ $relationName ] = [];
					}
				else
					{
					$tree[ $relationName ] = [$subtree];
					}
				}
			else
				{
				$tree[ $relationName ] = $subtree;
				}
			}
		return $tree;
		}

	protected function combineTrees($trees)
		{
		$countTrees = count($trees);
		for ($i = 0; $i < $countTrees; $i++)
			{
			if (isset($trees[ $i ]))
				{
				for ($j = $i + 1; $j < $countTrees; $j++)
					{
					if (isset($trees[ $j ]))
						{
						if ($this->compareObjectByIds($trees[ $i ], $trees[ $j ], $this->map->pks))
							{
							$this->combineTree($trees[ $i ], $trees[ $j ], $this->map);
							unset($trees[ $j ]);
							}
						}
					}
				}
			}
		$trees = array_values($trees);
		return $trees;
		}

	protected function combineTree(&$baseTree, $combineTree, $map)
		{
		if ($combineTree === null)
			{
			return;
			}
		foreach ($map->relations as $relationName => $relation)
			{
			if ($relation->multiple)
				{
				foreach ($combineTree[ $relationName ] as $combineObj)
					{
					$f = 0;
					foreach ($baseTree[ $relationName ] as &$baseObj)
						{
						if ($this->compareObjectByIds($baseObj, $combineObj, $relation->pks))
							{
							$this->combineTree($baseObj, $combineObj, $relation);
							$f = 1;
							}
						}
					if ($f == 0)
						{
						$baseTree[ $relationName ] = array_merge($baseTree[ $relationName ], $combineTree[ $relationName ]);
						}
					}
				}
			else
				{
				if ($this->compareObjectByIds($baseTree[ $relationName ], $combineTree[ $relationName ], $relation->pks))
					{
					$this->combineTree($baseTree[ $relationName ], $combineTree[ $relationName ], $relation);
					}
				}
			}
		}

	protected function isNullObject($obj1, $pks)
		{
		foreach ($pks as $key)
			{
			if (!array_key_exists($key,$obj1) || $obj1[ $key ] == null)
				{
				return true;
				}
			}
		return false;
		}

	protected function compareObjectByIds($obj1, $obj2, $pks)
		{
		foreach ($pks as $key)
			{
			if ($obj1[ $key ] != $obj2[ $key ])
				{
				return false;
				}
			}
		return true;
		}

	}