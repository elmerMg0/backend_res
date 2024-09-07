<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tipo_descuento".
 *
 * @property int $id
 * @property string $descripcion
 * @property float $descuento
 * @property bool $estado
 *
 * @property VentaDescuento[] $ventaDescuentos
 */
class TipoDescuento extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tipo_descuento';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'descuento', 'estado'], 'required'],
            [['descuento'], 'number'],
            [['estado'], 'boolean'],
            [['descripcion'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descripcion' => 'Descripcion',
            'descuento' => 'Descuento',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[VentaDescuentos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentaDescuentos()
    {
        return $this->hasMany(VentaDescuento::class, ['tipo_descuento_id' => 'id']);
    }
}
