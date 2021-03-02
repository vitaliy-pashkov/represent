<?php


namespace vpashkov\represent\core;


class SchemaRelation extends Schema
	{

	public Schema $parent;


	public ?array $relationSchema;

	public string $fullRelationName = '';
	public string $relationName;


	public function __construct($rawSchema, Config $config, Schema $parent, $relationName)
		{
		parent::__construct($rawSchema, $config);

		$this->root = $parent->root;
		$this->parent = $parent;
		$this->relationName = $relationName;
		$this->fullRelationName = $parent === $parent->root ? $relationName : $parent->fullRelationName . '.' . $relationName;
		$this->relationSchema = $parent->tableSchema['relations'][ $relationName ];

		$this->modelName = $parent->tableSchema['relations'][ $relationName ]['model'];

		$this->tableSchema = $this->getTableSchema();

		$this->modelClass = $this->tableSchema['modelClass'];

		$this->root->relationIndex++;
		$this->shortName = 'r' . $this->root->relationIndex;

		$this->root->shortRelationsMap[ $this->shortName ] = $this;
		$this->root->fullRelationsMap[ $this->fullRelationName ] = $this;

		foreach ($this->parent->path as $pathPart)
			{
			$this->path[] = $pathPart;
			}
		$this->path[] = $this->shortName;

		if ($this->relationSchema['type'] === 'parent')
			{
			$this->parent->addField($this->relationSchema['selfLink']);
			}
		if ($this->relationSchema['type'] === 'depend')
			{
			$this->addField($this->relationSchema['foreignLink']);
			}


		$this->collectRawSchema();
		}
	}