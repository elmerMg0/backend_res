<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "area_impresion".
 *
 * @property int $id
 * @property string $nombre
 *
 * @property AsignacionImpresora[] $asignacionImpresoras
 * @property ColaImpresion[] $colaImpresions
 * @property Producto[] $productos
 * @property VentaAreaImpresion[] $ventaAreaImpresions
 */
class AreaImpresion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'area_impresion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
        ];
    }

    /**
     * Gets query for [[AsignacionImpresoras]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAsignacionImpresoras()
    {
        return $this->hasMany(AsignacionImpresora::class, ['area_impresion_id' => 'id']);
    }

    /**
     * Gets query for [[ColaImpresions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getColaImpresions()
    {
        return $this->hasMany(ColaImpresion::class, ['area_impresion_id' => 'id']);
    }

    /**
     * Gets query for [[Productos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::class, ['area_impresion_id' => 'id']);
    }

    /**
     * Gets query for [[VentaAreaImpresions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentaAreaImpresions()
    {
        return $this->hasMany(VentaAreaImpresion::class, ['area_impresion_id' => 'id']);
    }
}
