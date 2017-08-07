<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

class BuilderTest extends \Codeception\Test\Unit
	{

	public function testGeneral()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'col1',
			'test2s' => [
				'*',
				'test4s' => [
					'test5' => [
						'*',
						'test6s' => [
							'*'
						]
					]
				],
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
		file_put_contents (\Yii::getAlias('@testData/builder/general.json'), json_encode($data));
//		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/builder/general.json'), json_encode($data));
		}


	}
