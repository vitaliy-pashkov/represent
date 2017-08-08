<?php

namespace vpashkov\represent;

class Condition
{
    public $name;
    public $field;
    public $value;
    public $operator;

    public function __construct($data)
    {
        $this->name = $data['name'];
        $this->field = $data['field'];
        $this->value = $data['value'];
        $this->operator = $data['operator'];
    }

    public function generateSql()
    {
        $patterns = [
            '==' => '${field} = ${value}',
            '!=' => '${field} <> ${value}',
            '>' => '${field} > ${value}',
            '>=' => '${field} >= ${value}',
            '<' => '${field} < ${value}',
            '<=' => '${field} <= ${value}',
            '~' => '${field} LIKE "${value}"',
            '!~' => 'NOT (${field} LIKE "${value}")',
        ];
        $sql = $patterns[ $this->operator ];

        if ($this->value == null) {
            if ($this->operator == '==') {
                $sql = '${field} IS NULL';
            }
            if ($this->operator == '!=') {
                $sql = '${field} IS NOT NULL';
            }
        }

        if ($this->operator == '~' && $this->value == '') {
            $sql = '( TRUE )';
        }


        $sql = str_replace('${field}', $this->field, $sql);
        $sql = str_replace('${value}', $this->value, $sql);

        return $sql;
    }


}