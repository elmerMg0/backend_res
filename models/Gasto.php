<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "gasto".
 *
 * @property int $id
 * @property string $nombre
 * @property int $unidad_medida_id
 * @property string $create_ts
 * @property string $update_ts
 *
 * @property RegistroGasto[] $registroGastos
 * @property UnidadMedida $unidadMedida
 */
class Gasto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gasto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'unidad_medida_id'], 'required'],
            [['unidad_medida_id'], 'default', 'value' => null],
            [['unidad_medida_id'], 'integer'],
            [['create_ts', 'update_ts'], 'safe'],
            [['nombre'], 'string', 'max' => 50],
            [['unidad_medida_id'], 'exist', 'skipOnError' => true, 'targetClass' => UnidadMedida::class, 'targetAttribute' => ['unidad_medida_id' => 'id']],
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
            'unidad_medida_id' => 'Unidad Medida ID',
            'create_ts' => 'Create Ts',
            'update_ts' => 'Update Ts',
        ];
    }

    /**
     * Gets query for [[RegistroGastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegistroGastos()
    {
        return $this->hasMany(RegistroGasto::class, ['gasto_id' => 'id']);
    }

    /**
     * Gets query for [[UnidadMedida]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnidadMedida()
    {
        return $this->hasOne(UnidadMedida::class, ['id' => 'unidad_medida_id']);
    }
}
