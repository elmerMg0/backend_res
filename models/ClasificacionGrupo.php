<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clasificacion_grupo".
 *
 * @property int $id
 * @property string $descripcion
 * @property bool $estado
 *
 * @property GrupoInsumo[] $grupoInsumos
 */
class ClasificacionGrupo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'clasificacion_grupo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'estado'], 'required'],
            [['estado'], 'boolean'],
            [['descripcion'], 'string', 'max' => 25],
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
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[GrupoInsumos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoInsumos()
    {
        return $this->hasMany(GrupoInsumo::class, ['clasificacion_grupo_id' => 'id']);
    }
}
