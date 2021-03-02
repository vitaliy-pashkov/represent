<?php

namespace Krlove\EloquentModelGenerator\Command;

use Illuminate\Config\Repository as AppConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Krlove\EloquentModelGenerator\Config;
use Krlove\EloquentModelGenerator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use vpashkov\represent\generator\PgsqlGenerator;


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

    /**
     * GenerateModelCommand constructor.
     * @param Generator $generator
     * @param AppConfig $appConfig
     */
    public function __construct(Generator $generator, AppConfig $appConfig)
        {
        parent::__construct();

        $this->generator = $generator;
        $this->appConfig = $appConfig;
        }


    public function handle()
        {

        //if(is pgsql)
        $generator = new PgsqlGenerator();
        $generator->collectFields('base_adverts');


        }

    }
