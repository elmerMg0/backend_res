<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "log_venta".
 *
 * @property int $id
 * @property int $venta_id
 * @property int $producto_id
 * @property int $cantidad
 * @property string $nombre_producto
 * @property int $numero_pedido
 */
class LogVenta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log_venta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venta_id', 'producto_id', 'cantidad', 'nombre_producto', 'numero_pedido'], 'required'],
            [['venta_id', 'producto_id', 'cantidad', 'numero_pedido'], 'default', 'value' => null],
            [['venta_id', 'producto_id', 'cantidad', 'numero_pedido'], 'integer'],
            [['nombre_producto'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'venta_id' => 'Venta ID',
            'producto_id' => 'Producto ID',
            'cantidad' => 'Cantidad',
            'nombre_producto' => 'Nombre Producto',
            'numero_pedido' => 'Numero Pedido',
        ];
    }
}
