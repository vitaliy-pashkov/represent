<?php

namespace vpashkov\represent\tests\represents\test1;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;
use vpashkov\represent\tests\models\Test2;
use vpashkov\represent\tests\models\Test3;

class View extends Represent
	{
	public function getMap()
		{
		return [
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
			'test2s' => [
				'#actions' => 'crud',
				'col2',
				'test4s' => [
					'*',
				],
			],
		];
		}

	public function getDictMaps()
		{
		return [
			'test3' => [
				'#model' => Test3::class,
				'*',
			],
			'test2' => [
				'#model' => Test2::class,
				'*',
			],
			'test4' => [
				'#model' => Test4::class,
				'#singleton' => true,
				'*',
			],
		];
		}

	public function processTest3($rows)
		{
		return $rows;
		}

	}


