<?php

namespace vpashkov\represent\core;

interface ModelInterface
	{

	static public function representFind($class, $where);

	public function representSave();

	public function representIsLinked($model, $relation);

	public function representLink($relationName, $model);

	public function representUnlink($relationName, $model, $deleteFlag);

	public function representDelete();

	public function representGetRelated($relationName);

	public function representGetValue($field);

	public function representSetValue($field, $value);

	public function representIsNewRecord();


	}