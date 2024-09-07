<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "traspaso_almacen".
 *
 * @property int $id
 * @property string $fecha
 * @property int $almacen_origen_id
 * @property int $almacen_destino_id
 * @property bool $estado
 * @property int|null $empresa_id
 * @property string|null $nota
 * @property int $usuario_id
 *
 * @property Almacen $almacenDestino
 * @property Almacen $almacenOrigen
 * @property TraspasoAlmacenInsumo[] $traspasoAlmacenInsumos
 * @property TraspasoAlmacenPresentacion[] $traspasoAlmacenPresentacions
 * @property Usuario $usuario
 */
class TraspasoAlmacen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'traspaso_almacen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha'], 'safe'],
            [['almacen_origen_id', 'almacen_destino_id', 'usuario_id'], 'required'],
            [['almacen_origen_id', 'almacen_destino_id', 'empresa_id', 'usuario_id'], 'default', 'value' => null],
            [['almacen_origen_id', 'almacen_destino_id', 'empresa_id', 'usuario_id'], 'integer'],
            [['estado'], 'boolean'],
            [['nota'], 'string', 'max' => 80],
            [['almacen_origen_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_origen_id' => 'id']],
            [['almacen_destino_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_destino_id' => 'id']],
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
            'almacen_origen_id' => 'Almacen Origen ID',
            'almacen_destino_id' => 'Almacen Destino ID',
            'estado' => 'Estado',
            'empresa_id' => 'Empresa ID',
            'nota' => 'Nota',
            'usuario_id' => 'Usuario ID',
        ];
    }

    /**
     * Gets query for [[AlmacenDestino]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacenDestino()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_destino_id']);
    }

    /**
     * Gets query for [[AlmacenOrigen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacenOrigen()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_origen_id']);
    }

    /**
     * Gets query for [[TraspasoAlmacenInsumos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacenInsumos()
    {
        return $this->hasMany(TraspasoAlmacenInsumo::class, ['traspaso_almacen_id' => 'id']);
    }

    /**
     * Gets query for [[TraspasoAlmacenPresentacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacenPresentacions()
    {
        return $this->hasMany(TraspasoAlmacenPresentacion::class, ['traspaso_almacen_id' => 'id']);
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
