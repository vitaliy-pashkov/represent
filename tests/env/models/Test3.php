<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test3".
 *
 * @property integer $id
 * @property string $col3
 *
 * @property Test1[] $test1s
 */
class Test3 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test3';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id'], 'integer'],
            [['col3'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'col3' => 'Col3',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest1s()
    {
        return $this->hasMany(Test1::className(), ['test3_id' => 'id']);
    }
}
