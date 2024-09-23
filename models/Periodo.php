<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "periodo".
 *
 * @property int $id
 * @property string $fecha_inicio
 * @property string|null $fecha_fin
 * @property float|null $caja_inicial
 * @property bool $estado
 * @property float|null $total_ventas
 * @property float|null $total_cierre_caja
 * @property int $usuario_id
 *
 * @property Usuario $usuario
 */
class Periodo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'periodo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha_inicio', 'fecha_fin'], 'safe'],
            [['caja_inicial', 'total_ventas', 'total_cierre_caja'], 'number'],
            [['estado', 'usuario_id'], 'required'],
            [['estado'], 'boolean'],
            [['usuario_id'], 'default', 'value' => null],
            [['usuario_id'], 'integer'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'caja_inicial' => 'Caja Inicial',
            'estado' => 'Estado',
            'total_ventas' => 'Total Ventas',
            'total_cierre_caja' => 'Total Cierre Caja',
            'usuario_id' => 'Usuario ID',
        ];
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
