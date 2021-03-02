<?php


namespace vpashkov\represent\yii2;


use vpashkov\represent\core\Config;

class YiiRepresent extends \vpashkov\represent\core\Represent
	{
	const REPRESENT_NS = 'represents';

	public $schemaPath = 'models/__RepresentSchema.php';

	public \yii\db\Transaction $transaction;

	public function __construct($schema = null, $parameters = [])
		{
		$this->config = new YiiConfig();
		parent::__construct($schema, $parameters);
		}

	static public function execSql($sql)
		{
//		echo $sql."\n";
		$result = \Yii::$app->db->createCommand($sql)->queryAll();
		return $result;
		}

	public function beginTransaction()
		{
		$this->transaction = \Yii::$app->db->beginTransaction();
		}

	public function rollbackTransaction()
		{
		$this->transaction->rollBack();
		}

	public function commitTransaction()
		{
		$this->transaction->commit();
		}

	public static function byName($name, $parameters=[], ?Config $config = null) : YiiRepresent
		{
		$config = new YiiConfig();
		return parent::byName($name, $parameters, $config);
		}

	}