<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "usuario".
 *
 * @property int $id
 * @property string $username
 * @property string|null $password_hash
 * @property string|null $access_token
 * @property string $nombres
 * @property string|null $url_image
 * @property string $tipo
 * @property bool $estado
 *
 * @property ArqueoInventario[] $arqueoInventarios
 * @property Compra[] $compras
 * @property MovimientoAlmacen[] $movimientoAlmacens
 * @property Periodo[] $periodos
 * @property RegistroGasto[] $registroGastos
 * @property TraspasoAlmacen[] $traspasoAlmacens
 * @property Venta[] $ventas
 */
class Usuario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'usuario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'nombres', 'tipo', 'estado'], 'required'],
            [['password_hash', 'access_token'], 'string'],
            [['estado'], 'boolean'],
            [['username', 'url_image', 'tipo'], 'string', 'max' => 50],
            [['nombres'], 'string', 'max' => 80],
            [['username'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password_hash' => 'Password Hash',
            'access_token' => 'Access Token',
            'nombres' => 'Nombres',
            'url_image' => 'Url Image',
            'tipo' => 'Tipo',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[ArqueoInventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArqueoInventarios()
    {
        return $this->hasMany(ArqueoInventario::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[Compras]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompras()
    {
        return $this->hasMany(Compra::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[MovimientoAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacens()
    {
        return $this->hasMany(MovimientoAlmacen::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[Periodos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodos()
    {
        return $this->hasMany(Periodo::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[RegistroGastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegistroGastos()
    {
        return $this->hasMany(RegistroGasto::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[TraspasoAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacens()
    {
        return $this->hasMany(TraspasoAlmacen::class, ['usuario_id' => 'id']);
    }

    /**
     * Gets query for [[Ventas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentas()
    {
        return $this->hasMany(Venta::class, ['usuario_id' => 'id']);
    }
}
