<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "receta".
 *
 * @property int $id
 * @property float $cantidad
 * @property int $insumo_id
 * @property int $producto_id
 *
 * @property AsignacionAreaAlmacen[] $asignacionAreaAlmacens
 * @property Insumo $insumo
 * @property Producto $producto
 */
class Receta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'receta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cantidad', 'insumo_id', 'producto_id'], 'required'],
            [['cantidad'], 'number'],
            [['insumo_id', 'producto_id'], 'default', 'value' => null],
            [['insumo_id', 'producto_id'], 'integer'],
            [['insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Insumo::class, 'targetAttribute' => ['insumo_id' => 'id']],
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
            'cantidad' => 'Cantidad',
            'insumo_id' => 'Insumo ID',
            'producto_id' => 'Producto ID',
        ];
    }

    /**
     * Gets query for [[AsignacionAreaAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAsignacionAreaAlmacens()
    {
        return $this->hasMany(AsignacionAreaAlmacen::class, ['receta_id' => 'id']);
    }

    /**
     * Gets query for [[Insumo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInsumo()
    {
        return $this->hasOne(Insumo::class, ['id' => 'insumo_id']);
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
