<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "gasto".
 *
 * @property int $id
 * @property string $nombre
 * @property int|null $unidad_medida_id
 * @property string $create_ts
 * @property int $categoria_gasto_id
 *
 * @property CategoriaGasto $categoriaGasto
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
            [['nombre', 'categoria_gasto_id'], 'required'],
            [['unidad_medida_id', 'categoria_gasto_id'], 'default', 'value' => null],
            [['unidad_medida_id', 'categoria_gasto_id'], 'integer'],
            [['create_ts'], 'safe'],
            [['nombre'], 'string', 'max' => 50],
            [['categoria_gasto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CategoriaGasto::class, 'targetAttribute' => ['categoria_gasto_id' => 'id']],
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
            'categoria_gasto_id' => 'Categoria Gasto ID',
        ];
    }

    /**
     * Gets query for [[CategoriaGasto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategoriaGasto()
    {
        return $this->hasOne(CategoriaGasto::class, ['id' => 'categoria_gasto_id']);
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
