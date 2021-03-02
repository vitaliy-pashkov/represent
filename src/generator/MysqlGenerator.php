<?php

namespace app\vpashkov\represent\generator;

use app\vpashkov\represent\core\Config;
use app\vpashkov\represent\helpers\BaseInflector;

class MysqlGenerator extends Generator
	{

	public static $typeMap = [
		'tinyint' => self::TYPE_TINYINT,
		'bit' => self::TYPE_INTEGER,
		'smallint' => self::TYPE_SMALLINT,
		'mediumint' => self::TYPE_INTEGER,
		'int' => self::TYPE_INTEGER,
		'integer' => self::TYPE_INTEGER,
		'bigint' => self::TYPE_BIGINT,
		'float' => self::TYPE_FLOAT,
		'double' => self::TYPE_DOUBLE,
		'real' => self::TYPE_FLOAT,
		'decimal' => self::TYPE_DECIMAL,
		'numeric' => self::TYPE_DECIMAL,
		'tinytext' => self::TYPE_TEXT,
		'mediumtext' => self::TYPE_TEXT,
		'longtext' => self::TYPE_TEXT,
		'longblob' => self::TYPE_BINARY,
		'blob' => self::TYPE_BINARY,
		'text' => self::TYPE_TEXT,
		'varchar' => self::TYPE_STRING,
		'string' => self::TYPE_STRING,
		'char' => self::TYPE_CHAR,
		'datetime' => self::TYPE_DATETIME,
		'year' => self::TYPE_DATE,
		'date' => self::TYPE_DATE,
		'time' => self::TYPE_TIME,
		'timestamp' => self::TYPE_TIMESTAMP,
		'enum' => self::TYPE_STRING,
		'varbinary' => self::TYPE_BINARY,
		'json' => self::TYPE_JSON,

	];

	public function generateSchema(Config $config)
		{
		$tablesList = $this->representClass::execSql('SELECT table_name as name FROM information_schema.tables where table_schema <> \'information_schema\' ORDER BY name;');

		$tables = [];
		foreach ($tablesList as $tableInfo)
			{
			$tableName = $tableInfo['name'];
			$modelName = BaseInflector::camelize(BaseInflector::singular($tableName));
			$tables[ $modelName ] = [
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
		if (class_exists($config->modelNs . ucfirst($modelName)))
			{
			return '\\' . $config->modelNs . ucfirst($modelName);
			}
		return '\Illuminate\Database\Eloquent\Model::class';
		}

	public function collectFields($tableName)
		{
		$fieldsList = $this->representClass::execSql('SELECT COLUMN_NAME as name, IS_NULLABLE as is_nullable, DATA_TYPE as db_type
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = \'' . $tableName . '\';');

		$fields = [];
		foreach ($fieldsList as $fieldRaw)
			{
			$fields [ $fieldRaw['name'] ] = self::$typeMap[ $fieldRaw['db_type'] ];
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
		$pksList = $this->representClass::execSql('SELECT k.column_name as name
			FROM information_schema.table_constraints t
			JOIN information_schema.key_column_usage k
			USING(constraint_name,table_schema,table_name)
			WHERE t.constraint_type=\'PRIMARY KEY\'
			  AND t.table_name=\'' . $tableName . '\';');

		$pks = [];
		foreach ($pksList as $pksRaw)
			{
			$pks[ $pksRaw['name'] ] = $pksRaw['name'];
			}

		return $pks;
		}

	public function collectRelations($tableName)
		{
		$relations = [];
		$fksList = $this->representClass::execSql('SELECT
                tc.table_name AS self_table,
                kcu.column_name AS self_column_name,
				kcu.referenced_table_name as foreign_table_name,
                kcu.referenced_column_name as foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = \'FOREIGN KEY\' AND tc.table_name=\'' . $tableName . '\' ORDER BY self_column_name');

		foreach ($fksList as $fks)
			{
			$selfPks = $this->collectPks($fks['self_table']);
			if (array_key_exists($fks['self_column_name'], $selfPks))
				{
				$relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name'])));
				}
			else
				{
				$relationName = preg_replace('/_id$/', '', $fks['self_column_name']);
				$relationName = preg_replace('/_Id$/', '', $relationName);
				$relationName = preg_replace('/_ID$/', '', $relationName);
				$relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($relationName)));
				}

			$modelName = BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name']));
			$relations[ $relationName ] = [
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
                kcu.referenced_table_name as self_table_name,
                kcu.referenced_column_name as self_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = \'FOREIGN KEY\' AND kcu.referenced_table_name=\'' . $tableName . '\'  ORDER BY self_column_name');

		foreach ($fksList as $fks)
			{
			$relationName = lcfirst(BaseInflector::camelize(BaseInflector::pluralize($fks['foreign_table_name'])));
			$modelName = BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name']));
			$relations[ $relationName ] = [
//                'name' => $relationName,
				'model' => $modelName,
				'table' => $fks['foreign_table_name'],
				'selfLink' => $fks['self_column_name'],
				'foreignLink' => $fks['foreign_column_name'],
				'multiple' => true,
				'type' => 'depend',
			];
			$relationName = lcfirst(BaseInflector::camelize(BaseInflector::singular($fks['foreign_table_name'])));
			$relations[ $relationName ] = [
//                'name' => $relationName,
				'model' => $modelName,
				'table' => $fks['foreign_table_name'],
				'selfLink' => $fks['self_column_name'],
				'foreignLink' => $fks['foreign_column_name'],
				'multiple' => false,
				'type' => 'depend',
			];


			$foreignPks = $this->collectPks($fks['foreign_table_name']);
			if (isset($foreignPks[ $fks['foreign_column_name'] ]) && count($foreignPks) === 2)
				{
				$viaFk = $this->representClass::execSql('SELECT
                        tc.table_name AS via_table_name,
                        kcu.column_name as via_column_name,
                        kcu.referenced_table_name AS rel_table_name,
                        kcu.referenced_column_name AS rel_column_name
                    FROM
                        information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                    WHERE tc.constraint_type = \'FOREIGN KEY\' AND tc.table_name=\'' . $fks['foreign_table_name'] . '\' AND kcu.column_name <> \'' . $fks['foreign_column_name'] . '\' ');

				if (count($viaFk) === 1)
					{
					$viaFk = $viaFk[0];
					$relationName = lcfirst(BaseInflector::camelize(BaseInflector::pluralize($viaFk['rel_table_name'])));
					$modelName = BaseInflector::camelize(BaseInflector::singular($viaFk['rel_table_name']));
					$relations[ $relationName ] = [
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
