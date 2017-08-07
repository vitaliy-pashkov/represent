<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;

class ModelUpdateTest extends \Codeception\Test\Unit
	{

	public function testSimpleOne()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#where' => 'id = 1',
			'col1',
		]);
		$data = $represent->getOne();
		$dataClone = (new \ArrayObject($data))->getArrayCopy();

		$data['col1'] = 'qwe';

		$status = $represent->saveOne($data);
		$data = $represent->getOne();
//		file_put_contents (\Yii::getAlias('@testData/modelUpdate/simpleOne.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelUpdate/simpleOneStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/simpleOne.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/simpleOneStatus.json'), json_encode($status));

		$represent->saveOne($dataClone);
		}

	public function testSimpleAll()
		{
		$represent = new Represent();
		$represent->setMap([
			'#actions' => 'crud',
			'#model' => Test1::class,
			'#order' => 'id',
			'col1',
		]);
		$data = $represent->getAll();
		$dataClone = (new \ArrayObject($data))->getArrayCopy();

		foreach ($data as &$row)
			{
			$val[] = $row['col1'];
			$row['col1'] = 'qwe';
			}

		$status = $represent->saveAll($data);

		$data = $represent->getAll();

//		file_put_contents (\Yii::getAlias('@testData/modelUpdate/simpleAll.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelUpdate/simpleAllStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/simpleAll.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/simpleAllStatus.json'), json_encode($status));

		$represent->saveAll($dataClone);
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
				'col3',
			],
		]);
		$data = $represent->getOne();
		$dataClone = (new \ArrayObject($data))->getArrayCopy();

		$data['test3']['col3'] = 'qwe';

		$status = $represent->saveOne($data);

		$data = $represent->getOne();

//		file_put_contents (\Yii::getAlias('@testData/modelUpdate/parent.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelUpdate/parentStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/parent.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/parentStatus.json'), json_encode($status));

		$represent->saveOne($dataClone);
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
				'col2',
			],
		]);
		$data = $represent->getOne();
		$dataClone = (new \ArrayObject($data))->getArrayCopy();

		$data['test2s'][0]['col2'] = 'qwe';

		$status = $represent->saveOne($data);

		$data = $represent->getOne();

//		file_put_contents (\Yii::getAlias('@testData/modelUpdate/depend.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelUpdate/dependStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/depend.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/dependStatus.json'), json_encode($status));

		$represent->saveOne($dataClone);
		}

	public function testVia()
		{
		$represent = new Represent();
		$represent->setMap([
			'#model' => Test1::class,
			'#actions' => 'crud',
			'#where' => 'id = 1',
			'col1',
			'test2s' => [
				'#actions' => 'crud',
				'col2',
				'test4s' => [
					'#actions' => 'crud',
					'*',
				],
			],
		]);
		$data = $represent->getOne();
		$dataClone = (new \ArrayObject($data))->getArrayCopy();

		$data['test2s'][0]['test4s'][0]['col4'] = 'qwe';

		$status = $represent->saveOne($data);

		$data = $represent->getOne();

//		file_put_contents (\Yii::getAlias('@testData/modelUpdate/via.json'), json_encode($data));
//		file_put_contents(\Yii::getAlias('@testData/modelUpdate/viaStatus.json'), json_encode($status));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/via.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/modelUpdate/viaStatus.json'), json_encode($status));

		$represent->saveOne($dataClone);
		}

	}
