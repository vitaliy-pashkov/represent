<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

class LoaderTest extends \Codeception\Test\Unit
	{

	public function testGeneral()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => 'id, test2s.id, test2s.test4s.id, test3.id',
			'col1',
			'test2s' => [
				'*',
				'test4s' => [
					'*',
				],
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/general.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/general.json'), json_encode($data));
		}

	public function testOrderArray()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/orderArray.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/orderArray.json'), json_encode($data));
		}

	public function testLimit()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#limit' => 3,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/limit.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/limit.json'), json_encode($data));
		}

	public function testOffset()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#offset' => 3,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/offset.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/offset.json'), json_encode($data));
		}

	public function testLimitOffset()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#limit' => 3,
			'#offset' => 3,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/limitOffset.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/limitOffset.json'), json_encode($data));
		}

	public function testLimitOffsetWhere()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#where' => 'id < 6',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#limit' => 3,
			'#offset' => 3,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getAll();
//		file_put_contents (\Yii::getAlias('@testData/loader/limitOffsetWhere.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/limitOffsetWhere.json'), json_encode($data));
		}

	public function testCount1()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(9,$count);
		}

	public function testCount2()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#where' => "test3.id = 2",
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(3,$count);
		}

	public function testCount3()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#where' => "test3.id <= 2 AND test2s.id > 10",
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(0,$count);
		}

	public function testCount4()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#where' => "test2s.id < 5",
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(2,$count);
		}

	public function testCount5()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#limit' => 5,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(5,$count);
		}

	public function testCount6()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#offset' => 3,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(6,$count);
		}

	public function testCount7()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'r',
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'#where' => "test3.id <= 2 AND test2s.id > 3",
			'#offset' => 1,
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$count = $represent->getCount();
		$this->assertEquals(1,$count);
		}

	public function testMeta1()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		]);
		$data = $represent->getMeta();
//		file_put_contents (\Yii::getAlias('@testData/loader/meta1.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/meta1.json'), json_encode($data));
		}

	public function testMeta2()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#where1' => ["test2s.id" => '1'],
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
		$data = $represent->getMeta();
//		file_put_contents (\Yii::getAlias('@testData/loader/meta2.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/meta2.json'), json_encode($data));
		}

	public function testByModel()
		{
		$represent = new Represent();
		$map = new \vpashkov\represent\Map([
			'#model' => Test1::class,
			'#order' => ['test2s.id' => SORT_DESC, 'id' => SORT_ASC],
			'col1',
			'test2s' => [
				'*',
			],
			'test3' => [
				'*',
			],
		], $represent);
		$loader = new \vpashkov\represent\Loader($map, $represent);

		$test1 = Test1::findOne(3);
		$loader->byModel($test1);
		$data = $loader->all();

//		file_put_contents (\Yii::getAlias('@testData/loader/byModel.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/loader/byModel.json'), json_encode($data));
		}

	/**
	 * @expectedException \vpashkov\represent\RepresentQueryException
	 */
	public function testUnknownFieldInConditionException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#where' => 'qwe = "1"',
			'test2s' => [
				'test4s' => [
				]
			]
		]);
		$represent->getAll();
		}

	/**
	 * @expectedException \yii\db\Exception
	 */
	public function testUnknownDbException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#where' => 'asd qwe',
			'test2s' => [
				'test4s' => [
				]
			]
		]);
		$represent->getAll();
		}
	}
