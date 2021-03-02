<?php

namespace vpashkov\represent\generator;

class Generator
	{
	public $modelNamespace = 'App\\Models\\';

	const TYPE_CHAR = 'string';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'string';
	const TYPE_TINYINT = 'boolean';
	const TYPE_SMALLINT = 'integer';
	const TYPE_INTEGER = 'integer';
	const TYPE_BIGINT = 'integer';
	const TYPE_FLOAT = 'float';
	const TYPE_DOUBLE = 'double';
	const TYPE_DECIMAL = 'double';
	const TYPE_DATETIME = 'string';
	const TYPE_TIMESTAMP = 'string';
	const TYPE_TIME = 'string';
	const TYPE_DATE = 'string';
	const TYPE_BINARY = 'string';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_MONEY = 'integer';
	const TYPE_JSON = 'json';

	public $representClass;


	public function __construct($representClass)
		{
		$this->representClass = $representClass;
		}

	function varExport($expression, $return = false)
		{
		$export = var_export($expression, true);
		$patterns = [
			"/array \(/" => '[',
			"/^([ ]*)\)(,?)$/m" => '$1]$2',
			"/=>[ ]?\n[ ]+\[/" => '=> [',
			"/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
		];
		$export = preg_replace(array_keys($patterns), array_values($patterns), $export);
		if ((bool)$return)
			{
			return $export;
			}
		else
			{
			echo $export;
			}
		}

	public static function createGenerator($representClass, $config)
		{
		$class = '\\vpashkov\\represent\\generator\\' . $config->getDbType() . 'Generator';
		return new $class($representClass);
		}

	}
