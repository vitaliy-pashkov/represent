<?php

namespace vpashkov\represent\generator;

use vpashkov\represent\core\Config;
use vpashkov\represent\helpers\BaseInflector;

class PgsqlGenerator extends Generator
{

    public static $typeMap = [
        'bit' => self::TYPE_INTEGER,
        'bit varying' => self::TYPE_INTEGER,
        'varbit' => self::TYPE_INTEGER,

        'bool' => self::TYPE_BOOLEAN,
        'boolean' => self::TYPE_BOOLEAN,

        'box' => self::TYPE_STRING,
        'circle' => self::TYPE_STRING,
        'point' => self::TYPE_STRING,
        'line' => self::TYPE_STRING,
        'lseg' => self::TYPE_STRING,
        'polygon' => self::TYPE_STRING,
        'path' => self::TYPE_STRING,

        'character' => self::TYPE_CHAR,
        'char' => self::TYPE_CHAR,
        'bpchar' => self::TYPE_CHAR,
        'character varying' => self::TYPE_STRING,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,

        'bytea' => self::TYPE_BINARY,

        'cidr' => self::TYPE_STRING,
        'inet' => self::TYPE_STRING,
        'macaddr' => self::TYPE_STRING,

        'real' => self::TYPE_FLOAT,
        'float4' => self::TYPE_FLOAT,
        'double precision' => self::TYPE_DOUBLE,
        'float8' => self::TYPE_DOUBLE,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,

        'money' => self::TYPE_MONEY,

        'smallint' => self::TYPE_SMALLINT,
        'int2' => self::TYPE_SMALLINT,
        'int4' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'int8' => self::TYPE_BIGINT,
        'oid' => self::TYPE_BIGINT, // should not be used. it's pg internal!

        'smallserial' => self::TYPE_SMALLINT,
        'serial2' => self::TYPE_SMALLINT,
        'serial4' => self::TYPE_INTEGER,
        'serial' => self::TYPE_INTEGER,
        'bigserial' => self::TYPE_BIGINT,
        'serial8' => self::TYPE_BIGINT,
        'pg_lsn' => self::TYPE_BIGINT,

        'date' => self::TYPE_DATE,
        'interval' => self::TYPE_STRING,
        'time without time zone' => self::TYPE_TIME,
        'time' => self::TYPE_TIME,
        'time with time zone' => self::TYPE_TIME,
        'timetz' => self::TYPE_TIME,
        'timestamp without time zone' => self::TYPE_TIMESTAMP,
        'timestamp' => self::TYPE_TIMESTAMP,
        'timestamp with time zone' => self::TYPE_TIMESTAMP,
        'timestamptz' => self::TYPE_TIMESTAMP,
        'abstime' => self::TYPE_TIMESTAMP,

        'tsquery' => self::TYPE_STRING,
        'tsvector' => self::TYPE_STRING,
        'txid_snapshot' => self::TYPE_STRING,

        'unknown' => self::TYPE_STRING,

        'uuid' => self::TYPE_STRING,
        'json' => self::TYPE_JSON,
        'jsonb' => self::TYPE_JSON,
        'xml' => self::TYPE_STRING,
    ];

    public function generateSchema(Config $config)
    {
        $tablesList = $this->representClass::execSql('SELECT tablename as name
            FROM pg_catalog.pg_tables
            WHERE schemaname != \'pg_catalog\' AND schemaname != \'information_schema\' ORDER BY name;');

        $tables = [];
        foreach ($tablesList as $tableInfo) {
            $tableName = $tableInfo['name'];
            $modelName = BaseInflector::singular($tableName);
            $modelName = BaseInflector::camelize($modelName);
            $tables[$modelName] = [
                'table' => $tableName,
                'pks' => $this->collectPks($tableName),
                'fields' => $this->collectFields($tableName),
                'relations' => $this->collectRelations($tableName),
                'modelClass' => $this->findModel($modelName, $config),
            ];
        }
        $content = "<?php return " . $this->varExport($tables, true) . ';';
        file_put_contents($config->schemaFilePath, $content);
    }

    public function findModel($modelName, Config $config)
    {
//        $test = new BaseAdvert();
//        echo get_class($test);

        echo $config->modelNs . ucfirst($modelName) . ' ' . ((class_exists($config->modelNs . ucfirst($modelName))) ? "Class exist" : "Class not exist") . "\n";
        if (class_exists($config->modelNs . ucfirst($modelName))) {
            return '\\' . $config->modelNs . ucfirst($modelName);
        }

        die;
        return $config->defaultModel;
    }

    public function collectFields($tableName)
    {
        $fieldsList = $this->representClass::execSql('SELECT column_name as name, is_nullable as is_nullable, data_type as db_type
        FROM "information_schema"."columns"
        WHERE table_name = \'' . $tableName . '\';');

        $fields = [];
        foreach ($fieldsList as $fieldRaw) {
            $fields [$fieldRaw['name']] = self::$typeMap[$fieldRaw['db_type']];
//                [
//                'name' => $fieldRaw->name,
//                'is_nullable' => $fieldRaw->is_nullable,
//                'db_type' => $fieldRaw->db_type,
//                'type' => self::$typeMap[ $fieldRaw->db_type ],
//            ];
        }

        return $fields;
    }

    public function collectPks($tableName)
    {
        $pksList = $this->representClass::execSql('SELECT a.attname as name
            FROM   pg_index i
            JOIN   pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE  i.indrelid = \'"' . $tableName . '"\'::regclass AND i.indisprimary;');

        $pks = [];
        foreach ($pksList as $pksRaw) {
            $pks[$pksRaw['name']] = $pksRaw['name'];
        }

        return $pks;
    }

    public function collectRelations($tableName)
    {
        $relations = [];
        $fksList = $this->representClass::execSql('SELECT
                tc.table_name AS self_table,
                kcu.column_name AS self_column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = \'FOREIGN KEY\' AND tc.table_name=\'' . $tableName . '\' ORDER BY self_column_name');

        foreach ($fksList as $fks) {

//            $selfPks = $this->collectPks($fks['self_table']);
//            if (array_key_exists($fks['self_column_name'], $selfPks)) {
//                $relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name'])));
//            } else {
//                $relationName = preg_replace('/_id$/', '', $fks['self_column_name']);
//                $relationName = preg_replace('/Id$/', '', $relationName);
//                $relationName = preg_replace('/_Id$/', '', $relationName);
//                $relationName = preg_replace('/_ID$/', '', $relationName);
//                $relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($relationName)));
//            }

            $relationName = preg_replace('/_id$/', '', $fks['self_column_name']);
            $relationName = preg_replace('/Id$/', '', $relationName);
            $relationName = preg_replace('/_Id$/', '', $relationName);
            $relationName = preg_replace('/_ID$/', '', $relationName);
            $relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($relationName)));

            $modelName = BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name']));
            $relations[$relationName] = [
                'model' => $modelName,
                'table' => $fks['foreign_table_name'],
                'selfLink' => $fks['self_column_name'],
                'foreignLink' => $fks['foreign_column_name'],
                'multiple' => false,
                'type' => 'parent',
            ];
        }

        $fksList = $this->representClass::execSql('SELECT
                tc.table_name AS foreign_table_name,
                kcu.column_name AS foreign_column_name,
                ccu.table_name AS self_table_name,
                ccu.column_name AS self_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = \'FOREIGN KEY\' AND ccu.table_name=\'' . $tableName . '\'  ORDER BY self_column_name');

        foreach ($fksList as $fks) {
            $relationName = lcfirst(BaseInflector::camelize(BaseInflector::pluralize($fks['foreign_table_name'])));
            $modelName = BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name']));
            $relations[$relationName] = [
//                'name' => $relationName,
                'model' => $modelName,
                'table' => $fks['foreign_table_name'],
                'selfLink' => $fks['self_column_name'],
                'foreignLink' => $fks['foreign_column_name'],
                'multiple' => true,
                'type' => 'depend',
            ];
            $relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name'])));
            $relations[$relationName] = [
//                'name' => $relationName,
                'model' => $modelName,
                'table' => $fks['foreign_table_name'],
                'selfLink' => $fks['self_column_name'],
                'foreignLink' => $fks['foreign_column_name'],
                'multiple' => false,
                'type' => 'depend',
            ];


            $foreignPks = $this->collectPks($fks['foreign_table_name']);
            if (isset($foreignPks[$fks['foreign_column_name']]) && count($foreignPks) === 2) {
//                echo $fks->table_name."\n";die;
                $viaFk = $this->representClass::execSql('SELECT
                        tc.table_name AS via_table_name,
                        kcu.column_name as via_column_name,
                        ccu.table_name AS rel_table_name,
                        ccu.column_name AS rel_column_name
                    FROM
                        information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                        JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
                    WHERE tc.constraint_type = \'FOREIGN KEY\' AND tc.table_name=\'' . $fks['foreign_table_name'] . '\' AND kcu.column_name <> \'' . $fks['foreign_column_name'] . '\' ');

                if (count($viaFk) === 1) {
                    $viaFk = $viaFk[0];
                    $relationName = $viaFk['via_column_name'];
                    $relationName = preg_replace('/_id$/', '', $relationName);
                    $relationName = preg_replace('/Id$/', '', $relationName);
                    $relationName = preg_replace('/_Id$/', '', $relationName);
                    $relationName = preg_replace('/_ID$/', '', $relationName);
                    $relationName = lcfirst(BaseInflector::camelize(BaseInflector::pluralize($relationName)));

                    $modelName = BaseInflector::camelize(BaseInflector::singular($viaFk['rel_table_name']));
                    $relations[$relationName] = [
//                    'name' => $relationName,
                        'model' => $modelName,
                        'table' => $viaFk['rel_table_name'],
                        'via' => [
                            'viaTable' => $fks['foreign_table_name'],
                            'selfLink' => $fks['self_column_name'],
                            'selfInViaLink' => $fks['foreign_column_name'],
                            'foreignInViaLink' => $viaFk['via_column_name'],
                            'foreignLink' => $viaFk['rel_column_name'],
                        ],
                        'multiple' => true,
                        'type' => 'via',
                    ];
                }
            }
        }

        return $relations;
    }

}
