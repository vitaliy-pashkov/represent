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

    public static function byName($name, $parameters = [], ?Config $config = null): YiiRepresent
    {
        if ($config === null) {
            $config = new YiiConfig();
        }

        $className = $config->createRepresentClassName($name);
        if (!class_exists($className)) {
            throw new \Exception("Class '$className' not found by represent name '$name'");
        }

        return new $className (null, $parameters);
    }

    public static function bySchema($schema, $parameters): YiiRepresent
    {
        return new YiiRepresent($schema, $parameters);
    }

}
