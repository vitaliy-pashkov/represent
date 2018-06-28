<?php

namespace vpashkov\represent;

class Filter
{
    public $name;
    public $rule;
    public $path;
    public $conditions;

    public function __construct($filterData)
    {
//    	print_r($filterData); die;
        $this->name = $filterData['name'];
        $this->rule = $filterData['rule'];
//		if(!isset($filterData['conditions']))
//			{
//			$filterData['conditions'] = [];
//			}

        $this->conditions = [];
        foreach ($filterData['conditions'] as $conditionData) {
            if ($conditionData['type'] == 'filter') {
                $this->conditions[] = new Filter($conditionData);
            }
            if ($conditionData['type'] == 'condition') {
                $this->conditions[] = new Condition($conditionData);
            }
        }
    }

    public function generateSql()
    {
        $sql = $this->rule;
        $sql = str_replace('&&', 'AND', $sql);
        $sql = str_replace('||', 'OR', $sql);
        $sql = str_replace('!', 'NOT', $sql);
        $sql = preg_replace('/[^0-9a-zA-Z()_. ]/i', '', $sql);

        if ($sql == '') {
            return 'TRUE';
        }

        $parts = explode(' ', $sql);
        foreach ($this->conditions as $condition) {
            foreach ($parts as &$part) {
                if ($condition->name == $part) {
                    $part = $condition->generateSql();
                }
            }
//			$sql = str_replace( $condition->name, $condition->generateSql(), $sql );
        }
	
        $sql = implode(' ', $parts);
        return $sql;
    }
}