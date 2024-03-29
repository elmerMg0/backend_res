<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detalle_arqueo_inventario".
 *
 * @property int $id
 * @property int $stock
 * @property int $declaracion
 * @property string|null $nota
 * @property int $producto_id
 * @property int $arqueo_inventario_id
 *
 * @property ArqueoInventario $arqueoInventario
 * @property Producto $producto
 */
class DetalleArqueoInventario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detalle_arqueo_inventario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['stock', 'declaracion', 'producto_id', 'arqueo_inventario_id'], 'required'],
            [['stock', 'declaracion', 'producto_id', 'arqueo_inventario_id'], 'default', 'value' => null],
            [['stock', 'declaracion', 'producto_id', 'arqueo_inventario_id'], 'integer'],
            [['nota'], 'string', 'max' => 80],
            [['arqueo_inventario_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArqueoInventario::class, 'targetAttribute' => ['arqueo_inventario_id' => 'id']],
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
            'stock' => 'Stock',
            'declaracion' => 'Declaracion',
            'nota' => 'Nota',
            'producto_id' => 'Producto ID',
            'arqueo_inventario_id' => 'Arqueo Inventario ID',
        ];
    }

    /**
     * Gets query for [[ArqueoInventario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArqueoInventario()
    {
        return $this->hasOne(ArqueoInventario::class, ['id' => 'arqueo_inventario_id']);
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
