<?php
/**
 * Created by PhpStorm.
 * User: vitaliy
 * Date: 23.05.2019
 * Time: 23:50
 */

namespace vpashkov\represent;


class Rule extends \yii\rbac\Rule
	{
	public function execute($userId, $authItem, $params)
		{
		if ($params['action'] == 'r')
			{
			if ($params['readType'] == 'one')
				{
				return $this->readOne($userId, $params['row']);
				}
			if ($params['readType'] == 'all')
				{
				return $this->readOne($userId, $params['rows']);
				}
			}
		if ($params['action'] == 'c')
			{
			return $this->create($userId, $params['model'], $params['row']);
			}
		if ($params['action'] == 'u')
			{
			return $this->update($userId, $params['model'], $params['row']);
			}
		if ($params['action'] == 'd')
			{
			return $this->update($userId, $params['model']);
			}
		}

	public function readOne($userId, $row)
		{
		return false;
		}

	public function readAll($userId, $rows)
		{
		return false;
		}

	public function create($userId, $model, $row)
		{
		return false;
		}

	public function update($userId, $model, $row)
		{
		return false;
		}

	public function delete($userId, $model, $row)
		{
		return false;
		}

	}