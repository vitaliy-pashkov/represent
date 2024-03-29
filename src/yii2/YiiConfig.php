<?php


namespace vpashkov\represent\yii2;


use vpashkov\represent\core\Config;

class YiiConfig extends Config
{

    public string $relationSep = '.';
    public string $aliasFieldSep = '__';

    public string $deleteFlag = '#delete';
    public string $unlinkFlag = '#unlink';
    public string $singletonFlag = '#singleton';

    public string $representNs = 'represents';

    public string $appNs = 'app\\';
    public string $defaultModel = 'yii\\db\\ActiveRecord';
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

        if (array_key_exists('represent', \Yii::$app->params)) {
            foreach (\Yii::$app->params['represent'] as $key => $value) {
                $this->$key = $value;
            }
        }

        parent::__construct();
    }

    static public function getDbType()
    {
        return ucfirst(\Yii::$app->db->driverName);
    }

    public function createRepresentClassName($name)
    {
        $representNS = $this->representNs;
        $appNS = $this->appNs;
        $modulesNS = $this->modulesNs;
        $nameSep = $this->nameSep;

        $nameParts = explode($nameSep, $name);
        $representName = false;
        if (count($nameParts) == 2) {
            array_splice($nameParts, 0, 0, $representNS);
            $name = $this->standRepresentName($nameParts);
            $representName = $appNS . $name;
        } elseif (count($nameParts) == 3) {
            array_splice($nameParts, 1, 0, $representNS);
            $name = $this->standRepresentName($nameParts);
            $representName = $modulesNS . $name;
        }

        return $representName;
    }

    public function standRepresentName($nameParts)
    {
        foreach ($nameParts as &$namePart) {
            if (strpos($namePart, '-')) {
                $namePart = strtolower($namePart);
                $namePartSubs = explode("-", $namePart);
                foreach ($namePartSubs as &$part) {
                    $part = ucfirst($part);
                }
                $namePart = implode($namePartSubs);
                $namePart = lcfirst($namePart);
            }
        }
        $nameParts[count($nameParts) - 1] = ucfirst($nameParts[count($nameParts) - 1]);
        $name = implode("\\", $nameParts);

        return $name;
    }

}
