<?php

namespace vpashkov\represent\core;

use vpashkov\represent\helpers\H;

class Represent
	{
	public array $parameters = [];
	public array $schemaParameters = [];
	public int $relationIndex;

	public bool $loadAfterSave = true;
	public Schema $schema;
	public array $tableSchema;

	public Config $config;

	public function __construct($schema = null, $parameters = [])
		{

		$this->parameters = array_merge($this->defaultParameters(), $parameters);
		$this->schemaParameters = H::get($this->parameters, $this->config->parametersSchemaPath, []);

		if ($schema === null)
			{
			$schema = $this->schema();
			}
		$this->schema = new SchemaRoot($schema, $this->config, $this->schemaParameters);
		}

	public static function byName($name, $parameters=[], ?Config $config=null)
		{
		$className = static::createRepresentClassName($name, $config);
		if (!class_exists($className))
			{
			throw new \Exception("Class '$className' not found by represent name '$name'");
			}
		return new $className (null, $parameters);
		}

	public static function bySchema($schema, $parameters)
		{
		return new Represent($schema, $parameters);
		}

	public function defaultParameters()
		{
		return [];
		}

	public function schema()
		{
		return null;
		}

	/**
	 * @return array
	 */
	public function dictSchemas()
		{
		return [];
		}

	/**
	 * @param array $rows
	 * @return mixed
	 */
	public function processOne($row)
		{
		return $row;
		}

	/**
	 * @param array $rows
	 * @return mixed
	 */
	public function processAll($rows)
		{
		foreach ($rows as &$row)
			{
			$row = $this->processOne($row);
			}
		return $rows;
		}

	/**
	 * @param array $row
	 * @return mixed
	 */
	public function deprocess($row)
		{
		return $row;
		}


	/**
	 * @return mixed
	 */
	public function all()
		{
		$this->beforeAll();
		$loader = new Loader($this->schema, $this);
		return $loader->all();
		}

	/**
	 * @return mixed|null
	 */
	public function one()
		{
		$this->beforeOne();
		$loader = new Loader($this->schema, $this);
		return $loader->one();
		}

	public function count()
		{
		$loader = new Loader($this->schema, $this);
		return $loader->count();
		}

	public function meta()
		{
		$loader = new Loader($this->schema, $this);
		return $loader->meta();
		}

	/**
	 * @return array
	 */
	public function dicts()
		{
		$this->beforeDicts();
		$dicts = [];
		foreach ($this->dictSchemas() as $dictName => $dictSchema)
			{
			if (H::get($dictSchema, $this->config->singletonFlag) === true)
				{
				continue;
				}
			$dicts [ $dictName ] = $this->dict($dictName);
			}
		return $dicts;
		}

	public function dict($dictName)
		{
		$this->beforeDict($dictName);
		$dictSchemas = $this->dictSchemas();
		$dictSchema = new SchemaRoot($dictSchemas[ $dictName ], $this->config, H::get($this->parameters, $dictName . "Schema", []));
		$get = H::get($dictSchema, '#get', 'all');
		$process = H::get($dictSchema, '#process', 'process' . ucfirst($dictName));
		$loader = new Loader($dictSchema, $this);
		$dict = [
			'data' => $loader->$get(),
			'count' => $loader->count(),
		];
		if (method_exists($this, $process))
			{
			$dict = $this->$process($dict);
			}
		return $dict;
		}

	public function saveAll($rows)
		{
		$statuses = [];
		foreach ($rows as $row)
			{
			$statuses[] = $this->saveOne($row);
			}
		return $statuses;
		}

	public function beginTransaction()
		{
		}

	public function commitTransaction()
		{
		}

	public function rollbackTransaction()
		{
		}


	public function saveOne($row)
		{
		$validationResult = $this->validate($row);
		if ($validationResult['valid'] !== true)
			{
			return ["status" => 'FAIL', 'valid' => false, 'errors' => $validationResult['errors']];
			}
		$this->beginTransaction();
		try
			{
			$row = $this->deprocess($row);

			$structure = new Structure($row, $this->schema, $this);
			$model = $structure->save();
			$this->commitTransaction();

			$newRow = $this->reloadAfterSave($model);
			$this->afterSave($newRow, $row, $structure->action);
			return ["status" => "OK", "row" => $newRow, "sourceRow" => $row, 'action' => $structure->action];
			}
		catch (\Exception $e)
			{
			$this->rollbackTransaction();
			return ["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())];
			}
		}

	public function reloadAfterSave($model)
		{
		if ($this->loadAfterSave !== true || $model == null)
			{
			return null;
			}
		$loader = new Loader(clone $this->schema, $this);
		$loader->byModel($model);
		$data = $loader->one();
		return $data;
		}

	public function validators()
		{
		return [];
		}

	public function validate($row)
		{
		$valid = true;
		$errors = [];
		$validators = $this->validators();
		foreach ($validators as $code => $validator)
			{
			$result = $validator($row);
			if (is_bool($result))
				{
				$result = ['valid' => $result, 'code' => $code];
				}
			if ($result['valid'] === false)
				{
				$valid &= $result['valid'];
				$errors[ $code ] = $result;
				}
			}
		return ['valid' => $valid, 'errors' => $errors];
		}

	public function deleteAll($rows)
		{
		$status = [];
		foreach ($rows as $row)
			{
			$status[] = $this->deleteOne($row);
			}
		return $status;
		}

	public function deleteOne($row)
		{
		$this->beginTransaction();
		try
			{
			$this->beforeDelete($row);
			$row = $this->deprocess($row);
			$structure = new Structure($row, $this->schema, $this);
			$structure->delete();

			$this->commitTransaction();
			$this->afterDelete($structure->minifyRow(), $row, 'delete');
			return ["status" => "OK", 'row' => $structure->minifyRow(), "sourceRow" => $row];
			}
		catch (\Exception $e)
			{
			$this->rollbackTransaction();
			return ["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())];
			}
		}

	public function beforeAll()
		{
		}

	public function beforeOne()
		{
		}

	public function beforeDict($dictName)
		{
		}

	public function beforeDicts()
		{
		}

	public function afterSave($row, $sourceRow, $action)
		{
		$this->afterModify($row, $sourceRow, $action, 'save');
		}

	public function beforeDelete($sourceRow)
		{
		}

	public function afterDelete($row, $sourceRow, $action)
		{
		$this->afterModify($row, $sourceRow, $action, 'delete');
		}

	public function afterModify($row, $sourceRow, $action, $generalAction)
		{
		}

	public static function createRepresentClassName($name, Config $config)
		{
		$representNS = $config->representNs;
		$appNS = $config->appNs;
		$modulesNS = $config->modulesNs;
		$nameSep = $config->nameSep;

		$nameParts = explode($nameSep, $name);
		$representName = false;
		if (count($nameParts) == 2)
			{
			array_splice($nameParts, 0, 0, $representNS);
			$name = self::standRepresentName($nameParts);
			$representName = $appNS . $name;
			}
		elseif (count($nameParts) == 3)
			{
			array_splice($nameParts, 1, 0, $representNS);
			$name = self::standRepresentName($nameParts);
			$representName = $modulesNS . $name;
			}
		return $representName;
		}

	public static function standRepresentName($nameParts)
		{
		foreach ($nameParts as &$namePart)
			{
			if (strpos($namePart, '-'))
				{
				$namePart = strtolower($namePart);
				$namePartSubs = explode("-", $namePart);
				foreach ($namePartSubs as &$part)
					{
					$part = ucfirst($part);
					}
				$namePart = implode($namePartSubs);
				$namePart = lcfirst($namePart);
				}
			}
		$nameParts[ count($nameParts) - 1 ] = ucfirst($nameParts[ count($nameParts) - 1 ]);
		$name = implode("\\", $nameParts);
		return $name;
		}

	public static function execSql($sql)
		{
		return [];
		}
	}
