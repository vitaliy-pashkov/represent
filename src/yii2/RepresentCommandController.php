<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\vpashkov\represent\yii2;

use app\vpashkov\represent\generator\Generator;
use app\vpashkov\represent\helpers\BaseInflector;
use yii\console\Controller;
use yii\console\ExitCode;


class RepresentCommandController extends Controller
	{
	public function actionGenerateSchema()
		{
		echo "\n";

		$representClass = \app\vpashkov\represent\yii2\YiiRepresent::class;

		$config = new YiiConfig();
		$generator = Generator::createGenerator($representClass, $config);

		$generator->generateSchema($config);

		return ExitCode::OK;
		}
	}
