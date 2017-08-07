<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

class ModelCreateTest extends \Codeception\Test\Unit
	{

	public function testSimpleOne()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'col1',
			'test3_id',
		]);
		$test1 = json_decode('{"id":"10","col1":"qwe", "test3_id":1}', true);
		$status = $represent->saveOne($test1);
		$data = $represent->getAll();

//		file_put_contents (\Yii::getAlias('@testData/modelCreate/simpleOne.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/simpleOneStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleOne.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleOneStatus.json'), json_encode($status));

		$status = $represent->deleteOne($test1);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/simpleOneDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleOneDeleteStatus.json'), json_encode($status));
		}

	public function testSimpleMany()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'col1',
			'test3_id',
		]);
		$test1 = json_decode('[{"id":"10","col1":"qwe", "test3_id":1}, {"id":"11","col1":"qwe", "test3_id":1}, {"id":"12","col1":"qwe", "test3_id":1}]', true);
		$status = $represent->saveAll($test1);
		$data = $represent->getAll();

//		file_put_contents (\Yii::getAlias('@testData/modelCreate/simpleMany.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/simpleManyStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleMany.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleManyStatus.json'), json_encode($status));

		$status = $represent->deleteAll($test1);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/simpleManyDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/simpleManyDeleteStatus.json'), json_encode($status));
		}

	public function testParent()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
			'test3' => [
				'#actions' => 'crud',
				'*',
			],
		]);

		$test1 = $represent->getOne();
		$test3 = json_decode('{"id": 4, "col3": "asd"}', true);
		$test1['test3'] = $test3;

		$status = $represent->saveOne($test1);
		$data = $represent->getOne();
//		file_put_contents (\Yii::getAlias('@testData/modelCreate/parent.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/parentStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/parent.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/parentStatus.json'), json_encode($status));
		$this->assertEquals(4, $data['test3_id']);

		$test1['test3_id'] = 1;
		$test1['test3']['#delete'] = true;
		$status = $represent->saveOne($test1);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/parentDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/parentDeleteStatus.json'), json_encode($status));
		}

	public function testDepend()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
			'test2s' => [
				'#actions' => 'crud',
				'*',
			],
		]);

		$test1 = $represent->getOne();
		$test2 = json_decode('{"id": 10, "col2": "asd"}', true);
		$test1['test2s'][] = $test2;

		$status = $represent->saveOne($test1);
		$data = $represent->getOne();
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/depend.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/dependStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/depend.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/dependStatus.json'), json_encode($status));
		$this->assertEquals(4, count($data['test2s']));

		foreach ($data['test2s'] as &$test2)
			{
			if ($test2['id'] == 10)
				{
				$test2['#delete'] = true;
				}
			}

		$status = $represent->saveOne($data);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/dependDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/dependDeleteStatus.json'), json_encode($status));
		}

	public function testVia()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
			'test2s' => [
				'#actions' => 'crud',
				'*',
				'test4s' => [
					'#actions' => 'crud',
					'*',
				],
			],
		]);

		$test1 = $represent->getOne();
		$test4 = json_decode('{"id": 10, "col4": "asd"}', true);
		$test1['test2s'][0]['test4s'][] = $test4;

		$status = $represent->saveOne($test1);
		$data = $represent->getOne();
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/via.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/viaStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/via.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/viaStatus.json'), json_encode($status));
		$this->assertEquals(3, count($data['test2s'][0]['test4s']));


		$data['test2s'][0]['test4s'][2]['#delete'] = true;

		$status = $represent->saveOne($data);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/viaDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/viaDeleteStatus.json'), json_encode($status));
		}


	public function testModelException()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions'=>'crud',
			'*'
		]);

		$test1 = ['col1'=>'asd'];

		$status = $represent->saveOne($test1);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/modelExceptionStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/modelExceptionStatus.json'), json_encode($status));
		}


	public function testUnlink()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
			'test2s' => [
				'#actions' => 'crud',
				'*',
				'test4s' => [
					'#actions' => 'crud',
					'*',
				],
			],
		]);

		$test1 = $represent->getOne();
		$test4 = $test1['test2s'][0]['test4s'][0];

		$test1['test2s'][0]['test4s'][0]['#unlink'] = true;

		$status = $represent->saveOne($test1);
		$data = $represent->getOne();
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/unlink.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/unlinkStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/unlink.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/unlinkStatus.json'), json_encode($status));

		$test1['test2s'][0]['test4s'][] = $test4;

		$status = $represent->saveOne($test1);
//		file_put_contents(\Yii::getAlias('@testData/modelCreate/unlinkDeleteStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelCreate/unlinkDeleteStatus.json'), json_encode($status));
		}
	}
