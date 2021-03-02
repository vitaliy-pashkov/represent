<?php

namespace vpashkov\represent\core;

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
			'===' => '${field} = ${value}',
			'!==' => '${field} <> ${value}',
			'==' => '${field} = "${value}"',
			'!=' => '${field} <> "${value}"',
			'>' => '${field} > ${value}',
			'>=' => '${field} >= ${value}',
			'<' => '${field} < ${value}',
			'<=' => '${field} <= ${value}',
			'>s' => '${field} > "${value}"',
			'>=s' => '${field} >= "${value}"',
			'<s' => '${field} < "${value}"',
			'<=s' => '${field} <= "${value}"',
			'~' => '${field} LIKE "%${value}%"',
			'!~' => 'NOT (${field} LIKE "%${value}%")',
		];
		$sql = $patterns[ $this->operator ];

		if ($this->value == null || $this->value == "null")
			{
			if ($this->operator == '==' || $this->operator == '===')
				{
				$sql = '${field} IS NULL';
				}
			if ($this->operator == '!=' || $this->operator == '!==')
				{
				$sql = '${field} IS NOT NULL';
				}
			}

		if (($this->operator == '~' || $this->operator == 't~') && $this->value == '')
			{
			$sql = '( TRUE )';
			}

		$this->value = addslashes($this->value);

//		$sql = str_replace('${field}', $this->field, $sql);
		$field = $this->templateToConcat($this->field);
		$sql = str_replace('${field}', $field, $sql);
		$sql = str_replace('${value}', $this->value, $sql);


//		echo $sql; die;

		return $sql;
		}

	public function templateToConcat($template)
		{
//		$template = ' ${field1} (${field2})';
//		$concat = 'CONCAT(" ", field1, " (", field2, ")"'
		$concat = '';
		$offset = 0;
		$fields = [];

		while (strpos($template, '@{', $offset) !== false)
			{
			$start = strpos($template, '@{', $offset) + 2;
			$finish = strpos($template, '}', $start);

			$preField = substr($template, $offset, $start - 2 - $offset);
			if (strlen($preField))
				{
				$fields [] = '"'.$preField.'"';
				}
			$fields [] = substr($template, $start, $finish - $start);

			$offset = $finish + 1;
			}
		$postField = '"'.substr($template, $offset, strlen($template) - $offset).'"';
		if (strlen($postField))
			{
			$fields [] = $postField;
			}
		if (count($fields) === 1)
			{
			$concat = $fields[0];
			}
		elseif (count($fields) > 1)
			{
			$concat = 'CONCAT(' . implode(', ', $fields) . ')';
			}

//		while (strpos($template, '@{', $offset) !== false)
//			{
//			$start = strpos($template, '@{', $offset) + 2;
//			$finish = strpos($template, '}', $start);
//
//			$subTemplate = substr($template, $offset, $start - 2 - $offset);
//			$field = substr($template, $start, $finish - $start);
//
//			if (strlen($subTemplate) > 0)
//				{
//				$concat .= '"' . $subTemplate . '", ';
//				}
//			$concat .= ' TRIM( ' . $field . ' ), ';
//			$offset = $finish + 1;
//			$countFields++;
//			}
//		$subTemplate = substr($template, $offset, strlen($template) - $offset);
//		if (strlen($subTemplate) > 0)
//			{
//			$concat .= '"' . $subTemplate . '", ';
//			}
//		$concat = substr($concat, 0, strlen($concat) - 2);
//		if ($countFields > 1)
//			{
//			$concat = 'CONCAT(' . $concat . ')';
//			}
//		echo $concat;die;
		return $concat;
		}

	}