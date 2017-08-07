<?php

namespace vpashkov\represent\tests\models;

use Yii;

/**
 * This is the model class for table "test7".
 *
 * @property integer $id
 * @property string $col
 * @property integer $test6_id
 *
 * @property Test6 $test6
 */
class Test7 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'test7';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'test6_id'], 'required'],
            [['id', 'test6_id'], 'integer'],
            [['col'], 'string', 'max' => 45],
            [['test6_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test6::className(), 'targetAttribute' => ['test6_id' => 'id']],
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
            'test6_id' => 'Test6 ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest6()
    {
        return $this->hasOne(Test6::className(), ['id' => 'test6_id']);
    }
}
