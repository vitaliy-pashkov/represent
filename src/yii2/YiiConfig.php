<?php


namespace app\vpashkov\represent\yii2;


use app\vpashkov\represent\core\Config;

class YiiConfig extends Config
	{

	public string $relationSep = '.';
	public string $aliasFieldSep = '__';

	public string $deleteFlag = '#delete';
	public string $unlinkFlag = '#unlink';
	public string $singletonFlag = '#singleton';

	public string $representNs = 'represents';

	public string $appNs = 'app\\';
	public string $modelNs = 'app\\models\\';
	public string $modulesNs = 'app\\modules\\';
	public string $nameSep = '/';
	public string $allowAllRights = '#all';
	public string $parametersSchemaPath = 'schema';

	public string $schemaFilePath = '/models/__RepresentSchema.php';

	public string $modelClass = YiiModel::class;



	public function __construct()
		{
		$this->schemaFilePath = \Yii::$app->basePath . $this->schemaFilePath;

		if (array_key_exists('represent', \Yii::$app->params))
			{
			foreach (\Yii::$app->params['represent'] as $key => $value)
				{
				$this->$key = $value;
				}
			}

		parent::__construct();
		}

	static public function getDbType()
		{
		return ucfirst(\Yii::$app->db->driverName);
		}

	}