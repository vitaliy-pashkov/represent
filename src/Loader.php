<?php

namespace vpashkov\represent;

use yii\db\ActiveRecord;

class Loader
{
    /** @var Map map */
    public $map;

    /**
     * Query constructor.
     * @param Map $map
     */
    public function __construct($map)
    {
        $this->map = $map;
    }

    /**
     * @param ActiveRecord $model
     */
    public function byModel($model)
    {
        $where = [];
        foreach ($this->map->pks as $key) {
            $where[ $key ] = $model->$key;
        }
        $this->map->where = [$where];
    }

    /**
     * @param \yii\db\ActiveQuery $activeQuery
     * @return mixed
     * @throws RepresentQueryException
     * @throws \yii\db\Exception
     */
    public function execute($activeQuery)
    {
        $sql = $activeQuery->createCommand()->getRawSql();
        try {
            $data = $this->map->modelClass::getDb()->createCommand($sql)->queryAll();
        } catch (\yii\db\Exception $e) {
            if ($e->errorInfo[0] == '42S22') {
                $field = str_replace("Unknown column '", '', $e->errorInfo[2]);
                $field = substr($field, 0, strpos($field, "'"));
                throw new RepresentQueryException("Unknown column '$field'", $this->map->represent, $field);
            } else {
                throw $e;
            }
        }

        return $data;
    }

    public function all()
    {
        $activeQuery = $this->allQuery();
        $rows = $this->execute($activeQuery);
        $builder = new Builder($this->map);
        return $builder->build($rows);
    }

    public function allQuery()
    {
        list($selectArray, $relationArray) = $this->generateArrays($this->map);
        /** @var \yii\db\ActiveQuery $query */
        $query = $this->map->modelClass::find();
        $query->from($this->fromQuery($relationArray));
        $query->select($selectArray);
        $this->combineWhere($query, $this->map->where);
        $this->combineOrder($query, $this->map->order);
        $query->joinWith($relationArray);
        return $query;
    }

    public function count()
    {
        $activeQuery = $this->countQuery();
        return $this->execute($activeQuery);
    }

    public function countQuery()
    {
        list($selectArray, $relationArray) = $this->generateArrays($this->map);
        /** @var \yii\db\ActiveQuery $query */
        $query = $this->map->modelClass::find();
        $query->from($this->fromQuery($relationArray, true, false));
        $query->select(['count(*)']);
        return $query;
    }

    public function meta($field)
    {
        $activeQuery = $this->metaQuery($field);
        return $this->execute($activeQuery);
    }

    /**
     * @param array $field
     * @return \yii\db\ActiveQuery
     */
    public function metaQuery($field)
    {
        list($selectArray, $relationArray) = $this->generateArrays($this->map, false);
        list($fullSelectArray, $fullRelationArray) = $this->generateArrays($this->map);
        /** @var \yii\db\ActiveQuery $query */
        $query = $this->map->modelClass::find();
        $query->from($this->fromQuery($fullRelationArray, true));
        $query->joinWith($relationArray);

        $selectArray = [];
        $selectArray [] = $field['alias'] . ' AS ' . $field['fullAlias'];
        $selectArray [] = 'count(*)';
        $query->select($selectArray);
        $query->orderBy($field['alias']);
        $query->groupBy([$field['alias']]);

        return $query;
    }


    protected function fromQuery($relationArray, $force = false, $limit = true)
    {
        if (($this->map->limit === false && $this->map->offset === false) && $force === false) {
            return [$this->map->shortName => $this->map->tableName];
        }

        $selectArray = [];
        foreach ($this->map->fields as $fieldName => $fieldConfig) {
            $selectArray[] = $fieldConfig['alias'];
        }

        /** @var \yii\db\ActiveQuery $query */
        $query = $this->map->modelClass::find();
        $this->combineWhere($query, $this->map->where);
        $this->combineOrder($query, $this->map->order, true);
        $query->from([$this->map->shortName => $this->map->tableName]);
        $query->joinWith($relationArray);
        $query->select($selectArray);
        $query->groupBy($selectArray);

        if ($limit === true) {
            $query->limit($this->map->limit);
            $query->offset($this->map->offset);
        }


        return [$this->map->shortName => $query];
    }


    protected function generateArrays($map, $includeMultiple = true)
    {
        $selectArray = [];
        $relationArray = [];
        foreach ($map->fields as $fieldName => $fieldConfig) {
            $selectArray[] = $fieldConfig['alias'] . ' AS ' . $fieldConfig['fullAlias'];
        }

        foreach ($map->relations as $relationName => $relation) {
            if ($relation->multiple && $includeMultiple !== true) {
                continue;
            }

            /**
             * @param \yii\db\ActiveQuery $relQuery
             */
            $relationArray [ $relation->relationPath ] = function ($relQuery) use ($relation) {
                $relQuery->from($relation->tableName . " AS " . $relation->shortName);
                $this->combineWhere($relQuery, $relation->where, 'andOnCondition');
            };

            list($subSelectArray, $subRelationArray) = $this->generateArrays($relation);
            $selectArray = array_merge($subSelectArray, $selectArray);
            $relationArray = array_merge($relationArray, $subRelationArray);
        }
        return [$selectArray, $relationArray];
    }

    public function combineWhere(&$query, $where, $glueFunction = 'andWhere')
    {
        foreach ($where as $condition) {
            if (is_array($condition)) {
                $query->$glueFunction($this->map->shortArray($condition));
            } elseif (is_string($condition)) {
                $query->$glueFunction($this->map->shortString($condition));
            }
        }
    }

    /**
     * @param \yii\db\ActiveQuery $query
     * @param mixed $order
     * @param bool $onlyRoot
     */
    public function combineOrder(&$query, $order, $onlyRoot = false)
    {
        if ($onlyRoot === false) {
            if (is_array($order)) {
                $query->orderBy($this->map->shortArray($order));
            } elseif (is_string($order)) {
                $orderItems = explode(',', $order);
                foreach ($orderItems as &$orderItem) {
                    $orderItem = $this->map->shortString($orderItem);
                }
                $order = implode(',', $orderItems);
                $query->orderBy($order);
            }
        } else {
            if (is_array($order)) {
                $rootOrder = [];
                foreach ($order as $key => $orderDir) {
                    if (strpos($key, Represent::RELATION_SEP) === false) {
                        $rootOrder[ $key ] = $orderDir;
                    }
                }
                $query->orderBy($this->map->shortArray($rootOrder));
            } elseif (is_string($order)) {
                $rootOrderItems = [];
                $orderItems = explode(',', $order);
                foreach ($orderItems as $orderItem) {
                    $parts = explode(' ', $orderItem);
                    $isRoot = false;
                    foreach ($parts as $part) {
                        if (strpos($part, Represent::RELATION_SEP) === false && $this->map->isSelectedField($part)) {
                            $isRoot = true;
                        }
                    }
                    if ($isRoot) {
                        $rootOrderItems[] = $orderItem;
                    }
                }
                $rootOrder = implode(',', $rootOrderItems);
                $query->orderBy($this->map->shortString($rootOrder));
            }
        }
    }


}