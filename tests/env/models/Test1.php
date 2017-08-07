<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test1".
 *
 * @property integer $id
 * @property string $col1
 * @property integer $test3_id
 *
 * @property Test3 $test3
 * @property Test2[] $test2s
 */
class Test1 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test1';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'test3_id'], 'required'],
            [['id', 'test3_id'], 'integer'],
            [['col1'], 'string', 'max' => 45],
            [['test3_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test3::className(), 'targetAttribute' => ['test3_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'col1' => 'Col1',
            'test3_id' => 'Test3 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest3()
    {
        return $this->hasOne(Test3::className(), ['id' => 'test3_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest2s()
    {
        return $this->hasMany(Test2::className(), ['test1_id' => 'id']);
    }
}
