<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test2".
 *
 * @property integer $id
 * @property string $col2
 * @property integer $test1_id
 *
 * @property Test1 $test1
 * @property Test2HasTest4[] $test2HasTest4s
 * @property Test4[] $test4s
 */
class Test2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test2';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'test1_id'], 'required'],
            [['id', 'test1_id'], 'integer'],
            [['col2'], 'string', 'max' => 45],
            [['test1_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test1::className(), 'targetAttribute' => ['test1_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'col2' => 'Col2',
            'test1_id' => 'Test1 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest1()
    {
        return $this->hasOne(Test1::className(), ['id' => 'test1_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest2HasTest4s()
    {
        return $this->hasMany(Test2HasTest4::className(), ['test2_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest4s()
    {
        return $this->hasMany(Test4::className(), ['id' => 'test4_id'])->viaTable('test2_has_test4', ['test2_id' => 'id']);
    }
}
