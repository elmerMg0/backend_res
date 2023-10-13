<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "unidad_medida".
 *
 * @property int $id
 * @property string $nombre
 * @property string $abreviatura
 *
 * @property Gasto[] $gastos
 */
class UnidadMedida extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'unidad_medida';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'abreviatura'], 'required'],
            [['nombre'], 'string', 'max' => 15],
            [['abreviatura'], 'string', 'max' => 5],
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
            'abreviatura' => 'Abreviatura',
        ];
    }

    /**
     * Gets query for [[Gastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGastos()
    {
        return $this->hasMany(Gasto::class, ['unidad_medida_id' => 'id']);
    }
}
