<?php
// config/routes.php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        // Página principal → Dashboard
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);

        // Autenticación
        $builder->connect('/login',  ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);

        $builder->resources('Users');

        // Inspecciones — exportación masiva (sin id de fila)
        $builder->connect('/inspecciones/export-sct', [
            'controller' => 'Inspecciones',
            'action' => 'exportSct',
        ]);

        $builder->connect('/inspecciones/horarios-ocupados-tecnico', [
            'controller' => 'Inspecciones',
            'action' => 'horariosOcupadosTecnico',
        ]);

        $builder->connect('/inspecciones/validar-folio', [
            'controller' => 'Inspecciones',
            'action' => 'validarFolio',
        ]);

        $builder->connect('/inspecciones/agregar-marca', [
            'controller' => 'Inspecciones',
            'action' => 'agregarMarca',
        ]);

        $builder->connect('/inspecciones/buscar-marca', [
            'controller' => 'Inspecciones',
            'action' => 'buscarMarca',
        ]);

        $builder->connect('/inspecciones/buscar-propietario', [
            'controller' => 'Inspecciones',
            'action' => 'buscarPropietario',
        ]);

        // Plantilla / PDF motriz: /inspecciones/html-motriz/{id} (no /inspecciones/{id}/html-motriz)
        $builder->connect('/inspecciones/html-motriz/{id}', [
            'controller' => 'Inspecciones',
            'action' => 'htmlMotriz',
        ], ['id' => '\d+', 'pass' => ['id'], '_name' => 'inspeccionesHtmlMotriz']);

        $builder->connect('/inspecciones/pdf-motriz/{id}', [
            'controller' => 'Inspecciones',
            'action' => 'pdfMotriz',
        ], ['id' => '\d+', 'pass' => ['id'], '_name' => 'inspeccionesPdfMotriz']);

        $builder->connect('/inspecciones/html-remolque/{id}', [
            'controller' => 'Inspecciones',
            'action' => 'htmlRemolque',
        ], ['id' => '\d+', 'pass' => ['id'], '_name' => 'inspeccionesHtmlRemolque']);

        $builder->connect('/inspecciones/pdf-remolque/{id}', [
            'controller' => 'Inspecciones',
            'action' => 'pdfRemolque',
        ], ['id' => '\d+', 'pass' => ['id'], '_name' => 'inspeccionesPdfRemolque']);

        $builder->resources('Inspecciones', function (RouteBuilder $r) {
            // PDF por inspección: /inspecciones/{id}/pdf
            $r->connect('/pdf', ['action' => 'pdf']);
            $r->connect('/pdf-lista', ['action' => 'pdfLista']);
            $r->connect('/modulo-impresion', ['action' => 'moduloImpresion']);
            $r->connect('/control-cesdia', ['action' => 'controlCesdia']);
        });

        // Dashboard
        $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index']);

        // Catálogos
        $builder->connect('/tecnicos/mi-firma', [
            'controller' => 'Tecnicos',
            'action' => 'miFirma',
        ]);
        $builder->resources('Tecnicos');
        $builder->resources('Propietarios');
        $builder->resources('Vehiculos');
        $builder->connect('/unidades-inspeccion/sello/{id}', [
            'controller' => 'UnidadesInspeccion',
            'action' => 'sello',
        ], ['id' => '\d+', 'pass' => ['id'], '_name' => 'unidadesInspeccionSello']);
        $builder->resources('UnidadesInspeccion');
        $builder->connect('/marcas-vehiculo/importar', [
            'controller' => 'MarcasVehiculo',
            'action' => 'importar',
        ]);
        $builder->connect('/marcas-vehiculo/buscar', [
            'controller' => 'MarcasVehiculo',
            'action' => 'buscar',
        ]);
        $builder->resources('MarcasVehiculo');
        $builder->resources('OrdenesServicio');

        $builder->fallbacks(DashedRoute::class);
    });
};
