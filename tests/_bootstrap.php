<?php

define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require_once(__DIR__ . '/../../../../vendor/yiisoft/yii2/Yii.php');
require __DIR__ .'/../../../../vendor/autoload.php';


\Yii::setAlias('@testData', __DIR__.'/_data');