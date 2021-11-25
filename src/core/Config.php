<?php


namespace vpashkov\represent\core;


class Config
	{

	public string $relationSep = '.';
	public string $methodSep = '->';
	public string $aliasFieldSep = '__';

	public string $deleteFlag = '#delete';
	public string $unlinkFlag = '#unlink';
	public string $singletonFlag = '#singleton';

	public string $representNs = 'Represents';

	public string $appNs = 'app\\';
	public string $defaultModel = '';
	public string $modelNs = 'app\\models\\';
	public string $modulesNs = 'app\\modules\\';
	public string $nameSep = '/';
	public string $allowAllRights = '#all';
	public string $parametersSchemaPath = 'schema';

	public string $schemaFilePath = '/';

	public string $modelClass;

	public function __construct()
		{

		}

	static public function getDbType()
		{
		return '';
		}

	public function getQuote()
		{
		$dbType = static::getDbType();
		if ($dbType === 'Mysql')
			{
			return '`';
			}
		if ($dbType === 'Pgsql')
			{
			return '"';
			}
		}

	public function w($name)
		{
		return $this->getQuote() . $name . $this->getQuote();
		}

	}
