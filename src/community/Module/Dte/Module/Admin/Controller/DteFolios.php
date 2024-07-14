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

namespace website\Dte\Admin;

/**
 * Clase para el controlador asociado a la tabla dte_folio de la base de
 * datos.
 */
class Controller_DteFolios extends \sowerphp\autoload\Controller
{

    /**
     * Acción que muestra la página principal para mantener los folios de la
     * empresa.
     */
    public function index()
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Renderizar vista.
        return $this->render(null, [
            'Emisor' => $Emisor,
            'folios' => $Emisor->getFolios(),
        ]);
    }

    /**
     * Acción que agrega mantenedor para un nuevo tipo de folios.
     */
    public function agregar()
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Variables para la vista.
        $this->set([
            'Emisor' => $Emisor,
            'dte_tipos' => $Emisor->getDocumentosAutorizados(),
        ]);
        // procesar creación del mantenedor
        if (isset($_POST['submit'])) {
            // verificar que esté autorizado a cargar folios del tipo de dte
            if (!$Emisor->documentoAutorizado($_POST['dte'])) {
                \sowerphp\core\Facade_Session_Message::write('La empresa no tiene habilitado en LibreDTE el documento de tipo '.$_POST['dte'].'. Contacte al área de soporte para que sea habilitado este tipo de documento.', 'error');
                return;
            }
            // crear mantenedor del folio
            $DteFolio = new Model_DteFolio($Emisor->rut, $_POST['dte'], $Emisor->enCertificacion());
            if (!$DteFolio->exists()) {
                $DteFolio->siguiente = 0;
                $DteFolio->disponibles = 0;
                $DteFolio->alerta = $_POST['alerta'];
                try {
                    $DteFolio->save();
                } catch (\Exception $e) {
                    \sowerphp\core\Facade_Session_Message::write('No fue posible crear el mantenedor del folio: '.$e->getMessage(), 'error');
                    return;
                }
            }
            // Si todo fue bien se redirecciona a la página de carga de CAF.
            return redirect('/dte/admin/dte_folios/subir_caf')->withInfo(
                'Ahora debe subir un archivo CAF para el tipo de documento '.mb_strtolower($DteFolio->getTipo()->tipo).'.'
            );
        }
    }

    /**
     * Acción que permite subir un caf para un tipo de folio.
     */
    public function subir_caf()
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Variables para la vista.
        $this->set([
            'Emisor' => $Emisor,
            'servidor_sii' => \sasco\LibreDTE\Sii::getServidor(),
        ]);
        // procesar solo si se envió el formulario
        if (isset($_POST['submit'])) {
            // verificar que se haya podido subir CAF
            if (!isset($_FILES['caf']) || $_FILES['caf']['error']) {
                $message = __(
                    'Error al subir el archivo XML del CAF. %s',
                    \sowerphp\general\Utility_File::uploadErrorCodeToMessage(
                        $_FILES['caf']['error']
                    )
                );
                \sowerphp\core\Facade_Session_Message::error($message);
                return;
            }
            $mimetype = \sowerphp\general\Utility_File::mimetype(
                $_FILES['caf']['tmp_name']
            );
            if (!in_array($mimetype, ['application/xml', 'text/xml'])) {
                $message = __(
                    'No ha sido posible cargar el archivo %s pues tiene formato %s, el cual es incorrecto. Debe [obtener un archivo XML de CAF válido en SII](%s) y luego subirlo acá.',
                    $_FILES['caf']['name'],
                    $mimetype,
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/of_solicita_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/of_solicita_folios',
                );
                \sowerphp\core\Facade_Session_Message::error($message);
                return;
            }
            $caf = file_get_contents($_FILES['caf']['tmp_name']);
            $Folios = new \sasco\LibreDTE\Sii\Folios($caf);
            // si no hay tipo se asume que el archivo no es válido
            if (!$Folios->getTipo()) {
                $message = __(
                    'No ha sido posible cargar el archivo %s pues no es un XML de un CAF válido. Debe [obtener un archivo XML de CAF válido en SII](%s) y luego subirlo acá.',
                    $_FILES['caf']['name'],
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/of_solicita_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/of_solicita_folios',
                );
                \sowerphp\core\Facade_Session_Message::write($message, 'error');
                return;
            }
            // buscar el mantenedor de folios del CAF, si no existe se tratará de crear el mantenedor
            $DteFolio = new Model_DteFolio($Emisor->rut, $Folios->getTipo(), (int)$Folios->getCertificacion());
            if (!$DteFolio->exists()) {
                // verificar que esté autorizado a cargar folios del tipo de dte
                if (!$Emisor->documentoAutorizado($Folios->getTipo())) {
                    $message = __(
                        'La empresa no tiene habilitado en LibreDTE el documento %s. Debe [contactarnos](%s) para que sea habilitado este tipo de documento en su cuenta.',
                        strtolower($DteFolio->getTipo()->tipo),
                        url('/contacto')
                    );
                    \sowerphp\core\Facade_Session_Message::write($message, 'error');
                    return;
                }
                // determinar alerta
                $cantidad = $Folios->getCantidad();
                if ($cantidad && $Emisor->config_sii_timbraje_multiplicador) {
                    $alerta = ceil((int)$cantidad / (int)$Emisor->config_sii_timbraje_multiplicador);
                } else {
                    $alerta = (int)$cantidad;
                }
                // crear mantenedor del folio
                $DteFolio->siguiente = 0;
                $DteFolio->disponibles = 0;
                $DteFolio->alerta = $alerta;
                try {
                    $DteFolio->save();
                } catch (\Exception $e) {
                    $message = __(
                        'No fue posible subir el XML del CAF pues no existía el mantenedor de folios asociado. Se trató de crear automáticamente dicho mantenedor pero no fue posible.<br/><br/>%s<br/><br/>Debe [crear el mantenedor de folios](%s) y luego tratar de [subir nuevamente el XML del CAF](%s).',
                        $e->getMessage(),
                        url('/dte/admin/dte_folios/agregar'),
                        url('/dte/admin/dte_folios/subir_caf')
                    );
                    \sowerphp\core\Facade_Session_Message::write($message, 'error');
                    return;
                }
            }
            // guardar el CAF
            try {
                $DteFolio->guardarFolios($caf);
                return redirect('/dte/admin/dte_folios/ver/'.$Folios->getTipo())->withSuccess(__(
                    'El archivo XML del CAF para el documento de tipo %s que inicia en %d y termina en %d fue cargado. El siguiente folio disponible es el %d, si necesita modificar el folio siguiente debe hacerlo [aquí](%s).',
                    strtolower($DteFolio->getTipo()->tipo),
                    $Folios->getDesde(),
                    $Folios->getHasta(),
                    $DteFolio->siguiente,
                    url('/dte/admin/dte_folios/modificar/'.$Folios->getTipo())
                ));
            } catch (\Exception $e) {
                \sowerphp\core\Facade_Session_Message::write($e->getMessage(), 'error');
                return;
            }
        }
    }

    /**
     * Acción que permite ver el mantenedor de folios.
     */
    public function ver($dte)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Obtener folio.
        $DteFolio = new Model_DteFolio(
            $Emisor->rut,
            (int)$dte,
            $Emisor->enCertificacion()
        );
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el mantenedor de folios solicitado.'
            );
        }
        // Renderizar vista.
        return $this->render(null, [
            'Emisor' => $Emisor,
            'DteFolio' => $DteFolio,
            'hoy' => date('Y-m-d'),
            'cafs' => $DteFolio->getCafs('DESC'),
        ]);
    }

    /**
     * Acción que permite ver los folios sin uso de un tipo de DTE.
     */
    public function sin_uso($dte)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Obtener folio.
        $DteFolio = new Model_DteFolio(
            $Emisor->rut,
            (int)$dte,
            $Emisor->enCertificacion()
        );
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el mantenedor de folios solicitado.'
            );
        }
        // Renderizar vista.
        return $this->render(null, [
            'Emisor' => $Emisor,
            'DteFolio' => $DteFolio,
            'folios_sin_uso' => $DteFolio->getSinUso(),
        ]);
    }

    /**
     * Acción que permite modificar un mantenedor de folios.
     */
    public function modificar($dte)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Obtener folio.
        $DteFolio = new Model_DteFolio(
            $Emisor->rut,
            (int)$dte,
            $Emisor->enCertificacion()
        );
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el mantenedor de folios solicitado.'
            );
        }
        // Variables para la vista.
        $this->set([
            'Emisor' => $Emisor,
            'DteFolio' => $DteFolio,
        ]);
        // Procesar formulario.
        if (isset($_POST['submit'])) {
            // validar que campos existan y asignar
            foreach (['siguiente', 'alerta'] as $attr) {
                if (empty($_POST[$attr])) {
                    \sowerphp\core\Facade_Session_Message::write('Debe especificar el campo: '.$attr.'.', 'error');
                    return;
                }
                $DteFolio->$attr = $_POST[$attr];
            }
            // verificar CAF vigente
            try {
                $Caf = $DteFolio->getCaf();
            } catch (\Exception $e) {
                \sowerphp\core\Facade_Session_Message::write('No fue posible abrir el XML del CAF que contiene el folio '.$DteFolio->siguiente.'. Por lo que no se pudo editar el mantenedor de folios. Se recomienda eliminar el XML del CAF que contiene al folio '.$DteFolio->siguiente.', volverlo a cargar y luego intentar modificar el mantenedor de folios.', 'error');
                return;
            }
            if (!$Caf) {
                \sowerphp\core\Facade_Session_Message::write('No se encontró un CAF que contenga el folio '.$DteFolio->siguiente.'.', 'error');
                return;
            }
            if (!$Caf->vigente()) {
                \sowerphp\core\Facade_Session_Message::write('El CAF que contiene el folio '.$DteFolio->siguiente.' está vencido, no se puede asignar, debe asignar uno vigente.', 'error');
                return;
            }
            // verificar que el folio siguiente que se está asignando no esté siendo usado actualmente por otro DTE ya emitido
            $DteEmitido = new \website\Dte\Model_DteEmitido($DteFolio->emisor, $DteFolio->dte, $DteFolio->siguiente, (int)$DteFolio->certificacion);
            if ($DteEmitido->exists()) {
                \sowerphp\core\Facade_Session_Message::write('El folio '.$DteFolio->siguiente.' se encuentra usado en LibreDTE, no se puede asignar como folio siguiente. Debe asignar un folio no usado, ni en LibreDTE, ni en otro sistema.', 'error');
                return;
            }
            // guardar y redireccionar
            try {
                if (!$DteFolio->calcularDisponibles()) {
                    \sowerphp\core\Facade_Session_Message::write('No fue posible actualizar el mantenedor de folios.', 'error');
                    return;
                }
                return redirect('/dte/admin/dte_folios')->withSuccess(
                    'El mantenedor de folios para tipo '.$DteFolio->dte.' ha sido actualizado.'
                );
            } catch (\Exception $e) {
                \sowerphp\core\Facade_Session_Message::write('No fue posible actualizar el mantenedor de folios: '.$e->getMessage(), 'error');
                return;
            }
        }
    }

    /**
     * Acción que permite eliminar un mantenedor de folios.
     */
    public function eliminar($dte)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Validar usuario que puede eliminar folios.
        if (!$Emisor->usuarioAutorizado($this->Auth->User, 'admin')) {
            return redirect('/dte/admin/dte_folios')->withError(
                'Solo un administrador de la empresa puede eliminar un mantenedor de folios.'
            );
        }
        // Obtener folios.
        $DteFolio = new Model_DteFolio($Emisor->rut, (int)$dte, $Emisor->enCertificacion());
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el mantenedor de folios solicitado.'
            );
        }
        // Obtener los CAF.
        $cafs = $DteFolio->getCafs();
        if (!empty($cafs)) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(
                'No es posible eliminar el mantenedor de folios, ya que tiene archivos CAF asociados. Debe eliminar primero cada uno de los CAF y luego eliminar el mantenedor de folios.'
            );
        }
        $DteFolio->delete();
        return redirect('/dte/admin/dte_folios')->withSuccess(
            'El mantenedor de folios de '.$DteFolio->getTipo()->tipo.' ha sido eliminado.'
        );
    }

    /**
     * Acción que permite descargar el XML del archivo CAF.
     */
    public function xml($dte, $desde)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Validar que el usuario pueda descargar los XML de folios.
        if (!$Emisor->usuarioAutorizado($this->Auth->User, 'admin')) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(
                'Solo un administrador de la empresa puede descargar los archivos XML de los CAF desde LibreDTE.'
            );
        }
        $DteCaf = new Model_DteCaf($Emisor->rut, $dte, $Emisor->enCertificacion(), $desde);
        if (!$DteCaf->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el archivo CAF solicitado.'
            );
        }
        // entregar XML
        $file = 'caf_'.$Emisor->rut.'-'.$Emisor->dv.'_'.$dte.'_'.$desde.'.xml';
        $xml = $DteCaf->getXML();
        $this->response->type('application/xml', 'ISO-8859-1');
        $this->response->header('Content-Length', strlen($xml));
        $this->response->header('Content-Disposition', 'attachement; filename="'.$file.'"');
        $this->response->sendAndExit($xml);
    }

    /**
     * Acción que permite eliminar un XML (CAF) específico del mantenedor de folios.
     */
    public function eliminar_xml($dte, $desde)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Validar que el usuario pueda eliminar XML de folios.
        if (!$Emisor->usuarioAutorizado($this->Auth->User, 'admin')) {
            return redirect('/dte/admin/dte_folios')->withError(
                'Solo un administrador de la empresa puede eliminar los archivos CAF.'
            );
        }
        // Obtener folio.
        $DteFolio = new Model_DteFolio(
            $Emisor->rut,
            (int)$dte,
            $Emisor->enCertificacion()
        );
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'No existe el mantenedor de folios solicitado.'
            );
        }
        // Obtener CAF.
        $DteCaf = new Model_DteCaf(
            $Emisor->rut,
            $dte,
            $Emisor->enCertificacion(),
            $desde
        );
        if (!$DteCaf->exists()) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(
                'No existe el archivo CAF solicitado.'
            );
        }
        $Caf = $DteCaf->getCAF();
        $vigente = $Caf ? $Caf->vigente() : false;
        $usado = $DteCaf->usado();
        if ($vigente && $usado) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(
                'No es posible eliminar un XML de un CAF vigente y con folios usados en LibreDTE. Debe esperar a que el CAF esté vencido y ahí lo podrá eliminar.'
            );
        }
        // Eliminar CAF y recalcular disponibles.
        $DteCaf->delete();
        $DteFolio->calcularDisponibles();
        return redirect('/dte/admin/dte_folios/ver/'.$dte)->withSuccess(
            'El XML del CAF de '.$DteCaf->getTipo()->tipo.' que inicia en '.$DteCaf->desde.' ha sido eliminado.'
        );
    }

    /**
     * Acción que permite reobtener un archivo CAF al SII y cargarlo en LibreDTE.
     */
    public function reobtener_caf($dte = null)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Variables para la vista.
        $this->set([
            'Emisor' => $Emisor,
            'dte_tipos' => $Emisor->getDocumentosAutorizados(),
            'dte' => $dte,
        ]);
        // procesar solicitud de folios
        if (isset($_POST['submit'])) {
            // buscar el mantenedor de folios del CAF
            $DteFolio = new Model_DteFolio($Emisor->rut, (int)$_POST['dte'], $Emisor->enCertificacion());
            if (!$DteFolio->exists()) {
                $message = __(
                    'Antes de reobtener el XML de un CAF de %s, primero debe [crear el mantenedor de folios](%s).',
                    strtolower($DteFolio->getTipo()->tipo),
                    url('/dte/admin/dte_folios/agregar')
                );
                \sowerphp\core\Facade_Session_Message::write($message, 'error');
                return;
            }
            // recuperar firma electrónica
            $Firma = $Emisor->getFirma($this->Auth->User->id);
            if (!$Firma) {
                $message = __(
                    'No ha sido posible obtener el listado de CAF solicitados en el SII, pues no hay una firma electrónica asociada a la empresa (o bien no se pudo cargar). Debe [agregar su firma](%s) antes de utilizar esta opción.',
                    url('/dte/admin/firma_electronicas')
                );
                \sowerphp\core\Facade_Session_Message::write($message, 'error');
                return;
            }
            // consultar listado de solicitudes
            $r = apigateway(
                '/sii/dte/caf/solicitudes/'.$Emisor->getRUT().'/'.$DteFolio->dte.'?formato=json&certificacion='.$Emisor->enCertificacion(),
                [
                    'auth' => [
                        'cert' => [
                            'cert-data' => $Firma->getCertificate(),
                            'pkey-data' => $Firma->getPrivateKey(),
                        ],
                    ],
                ]
            );
            if ($r['status']['code'] != 200) {
                \sowerphp\core\Facade_Session_Message::write('No fue posible obtener el listado de CAFs solicitados en SII: '.$r['body'], 'error');
                return;
            }
            // no hay folios timbrados en SII
            if (empty($r['body'])) {
                \sowerphp\core\Facade_Session_Message::write('No se encontraron folios para el tipo de documento '.$DteFolio->dte.' en SII.', 'warning');
                return;
            }
            // armar listado de solicitudes de folios que no están en LibreDTE
            $solicitudes = [];
            foreach ($r['body'] as $s) {
                $DteCaf = new Model_DteCaf($Emisor->rut, $DteFolio->dte, $Emisor->enCertificacion(), $s['inicial']);
                if (!$DteCaf->hasta) {
                    $solicitudes[] = $s;
                }
            }
            // si todo está cargado -> ok
            if (empty($solicitudes)) {
                \sowerphp\core\Facade_Session_Message::write('Todos los folios encontrados en el SII se encuentran cargados en LibreDTE.', 'ok');
                return;
            }
            // asignar variables para la vista
            $this->set([
                'solicitudes' => $solicitudes,
                'dte' => $DteFolio->dte,
            ]);
        }
    }

    /**
     * Acción que permite descargar un archivo CAF previamente solicitado al SII
     * y cargarlo en LibreDTE.
     */
    public function reobtener_caf_cargar($dte, $folio_inicial, $folio_final, $fecha_autorizacion)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // buscar el mantenedor de folios del CAF
        $DteFolio = new Model_DteFolio($Emisor->rut, (int)$dte, $Emisor->enCertificacion());
        if (!$DteFolio->exists()) {
            return redirect('/dte/admin/dte_folios')->withError(
                'Primero debe crear el mantenedor de los folios de tipo '.$dte.'.'
            );
        }
        // si ya existe un caf no se vuelve a cargar
        $DteCaf = new Model_DteCaf($Emisor->rut, $DteFolio->dte, $Emisor->enCertificacion(), $folio_inicial);
        if ($DteCaf->hasta) {
            return redirect('/dte/admin/dte_folios/reobtener_caf/'.$DteFolio->dte)
                ->withSuccess('El CAF solicitado ya se encontraba cargado.')
            ;
        }
        // recuperar firma electrónica
        $Firma = $Emisor->getFirma($this->Auth->User->id);
        if (!$Firma) {
            return redirect('/dte/admin/dte_folios/reobtener_caf/'.$DteFolio->dte)
                ->withError(__(
                    'No ha sido posible reobtener el XML del CAF, pues no hay una firma electrónica asociada a la empresa (o bien no se pudo cargar). Debe [agregar su firma](%s) antes de utilizar esta opción.',
                    url('/dte/admin/firma_electronicas')
                ))
            ;
        }
        // consultar listado de solicitudes
        $r = apigateway(
            '/sii/dte/caf/xml/'.$Emisor->getRUT().'/'.$DteFolio->dte.'/'.$folio_inicial.'/'.$folio_final.'/'.$fecha_autorizacion.'?certificacion='.$Emisor->enCertificacion(),
            [
                'auth' => [
                    'cert' => [
                        'cert-data' => $Firma->getCertificate(),
                        'pkey-data' => $Firma->getPrivateKey(),
                    ],
                ],
            ]
        );
        if ($r['status']['code'] != 200) {
            return redirect('/dte/admin/dte_folios/reobtener_caf/'.$DteFolio->dte)
                ->withError(__(
                    'No fue posible obtener el XML del CAF desde el SII.<br/><br/>%s<br/><br/>Se recomienda usar la opción de [reobtención directa en SII](%s) y luego [subir el XML del CAF a LibreDTE](%s).',
                    $r['body'],
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios',
                    url('/dte/admin/dte_folios/subir_caf')
                ))
            ;
        }
        // guardar el CAF
        try {
            $DteFolio->guardarFolios($r['body']);
            $Folios = new \sasco\LibreDTE\Sii\Folios($r['body']);
            return redirect('/dte/admin/dte_folios/reobtener_caf/'.$DteFolio->dte)
                ->withSuccess(
                    'El XML del CAF para el documento de tipo '.$Folios->getTipo().' que inicia en '.$Folios->getDesde().' fue cargado. El siguiente folio disponible es '.$DteFolio->siguiente.'.'
                )
            ;
        } catch (\Exception $e) {
            return redirect('/dte/admin/dte_folios/reobtener_caf/'.$DteFolio->dte)
                ->withError(__(
                    'No fue posible guardar el XML del CAF obtenido desde el SII.<br/><br/>%s<br/><br/>Se recomienda usar la opción de [reobtención directa en SII](%s) y luego [subir el XML del CAF a LibreDTE](%s).',
                    $e->getMessage(),
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios',
                    url('/dte/admin/dte_folios/subir_caf')
                ))
            ;
        }
    }

    /**
     * Acción que permite solicitar un archivo CAF al SII y cargarlo en LibreDTE.
     */
    public function solicitar_caf($dte = null)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Variables para la vista.
        $this->set([
            'Emisor' => $Emisor,
            'dte_tipos' => $Emisor->getDocumentosAutorizados(),
            'dte' => $dte,
        ]);
        // procesar solicitud de folios
        if (isset($_POST['submit'])) {
            // buscar el mantenedor de folios del CAF
            $DteFolio = new Model_DteFolio($Emisor->rut, $_POST['dte'], $Emisor->enCertificacion());
            if (!$DteFolio->exists()) {
                \sowerphp\core\Facade_Session_Message::write('Primero debe crear el mantenedor de los folios de tipo '.$_POST['dte'].'.', 'error');
                return;
            }
            // solicitar timbraje
            if ($_POST['cantidad'] <= 0) {
                \sowerphp\core\Facade_Session_Message::write('La cantidad de folios solicitados debe ser mayor a 0.', 'error');
                return;
            }
            try {
                $xml = $DteFolio->timbrar($_POST['cantidad']);
            } catch (\Exception $e) {
                $message = __(
                    'No fue posible solicitar un nuevo XML de CAF al SII mediante LibreDTE.<br/><br/>%s<br/><br/>Se recomienda usar la opción de [timbraje directo en SII](%s) y luego [subir el XML del CAF a LibreDTE](%s).',
                    $e->getMessage(),
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/of_solicita_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/of_solicita_folios',
                    url('/dte/admin/dte_folios/subir_caf')
                );
                \sowerphp\core\Facade_Session_Message::write($message, 'error');
                return;
            }
            // guardar timbraje
            try {
                $Folios = $DteFolio->guardarFolios($xml);
                return redirect('/dte/admin/dte_folios')->withSuccess(
                    'El CAF para el documento de tipo '.$Folios->getTipo().' que inicia en '.$Folios->getDesde().' fue cargado. El siguiente folio disponible es '.$DteFolio->siguiente.'.'
                );
            } catch (\Exception $e) {
                $message = __(
                    'No fue posible guardar el XML del CAF obtenido desde el SII.<br/><br/>%s<br/><br/>Se recomienda revisar si se puede [reobtener el CAF](%s) o bien usar la opción de [timbraje directo en SII](%s). Luego, teniendo el XML con alguna de las opciones previas, [subir el XML del CAF a LibreDTE](%s).',
                    $e->getMessage(),
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/rf_reobtencion1_folios',
                    $Emisor->enCertificacion()
                        ? 'https://maullin.sii.cl/cvc_cgi/dte/of_solicita_folios'
                        : 'https://palena.sii.cl/cvc_cgi/dte/of_solicita_folios',
                    url('/dte/admin/dte_folios/subir_caf')
                );
                \sowerphp\core\Facade_Session_Message::write($message, 'error');
                return;
            }
        }
    }

    /**
     * Acción que muestra la página con el estado del folio en el SII.
     */
    public function estado($dte, $folio)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Consultar estado a servicio web.
        $r = $this->consume('/api/dte/admin/dte_folios/estado/'.$dte.'/'.$folio.'/'.$Emisor->rut.'?formato=html');
        if ($r['status']['code'] != 200) {
            die($r['body']);
        }
        $this->layout = null;
        return $this->render(null, [
            'Emisor' => $Emisor,
            'dte' => $dte,
            'folio' => $folio,
            'estado_web' => $r['body'],
        ]);
    }

    /**
     * Acción que permite anular un folio directamente en el sitio del SII.
     */
    public function anular($dte, $folio)
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Anular folio usando servicio web.
        $r = $this->consume('/api/dte/admin/dte_folios/anular/'.$dte.'/'.$folio.'/'.$Emisor->rut.'?formato=html');
        if ($r['status']['code'] != 200) {
            $this->response->sendAndExit($r['body']);
        }
        $this->response->sendAndExit($r['body']);
    }

    /**
     * Acción que permite descargar del SII los folios según su estado.
     */
    public function descargar($dte, $folio, $estado = 'recibidos')
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Obtener CAF solicitado.
        $DteCaf = new Model_DteCaf($Emisor->rut, $dte, $Emisor->enCertificacion(), $folio);
        if (!$DteCaf->exists()) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(
                'No existe el CAF solicitado.'
            );
        }
        try {
            $detalle = $DteCaf->{'getFolios' . ucfirst($estado)}();
        } catch(\Exception $e) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withError(__(
                'No fue posible descargar el estado de folios %s: %s',
                $estado,
                $e->getMessage()
            ));
        }
        if (!$detalle) {
            return redirect('/dte/admin/dte_folios/ver/'.$dte)->withWarning(
                'No se encontraron folios con el estado \''.$estado.'\' en el SII para el CAF que inicia en '.$folio.'.'
            );
        }
        array_unshift($detalle, ['Folio inicial', 'Folio final', 'Cantidad de folios']);
        $csv = \sowerphp\general\Utility_Spreadsheet_CSV::get($detalle);
        $this->response->sendAndExit($csv, 'folios_'.$estado.'_'.$Emisor->rut.'_'.$dte.'_'.$folio.'_'.date('Y-m-d').'.csv');
    }

    /**
     * Acción que permite solicitar el informe de estado de los folios.
     */
    public function informe_estados()
    {
        // Obtener contribuyente que se está utilizando en la sesión.
        try {
            $Emisor = libredte()->getSessionContribuyente();
        } catch (\Exception $e) {
            return libredte()->redirectContribuyenteSeleccionar($e);
        }
        // Obtener documentos autorizados.
        $aux = $Emisor->getDocumentosAutorizados();
        $documentos = [];
        foreach ($aux as $d) {
            if (!in_array($d['codigo'], [39, 41])) {
                $documentos[] = $d;
            }
        }
        $this->set([
            'documentos' => $documentos,
        ]);
        // procesar formulario
        if (isset($_POST['submit'])) {
            // si no hay documentos error
            if (empty($_POST['documentos'])) {
                \sowerphp\core\Facade_Session_Message::write('Debe seleccionar al menos un tipo de documento para obtener el estado.', 'error');
                return;
            }
            if (empty($_POST['estados'])) {
                \sowerphp\core\Facade_Session_Message::write('Debe seleccionar al menos un estado a obtener.', 'error');
                return;
            }
            // lanzar comando
            $cmd = 'Dte.Admin.DteFolios_Estados '.escapeshellcmd((int)$Emisor->rut).' '.escapeshellcmd(implode(',',$_POST['documentos'])).' '.escapeshellcmd(implode(',',$_POST['estados'])).' '.escapeshellcmd((int)$this->Auth->User->id).' -v';
            if ($this->shell($cmd)) {
                \sowerphp\core\Facade_Session_Message::write('Error al tratar de generar su informe, por favor reintentar.', 'error');
            } else {
                \sowerphp\core\Facade_Session_Message::write('Su informe está siendo generado, será enviado a su correo cuando esté listo.', 'ok');
            }
            return redirect('/dte/admin/dte_folios');
        }
    }

    /**
     * Recurso que entrega el la información de cierto mantenedor de folios.
     */
    public function _api_info_GET($dte, $emisor)
    {
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        $Emisor = new \website\Dte\Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe.', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/admin/dte_folios/ver')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada.', 403);
        }
        $DteFolio = new Model_DteFolio($Emisor->rut, (int)$dte, $Emisor->enCertificacion());
        if (!$DteFolio->exists()) {
            $this->Api->send('No existe el mantenedor de folios para el tipo de DTE '.$dte.'.', 404);
        }
        extract($this->request->getValidatedData(['sinUso' => false]));
        if ($sinUso) {
            $DteFolio->sin_uso = $DteFolio->getSinUso();
        }
        return $DteFolio;
    }

    /**
     * Recurso que permite modificar el mantenedor de folios.
     * Modifica: folio siguiente y/o alerta.
     */
    public function _api_modificar_POST($dte, $emisor)
    {
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        $Emisor = new \website\Dte\Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe.', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/dte_emitidos/ver')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada.', 403);
        }
        $DteFolio = new Model_DteFolio($Emisor->rut, (int)$dte, $Emisor->enCertificacion());
        if (!$DteFolio->exists()) {
            $this->Api->send('No existe el mantenedor de folios para el tipo de DTE '.$dte.'.', 404);
        }
        // validar que campos existan y asignar
        foreach (['siguiente', 'alerta'] as $attr) {
            if (isset($this->Api->data[$attr])) {
                $DteFolio->$attr = $this->Api->data[$attr];
            }
        }
        // verificar CAF vigente
        $Caf = $DteFolio->getCaf();
        if (!$Caf) {
            $this->Api->send('CAF que contenga el folio '.$DteFolio->siguiente.' no se encuentra cargado.', 500);
        }
        if (!$Caf->vigente()) {
            $this->Api->send('El CAF que contiene el folio '.$DteFolio->siguiente.' está vencido, no se puede asignar.', 500);
        }
        // verificar que el folio siguiente que se está asignando no esté siendo usado actualmente por otro DTE ya emitido
        $DteEmitido = new \website\Dte\Model_DteEmitido(
            $DteFolio->emisor,
            $DteFolio->dte,
            $DteFolio->siguiente,
            (int)$DteFolio->certificacion
        );
        if ($DteEmitido->exists()) {
            $this->Api->send('El folio '.$DteFolio->siguiente.' se encuentra usado, no se puede asignar como folio siguiente.', 500);
        }
        // guardar e informar
        try {
            if (!$DteFolio->calcularDisponibles()) {
                $this->Api->send('No fue posible actualizar el mantenedor de folios.', 500);
            }
            return $DteFolio;
        } catch (\Exception $e) {
            $this->Api->send('No fue posible actualizar el mantenedor de folios: '.$e->getMessage().'.', 500);
        }
    }

    /**
     * Recurso que permite solicitar un CAF al SII.
     */
    public function _api_solicitar_caf_GET($dte, $cantidad, $emisor)
    {
        // crear usuario, emisor y verificar permisos
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        $Emisor = new \website\Dte\Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe.', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/admin/dte_folios/subir_caf')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada.', 403);
        }
        // verificar que exista un mantenedor de folios
        $DteFolio = new Model_DteFolio($Emisor->rut, (int)$dte, $Emisor->enCertificacion());
        if (!$DteFolio->exists()) {
            $this->Api->send('Primero debe crear el mantenedor de los folios de tipo '.$dte.'.', 500);
        }
        if (!$DteFolio->siguiente) {
            $this->Api->send('Debe tener al menos un CAF cargado manualmente antes de solicitar timbraje vía LibreDTE.', 500);
        }
        // solicitar timbraje
        try {
            $xml = $DteFolio->timbrar($cantidad);
            return base64_encode($xml);
        } catch (\Exception $e) {
            $this->Api->send('No fue posible timbrar: '.$e->getMessage().'.', 500);
        }
    }

    /**
     * Recurso que permite consultar el estado de un folio en el SII.
     */
    public function _api_estado_GET($dte, $folio, $emisor)
    {
        extract($this->request->getValidatedData(['formato' => 'json']));
        // crear usuario, emisor y verificar permisos
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        $Emisor = new \website\Dte\Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe.', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/admin/dte_folios/ver')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada.', 403);
        }
        // recuperar firma electrónica
        $Firma = $Emisor->getFirma($User->id);
        if (!$Firma) {
            $message = __(
                'No ha sido posible consultar el estado de un folio, pues no hay una firma electrónica asociada a la empresa (o bien no se pudo cargar). Debe [agregar su firma](%s) antes de utilizar esta opción.',
                url('/dte/admin/firma_electronicas')
            );
            $this->Api->send($message, 506);
        }
        // consultar estado del folio
        $r = apigateway(
            '/sii/dte/caf/estado/'.$Emisor->getRUT().'/'.$dte.'/'.$folio.'?formato='.$formato.'&certificacion='.$Emisor->enCertificacion(),
            [
                'auth' => [
                    'cert' => [
                        'cert-data' => $Firma->getCertificate(),
                        'pkey-data' => $Firma->getPrivateKey(),
                    ],
                ],
            ]
        );
        if ($r['status']['code'] != 200) {
            $this->Api->send('No fue posible consultar el estado del folio: '.$r['body'], 500);
        }
        if ($formato == 'html') {
            $this->Api->response()->type('text/html');
        } else {
            $this->Api->response()->type('application/json');
        }
        $this->Api->send($r['body']);
    }

    /**
     * Recurso que permite anular un folio en el SII.
     */
    public function _api_anular_GET($dte, $folio, $emisor)
    {
        extract($this->request->getValidatedData(['formato' => 'json']));
        // crear usuario, emisor y verificar permisos
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        $Emisor = new \website\Dte\Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe.', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/admin/dte_folios/subir_caf')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada.', 403);
        }
        // recuperar firma electrónica
        $Firma = $Emisor->getFirma($User->id);
        if (!$Firma) {
            $message = __(
                'No ha sido posible anular el folio, pues no hay una firma electrónica asociada a la empresa (o bien no se pudo cargar). Debe [agregar su firma](%s) antes de utilizar esta opción.',
                url('/dte/admin/firma_electronicas')
            );
            $this->Api->send($message, 506);
        }
        // anular folio
        $r = apigateway(
            '/sii/dte/caf/anular/'.$Emisor->getRUT().'/'.$dte.'/'.$folio.'?formato='.$formato.'&certificacion='.$Emisor->enCertificacion(),
            [
                'auth' => [
                    'cert' => [
                        'cert-data' => $Firma->getCertificate(),
                        'pkey-data' => $Firma->getPrivateKey(),
                    ],
                ],
            ]
        );
        if ($r['status']['code'] != 200) {
            $this->Api->send('No fue posible anular el folio: '.$r['body'], 500);
        }
        if ($formato == 'html') {
            $this->Api->response()->type('text/html');
        } else {
            $this->Api->response()->type('application/json');
        }
        $this->Api->send($r['body']);
    }

}
