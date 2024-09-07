<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "grupo_modificadores_detalle".
 *
 * @property int $id
 * @property float $precio_venta
 * @property int $producto_id
 * @property int $grupo_modificadores_id
 * @property float|null $costo_compra
 *
 * @property GrupoModificadores $grupoModificadores
 * @property Producto $producto
 */
class GrupoModificadoresDetalle extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grupo_modificadores_detalle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['precio_venta', 'producto_id', 'grupo_modificadores_id'], 'required'],
            [['precio_venta', 'costo_compra'], 'number'],
            [['producto_id', 'grupo_modificadores_id'], 'default', 'value' => null],
            [['producto_id', 'grupo_modificadores_id'], 'integer'],
            [['grupo_modificadores_id'], 'exist', 'skipOnError' => true, 'targetClass' => GrupoModificadores::class, 'targetAttribute' => ['grupo_modificadores_id' => 'id']],
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
            'precio_venta' => 'Precio Venta',
            'producto_id' => 'Producto ID',
            'grupo_modificadores_id' => 'Grupo Modificadores ID',
            'costo_compra' => 'Costo Compra',
        ];
    }

    /**
     * Gets query for [[GrupoModificadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoModificadores()
    {
        return $this->hasOne(GrupoModificadores::class, ['id' => 'grupo_modificadores_id']);
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
