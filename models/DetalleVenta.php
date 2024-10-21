<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detalle_venta".
 *
 * @property int $id
 * @property int $cantidad
 * @property int $producto_id
 * @property int $venta_id
 * @property string $estado
 * @property bool|null $impreso
 * @property string|null $create_ts
 * @property float|null $precio_venta
 * @property float|null $costo_compra
 * @property string|null $nota
 * @property int|null $detalle_venta_id
 *
 * @property DetalleVenta $detalleVenta
 * @property DetalleVenta[] $detalleVentas
 * @property Producto $producto
 * @property Venta $venta
 */
class DetalleVenta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detalle_venta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cantidad', 'producto_id', 'estado'], 'required'],
            [['cantidad', 'producto_id', 'detalle_venta_id'], 'default', 'value' => null],
            [['cantidad', 'producto_id', 'detalle_venta_id'], 'integer'],
            [['impreso'], 'boolean'],
            [['create_ts'], 'safe'],
            [['precio_venta', 'costo_compra'], 'number'],
            [['nota'], 'string'],
            [['estado'], 'string', 'max' => 20],
            [['detalle_venta_id'], 'exist', 'skipOnError' => true, 'targetClass' => DetalleVenta::class, 'targetAttribute' => ['detalle_venta_id' => 'id']],
            [['producto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_id' => 'id']],
            [['venta_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venta::class, 'targetAttribute' => ['venta_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cantidad' => 'Cantidad',
            'producto_id' => 'Producto ID',
            'venta_id' => 'Venta ID',
            'estado' => 'Estado',
            'impreso' => 'Impreso',
            'create_ts' => 'Create Ts',
            'precio_venta' => 'Precio Venta',
            'costo_compra' => 'Costo Compra',
            'nota' => 'Nota',
            'detalle_venta_id' => 'Detalle Venta ID',
        ];
    }

    /**
     * Gets query for [[DetalleVenta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleVenta()
    {
        return $this->hasOne(DetalleVenta::class, ['id' => 'detalle_venta_id']);
    }

    /**
     * Gets query for [[DetalleVentas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, ['detalle_venta_id' => 'id']);
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
     * Gets query for [[Venta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVenta()
    {
        return $this->hasOne(Venta::class, ['id' => 'venta_id']);
    }
}
