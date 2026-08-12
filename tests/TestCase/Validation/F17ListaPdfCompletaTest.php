<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Validation\F17ListaPdfValidador;
use App\Validation\Nom068Formato;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Prueba completa F-17: estructura del PDF lista vs formato oficial.
 *
 * No depende de BD: arma una entidad mínima T3 y renderiza pdf_lista.
 */
class F17ListaPdfCompletaTest extends TestCase
{
    public function testNom068FormatoFilasYParesF17(): void
    {
        $this->assertSame(10, Nom068Formato::filasTablaComplementaria('F17_TRACTO'));
        $pares = Nom068Formato::paresVarilla('F17_TRACTO');
        $this->assertArrayHasKey('1', $pares);
        $this->assertArrayHasKey('2', $pares);
        $this->assertArrayHasKey('3-4', $pares);
        $this->assertArrayHasKey('9-10', $pares);
        $this->assertArrayNotHasKey('11-12', $pares);
    }

    public function testListaPdfContieneTodaLaInformacionOficial(): void
    {
        // Evitar Migrator/BD: bootstrap mínimo ya ocurrió; renderizar con entidad en memoria.
        $html = $this->renderListaHtml($this->inspeccionF17Completa());

        $this->assertNotSame('', $html);
        $this->assertStringContainsString('F-17', $html);

        $fallas = F17ListaPdfValidador::fallas(F17ListaPdfValidador::validar($html, 'T3'));
        $this->assertSame(
            [],
            $fallas,
            "PDF F-17 incompleto:\n - " . implode("\n - ", $fallas)
        );
    }

    public function testValidadorDetectaHtmlVacio(): void
    {
        $fallas = F17ListaPdfValidador::fallas(F17ListaPdfValidador::validar('<html></html>'));
        $this->assertNotEmpty($fallas);
        $this->assertFalse(F17ListaPdfValidador::esCompleto('<html></html>'));
    }

    private function renderListaHtml(Entity $inspeccion): string
    {
        if (!Configure::check('App.encoding')) {
            Configure::write('App.encoding', 'UTF-8');
        }
        if (!Configure::check('App.paths.templates')) {
            Configure::write('App.paths.templates', [ROOT . DS . 'templates' . DS]);
        }

        $response = new Response(['charset' => 'UTF-8']);
        $view = new View(new ServerRequest(), $response);
        $view->disableAutoLayout();
        $view->setTemplatePath('Inspecciones');
        $view->setTemplate('pdf_lista');
        $view->set([
            'inspeccion' => $inspeccion,
            'tipoFormulario' => 'F17_TRACTO',
            'logoDataUri' => '',
            'firmaDataUri' => '',
        ]);

        return $view->render();
    }

    private function inspeccionF17Completa(): Entity
    {
        $cumple = static fn (): Entity => new Entity(['id' => 1] + array_fill_keys([
            'faros_principales', 'faros_altura', 'faros_montaje', 'galibo_delantero',
            'luz_alta_baja', 'luz_diurna', 'luces_traseras', 'direccionales', 'luces_peligro',
            'luces_intermitentes', 'luz_niebla', 'parabrisas', 'parabrisas_tipo',
            'ventanas_laterales', 'ventana_posterior', 'limpiaparabrisas', 'inyectores_agua',
            'defensa_delantera', 'placa_identificacion', 'luces_freno', 'luces_reversa',
            'luz_placa_trasera',
        ], 'CUMPLE'));

        $llantas = [];
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10] as $i => $n) {
            $llantas[] = new Entity([
                'id' => $i + 1,
                'numero_llanta' => $n,
                'posicion' => 'EXTERNA',
                'profundidad_mm' => 6 + $i,
                'presion_psi' => 90 + $i,
                'profundidad_cumple' => 'CUMPLE',
                'presion_cumple' => 'CUMPLE',
                'banda_rodamiento' => 'CUMPLE',
                'costados' => 'CUMPLE',
                'rin_condicion' => 'CUMPLE',
                'rin_sujetadores' => 'CUMPLE',
                'rin_artilleria' => 'CUMPLE',
            ]);
        }

        $rines = [];
        for ($i = 1; $i <= 10; $i++) {
            $rines[] = new Entity([
                'id' => $i,
                'numero_llanta' => $i,
                'num_sujetadores' => 10,
                'sujetadores_cumple' => 'CUMPLE',
                'maza_cumple' => 'CUMPLE',
                'balero_cumple' => 'CUMPLE',
            ]);
        }

        $obs = [];
        for ($i = 1; $i <= 6; $i++) {
            $obs[] = new Entity([
                'orden' => $i,
                'punto_nom' => (string)(50 + $i),
                'requisito' => 'Obs prueba ' . $i,
            ]);
        }

        $prop = new Entity(['id' => 1, 'nombre' => 'PROPIETARIO PRUEBA F17']);
        $veh = new Entity([
            'id' => 1,
            'tipo_vehiculo' => 'T3',
            'marca' => 'KENWORTH',
            'anio' => 2020,
            'niv' => '1XKAD49X0KJ123456',
            'placas' => 'F17TST',
            'propietario' => $prop,
        ]);

        return new Entity([
            'id' => 900017,
            'tipo_formulario' => 'F17_TRACTO',
            'folio_dictamen' => 'M-PRUEBA-F17',
            'odometro' => 210000,
            'volante_cm' => 45.7,
            'holgura_cm' => 8.0,
            'dictamen' => 'CUMPLE',
            'estatus_registro' => 'ACTIVA',
            'resultado' => 'APROBADO',
            'fecha_inspeccion' => new \DateTimeImmutable('2026-08-03'),
            'fecha_inspeccion_ant' => new \DateTimeImmutable('2025-08-03'),
            'tipo_camara_frenado' => 'CAMARA DE FRENO TIPO ABRAZADERA',
            'camara_abrazadera_mm' => 30,
            'varilla_ll1_mm' => 35,
            'varilla_ll1_resultado' => 'CUMPLE',
            'varilla_ll2_mm' => 35,
            'varilla_ll2_resultado' => 'CUMPLE',
            'varilla_ll1_2_mm' => 35,
            'varilla_ll1_2_resultado' => 'CUMPLE',
            'varilla_ll3_4_mm' => 35,
            'varilla_ll3_4_resultado' => 'CUMPLE',
            'varilla_ll5_6_mm' => 35,
            'varilla_ll5_6_resultado' => 'CUMPLE',
            'varilla_ll7_8_mm' => 35,
            'varilla_ll7_8_resultado' => 'CUMPLE',
            'varilla_ll9_10_mm' => 35,
            'varilla_ll9_10_resultado' => 'CUMPLE',
            'vehiculo' => $veh,
            'tecnico' => new Entity(['id' => 1, 'nombre' => 'TECNICO PRUEBA', 'numero_equipo' => 'EQ-17']),
            'unidades_inspeccion' => new Entity(['id' => 1, 'nombre' => 'UV PRUEBA']),
            'inspeccion_llantas' => $llantas,
            'inspeccion_rines' => $rines,
            'inspeccion_observaciones' => $obs,
            'inspeccion_iluminacion' => $cumple(),
            'inspeccion_chasis' => new Entity([
                'vigas_chasis' => 'CUMPLE',
                'sujetadores_chasis' => 'CUMPLE',
                'traviesas' => 'CUMPLE',
                'combustible_tapon' => 'CUMPLE',
                'combustible_tanque' => 'CUMPLE',
                'combustible_cubierta' => 'CUMPLE',
                'combustible_lineas' => 'CUMPLE',
                'escape_multiple' => 'CUMPLE',
                'escape_mofle' => 'CUMPLE',
                'escape_tubos' => 'CUMPLE',
                'escape_montaje' => 'CUMPLE',
                'bateria' => 'CUMPLE',
                'mangueras_tuberia' => 'CUMPLE',
            ]),
            'inspeccion_suspension' => new Entity(array_fill_keys([
                'pernos_tipo_u', 'brazo_control', 'amortiguadores_delantera', 'amortiguadores',
                'muelles', 'barra_torsion', 'amortiguadores_trasera_2', 'suspension_aire',
                'valvula_proteccion_65psi', 'viga_oscilante', 'salpicaderas',
            ], 'CUMPLE') + ['brazos_torque' => 'CUMPLE']),
            'inspeccion_sistema_aire' => new Entity([
                'compresor_aire' => 'CUMPLE',
                'gobernador' => 'CUMPLE',
                'dispositivo_baja_presion' => 'CUMPLE',
                'mangueras_tuberia' => 'CUMPLE',
                'deposito_aire' => 'CUMPLE',
                'fugas_sistema' => 'CUMPLE',
                'caida_presion_cumple' => 'CUMPLE',
                'caida_presion_psi' => 1.0,
                'tiempo_carga_min' => 1.5,
                'tiempo_carga_cumple' => 'CUMPLE',
                'valvulas_sistema' => 'CUMPLE',
                'valvula_pedal' => 'CUMPLE',
                'valvula_control_remolque' => 'CUMPLE',
                'valvula_liberacion_rapida' => 'CUMPLE',
                'valvulas_relevo_linea_azul' => 'CUMPLE',
                'proteccion_camion' => 'CUMPLE',
                'valvulas_control' => 'CUMPLE',
                'componentes_conexiones' => 'CUMPLE',
                'conexiones_aire_remolque' => 'CUMPLE',
                'conexiones_elec_remolque' => 'CUMPLE',
                'presion_cierre_con_disp' => 90,
                'presion_cierre_sin_disp' => 62,
                'manometro' => 'CUMPLE',
            ]),
            'inspeccion_frenos' => new Entity(array_fill_keys([
                'frenos_abs', 'balatas', 'mecanismo_camara', 'componentes_mecanicos',
                'frenos_tambor', 'frenos_electricos_ret',
            ], 'CUMPLE')),
            'inspeccion_freno' => new Entity(array_fill_keys([
                'frenos_abs', 'balatas', 'mecanismo_camara', 'componentes_mecanicos',
                'frenos_tambor', 'frenos_electricos_ret',
            ], 'CUMPLE')),
            'inspeccion_acoplamiento' => new Entity(array_fill_keys([
                'quinta_rueda', 'deslizadores', 'gancho_pinzon', 'quinta_rueda_oscilante',
                'manija_operacion',
            ], 'CUMPLE')),
            'inspeccion_cabina' => new Entity(array_fill_keys([
                'volante', 'operacion_direccion', 'juego_volante', 'topes_direccion',
                'direccion_telescopica', 'columna_direccion', 'barra_acoplamiento',
                'terminales_direccion', 'brazo_pitman', 'junta_transversal', 'caja_direccion',
                'brazos_torque', 'etiqueta_fabricante', 'visera_sol', 'interruptores',
                'luz_tablero', 'manometro_aire', 'proteccion_camion', 'freno_emergencia',
                'espejos', 'desempanante',
            ], 'CUMPLE')),
        ]);
    }
}
