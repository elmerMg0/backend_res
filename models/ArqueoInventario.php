<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "arqueo_inventario".
 *
 * @property int $id
 * @property string $fecha
 * @property int $usuario_id
 *
 * @property DetalleArqueoInventario[] $detalleArqueoInventarios
 * @property Usuario $usuario
 */
class ArqueoInventario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'arqueo_inventario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha', 'usuario_id'], 'required'],
            [['fecha'], 'safe'],
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
            'fecha' => 'Fecha',
            'usuario_id' => 'Usuario ID',
        ];
    }

    /**
     * Gets query for [[DetalleArqueoInventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleArqueoInventarios()
    {
        return $this->hasMany(DetalleArqueoInventario::class, ['arqueo_inventario_id' => 'id']);
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
