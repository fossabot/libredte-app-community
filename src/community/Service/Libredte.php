<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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

namespace website;

use \sowerphp\core\Interface_Service;
use \sowerphp\core\Service_Config;
use \sowerphp\core\Service_Http_Session;
use \sowerphp\core\Service_Http_Redirect;
use \sowerphp\core\Network_Request as Request;
use \sowerphp\app\Sistema\Usuarios\Model_Usuario;
use \website\Dte\Model_Contribuyente;

/**
 * Servicio para trabajar con LibreDTE.
 */
class Service_Libredte implements Interface_Service
{

    /**
     * Servivio de configuración de la aplicación.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Http_Session
     */
    protected $sessionService;

    /**
     * Servicio de redireccionamiento HTTP de la aplicación.
     *
     * @var Service_Http_Redirect
     */
    protected $redirectService;

    /**
     * Solicitud HTTP que se está procesando.
     *
     * @var Request
     */
    protected $request;

    /**
     * Instancia del contribuyente con el que se está trabajando durante la
     * ejecución de la aplicación.
     *
     * Este contribuyente se recordará en la sesión del usuario para ser
     * utilizado entre las diferentes llamadas. Se guarda el objeto para no ir
     * a la base de datos cada vez que se requiere utilizar.
     *
     * @var Model_Contribuyente|null
     */
    protected $contribuyente = null;

    /**
     * Constructor del servicio con sus dependencias.
     */
    public function __construct(
        Service_Config $configService,
        Service_Http_Session $sessionService,
        Service_Http_Redirect $redirectService,
        Request $request
    )
    {
        $this->configService = $configService;
        $this->sessionService = $sessionService;
        $this->redirectService = $redirectService;
        $this->request = $request;
    }

    /**
     * Registra el servicio de LibreDTE.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de LibreDTE.
     *
     * @return void
     */
    public function boot(): void
    {
        // No validar SSL de sitios del SII.
        // Esta opción solo debe ser usada en caso de problemas de certificado.
        if ($this->configService->get('modules.Dte.sii.verificar_ssl') === false) {
            \sasco\LibreDTE\Sii::setVerificarSSL(false);
        }
    }

    /**
     * Finaliza el servicio de LibreDTE.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Indica que la instancia de LibreDTE es Edición Comunidad.
     *
     * @return bool True pues este servicio es de la edición enterprise.
     */
    public function isEnterpriseEdition(): bool
    {
        return false;
    }

    /**
     * Entrega la instancia del proveedor del servicio de LibreDTE.
     *
     * @return Model_Contribuyente
     */
    public function getProveedor(): ?Model_Contribuyente
    {
        $rutProveedor = $this->configService->get('libredte.proveedor.rut');
        return $this->contribuyente($rutProveedor);
    }

    /**
     * Instanciar un contribuyente.
     *
     * @param int|string $rut RUT del contribuyente, puede ser con DV.
     * @return Model_Contribuyente Contribuyente instanciado o null si no existe.
     */
    public function contribuyente($rut): ?Model_Contribuyente
    {
        // Si no se especificó un RUT se retorna null.
        if (empty($rut)) {
            return null;
        }
        // Si el RUT no es numérico, entonces se pasó con guión y DV.
        if (!is_numeric($rut)) {
            $rut = (int)(explode('-', str_replace('.', '', $rut))[0]);
        }
        // Instanciar contribuyente solicitado.
        $contribuyente = model()->getContribuyente($rut);
        if (!$contribuyente->exists()) {
            return null;
        }
        // Entregar contribuyente solicitado.
        return $contribuyente;
    }

    /**
     * Obtener el contribuyente si el usuario tiene permiso para realizar
     * acciones con él.
     *
     * @param int|string $rut RUT del contribuyente, puede ser con DV.
     * @param Model_Usuario $Usuario Usuario que se debe autenticar.
     * @param string|array $permisos Permisos que se deben validar. Puede ser
     * solo un permisos (recurso), un listado o ninguno, en cuyo caso solo se
     * valida que el usuario sea un usuario autorizado.
     * @return Model_Contribuyente Contribuyente instanciado autorizado.
     */
    public function authenticate(
        $rut,
        Model_Usuario $user,
        $permisos = []
    ): Model_Contribuyente
    {
        // Obtener contribuyente solicitado.
        $contribuyente = $this->contribuyente($rut);
        if ($contribuyente === null) {
            throw new \Exception(__(
                'Contribuyente solicitado "%s" no pudo ser obtenido.',
                $rut
            ), 404);
        }
        // Verificar que la empresa esté registrada. O sea, con usuario
        // principal asociado.
        if (!$contribuyente->usuario) {
            throw new \Exception(__(
                'Contribuyente solicitado "%s" no está registrado (sin usuario asociado).',
                $rut
            ), 404);
        }
        // Verificar el permiso que debe tener el usuario para trabajar con
        // el contribuyente.
        if (!$contribuyente->usuarioAutorizado($user, $permisos)) {
            throw new \Exception(__(
                'El usuario %s no está autorizado trabajar con la empresa %s.',
                $user->usuario,
                $contribuyente->getNombre()
            ), 403);
        }
        // Entregar el contribuyente instanciado y que el usuario tiene acceso.
        return $contribuyente;
    }

    /**
     * Método que asigna el objeto del contribuyente para ser "recordado".
     */
    public function setSessionContribuyente(Model_Contribuyente $contribuyente): void
    {
        $this->contribuyente = $contribuyente;
        $this->sessionService->put('dte.contribuyente', $contribuyente);
        $this->sessionService->forget('dte.certificacion');
    }

    /**
     * Método que entrega el objeto del contribuyente que ha sido seleccionado
     * para ser usado en la sesión. Si no hay uno seleccionado se fuerza a
     * seleccionar.
     *
     * @return Model_Contribuyente Objeto con el contribuyente.
     */
    public function getSessionContribuyente(bool $required = true): ?Model_Contribuyente
    {
        // Si no hay contribuyente asignado en el servicio se deberá determinar
        // el contribuyente que corresponde usar.
        if (!isset($this->contribuyente)) {
            // Buscar contribuyente almacenado en la sesión del usuario.
            $this->contribuyente = $this->sessionService->get('dte.contribuyente');
            // Si no hay contribuyente, se revisará si se debe exigir que si
            // exista uno. En cuyo caso se lanzará una excepción por no estar
            // asignado el contribuyente en la sesión.
            if (!$this->contribuyente) {
                if ($required) {
                    throw new \Exception(__(
                        'Antes de acceder a %s debe seleccionar el contribuyente que usará durante la sesión de LibreDTE.',
                        $this->request->getRequestUriDecoded()
                    ), 412);
                }
            // Si el contribuyente existe se inicializan configuraciones
            // asociadas al contribuyente.
            } else {
                \sasco\LibreDTE\Sii::setAmbiente(
                    $this->contribuyente->enCertificacion()
                );
            }
        }
        // Entregar el contribuyente asignado a la ejecución del servicio.
        return $this->contribuyente;
    }

    /**
     * Método que redirecciona al usuario a la página de selección del
     * contribuyente con el mensaje de error de la excepción.
     */
    public function redirectContribuyenteSeleccionar(\Exception $e)
    {
        $resource = '/dte/contribuyentes/seleccionar';
        return $this->redirectService
            ->to($resource)
            ->withError($e->getMessage())
        ;
    }

    /**
     * Método para consumir un servicio web (API) de la aplicación web de
     * LibreDTE.
     *
     * @param string $resource Recurso que se desea consumir.
     * @param array $data Datos a enviar en una solicitud POST.
     * @return array Resultado de la llamada al servicio web de LibreDTE.
     */
    public function consume(string $resource, array $data = []): array
    {

    }

}
