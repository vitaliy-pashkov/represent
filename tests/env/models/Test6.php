<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test6".
 *
 * @property integer $id
 * @property string $col
 * @property integer $test5_id
 *
 * @property Test5 $test5
 * @property Test7[] $test7s
 */
class Test6 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test6';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'test5_id'], 'required'],
            [['id', 'test5_id'], 'integer'],
            [['col'], 'string', 'max' => 45],
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
            'col' => 'Col',
            'test5_id' => 'Test5 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest5()
    {
        return $this->hasOne(Test5::className(), ['id' => 'test5_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest7s()
    {
        return $this->hasMany(Test7::className(), ['test6_id' => 'id']);
    }
}
