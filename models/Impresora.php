<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "impresora".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $lugar
 */
class Impresora extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'impresora';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 50],
            [['lugar'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'lugar' => 'Lugar',
        ];
    }
}
