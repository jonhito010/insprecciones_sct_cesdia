<?php
// src/Controller/AppController.php
namespace App\Controller;

use Authentication\IdentityInterface;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Psr\Http\Message\ResponseInterface;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            $isLogin = $this->request->getParam('controller') === 'Users'
                && $this->request->getParam('action') === 'login';
            if (!$isLogin) {
                $this->Flash->error('Debes iniciar sesión.');
                $event->setResult($this->redirect(['controller' => 'Users', 'action' => 'login']));
            }

            return;
        }

        $redirect = $this->_restringirAccesoPorRol();
        if ($redirect !== null) {
            $event->setResult($redirect);

            return;
        }

        $auth = $this->Authentication->getResult();
        $this->set('esAdministrador', ($auth && $auth->isValid()) && $this->esAdministrador());

        $this->_registrarUltimoAccesoSiCorresponde();
    }

    /**
     * Sesión válida: administrador (rol admin) o técnico (rol tecnico + tecnico_id).
     */
    protected function identity(): ?IdentityInterface
    {
        $r = $this->Authentication->getResult();
        if (!$r || !$r->isValid()) {
            return null;
        }

        return $this->Authentication->getIdentity();
    }

    protected function esAdministrador(): bool
    {
        $i = $this->identity();
        if ($i === null) {
            return false;
        }

        return (string)$i->get('rol') === 'admin';
    }

    /**
     * Administrador: null (sin filtro por técnico).
     * Técnico vinculado: id del técnico (>0).
     * Técnico sin vínculo u otro caso: -1 (no debe ver inspecciones ajenas; consultas vacías).
     */
    protected function alcanceTecnicoId(): ?int
    {
        if ($this->esAdministrador()) {
            return null;
        }
        $i = $this->identity();
        if ($i === null || (string)$i->get('rol') !== 'tecnico') {
            return -1;
        }
        $tid = $i->get('tecnico_id');
        if ($tid === null || $tid === '') {
            return -1;
        }

        return (int)$tid;
    }

    /**
     * Técnicos: solo inspecciones (acciones acotadas) y cerrar sesión.
     */
    private function _restringirAccesoPorRol(): ?ResponseInterface
    {
        if ($this->esAdministrador()) {
            return null;
        }

        $i = $this->identity();
        if ($i === null) {
            return null;
        }

        if ((string)$i->get('rol') !== 'tecnico') {
            $this->Flash->error('Tu cuenta no tiene un rol válido. Contacta al administrador.');

            return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
        }

        $controller = (string)$this->request->getParam('controller');
        $action = (string)$this->request->getParam('action');

        $permitidas = [
            'Users' => ['logout'],
            'Inspecciones' => ['index', 'add', 'view', 'edit', 'pdf', 'pdfLista', 'moduloImpresion', 'pdfRemolque', 'htmlRemolque', 'pdfMotriz', 'htmlMotriz', 'controlCesdia', 'horariosOcupadosTecnico', 'validarFolio', 'agregarMarca', 'buscarMarca'],
            'Tecnicos' => ['miFirma'],
        ];

        $ok = isset($permitidas[$controller]) && in_array($action, $permitidas[$controller], true);
        if (!$ok) {
            return $this->redirect(['controller' => 'Inspecciones', 'action' => 'index']);
        }

        return null;
    }

    /**
     * Actualiza users.ultimo_acceso con poca frecuencia (sesión) para no saturar la BD.
     */
    private function _registrarUltimoAccesoSiCorresponde(): void
    {
        $identity = $this->identity();
        if ($identity === null) {
            return;
        }
        $userId = (int)$identity->getIdentifier();
        if ($userId <= 0) {
            return;
        }

        $session = $this->request->getSession();
        $clave = 'ultimo_acceso_touch_ts';
        $ahora = time();
        $ultimo = $session->read($clave);
        if ($ultimo !== null && ($ahora - (int)$ultimo) < 120) {
            return;
        }
        $session->write($clave, $ahora);

        $this->fetchTable('Users')
            ->updateQuery()
            ->set(['ultimo_acceso' => DateTime::now()])
            ->where(['id' => $userId])
            ->execute();
    }
}
