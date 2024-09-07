<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "licencia".
 *
 * @property int $id
 * @property string $fecha_vencimiento
 * @property string $tipo_licencia
 * @property bool $estado
 * @property string $fecha_creacion
 * @property string $fecha_actualizacion
 * @property string $notas_adicionales
 */
class Licencia extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'licencia';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha_vencimiento', 'tipo_licencia', 'estado', 'fecha_creacion', 'fecha_actualizacion', 'notas_adicionales'], 'required'],
            [['fecha_vencimiento', 'fecha_creacion', 'fecha_actualizacion'], 'safe'],
            [['estado'], 'boolean'],
            [['notas_adicionales'], 'string'],
            [['tipo_licencia'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fecha_vencimiento' => 'Fecha Vencimiento',
            'tipo_licencia' => 'Tipo Licencia',
            'estado' => 'Estado',
            'fecha_creacion' => 'Fecha Creacion',
            'fecha_actualizacion' => 'Fecha Actualizacion',
            'notas_adicionales' => 'Notas Adicionales',
        ];
    }
}
