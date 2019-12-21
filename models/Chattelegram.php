<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "chat_telegram".
 *
 * @property int $id
 * @property string $chat
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_by
 * @property int|null $updated_at
 */
class Chattelegram extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_telegram';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chat'], 'required'],
            [['chat'], 'string'],
            [['created_by', 'created_at', 'updated_by', 'updated_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat' => 'Chat',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }
}
