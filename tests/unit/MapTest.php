<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\RepresentQueryException;
use \vpashkov\represent\tests\models\Test1;

class MapTest extends \Codeception\Test\Unit
	{

	public function testGeneral()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#where' => ['col1' => 'a', 'test3.col3' => 'q'],
			'#where1' => 'test2s.col2 = "z" OR test2s.col2 = "x"',
			'#order' => 'id',
			'#limit' => 1,
			'#offset' => 0,
			'col1',
			'test2s' => [
				'*'
			],
			'test3' => [
				'*'
			]
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/map/general.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/map/general.json'), json_encode($data));
		}


	/**
	 * @expectedException \vpashkov\represent\RepresentQueryException
	 */
	public function testFieldException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'col3',
		]);
		$represent->getAll();
		}

	/**
	 * @expectedException \vpashkov\represent\RepresentQueryException
	 */
	public function testRelationException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'test' => [],
		]);
		$represent->getAll();
		}

	/**
	 * @expectedException \vpashkov\represent\RepresentQueryException
	 */
	public function testWhereRelationException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#where' => 'test2.test4s.col4 = "1"',
			'test2s' => [
				'test4s' => [
				]
			]
		]);
		$represent->getAll();
		}

	/**
	 * @expectedException \vpashkov\represent\RepresentQueryException
	 */
	public function testArrayWhereFieldException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#where1' => ["test2s.idq" => '1'],
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
		$represent->getAll();
		}

	}
