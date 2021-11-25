<?php


namespace vpashkov\represent\laravel;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use vpashkov\represent\core\Config;

class LaravelConfig extends Config
{

    public string $relationSep = '.';
    public string $aliasFieldSep = '__';

    public string $deleteFlag = '#delete';
    public string $unlinkFlag = '#unlink';
    public string $singletonFlag = '#singleton';

    public string $representNs = 'Represents';

    public string $appNs = 'App\\';
    public string $defaultModel = 'Illuminate\\Database\\Eloquent\\Model';
    public string $modelNs = 'App\\Models\\';
    public string $modulesNs = 'App\\Modules\\';
    public string $nameSep = '/';
    public string $allowAllRights = '#all';
    public string $parametersSchemaPath = 'schema';

    public string $schemaFilePath = '/Models/__RepresentSchema.php';

    public $responseException = HttpResponseException::class;

    public string $modelClass = LaravelModel::class;


    public function __construct()
    {
        $this->schemaFilePath = app_path() . $this->schemaFilePath;

//        if (array_key_exists('represent', \Yii::$app->params)) {
//            foreach (\Yii::$app->params['represent'] as $key => $value) {
//                $this->$key = $value;
//            }
//        }

        parent::__construct();
    }

    static public function getDbType()
    {
        $connection = config('database.default');

        $driver = config("database.connections.{$connection}.driver");
        return ucfirst($driver);
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
                $namePart = ucfirst($namePart);
            }
            $namePart = ucfirst($namePart);
        }
        $nameParts[count($nameParts) - 1] = ucfirst($nameParts[count($nameParts) - 1]);
        $name = implode("\\", $nameParts);

        return $name;
    }

}
