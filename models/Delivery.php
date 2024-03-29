<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "delivery".
 *
 * @property int $id
 * @property string $nombre
 * @property int $celular
 * @property string|null $direccion
 * @property string $fecha_creacion
 */
class Delivery extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'celular'], 'required'],
            [['celular'], 'default', 'value' => null],
            [['celular'], 'integer'],
            [['fecha_creacion'], 'safe'],
            [['nombre'], 'string', 'max' => 50],
            [['direccion'], 'string', 'max' => 80],
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
            'celular' => 'Celular',
            'direccion' => 'Direccion',
            'fecha_creacion' => 'Fecha Creacion',
        ];
    }
}
