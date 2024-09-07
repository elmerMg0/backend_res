<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "movimiento_almacen_insumo".
 *
 * @property int $id
 * @property int $insumo_id
 * @property float $cantidad
 * @property float $costo_unitario
 * @property int $movimiento_almacen_id
 *
 * @property Insumo $insumo
 * @property MovimientoAlmacen $movimientoAlmacen
 */
class MovimientoAlmacenInsumo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'movimiento_almacen_insumo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['insumo_id', 'cantidad', 'costo_unitario', 'movimiento_almacen_id'], 'required'],
            [['insumo_id', 'movimiento_almacen_id'], 'default', 'value' => null],
            [['insumo_id', 'movimiento_almacen_id'], 'integer'],
            [['cantidad', 'costo_unitario'], 'number'],
            [['insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Insumo::class, 'targetAttribute' => ['insumo_id' => 'id']],
            [['movimiento_almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => MovimientoAlmacen::class, 'targetAttribute' => ['movimiento_almacen_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'insumo_id' => 'Insumo ID',
            'cantidad' => 'Cantidad',
            'costo_unitario' => 'Costo Unitario',
            'movimiento_almacen_id' => 'Movimiento Almacen ID',
        ];
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
     * Gets query for [[MovimientoAlmacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacen()
    {
        return $this->hasOne(MovimientoAlmacen::class, ['id' => 'movimiento_almacen_id']);
    }
}
