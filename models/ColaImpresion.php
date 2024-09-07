<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cola_impresion".
 *
 * @property int $id
 * @property int $venta_id
 * @property int $area_impresion_id
 * @property bool $estado
 *
 * @property AreaImpresion $areaImpresion
 * @property Venta $venta
 */
class ColaImpresion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cola_impresion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venta_id', 'area_impresion_id'], 'required'],
            [['venta_id', 'area_impresion_id'], 'default', 'value' => null],
            [['venta_id', 'area_impresion_id'], 'integer'],
            [['estado'], 'boolean'],
            [['area_impresion_id'], 'exist', 'skipOnError' => true, 'targetClass' => AreaImpresion::class, 'targetAttribute' => ['area_impresion_id' => 'id']],
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
            'venta_id' => 'Venta ID',
            'area_impresion_id' => 'Area Impresion ID',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[AreaImpresion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAreaImpresion()
    {
        return $this->hasOne(AreaImpresion::class, ['id' => 'area_impresion_id']);
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
