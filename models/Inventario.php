<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "inventario".
 *
 * @property int $id
 * @property string $fecha
 * @property int $stock
 * @property int|null $nuevo_stock
 * @property int|null $total
 * @property int $producto_id
 * @property bool|null $last_one
 *
 * @property Producto $producto
 */
class Inventario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inventario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha', 'stock', 'producto_id'], 'required'],
            [['fecha'], 'safe'],
            [['stock', 'nuevo_stock', 'total', 'producto_id'], 'default', 'value' => null],
            [['stock', 'nuevo_stock', 'total', 'producto_id'], 'integer'],
            [['last_one'], 'boolean'],
            [['producto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fecha' => 'Fecha',
            'stock' => 'Stock',
            'nuevo_stock' => 'Nuevo Stock',
            'total' => 'Total',
            'producto_id' => 'Producto ID',
            'last_one' => 'Last One',
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
}
