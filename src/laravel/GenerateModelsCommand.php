<?php

namespace vpashkov\represent\laravel;

use Illuminate\Config\Repository as AppConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use vpashkov\represent\generator\Generator;
use vpashkov\represent\helpers\BaseInflector;
use vpashkov\represent\laravel\LaravelConfig;


class GenerateModelsCommand extends Command
{

    protected $signature = 'vpashkov:generate:models {--table=}';

    /**
     * @var string
     */
    protected $name = 'vpashkov:generate:models';

    /**
     * @var Generator
     */
    protected $generator;

    /**
     * @var AppConfig
     */
    protected $appConfig;

//    /**
//     * GenerateModelCommand constructor.
//     * @param Generator $generator
//     * @param AppConfig $appConfig
//     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $table = $this->option('table');

        if ($table === null) {

            $tables = LaravelRepresent::execSql('SELECT *
            FROM pg_catalog.pg_tables
            WHERE schemaname != \'pg_catalog\' AND
                schemaname != \'information_schema\';');
        } else {
            $tables = [['tablename' => $table]];
        }

        foreach ($tables as $table) {
            $tableName = $table['tablename'];
            $modelName = BaseInflector::singular($tableName);
            $modelName = BaseInflector::camelize($modelName);
            echo "$modelName $tableName start \n";
            Artisan::call('krlove:generate:model ' . $modelName . ' --table-name=\'"' . $tableName . '"\' --output-path=Models --base-class-name=vpashkov\\\represent\\\laravel\\\LaravelModel --namespace=App\\\Models --no-timestamps');
        }

//        $tableName = 'placeRegion';
//        $modelName = 'PlaceRegion';
//        Artisan::call('krlove:generate:model ' . $modelName . ' --table-name=\'"'.$tableName.'"\' --output-path=Models --namespace=App\\\Models');

        return 0;
    }

}
