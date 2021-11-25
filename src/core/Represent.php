<?php

namespace vpashkov\represent\core;

use Illuminate\Http\Exceptions\HttpResponseException;
use vpashkov\represent\helpers\H;

class Represent
{
    public bool $private = false;
    public bool $multiply = true;
    public bool $onlyMultiply = false;
    public array $parameters = [];
    public array $schemaParameters = [];
    public int $relationIndex;

    public bool $loadAfterSave = true;
    public bool $returnSourceRow = true;
    public Schema $schema;
    public array $tableSchema;

    public Config $config;

    public function __construct($schema = null, $parameters = [])
    {

        $this->parameters = array_merge($this->defaultParameters(), $parameters);
        $this->schemaParameters = H::get($this->parameters, $this->config->parametersSchemaPath, []);

        if ($schema === null) {
            $schema = $this->schema();
        }
        $this->schema = new SchemaRoot($schema, $this->config, $this->schemaParameters);
    }

    public function defaultParameters()
    {
        return [];
    }

    public function schema()
    {
        return null;
    }

    public static function byName($name, $parameters = [], ?Config $config = null)
    {
        return null;
    }

    public static function bySchema($schema, $parameters)
    {
        return new Represent($schema, $parameters);
    }

    public static function execSql($sql)
    {
        return [];
    }

    /**
     * @param array $rows
     * @return mixed
     */
    public function processAll($rows)
    {
        foreach ($rows as &$row) {
            $row = $this->processOne($row);
        }

        return $rows;
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
     * @return mixed
     */
    public function all()
    {
        $this->beforeAll();
        $loader = new Loader($this->schema, $this);

        return $loader->all();
    }

    public function beforeAll()
    {
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

    public function beforeOne()
    {
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
        foreach ($this->dictSchemas() as $dictName => $dictSchema) {
            if (H::get($dictSchema, $this->config->singletonFlag) === true) {
                continue;
            }
            $dicts [$dictName] = $this->dict($dictName);
        }

        return $dicts;
    }

    public function beforeDicts()
    {
    }

    /**
     * @return array
     */
    public function dictSchemas()
    {
        return [];
    }

    public function dict($dictName)
    {
        $this->beforeDict($dictName);
        $dictSchemas = $this->dictSchemas();
        $dictSchema = $this->createDictSchema($dictName);
        $get = H::get($this->dictSchemas()[$dictName], '#get', 'all');
        $process = H::get($dictSchema, '#process', 'process' . ucfirst($dictName));
        $loader = new Loader($dictSchema, $this);
        $dict = [
            'data' => $loader->$get(),
            'count' => $loader->count(),
        ];
        if (method_exists($this, $process)) {
            $dict = $this->$process($dict);
        }

        return $dict;
    }

    public function beforeDict($dictName)
    {
    }

    public function createDictSchema($dictName)
    {
        return new SchemaRoot($this->dictSchemas()[$dictName], $this->config, H::get($this->parameters, $dictName . "Schema", []));
    }

    public function saveAll($rows)
    {
        $statuses = [];
        foreach ($rows as $row) {
            $statuses[] = $this->saveOne($row);
        }

        return $statuses;
    }

    public function saveOne($row)
    {
        $validationResult = $this->validate($row);
        if ($validationResult['valid'] !== true) {
            $this->throwException(["status" => 'FAIL', 'valid' => false, 'errors' => $validationResult['errors']], 400);
//                return ["status" => 'FAIL', 'valid' => false, 'errors' => $validationResult['errors']];
        }
        $this->beginTransaction();
        try {


            $row = $this->deprocess($row);

            $this->beforeSave($row);

            $structure = new Structure($row, $this->schema, $this);
            $model = $structure->save();
            $this->commitTransaction();

            $newRow = $this->reloadAfterSave($model);
            $this->afterSave($newRow, $row, $structure->action);

            if (!$this->returnSourceRow) {
                $row = null;
            }

            return ["status" => "OK", "row" => $newRow, "sourceRow" => $row, 'action' => $structure->action];
        } catch (\Exception $e) {
            $this->rollbackTransaction();

            if ($e instanceof $this->config->responseException) {
                throw $e;
            }

            $this->throwException(["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())], 500);
//            return ["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())];
        }
    }

    public function validate($row)
    {
        $valid = true;
        $errors = [];
        $validators = $this->validators();
        foreach ($validators as $code => $validator) {
            $result = $validator($row);
            if (is_bool($result)) {
                $result = ['valid' => $result, 'code' => $code];
            }
            if ($result['valid'] === false) {
                $valid &= $result['valid'];
                $errors[$code] = $result;
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function validators()
    {
        return [];
    }

    public function throwException($data, $code)
    {
        throw new \Exception(json_encode($data));
    }

    public function beginTransaction()
    {
    }

    /**
     * @param array $row
     * @return mixed
     */
    public function deprocess($row)
    {
        return $row;
    }

    public function beforeSave($row)
    {
    }

    public function commitTransaction()
    {
    }

    public function reloadAfterSave($model)
    {
        if ($this->loadAfterSave !== true || $model == null) {
            return null;
        }
        $loader = new Loader(clone $this->schema, $this);
        $loader->byModel($model);
        $data = $loader->one();

        return $data;
    }

    public function afterSave(&$row, &$sourceRow, $action)
    {
        $this->afterModify($row, $sourceRow, $action, 'save');
    }

    public function afterModify(&$row, &$sourceRow, $action, $generalAction)
    {
    }

    public function rollbackTransaction()
    {
    }

    public function deleteAll($rows)
    {
        $status = [];
        foreach ($rows as $row) {
            $status[] = $this->deleteOne($row);
        }

        return $status;
    }

    public function deleteOne($row)
    {
        $this->beginTransaction();
        try {
            $this->beforeDelete($row);
            $row = $this->deprocess($row);
            $structure = new Structure($row, $this->schema, $this);
            $structure->delete();

            $this->commitTransaction();
            $this->afterDelete($structure->minifyRow(), $row, 'delete');

            return ["status" => "OK", 'row' => $structure->minifyRow(), "sourceRow" => $row];
        } catch (\Exception $e) {
            $this->rollbackTransaction();

            return ["status" => "FAIL", "error" => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())];
        }
    }

    public function beforeDelete($sourceRow)
    {
    }

    public function afterDelete($row, $sourceRow, $action)
    {
        $this->afterModify($row, $sourceRow, $action, 'delete');
    }

    public function excludeFieldsFromDoc()
    {
        return [];
    }

    public function includeFieldsInDoc()
    {
        return [];
    }

    public function nullValue()
    {
        return null;
    }
}
