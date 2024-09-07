<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "paquete".
 *
 * @property int $id
 * @property int $producto_id
 * @property float $precio_venta
 * @property float $costo_compra
 * @property int $cantidad
 * @property int $producto_parent_id
 *
 * @property Producto $producto
 * @property Producto $productoParent
 */
class Paquete extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paquete';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['producto_id', 'precio_venta', 'costo_compra', 'cantidad', 'producto_parent_id'], 'required'],
            [['producto_id', 'cantidad', 'producto_parent_id'], 'default', 'value' => null],
            [['producto_id', 'cantidad', 'producto_parent_id'], 'integer'],
            [['precio_venta', 'costo_compra'], 'number'],
            [['producto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_id' => 'id']],
            [['producto_parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'producto_id' => 'Producto ID',
            'precio_venta' => 'Precio Venta',
            'costo_compra' => 'Costo Compra',
            'cantidad' => 'Cantidad',
            'producto_parent_id' => 'Producto Parent ID',
        ];
    }

    /**
     * Gets query for [[Producto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducto()
    {
        return $this->hasOne(Producto::class, ['id' => 'producto_id']);
    }

    /**
     * Gets query for [[ProductoParent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductoParent()
    {
        return $this->hasOne(Producto::class, ['id' => 'producto_parent_id']);
    }
}
