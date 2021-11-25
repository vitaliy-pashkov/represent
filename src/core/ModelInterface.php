<?php

namespace vpashkov\represent\core;

interface ModelInterface
	{

	static public function representFind($class, $where);

	public function representSave(Schema $schema);

	public function representIsLinked($model, $relation);

	public function representLink($relationName, $model, $parentRelation, $childRelation);

	public function representUnlink($relationName, $model, $deleteFlag, $parentRelation, $childRelation);

	public function representDelete();

	public function representGetRelated($relationName);

	public function representGetValue($field);

	public function representSetValue($field, $value);

	public function representIsNewRecord();


	}
