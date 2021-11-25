<?php
if (!function_exists('represent')) {

    function represent(string $name, $parameters = [])
    {
        return \vpashkov\represent\laravel\LaravelRepresent::byName($name, $parameters);
    }
    
}
