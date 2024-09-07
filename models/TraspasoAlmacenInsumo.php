<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "traspaso_almacen_insumo".
 *
 * @property int $id
 * @property int $traspaso_almacen_id
 * @property int $insumo_id
 * @property float $cantidad
 * @property float $costo_unitario
 * @property float $existencia
 *
 * @property Insumo $insumo
 * @property TraspasoAlmacen $traspasoAlmacen
 */
class TraspasoAlmacenInsumo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'traspaso_almacen_insumo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['traspaso_almacen_id', 'insumo_id', 'cantidad', 'costo_unitario', 'existencia'], 'required'],
            [['traspaso_almacen_id', 'insumo_id'], 'default', 'value' => null],
            [['traspaso_almacen_id', 'insumo_id'], 'integer'],
            [['cantidad', 'costo_unitario', 'existencia'], 'number'],
            [['insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Insumo::class, 'targetAttribute' => ['insumo_id' => 'id']],
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
            'insumo_id' => 'Insumo ID',
            'cantidad' => 'Cantidad',
            'costo_unitario' => 'Costo Unitario',
            'existencia' => 'Existencia',
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
     * Gets query for [[TraspasoAlmacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacen()
    {
        return $this->hasOne(TraspasoAlmacen::class, ['id' => 'traspaso_almacen_id']);
    }
}
