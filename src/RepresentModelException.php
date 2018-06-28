<?php

namespace vpashkov\represent;


use yii\base\Exception;
use yii\db\ActiveRecord;

class RepresentModelException extends Exception
{
    public $model;
    public $errors;
    public $row;

    /**
     * RepresentQueryException constructor.
     * @param ActiveRecord $model
     * @param array $errors
     * @param array $row
     */
    public function __construct($model, $errors, $row)
    {
        $this->model = $model;
        $this->errors = $errors;
        $this->row = $row;
    }

    public function info()
    {
        return [
            "errors" => $this->errors,
            "source row" => $this->row,
            "model_attributes" => $this->model->attributes,
        ];
    }

}