<?php

/**
 * LibreDTE: Aplicación Web - Edición Comunidad.
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace website\Sistema\General;

use sowerphp\autoload\Model;

/**
 * Modelo singular de la tabla "banco" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_Banco extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'verbose_name' => 'Entidad bancaria',
            'verbose_name_plural' => 'Entidades bancarias',
            'db_table_comment' => 'Entidades bancarias de Chile con su respectivo código de la CMF (ex SBIF).',
            'ordering' => ['banco'],
        ],
        'fields' => [
            'codigo' => [
                'type' => self::TYPE_CHAR,
                'primary_key' => true,
                'length' => 3,
                'verbose_name' => 'Código',
                'help_text' => 'Código asignado al banco por la CMF (ex SBIF).',
            ],
            'banco' => [
                'type' => self::TYPE_STRING,
                'max_length' => 40,
                'verbose_name' => 'Banco',
                'help_text' => 'Nombre del banco.',
            ],
        ],
        'form' => [
            'layout' => [
                [
                    'rows' => [
                        [
                            'codigo', 'banco',
                        ],
                    ],
                ]
            ],
        ]
    ];

}
