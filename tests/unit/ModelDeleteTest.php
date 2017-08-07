<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test6;
use \vpashkov\represent\tests\models\Test1;

class ModelDeleteTest extends \Codeception\Test\Unit
	{

	public function testSimpleOne()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test6::class,
			'#where' => 'id = 6',
			'*',
		]);
		$test6 = $represent->getOne();
		$status = $represent->deleteOne($test6);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/simpleOneStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/simpleOneStatus.json'), json_encode($status));

		$status = $represent->saveOne($test6);
//		file_put_contents(\Yii::getAlias('@testData/modelDelete/simpleOneRecoverStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/simpleOneRecoverStatus.json'), json_encode($status));
		}

	public function testRights()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test6::class,
			'#where' => 'id = 6',
			'*',
		]);
		$test6 = $represent->getOne();
		$status = $represent->deleteOne($test6);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/rightsStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/rightsStatus.json'), json_encode($status));
		}

	public function testFlag()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test6::class,
			'#actions' => 'crud',
			'#where' => 'id = 6',
			'*',
		]);
		$test6 = $represent->getOne();

		$test6['#delete'] = true;

		$status = $represent->saveOne($test6);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/flagStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/flagStatus.json'), json_encode($status));
		$test6['#delete'] = false;
		$status = $represent->saveOne($test6);
//		file_put_contents(\Yii::getAlias('@testData/modelDelete/flagRecoverStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/flagRecoverStatus.json'), json_encode($status));
		}

	public function testSimpleMany()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test6::class,
			'*',
			'test7s' => [
				'#actions' => 'crud',
				'*'
			]
		]);
		$test6 = $represent->getAll();
		$status = $represent->deleteAll($test6);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/simpleManyStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/simpleManyStatus.json'), json_encode($status));

		$status = $represent->saveAll($test6);
//		file_put_contents(\Yii::getAlias('@testData/modelDelete/simpleManyRecoverStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/simpleManyRecoverStatus.json'), json_encode($status));
		}

	public function testParent()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test6::class,
			'#where' => 'id = 10',
			'*',
			'test7s' => [
				'#actions' => 'crud',
				'*'
			],
			'test5' => [
				'#actions' => 'crud',
				'*',
			]
		]);
		$test6 = $represent->getOne();

		$status = $represent->deleteOne($test6);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/parentStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/parentStatus.json'), json_encode($status));

		$status = $represent->saveOne($test6);
//		file_put_contents(\Yii::getAlias('@testData/modelDelete/parentRecoverStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/parentRecoverStatus.json'), json_encode($status));
		}

	public function testError()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'crud',
			'*',
		]);
		$status = $represent->deleteOne(['id'=>1]);

//		file_put_contents(\Yii::getAlias('@testData/modelDelete/errorStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelDelete/errorStatus.json'), json_encode($status));
		}

	}
