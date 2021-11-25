<?php

namespace vpashkov\represent\laravel;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
    {
    /**
     * {@inheritDoc}
     */
    public function boot()
        {
        $this->commands([
            GenerateSchemaCommand::class,
        ]);
        }

    public function register()
    {
        $this->commands([
            GenerateSchemaCommand::class,
            GenerateModelsCommand::class,
        ]);
    }
    }
