<?php

namespace vpashkov\represent;

use yii\db\Exception;

class Represent
	{
	const RELATION_SEP = '.';
	const YII_AR_RELATION_SEP = '.';
	const DB_FIELD_SEP = '.';

	const ALIAS_FIELD_SEP = '__';
	const ALIAS_TABLE_SEP = '_';

	const DELETE_FLAG = '#delete';
	const UNLINK_FLAG = '#unlink';
	const SINGLETON_FLAG = '#singleton';

	const REPRESENT_NS = 'represents';
	const APP_NS = '\\app\\';
	const MODULES_NS = '\\app\\modules\\';
	const NAME_SEP = '/';
	const ALLOW_ALL_RIGHT = '#all';

	public $maxLimit = 1000000;

	public $options = [];
	public $relationIndex;
	public $collectRequestOptions = true;
	public $rawMap;

	public $loadAfterSave = true;


	/** @var bool|Map $map */
	private $map = false;


	public function __construct($map = false, $options = [], $collectRequestOptions = true)
		{
		$this->collectRequestOptions = $collectRequestOptions;
		$this->options = $this->collectOptions($options);

		if ($map === false)
			{
			$map = $this->getMap();
			}

		if ($map !== false)
			{
			$this->setMap($map);
			}
		}

	public static function create($name, $options = [], $collectRequestOptions = true)
		{
		$className = static::createRepresentClassName($name);
		if (!class_exists($className))
			{
			throw new \Exception("Class '$className' not found by represent name '$name'");
			}
		return new $className (false, $options, $collectRequestOptions);
		}

	/**
	 * @return array
	 */
	protected function getDefaultOptions()
		{
		return [];
		}

	/**
	 * @return false|array
	 */
	protected function getMap()
		{
		return false;
		}

	/**
	 * @return array
	 */
	protected function getDictMaps()
		{
		return [];
		}

	/**
	 * @return array
	 */
	protected function getWidgetConfig()
		{
		return [];
		}

	/**
	 * @param array $rows
	 * @return mixed
	 */
	protected function process($rows)
		{
		return $rows;
		}

	/**
	 * @param array $row
	 * @return mixed
	 */
	protected function deprocess($row)
		{
		return $row;
		}


	/**
	 * @param array $map
	 * @return $this
	 */
	public function setMap($map)
		{
		$this->rawMap = $map;
		$this->map = new Map($this->rawMap, $this);
		return $this;
		}

	/**
	 * @return mixed
	 */
	public function getAll()
		{
		$this->beforeGetAll();
		$this->isMapSet();
		$loader = new Loader($this->map, $this);
		$data = $this->load($loader);
		if (RepresentModel::checkRights('r', $this->map->rights, ['readType' => 'all', 'rows' => $data]))
			{
			return $data;
			}
		return [];
		}

	/**
	 * @return mixed|null
	 */
	public function getOne()
		{
		$this->beforeGetOne();
		$this->isMapSet();
		$map = clone $this->map;
		$map->limit = 1;
		$map->offset = 0;
		$loader = new Loader($map, $this);
		$data = $this->load($loader);

		if (count($data) > 0)
			{
			if (RepresentModel::checkRights('r', $this->map->rights, ['readType' => 'one', 'row' => $data[0]]))
				{
				return $data[0];
				}
			}
		return null;
		}

	/**
	 * @param string $dictName
	 * @return mixed
	 */
	public function getDict($dictName)
		{
		$this->beforeGetDict();
		$dictsQuery = $this->getDictMaps();
		$map = new Map($dictsQuery[ $dictName ], $this, $dictName . '_map');

		$get = 'all';
		if (isset($dictQueryConfig['#get']))
			{
			$get = $dictQueryConfig['#get'];
			}

		$loader = new Loader($map);
		return $this->loadDict($loader, $dictName, $get);
		}

	/**
	 * @return array
	 */
	public function getDicts()
		{
		$this->beforeGetDicts();
		$dicts = [];
		foreach ($this->getDictMaps() as $dictName => $dictQueryConfig)
			{
			if (isset($dictQueryConfig[ Represent::SINGLETON_FLAG ]) && $dictQueryConfig[ Represent::SINGLETON_FLAG ] === true)
				{
				continue;
				}
			$get = 'all';
			if (isset($dictQueryConfig['#get']))
				{
				$get = $dictQueryConfig['#get'];
				}
			$map = new Map($dictQueryConfig, $this, $dictName . '_map');
			$loader = new Loader($map, $this);
			$dicts [ $dictName ] = $this->loadDict($loader, $dictName, $get);
			}
		return $dicts;
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

	/**
	 * @param $row
	 * @return array
	 */
	public function saveOne($row)
		{
		$this->isMapSet();
		$map = clone $this->map;

		$transaction = null;
		if ($map->modelClass::getDb()->getTransaction() === null)
			{
			/** @var \yii\db\Transaction $transaction */
			$transaction = $map->modelClass::getDb()->beginTransaction();
			}

		try
			{
			$row = $this->deprocess($row);
			$representModel = new RepresentModel($row, $map);
			$model = $representModel->representSave();

			if ($transaction !== null)
				{
				$transaction->commit();
				}

			$rowData = null;
			if ($this->loadAfterSave === true)
				{
				if ($model != null)
					{
					$loader = new Loader($map, $this);
					$loader->byModel($model);
					$data = $this->load($loader);
					if (count($data) > 0)
						{
						$rowData = $data[0];
						}
					else
						{
						$rowData = [];
						}
					}
				else
					{
					$rowData = $representModel->row;
					}
				}
			$this->afterSave($rowData, $row, $representModel->action);
			return ["status" => "OK", "row" => $rowData, "sourceRow" => $row, 'action' => $representModel->action];
			}
		catch (\Exception $e)
			{
			if ($transaction !== null)
				{
				$transaction->rollBack();
				}

			if ($e instanceof RepresentModelException)
				{
				return ["status" => "FAIL", "error" => $e->info(), 'trace' => explode("\n", $e->getTraceAsString())];
				}
			return ["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())];
			}
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
		$this->isMapSet();
		$transaction = null;
		if ($this->map->modelClass::getDb()->getTransaction() === null)
			{
			/** @var \yii\db\Transaction $transaction */
			$transaction = $this->map->modelClass::getDb()->beginTransaction();
			}
		try
			{
			$this->beforeDelete($row);
			$row = $this->deprocess($row);
			$representModel = new RepresentModel($row, $this->map);
			$representModel->representDelete();

			if ($transaction !== null)
				{
				$transaction->commit();
				}
			$this->afterDelete($representModel->minifyRow(), $row, 'delete');
			return ["status" => "OK", 'row' => $representModel->minifyRow(), "sourceRow" => $row];
			}
		catch (\yii\db\Exception $e)
			{
			if ($transaction !== null)
				{
				$transaction->rollBack();
				}
			return ["status" => "FAIL", "error" => explode("\n", $e->getMessage())];
			}
		}

	public function beforeGetAll()
		{
		}

	public function beforeGetOne()
		{
		}

	public function beforeGetDict()
		{
		}

	public function beforeGetDicts()
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

	public function getCount()
		{
		$this->isMapSet();
		$loader = new Loader($this->map, $this);
		$rawCount = $loader->count();
		return $rawCount[0]['count(*)'];
		}

	public function getMeta()
		{
		$this->isMapSet();
		$loader = new Loader($this->map, $this);
		$meta = $this->collectMeta($loader, $this->map);
		return $meta;
		}


	public function isMapSet()
		{
		if ($this->map === false)
			{
			throw new \Exception("Map not set for represent '" . get_class($this) . "'");
			}
		}


	private function collectOptions($options)
		{
		if ($this->collectRequestOptions == true)
			{
			if (\Yii::$app instanceof \Yii\web\Application)
				{
				$options = array_merge(\Yii::$app->request->get(), $options);

				if (strpos(\Yii::$app->request->contentType, 'application/json') !== false)
					{
					$raw = \Yii::$app->request->getRawBody();
					$post = json_decode($raw, true);
					$options = array_merge($post, $options);
					}
				elseif (strpos(\Yii::$app->request->contentType, 'application/x-www-form-urlencoded') !== false)
					{
					$options = array_merge(\Yii::$app->request->post(), $options);
					}

				}
			if (\Yii::$app instanceof \Yii\console\Application)
				{
				$options = \Yii::$app->request->getParams();
				}
			}
		$options = array_merge($this->getDefaultOptions(), $options);
		return $options;
		}

	/**
	 * @param Loader $loader
	 * @return mixed
	 */
	private function load($loader)
		{
		$data = $loader->all();
		$data = $this->innerProcess($data);
		return $data;
		}

	private function innerProcess($rows)
		{
		$rows = $this->process($rows);
		return $rows;
		}

	/**
	 * @param Loader $loader
	 * @param string $dictName
	 * @return mixed
	 */
	private function loadDict($loader, $dictName, $get)
		{
		$dict = [
			'data' => $loader->all(),
			'count' => intval($loader->count()[0]['count(*)']),
		];
		if ($get == 'one')
			{
			if (count($dict['data']) > 0)
				{
				$dict['data'] = $dict['data'][0];
				}
			}
		$dict = $this->innerProcessDict($dict, $dictName);
		return $dict;
		}

	private function innerProcessDict($dict, $dictName)
		{
		$functionName = 'process' . ucfirst($dictName);
		if (method_exists($this, $functionName))
			{
			$dict = $this->$functionName($dict);
			}
		return $dict;
		}

	/**
	 * @param Loader $loader
	 * @param Map $map
	 * @return array
	 */
	private function collectMeta($loader, $map)
		{
		$meta = [];
		foreach ($map->fields as $fieldName => $field)
			{
			if (!in_array($fieldName, $map->pks))
				{
				$rawMeta = $loader->meta($field);
				$meta[ $field['fullName'] ] = $this->processMeta($rawMeta, $field['fullAlias']);
				}
			}
		foreach ($map->relations as $relationName => $relation)
			{
			if (!$relation->multiple)
				{
				$subMeta = $this->collectMeta($loader, $relation);
				$meta = array_merge($meta, $subMeta);
				}

			}
		return $meta;
		}

	private function processMeta($rawMeta, $field)
		{
		$meta = [];
		foreach ($rawMeta as $item)
			{
			$meta[] = [
				"value" => $item[ $field ],
				"count" => $item['count(*)'],
			];
			}
		return $meta;
		}

	public function processSql($sql)
		{
		return $sql;
		}

	protected static function createRepresentClassName($name)
		{
		$representNS = static::REPRESENT_NS;
		$appNS = static::APP_NS;
		$modulesNS = static::MODULES_NS;
		$nameSep = static::NAME_SEP;

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

	protected static function standRepresentName($nameParts)
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
	}
