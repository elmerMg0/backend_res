<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "arqueo_inventario".
 *
 * @property int $id
 * @property string $fecha
 * @property int $usuario_id
 * @property int $almacen_id
 * @property float $inventario_teorico
 * @property float $inventario_fisico
 *
 * @property DetalleArqueoInventario[] $detalleArqueoInventarios
 * @property DetallePresArqueoInventario[] $detallePresArqueoInventarios
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
            [['fecha', 'usuario_id', 'almacen_id', 'inventario_teorico', 'inventario_fisico'], 'required'],
            [['fecha'], 'safe'],
            [['usuario_id', 'almacen_id'], 'default', 'value' => null],
            [['usuario_id', 'almacen_id'], 'integer'],
            [['inventario_teorico', 'inventario_fisico'], 'number'],
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
            'almacen_id' => 'Almacen ID',
            'inventario_teorico' => 'Inventario Teorico',
            'inventario_fisico' => 'Inventario Fisico',
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
     * Gets query for [[DetallePresArqueoInventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetallePresArqueoInventarios()
    {
        return $this->hasMany(DetallePresArqueoInventario::class, ['arqueo_inventario_id' => 'id']);
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
