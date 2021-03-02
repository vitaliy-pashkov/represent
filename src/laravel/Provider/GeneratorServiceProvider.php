<?php

namespace app\vpashkov\represent\Provider;

use Illuminate\Support\ServiceProvider;
use Krlove\EloquentModelGenerator\Command\GenerateModelCommand;
use Krlove\EloquentModelGenerator\EloquentModelBuilder;
use Krlove\EloquentModelGenerator\Processor\CustomPrimaryKeyProcessor;
use Krlove\EloquentModelGenerator\Processor\CustomPropertyProcessor;
use Krlove\EloquentModelGenerator\Processor\ExistenceCheckerProcessor;
use Krlove\EloquentModelGenerator\Processor\FieldProcessor;
use Krlove\EloquentModelGenerator\Processor\NamespaceProcessor;
use Krlove\EloquentModelGenerator\Processor\RelationProcessor;
use Krlove\EloquentModelGenerator\Processor\TableNameProcessor;


class GeneratorServiceProvider extends ServiceProvider
    {
    /**
     * {@inheritDoc}
     */
    public function register()
        {
        $this->commands([
            GenerateModelCommand::class,
        ]);
        }
    }
