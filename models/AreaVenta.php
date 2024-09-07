<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "area_venta".
 *
 * @property int $id
 * @property bool $estado
 * @property int|null $nro_filas
 * @property int|null $nro_columnas
 * @property string $nombre
 * @property string|null $url_image
 * @property string $tipo
 *
 * @property Mesa[] $mesas
 * @property Venta[] $ventas
 */
class AreaVenta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'area_venta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['estado'], 'boolean'],
            [['nro_filas', 'nro_columnas'], 'default', 'value' => null],
            [['nro_filas', 'nro_columnas'], 'integer'],
            [['nombre', 'tipo'], 'required'],
            [['nombre'], 'string', 'max' => 20],
            [['url_image'], 'string', 'max' => 30],
            [['tipo'], 'string', 'max' => 10],
            [['nombre'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'estado' => 'Estado',
            'nro_filas' => 'Nro Filas',
            'nro_columnas' => 'Nro Columnas',
            'nombre' => 'Nombre',
            'url_image' => 'Url Image',
            'tipo' => 'Tipo',
        ];
    }

    /**
     * Gets query for [[Mesas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMesas()
    {
        return $this->hasMany(Mesa::class, ['area_venta_id' => 'id']);
    }

    /**
     * Gets query for [[Ventas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentas()
    {
        return $this->hasMany(Venta::class, ['area_venta_id' => 'id']);
    }
}
