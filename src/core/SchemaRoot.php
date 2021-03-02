<?php


namespace vpashkov\represent\core;


class SchemaRoot extends Schema
	{

	public array $shortRelationsMap = [];
	public array $fullRelationsMap = [];

	public ?int $limit = null;
	public ?int $offset = null;

	public int $relationIndex = 1;

	public array $schemaParameters = [];

	public function __construct($rawSchema, $config, $schemaParameters = [])
		{
		parent::__construct($rawSchema, $config);

		$this->schemaParameters = $schemaParameters;

		$this->relationIndex = 1;
		$this->root = $this;


		$this->modelName = $this->calcModelName($this->rawSchema['#model']);

		$this->shortName = 'r' . $this->relationIndex;
		$this->tableSchema = $this->getTableSchema();
		$this->modelClass = $this->tableSchema['modelClass'];
		$this->path[] = $this->shortName;
		$this->shortRelationsMap[ $this->shortName ] = $this;


		$this->collectRawSchema();

		$this->collectSchemaParameters($schemaParameters);
		$this->collectCustomFields($rawSchema);
		$this->postCollect();
		}

	public function calcModelName($rawModelName)
		{
		if (class_exists($rawModelName))
			{
			$parts = explode('\\', $rawModelName);
			return $parts[ count($parts) - 1 ];
			}
		return $rawModelName;
		}


	}