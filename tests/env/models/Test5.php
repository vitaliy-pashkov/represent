<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test5".
 *
 * @property integer $id
 * @property string $col
 *
 * @property Test4[] $test4s
 * @property Test6[] $test6s
 */
class Test5 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test5';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id'], 'integer'],
            [['col'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'col' => 'Col',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest4s()
    {
        return $this->hasMany(Test4::className(), ['test5_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest6s()
    {
        return $this->hasMany(Test6::className(), ['test5_id' => 'id']);
    }
}
