<?php

namespace tests;

use \vpashkov\represent\Represent;
use \vpashkov\represent\tests\models\Test1;
use \vpashkov\represent\tests\models\Test4;

class OptionMapTest extends \Codeception\Test\Unit
	{

	public function testGeneral()
		{
		$map = [
			'filter' => [
				'type' => 'filter',
				'name' => 'general',
				'rule' => 'search',
				'conditions' => [
					[
						'type' => 'condition',
						'name' => 'search',
						'field' => 'id',
						'operator' => '<',
						'value' => 5,
					],
				],
			],
			'where' => "id > 1",
			'limit' => 2,
			'offset' => 1,
			'order' => 'id DESC',
		];

		$represent = new Represent([
			'#model' => Test1::class,
			'col1',
		], [
			'map' => json_encode($map),
		]);
		$data = $represent->getAll();
//		file_put_contents(\Yii::getAlias('@testData/optionMap/general.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/optionMap/general.json'), json_encode($data));
		}

	public function testFilter()
		{
		$map = [
			'filter' => [
				'type' => 'filter',
				'name' => 'general',
				'rule' => '( search || id ) && empty',
				'conditions' => [
					[
						'type' => 'filter',
						'name' => 'search',
						'rule' => 'search_sub',
						'conditions' => [
							[
								'type' => 'condition',
								'name' => 'search_sub',
								'field' => 'id',
								'operator' => '<',
								'value' => 5,
							],
						],
					],
					[
						'type' => 'condition',
						'name' => 'id',
						'field' => 'id',
						'operator' => '==',
						'value' => 9,
					],
					[
						'type' => 'filter',
						'name' => 'empty',
						'rule' => '',
						'conditions' => [],
					],
				],
			],
		];

		$represent = new Represent([
			'#model' => Test1::class,
			'col1',
		], [
			'map' => json_encode($map),
		]);
		$data = $represent->getAll();
//		file_put_contents(\Yii::getAlias('@testData/optionMap/filter.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/optionMap/filter.json'), json_encode($data));
		}

	public function testCondition()
		{
		$map = [
			'filter' => [
				'type' => 'filter',
				'name' => 'general',
				'rule' => '( null || ( id && not_null )) && like',
				'conditions' => [
					[
						'type' => 'condition',
						'name' => 'null',
						'field' => 'test5_id',
						'operator' => '==',
						'value' => null,
					],
					[
						'type' => 'condition',
						'name' => 'not_null',
						'field' => 'id',
						'operator' => '!=',
						'value' => null,
					],
					[
						'type' => 'condition',
						'name' => 'id',
						'field' => 'id',
						'operator' => '==',
						'value' => 7,
					],
					[
						'type' => 'condition',
						'name' => 'like',
						'field' => 'col4',
						'operator' => '~',
						'value' => '',
					],
				],
			],
		];

		$represent = new Represent([
			'#model' => Test4::class,
			'col4',
			'test5_id',
		], [
			'map' => json_encode($map),
		]);
		$data = $represent->getAll();
//		file_put_contents(\Yii::getAlias('@testData/optionMap/condition.json'), json_encode($data));
		$this->assertJsonStringEqualsJsonFile(\Yii::getAlias('@testData/optionMap/condition.json'), json_encode($data));
		}

	/**
	 * @expectedException \Exception
	 */
	public function testMaxLimit()
		{
		$map = [
			'limit' => 1000001,
		];

		$represent = new Represent([
			'#model' => Test1::class,
			'col1',
		], [
			'map' => json_encode($map),
		]);
		$data = $represent->getAll();
		}


	}
