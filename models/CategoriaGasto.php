<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "categoria_gasto".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 *
 * @property Gasto[] $gastos
 */
class CategoriaGasto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'categoria_gasto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 25],
            [['descripcion'], 'string', 'max' => 80],
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
            'descripcion' => 'Descripcion',
        ];
    }

    /**
     * Gets query for [[Gastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGastos()
    {
        return $this->hasMany(Gasto::class, ['categoria_gasto_id' => 'id']);
    }
}
