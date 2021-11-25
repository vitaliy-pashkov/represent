<?php

namespace vpashkov\represent\laravel;

use Illuminate\Config\Repository as AppConfig;
use Illuminate\Console\Command;
use vpashkov\represent\generator\Generator;
use vpashkov\represent\laravel\LaravelConfig;


class GenerateSchemaCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'vpashkov:generate:schema';

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

//        $this->generator = $generator;
//        $this->appConfig = $appConfig;
    }


    public function handle()
    {

        $representClass = \vpashkov\represent\laravel\LaravelRepresent::class;
        $config = new LaravelConfig();
        $generator = Generator::createGenerator($representClass, $config);

        $generator->generateSchema($config);


    }

}
