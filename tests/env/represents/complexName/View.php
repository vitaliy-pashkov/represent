<?php

namespace vpashkov\represent\tests\represents\complexName;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

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
				]
			]
		];
		}

	}


