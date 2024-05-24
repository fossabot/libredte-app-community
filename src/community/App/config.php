<?php

/**
 * LibreDTE: Aplicación Web - Edición Comunidad.
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

return [

    // Configuración depuración
    'debug' => env('LIBREDTE_APP_DEBUG', true),
    'error.level' => env('LIBREDTE_APP_DEBUG', true) ? E_ALL : null,

    // Tiempo máximo de ejecución del script PHP
    'max_execution_time' => (int)env('LIBREDTE_APP_EXECUTION_TIMEOUT', 600),

    // Tiempo de duración de la sesión en minutos
    'session.expires' => (int)env('LIBREDTE_APP_SESSION_EXPIRES', 600),

    // Delimitador en archivos CSV
    'spreadsheet.csv.delimiter' => env('LIBREDTE_APP_SPREADSHEET_CSV_DELIMITER', ';'),

    // Tema de la página (diseño)
    'page.layout' => env('LIBREDTE_APP_PAGE_LAYOUT', 'LibreDTE'),

    // Textos de la página
    'page.header.title' => env('LIBREDTE_APP_PAGE_HEADER_TITLE', 'LibreDTE'),
    'page.body.title' => env('LIBREDTE_APP_PAGE_BODY_TITLE', 'LibreDTE'),

    // Menú principal del sitio web
    'nav.website' => [
        '/dte' => [
            'name' => 'Módulo de Facturación',
            'desc' => 'Accede al módulo de facturación electrónica',
            'icon' => 'fa fa-file-invoice',
        ],
    ],

    // Menú principal de la aplicación web
    'nav.app' => [
        'dte' => [
            'link' => '/dte',
            'name' => 'Facturación',
            'icon' => 'fa fa-file-invoice',
            'menu' => [
                '/dashboard' => [
                    'name' => 'Dashboard DTE',
                    'icon' => 'fas fa-tachometer-alt',
                ],
                '/documentos/emitir' => [
                    'name' => 'Emitir documento',
                    'desc' => 'Emitir documento tributario electrónico (DTE)',
                    'icon' => 'fas fa-file-alt',
                ],
                '/dte_tmps/listar' => [
                    'name' => 'Documentos temporales',
                    'desc' => 'Revisar documentos temporales (borradores o cotizaciones)',
                    'icon' => 'far fa-file',
                ],
                '/dte_emitidos/listar' => [
                    'name' => 'Documentos emitidos',
                    'desc' => 'Revisar documentos emitidos',
                    'icon' => 'fas fa-sign-out-alt',
                ],
                '/dte_recibidos/listar' => [
                    'name' => 'Documentos recibidos',
                    'desc' => 'Revisar documentos recibidos',
                    'icon' => 'fas fa-sign-in-alt',
                ],
                '/dte_intercambios/listar' => [
                    'name' => 'Bandeja de intercambio',
                    'desc' => 'Menú de intercambio de DTE entre contribuyentes',
                    'icon' => 'fas fa-exchange-alt',
                ],
                '/registro_compras/pendientes' => [
                    'name' => 'Recibidos pendientes',
                    'desc' => 'Ver listado de documentos recibidos pendientes de procesar en SII',
                    'icon' => 'fas fa-paperclip',
                ],
                '/dte_ventas' => [
                    'name' => 'Libro de ventas',
                    'desc' => 'Acceder al Libro de Ventas',
                    'icon' => 'fa fa-book',
                ],
                '/dte_compras' => [
                    'name' => 'Libro de compras',
                    'desc' => 'Acceder al Libro de Compras',
                    'icon' => 'fa fa-book',
                ],
                '/dte_guias' => [
                    'name' => 'Libro de guías',
                    'desc' => 'Acceder al Libro de Guías de despacho',
                    'icon' => 'fa fa-book',
                ],
                '/dte_boletas' => [
                    'name' => 'Libro de boletas',
                    'desc' => 'Acceder al Libro de Boletas',
                    'icon' => 'fa fa-book',
                ],
                '/dte_boleta_consumos/listar/1/dia/D' => [
                    'name' => 'Consumo de folios',
                    'desc' => 'Resumen de Ventas Diarias (RDV) o Ex Reporte de Consumo de Folios (RCOF)',
                    'icon' => 'fa fa-archive',
                ],
                '/cesiones/listar' => [
                    'name' => 'Cesiones',
                    'desc' => 'Cesiones de documentos tributarios electrónicos',
                    'icon' => 'fas fa-external-link-square-alt',
                ],
                '/cobranzas/buscar' => [
                    'name' => 'Pagos programados',
                    'desc' => 'Buscar pagos programados ventas a crédito',
                    'icon' => 'fas fa-calendar-alt',
                ],
                '/informes' => [
                    'name' => 'Informes',
                    'desc' => 'Informes y reportes de la operación mensual',
                    'icon' => 'fa fa-file',
                ],
                '/admin' => [
                    'name' => 'Administración',
                    'desc' => 'Administración del módulo DTE',
                    'icon' => 'fa fa-cogs',
                ],
            ]
        ],
        'honorarios' => [
            'link' => '/honorarios',
            'name' => 'Honorarios',
            'icon' => 'fas fa-user-friends',
        ],
        'utilidades' => [
            'link' => '/utilidades',
            'name' => 'Utilidades',
            'icon' => 'fa fa-cog',
        ],
        'certificacion' => [
            'link' => '/certificacion',
            'name' => 'Certificación DTE',
            'icon' => 'fa fa-certificate',
        ],
        'seleccionar_empresa' => [
            'link' => '/dte/contribuyentes/seleccionar',
            'name' => 'Seleccionar empresa',
            'icon' => 'fa fa-mouse-pointer',
        ],
    ],

    // Menú por defecto de la empresa si no tiene definido uno personalizado
    'nav.contribuyente' => [
        (object)['enlace' => '/dte/documentos/emitir', 'icono' => 'fas fa-file-invoice', 'nombre' => 'Emitir documento'],
        (object)['enlace' => '/dte/dte_tmps/listar', 'icono' => 'far fa-file', 'nombre' => 'Documentos temporales'],
        (object)['enlace' => '/dte/dte_emitidos/listar', 'icono' => 'fas fa-sign-out-alt', 'nombre' => 'Documentos emitidos'],
        (object)['enlace' => '/dte/dte_recibidos/listar', 'icono' => 'fas fa-sign-in-alt', 'nombre' => 'Documentos recibidos'],
        (object)['enlace' => '/dte/dte_intercambios/listar', 'icono' => 'fas fa-exchange-alt', 'nombre' => 'Bandeja de intercambio'],
        (object)['enlace' => '/dte/informes', 'icono' => 'fa fa-file', 'nombre' => 'Informes de facturación'],
    ],

    // Configuración para la base de datos
    'database.default' => array(
        'type' => 'PostgreSQL', // solo se soporta la base de datos PostgreSQL
        'host' => env('LIBREDTE_APP_DATABASE_DEFAULT_HOST', 'localhost'),
        'port' => (int)env('LIBREDTE_APP_DATABASE_DEFAULT_PORT', 5432),
        'user' => env('LIBREDTE_APP_DATABASE_DEFAULT_USER', 'libredte'),
        'pass' => env('LIBREDTE_APP_DATABASE_DEFAULT_PASS', ''),
        'name' => env('LIBREDTE_APP_DATABASE_DEFAULT_NAME', 'libredte'),
        'pers' => (bool)env('LIBREDTE_APP_DATABASE_DEFAULT_PERS', false),
    ),

    // Configuración para el correo electrónico
    'email.default' => array(
        'type' => 'smtp-phpmailer',
        'host' => env('LIBREDTE_APP_EMAIL_DEFAULT_HOST', 'ssl://smtp.gmail.com'),
        'port' => (int)env('LIBREDTE_APP_EMAIL_DEFAULT_PORT', 465),
        'user' => env('LIBREDTE_APP_EMAIL_DEFAULT_USER', ''),
        'pass' => env('LIBREDTE_APP_EMAIL_DEFAULT_PASS', ''),
        'from' => array(
            'email' => env('LIBREDTE_APP_EMAIL_DEFAULT_FROM_EMAIL', ''),
            'name' => env('LIBREDTE_APP_EMAIL_DEFAULT_FROM_NAME', 'LibreDTE')
        ),
        'to' => env('LIBREDTE_APP_EMAIL_DEFAULT_TO', ''),
    ),

    // Módulos que utiliza la aplicación
    'modules' => [
        'Dev',
        'Dte',
        'Dte.Admin',
        'Dte.Admin.Mantenedores',
        'Dte.Informes',
        'Dte.Pdf',
        'Honorarios',
        'Sistema.General',
        'Sistema.General.DivisionGeopolitica',
        'Utilidades',
    ],

    // configuración de permisos de la empresa en la aplicación
    'empresa.permisos' => [
        'admin' => [
            'nombre' => 'Administrador',
            'descripcion' => 'Incluye editar empresa y otros usuarios, respaldos, descargar CAF, corregir Track ID',
            'grupos' => ['dte_plus'],
        ],
        'dte' => [
            'nombre' => 'Módulo facturación electrónica',
            'descripcion' => 'Emisión de DTE, recepción, informes y libros de compra/venta',
            'grupos' => ['dte_plus'],
        ],
    ],

    // configuración general del módulo DTE
    'dte' => [
        // contraseña que se usará para encriptar datos sensibles en la BD
        'pkey' => env('LIBREDTE_APP_DTE_PKEY', ''), // DEBE ser de 32 chars
        // configuración de logos de las empresas
        'logos' => [
            'width' => (int)env('LIBREDTE_APP_DTE_LOGOS_WIDTH', 150),
            'height' => (int)env('LIBREDTE_APP_DTE_LOGOS_HEIGHT', 100),
        ],
        // DTE autorizados por defecto para ser usados por las nuevas empresas
        'dtes' => array_map('trim', explode(',', env('LIBREDTE_APP_DTE_DTES', '33,56,61'))),
        // opciones para los PDF
        'pdf' => [
            // =true se asignará texto por defecto. String al lado izquierdo o bien arreglo con índices left y right con sus textos
            'footer' => env('LIBREDTE_APP_DTE_PDF_FOOTER', true),
        ],
        // validar SSL de sitios del SII
        'verificar_ssl' => (bool)env('LIBREDTE_APP_DTE_VERIFICAR_SSL', true),
        // web verificacion boletas (debe ser la ruta completa, incluyendo /boletas)
        'web_verificacion' => env('LIBREDTE_APP_DTE_WEB_VERIFICACION'),
        // clase para envío de boletas al SII
        'clase_boletas' => env('LIBREDTE_APP_DTE_CLASE_BOLETAS', '\website\Dte\Utility_EnvioBoleta'),
        // permitir que los usuarios puedan transferir empresas
        'transferir_contribuyente' => (bool)env('LIBREDTE_APP_DTE_TRANSFERIR_CONTRIBUYENTE', false),
    ],

    // configuración para API de contribuyentes
    'api_contribuyentes' => [
        'dte_items' => [
            'name' => 'Obtener productos o servicios desde API',
        ],
        'dte_pdf' => [
            'name' => 'Generar PDF de DTE personalizado',
        ],
        'dte_intercambio_responder' => [
            'name' => 'Procesar los XML de intercambios de DTE',
        ],
    ],

    // configuración para las aplicaciones de terceros que se pueden usar en LibreDTE
    'apps_3rd_party' => [
        'dtepdfs' => [
            'directory' => dirname(dirname(__FILE__)) . '/Module/Dte/Module/Pdf/Utility/Apps',
            'namespace' => '\website\Dte\Pdf',
        ],
    ],

    // configuración autenticación servicios externos
    'proveedores.api' => [
        // Desbloquea las funcionalidades Extra de LibreDTE
        // Regístrate Gratis en https://www.apigateway.cl
        'apigateway' => [
            'url' => env('LIBREDTE_APP_PROVEEDORES_API_APIGATEWAY_URL', 'https://apigateway.cl'),
            'token' => env('LIBREDTE_APP_PROVEEDORES_API_APIGATEWAY_TOKEN'),
        ],
    ],

    // configuración de la aplicación LibreDTE
    'libredte' => [
        'proveedor' => [
            'rut' => (int)env('LIBREDTE_APP_LIBREDTE_PROVEEDOR_RUT'),
        ],
    ],

    // método de encriptación por defecto
    'data.crypt.method' => env('LIBREDTE_APP_DATA_CRYPT_METHOD', 'sodium'),

    // configuración de caché por defecto
    'cache.default' => [
        'host' => env('LIBREDTE_APP_CACHE_DEFAULT_HOST', '127.0.0.1'),
        'port' => env('LIBREDTE_APP_CACHE_DEFAULT_PORT', 11211),
    ],

    // Extensiones para las páginas que se desean renderizar
    'page.extensions' => ['php'],

];
