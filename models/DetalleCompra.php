<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detalle_compra".
 *
 * @property int $id
 * @property int $presentacion_id
 * @property int $compra_id
 * @property float $cantidad
 * @property int $almacen_id
 * @property float $costo_unitario
 *
 * @property Almacen $almacen
 * @property Compra $compra
 * @property Presentacion $presentacion
 */
class DetalleCompra extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detalle_compra';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['presentacion_id', 'compra_id', 'cantidad', 'almacen_id', 'costo_unitario'], 'required'],
            [['presentacion_id', 'compra_id', 'almacen_id'], 'default', 'value' => null],
            [['presentacion_id', 'compra_id', 'almacen_id'], 'integer'],
            [['cantidad', 'costo_unitario'], 'number'],
            [['almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_id' => 'id']],
            [['compra_id'], 'exist', 'skipOnError' => true, 'targetClass' => Compra::class, 'targetAttribute' => ['compra_id' => 'id']],
            [['presentacion_id'], 'exist', 'skipOnError' => true, 'targetClass' => Presentacion::class, 'targetAttribute' => ['presentacion_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'presentacion_id' => 'Presentacion ID',
            'compra_id' => 'Compra ID',
            'cantidad' => 'Cantidad',
            'almacen_id' => 'Almacen ID',
            'costo_unitario' => 'Costo Unitario',
        ];
    }

    /**
     * Gets query for [[Almacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacen()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_id']);
    }

    /**
     * Gets query for [[Compra]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompra()
    {
        return $this->hasOne(Compra::class, ['id' => 'compra_id']);
    }

    /**
     * Gets query for [[Presentacion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPresentacion()
    {
        return $this->hasOne(Presentacion::class, ['id' => 'presentacion_id']);
    }
}
