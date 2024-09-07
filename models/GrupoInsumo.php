<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "grupo_insumo".
 *
 * @property int $id
 * @property string $descripcion
 * @property int $clasificacion_grupo_id
 * @property bool $estado
 *
 * @property ClasificacionGrupo $clasificacionGrupo
 */
class GrupoInsumo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grupo_insumo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'clasificacion_grupo_id', 'estado'], 'required'],
            [['clasificacion_grupo_id'], 'default', 'value' => null],
            [['clasificacion_grupo_id'], 'integer'],
            [['estado'], 'boolean'],
            [['descripcion'], 'string', 'max' => 50],
            [['clasificacion_grupo_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClasificacionGrupo::class, 'targetAttribute' => ['clasificacion_grupo_id' => 'id']],
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
            'clasificacion_grupo_id' => 'Clasificacion Grupo ID',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[ClasificacionGrupo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClasificacionGrupo()
    {
        return $this->hasOne(ClasificacionGrupo::class, ['id' => 'clasificacion_grupo_id']);
    }
}
