<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "traspaso_almacen_presentacion".
 *
 * @property int $id
 * @property int $traspaso_almacen_id
 * @property int $presentacion_id
 * @property float $cantidad
 * @property float $costo_unitario
 * @property float $existencia
 *
 * @property Presentacion $presentacion
 * @property TraspasoAlmacen $traspasoAlmacen
 */
class TraspasoAlmacenPresentacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'traspaso_almacen_presentacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['traspaso_almacen_id', 'presentacion_id', 'cantidad', 'costo_unitario', 'existencia'], 'required'],
            [['traspaso_almacen_id', 'presentacion_id'], 'default', 'value' => null],
            [['traspaso_almacen_id', 'presentacion_id'], 'integer'],
            [['cantidad', 'costo_unitario', 'existencia'], 'number'],
            [['presentacion_id'], 'exist', 'skipOnError' => true, 'targetClass' => Presentacion::class, 'targetAttribute' => ['presentacion_id' => 'id']],
            [['traspaso_almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => TraspasoAlmacen::class, 'targetAttribute' => ['traspaso_almacen_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'traspaso_almacen_id' => 'Traspaso Almacen ID',
            'presentacion_id' => 'Presentacion ID',
            'cantidad' => 'Cantidad',
            'costo_unitario' => 'Costo Unitario',
            'existencia' => 'Existencia',
        ];
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

    /**
     * Gets query for [[TraspasoAlmacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacen()
    {
        return $this->hasOne(TraspasoAlmacen::class, ['id' => 'traspaso_almacen_id']);
    }
}
