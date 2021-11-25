<?php

namespace vpashkov\represent\laravel;


use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use vpashkov\represent\core\Config;

class LaravelRepresent extends \vpashkov\represent\core\Represent
{

    public function __construct($schema = null, $parameters = [])
    {
        $this->config = new LaravelConfig();
        parent::__construct($schema, $parameters);
    }

    static public function execSql($sql)
    {
        $result = DB::select($sql);
        $result = array_map(function ($value)
            {
            return (array)$value;
            }, $result);

        return $result;
    }

    public function beginTransaction()
    {
        DB::beginTransaction();
    }

    public function rollbackTransaction()
    {
        DB::rollBack();
    }

    public function commitTransaction()
    {
        DB::commit();
    }

    public static function byName($name, $parameters = [], ?Config $config = null): LaravelRepresent
    {
        if ($config === null) {
            $config = new LaravelConfig();
        }

        $className = $config->createRepresentClassName($name);
        if (!class_exists($className)) {
            throw new \Exception("Class '$className' not found by represent name '$name'");
        }

        return new $className (null, $parameters);
    }

    public static function bySchema($schema, $parameters = []): LaravelRepresent
    {
        return new LaravelRepresent($schema, $parameters);
    }

    public function validate($row)
    {
        $valid = true;
        $errors = [];
        $validators = $this->validators();
        foreach ($validators as $code => $validator) {

            if (is_array($validator)) {
                $laravelValidator = Validator::make($row, $validator);
                $result = $laravelValidator->passes();
                if (is_bool($result)) {
                    $result = ['valid' => $result, 'code' => $code];
                }
                if ($result['valid'] === false) {
                    $valid &= $result['valid'];

                    $validatorErrors = $laravelValidator->errors();
                    $fields = $validatorErrors->keys();
                    foreach ($fields as $field) {
                        $errors[$code] = [
                            'field' => $field,
                            'text' => $validatorErrors->get($field)[0],
                        ];
                    }
                }
            } elseif (is_callable($validator)) {
                $result = $validator($row);
                if (is_bool($result)) {
                    $result = ['valid' => $result, 'code' => $code];
                }
                if ($result['valid'] === false) {
                    $valid &= $result['valid'];
                    $errors[$code] = $result;
                }
            }


        }

        return ['valid' => $valid, 'errors' => $errors];
    }
    
    public function throwException($data, $code)
    {
        throw new HttpResponseException(response()->json($data, 500));
    }

}
