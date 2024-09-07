<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "proveedor".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $razon_social
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $email
 * @property int|null $credito
 * @property bool $estado
 *
 * @property Gasto[] $gastos
 * @property Presentacion[] $presentacions
 */
class Proveedor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proveedor';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['credito'], 'default', 'value' => null],
            [['credito'], 'integer'],
            [['estado'], 'boolean'],
            [['nombre'], 'string', 'max' => 50],
            [['razon_social'], 'string', 'max' => 25],
            [['direccion', 'email'], 'string', 'max' => 80],
            [['telefono'], 'string', 'max' => 15],
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
            'razon_social' => 'Razon Social',
            'direccion' => 'Direccion',
            'telefono' => 'Telefono',
            'email' => 'Email',
            'credito' => 'Credito',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[Gastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGastos()
    {
        return $this->hasMany(Gasto::class, ['proveedor_id' => 'id']);
    }

    /**
     * Gets query for [[Presentacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPresentacions()
    {
        return $this->hasMany(Presentacion::class, ['proveedor_id' => 'id']);
    }
}
