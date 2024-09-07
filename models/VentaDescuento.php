<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "venta_descuento".
 *
 * @property int $id
 * @property int $venta_id
 * @property string $tipo
 * @property float $valor
 * @property string|null $comentario
 * @property int $tipo_descuento_id
 *
 * @property TipoDescuento $tipoDescuento
 * @property Venta $venta
 */
class VentaDescuento extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'venta_descuento';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venta_id', 'tipo', 'valor'], 'required'],
            [['venta_id', 'tipo_descuento_id'], 'default', 'value' => null],
            [['venta_id', 'tipo_descuento_id'], 'integer'],
            [['valor'], 'number'],
            [['comentario'], 'string'],
            [['tipo'], 'string', 'max' => 15],
            [['tipo_descuento_id'], 'exist', 'skipOnError' => true, 'targetClass' => TipoDescuento::class, 'targetAttribute' => ['tipo_descuento_id' => 'id']],
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
            'tipo' => 'Tipo',
            'valor' => 'Valor',
            'comentario' => 'Comentario',
            'tipo_descuento_id' => 'Tipo Descuento ID',
        ];
    }

    /**
     * Gets query for [[TipoDescuento]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTipoDescuento()
    {
        return $this->hasOne(TipoDescuento::class, ['id' => 'tipo_descuento_id']);
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
