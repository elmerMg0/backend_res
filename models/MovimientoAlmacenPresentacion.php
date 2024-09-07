<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "movimiento_almacen_presentacion".
 *
 * @property int $id
 * @property int $presentacion_id
 * @property float $cantidad
 * @property float $costo_unitario
 * @property int $movimiento_almacen_id
 *
 * @property MovimientoAlmacen $movimientoAlmacen
 */
class MovimientoAlmacenPresentacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'movimiento_almacen_presentacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['presentacion_id', 'cantidad', 'costo_unitario', 'movimiento_almacen_id'], 'required'],
            [['presentacion_id', 'movimiento_almacen_id'], 'default', 'value' => null],
            [['presentacion_id', 'movimiento_almacen_id'], 'integer'],
            [['cantidad', 'costo_unitario'], 'number'],
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
            'presentacion_id' => 'Presentacion ID',
            'cantidad' => 'Cantidad',
            'costo_unitario' => 'Costo Unitario',
            'movimiento_almacen_id' => 'Movimiento Almacen ID',
        ];
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
