<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "configuracion_impresora".
 *
 * @property int $id
 * @property bool|null $nombre_empresa
 * @property bool|null $telefono
 * @property bool|null $usuario
 * @property bool|null $cliente
 * @property bool|null $sub_total
 * @property bool|null $descuento
 * @property bool|null $comentario
 * @property string|null $comentario_text
 * @property bool|null $nota_caja
 */
class ConfiguracionImpresora extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'configuracion_impresora';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre_empresa', 'telefono', 'usuario', 'cliente', 'sub_total', 'descuento', 'comentario', 'nota_caja'], 'boolean'],
            [['comentario_text'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre_empresa' => 'Nombre Empresa',
            'telefono' => 'Telefono',
            'usuario' => 'Usuario',
            'cliente' => 'Cliente',
            'sub_total' => 'Sub Total',
            'descuento' => 'Descuento',
            'comentario' => 'Comentario',
            'comentario_text' => 'Comentario Text',
            'nota_caja' => 'Nota Caja',
        ];
    }
}
