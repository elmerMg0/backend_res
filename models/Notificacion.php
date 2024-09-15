<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "notificacion".
 *
 * @property int $id
 * @property string $mensaje
 * @property bool $leido
 * @property string $create_ts
 */
class Notificacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notificacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mensaje'], 'required'],
            [['mensaje'], 'string'],
            [['leido'], 'boolean'],
            [['create_ts'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mensaje' => 'Mensaje',
            'leido' => 'Leido',
            'create_ts' => 'Create Ts',
        ];
    }
}
