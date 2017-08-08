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
        return $trees;
    }

    protected function buildTrees(&$rows)
    {
        $trees = [];
        foreach ($rows as &$row) {
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
        $tree = [
            "#table" => $map->tableName,
        ];

        foreach ($row as $field => $value) {
            if (array_key_exists($field, $map->shortFields)) {
                $tree [ $map->shortFields[ $field ] ] = $value;
            }
        }

        if ($this->isNullObject($tree, $map->pks)) {
            return null;
        }

        foreach ($map->relations as $relationName => $relation) {
            $subtree = $this->buildTree($row, $relation);

            if ($relation->multiple) {
                if ($subtree == null) {
                    $tree[ $relationName ] = [];
                } else {
                    $tree[ $relationName ] = [$subtree];
                }
            } else {
                $tree[ $relationName ] = $subtree;
            }
        }
        return $tree;
    }

    protected function combineTrees($trees)
    {
        $countTrees = count($trees);
        for ($i = 0; $i < $countTrees; $i++) {
            if (isset($trees[ $i ])) {
                for ($j = $i + 1; $j < $countTrees; $j++) {
                    if (isset($trees[ $j ])) {
                        if ($this->compareObjectByIds($trees[ $i ], $trees[ $j ], $this->map->pks)) {
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
        foreach ($map->relations as $relationName => $relation) {
            if ($relation->multiple) {
                foreach ($combineTree[ $relationName ] as $combineObj) {
                    $f = 0;
                    foreach ($baseTree[ $relationName ] as &$baseObj) {
                        if ($this->compareObjectByIds($baseObj, $combineObj, $relation->pks)) {
                            $this->combineTree($baseObj, $combineObj, $relation);
                            $f = 1;
                        }
                    }
                    if ($f == 0) {
                        $baseTree[ $relationName ] = array_merge($baseTree[ $relationName ], $combineTree[ $relationName ]);
                    }
                }
            } else {
                if ($this->compareObjectByIds($baseTree[ $relationName ], $combineTree[ $relationName ], $relation->pks)) {
                    $this->combineTree($baseTree[ $relationName ], $combineTree[ $relationName ], $relation);
                }
            }
        }
    }

    protected function isNullObject($obj1, $pks)
    {
        foreach ($pks as $key) {
            if ($obj1[ $key ] == null) {
                return true;
            }
        }
        return false;
    }

    protected function compareObjectByIds($obj1, $obj2, $pks)
    {
        foreach ($pks as $key) {
            if ($obj1[ $key ] != $obj2[ $key ]) {
                return false;
            }
        }
        return true;
    }

}