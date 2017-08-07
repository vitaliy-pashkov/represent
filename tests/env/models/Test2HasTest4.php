<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test2_has_test4".
 *
 * @property integer $test2_id
 * @property integer $test4_id
 *
 * @property Test2 $test2
 * @property Test4 $test4
 */
class Test2HasTest4 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test2_has_test4';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['test2_id', 'test4_id'], 'required'],
            [['test2_id', 'test4_id'], 'integer'],
            [['test2_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test2::className(), 'targetAttribute' => ['test2_id' => 'id']],
            [['test4_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test4::className(), 'targetAttribute' => ['test4_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'test2_id' => 'Test2 ID',
            'test4_id' => 'Test4 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest2()
    {
        return $this->hasOne(Test2::className(), ['id' => 'test2_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest4()
    {
        return $this->hasOne(Test4::className(), ['id' => 'test4_id']);
    }
}
