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
			'===' => '${field} = "${value}"',
			'!==' => '${field} <> "${value}"',
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
			't~' => '${template} LIKE "%${value}%"',
			't!~' => 'NOT (${template} LIKE "%${value}%")',
		];
		$sql = $patterns[ $this->operator ];

		if ($this->value == null)
			{
			if ($this->operator == '==')
				{
				$sql = '${field} IS NULL';
				}
			if ($this->operator == '!=')
				{
				$sql = '${field} IS NOT NULL';
				}
			}

		if (($this->operator == '~' || $this->operator == 't~') && $this->value == '')
			{
			$sql = '( TRUE )';
			}

		$this->value = addslashes($this->value);

		$sql = str_replace('${field}', $this->field, $sql);
		$sql = str_replace('${value}', $this->value, $sql);


		if ($this->operator == 't~')
			{
			$template = $this->templateToConcat($this->field);
			$sql = str_replace('${template}', $template, $sql);
			}
//		echo $sql; die;

		return $sql;
		}

	public function templateToConcat($template)
		{
//		$template = ' ${field1} (${field2})';
//		$concat = 'CONCAT(" ", field1, " (", field2, ")"'
		$concat = 'CONCAT(';
		$offset = 0;
		while (strpos($template, '{{', $offset) !== false)
			{
			$start = strpos($template, '{{', $offset) + 2;
			$finish = strpos($template, '}}', $start);

			$subTemplate = substr($template, $offset, $start - 2 - $offset);
			$field = substr($template, $start, $finish - $start);

			if (strlen($subTemplate) > 0)
				{
				$concat .= '"' . $subTemplate . '", ';
				}
			$concat .= ' TRIM( ' . $field . ' ), ';
			$offset = $finish + 2;
			}
		$subTemplate = substr($template, $offset, strlen($template) - $offset);
		if (strlen($subTemplate) > 0)
			{
			$concat .= '"' . $subTemplate . '", ';
			}
		$concat = substr($concat, 0, strlen($concat) - 2);
		$concat .= ')';
//		echo $concat;die;
		return $concat;
		}

	}