<?php

namespace vpashkov\represent\tests\modules\main\represents\test1;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

class View extends Represent
	{
	public function getQuery()
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
				]
			]
		];
		}

	}


