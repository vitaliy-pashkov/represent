<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\TestRepresent;

class RepresentTest extends \Codeception\Test\Unit
	{

	public function testCreateName()
		{
		$represent = TestRepresent::create("test1/view");

		$this->assertInstanceOf(\vpashkov\represent\tests\represents\test1\View::class, $represent);
		}

	public function testCreateModuleName()
		{
		$represent = TestRepresent::create("main/test1/view");

		$this->assertInstanceOf( \vpashkov\represent\tests\modules\main\represents\test1\View::class, $represent);
		}

	public function testCreateComplexName()
		{
		$represent = TestRepresent::create("complex-name/view");

		$this->assertInstanceOf( \vpashkov\represent\tests\represents\complexName\View::class, $represent);
		}


	/**
	 * @expectedException \Exception
	 */
	public function testCreateNameException()
		{
		$represent = TestRepresent::create("test1/view1");
		}

	public function testGetOneNull()
		{
		$represent = TestRepresent::create("test1/view");
		$represent->setMap([
			'#model' => \vpashkov\represent\tests\models\Test1::class,
			'#where' => 'id > 10',
			'*'
		]);
		$data = $represent->getOne();
		$this->assertEquals(null, $data);
		}

	public function testGetDicts()
		{
		$represent = TestRepresent::create("test1/view");
		$represent->setMap([
			'#model' => \vpashkov\represent\tests\models\Test1::class,
			'*'
		]);
		$data = $represent->getDicts();
//		file_put_contents (\Yii::getAlias('@testData/represent/dicts.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/represent/dicts.json'), json_encode($data));
		}

	public function testGetDict()
		{
		$represent = TestRepresent::create("test1/view");
		$represent->setMap([
			'#model' => \vpashkov\represent\tests\models\Test1::class,
			'*'
		]);
		$data = $represent->getDict('test3');
//		file_put_contents (\Yii::getAlias('@testData/represent/dict.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/represent/dict.json'), json_encode($data));
		}

	/**
	 * @expectedException \Exception
	 */
	public function testMapNotSet()
		{
		$represent = new Represent();
		$data = $represent->getAll();
		}

	}
