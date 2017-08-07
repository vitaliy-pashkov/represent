<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test4".
 *
 * @property integer $id
 * @property string $col4
 * @property integer $test5_id
 *
 * @property Test2HasTest4[] $test2HasTest4s
 * @property Test2[] $test2s
 * @property Test5 $test5
 */
class Test4 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test4';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'test5_id'], 'integer'],
            [['col4'], 'string', 'max' => 45],
            [['test5_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test5::className(), 'targetAttribute' => ['test5_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'col4' => 'Col4',
            'test5_id' => 'Test5 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest2HasTest4s()
    {
        return $this->hasMany(Test2HasTest4::className(), ['test4_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest2s()
    {
        return $this->hasMany(Test2::className(), ['id' => 'test2_id'])->viaTable('test2_has_test4', ['test4_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest5()
    {
        return $this->hasOne(Test5::className(), ['id' => 'test5_id']);
    }
}
