<?php


namespace vpashkov\represent\core;


class Serializer
{

    static public function serialize($trees, Schema $schema)
    {
        $trees = self::toMap($trees, $schema, true);

        if ($schema->serialize === 'models') {
            $trees = self::treesAsModels($trees, $schema);
        }

        return $trees;
    }

    static public function toMap($trees, $schema, $isMultiply)
    {
        foreach ($schema->relations as $relationName => $relation) {
            if ($isMultiply == true) {
//				echo "multiple $relationName \n";
                foreach ($trees as &$tree) {
                    if (array_key_exists($relationName, $tree) && $tree[$relationName] != null) {
                        $tree[$relationName] = self::toMap($tree[$relationName], $relation, $relation->relationSchema['multiple']);
                    }
                }
            } else {
//				echo "single $relationName \n";
//				print_r($trees);
//				echo "____________________";
                if (array_key_exists($relationName, $trees) && $trees[$relationName] != null) {
                    $trees[$relationName] = self::toMap($trees[$relationName], $relation, $relation->relationSchema['multiple']);
                }
            }
        }

//		print_r($trees);


        $mapTrees = [];
        if ($schema->mapBy != null) {
            foreach ($trees as $tree1) {
                $mapTrees[$tree1[$schema->mapBy]] = $tree1;
            }
        } else {
            $mapTrees = $trees;
        }

//		print_r($mapTrees);

        return $mapTrees;
    }

    static public function treesAsModels($trees, Schema $schema)
    {
        $models = [];
        foreach ($trees as $tree) {
            $model = self::treeAsModel($tree, $schema);
            $models[] = $model;
        }

        return $models;
    }

    static public function treeAsModel($tree, Schema $schema)
    {
        $modelClass = $schema->tableSchema['modelClass'];

        $model = new $modelClass();
        $modelClass::populateRecord($model, $tree);
        foreach ($schema->relations as $relationName => $relation) {
            $models = self::treesAsModels($tree[$relationName], $relation);
            $model->populateRelation($relationName, $models);
        }

        return $model;
    }

}