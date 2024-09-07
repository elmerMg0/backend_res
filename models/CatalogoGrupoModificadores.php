<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "catalogo_grupo_modificadores".
 *
 * @property int $id
 * @property string $descripcion
 *
 * @property GrupoModificadores[] $grupoModificadores
 */
class CatalogoGrupoModificadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogo_grupo_modificadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion'], 'required'],
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
        ];
    }

    /**
     * Gets query for [[GrupoModificadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoModificadores()
    {
        return $this->hasMany(GrupoModificadores::class, ['catalogo_grupo_modificadores_id' => 'id']);
    }
}
