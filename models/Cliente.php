<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cliente".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $celular
 * @property string|null $direccion
 * @property string|null $descripcion_domicilio
 * @property string $fecha_crecion
 * @property string|null $google_maps_link
 *
 * @property Venta[] $ventas
 */
class Cliente extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cliente';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'fecha_crecion'], 'required'],
            [['direccion', 'descripcion_domicilio', 'google_maps_link'], 'string'],
            [['fecha_crecion'], 'safe'],
            [['nombre'], 'string', 'max' => 80],
            [['celular'], 'string', 'max' => 8],
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
            'descripcion_domicilio' => 'Descripcion Domicilio',
            'fecha_crecion' => 'Fecha Crecion',
            'google_maps_link' => 'Google Maps Link',
        ];
    }

    /**
     * Gets query for [[Ventas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentas()
    {
        return $this->hasMany(Venta::class, ['cliente_id' => 'id']);
    }
}
