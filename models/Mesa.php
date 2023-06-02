<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mesa".
 *
 * @property int $id
 * @property string $estado
 * @property bool $habilitado
 * @property int $salon_id
 * @property string|null $nombre
 *
 * @property Salon $salon
 * @property Venta[] $ventas
 */
class Mesa extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mesa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['habilitado'], 'boolean'],
            [['salon_id'], 'required'],
            [['salon_id'], 'default', 'value' => null],
            [['salon_id'], 'integer'],
            [['estado'], 'string', 'max' => 25],
            [['nombre'], 'string', 'max' => 20],
            [['salon_id'], 'exist', 'skipOnError' => true, 'targetClass' => Salon::class, 'targetAttribute' => ['salon_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'estado' => 'Estado',
            'habilitado' => 'Habilitado',
            'salon_id' => 'Salon ID',
            'nombre' => 'Nombre',
        ];
    }

    /**
     * Gets query for [[Salon]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSalon()
    {
        return $this->hasOne(Salon::class, ['id' => 'salon_id']);
    }

    /**
     * Gets query for [[Ventas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentas()
    {
        return $this->hasMany(Venta::class, ['mesa_id' => 'id']);
    }
}
