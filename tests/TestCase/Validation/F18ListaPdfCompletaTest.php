<?php
declare(strict_types=1);

namespace App\Test\TestCase\Validation;

use App\Validation\F18ListaPdfValidador;
use App\Validation\Nom068Formato;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Prueba completa F-18: estructura del PDF lista vs formato oficial.
 */
class F18ListaPdfCompletaTest extends TestCase
{
    public function testNom068FormatoFilasYParesF18(): void
    {
        $this->assertSame(10, Nom068Formato::filasTablaComplementaria('F18_CAMION'));
        $pares = Nom068Formato::paresVarilla('F18_CAMION');
        $this->assertArrayHasKey('1', $pares);
        $this->assertArrayHasKey('2', $pares);
        $this->assertArrayHasKey('3-4', $pares);
        $this->assertArrayHasKey('9-10', $pares);
        $this->assertArrayNotHasKey('1-2', $pares);
    }

    public function testListaPdfContieneTodaLaInformacionOficial(): void
    {
        $html = $this->renderListaHtml($this->inspeccionF18Completa());

        $this->assertNotSame('', $html);
        $this->assertStringContainsString('F-18', $html);

        $fallas = F18ListaPdfValidador::fallas(F18ListaPdfValidador::validar($html, 'C3'));
        $this->assertSame(
            [],
            $fallas,
            "PDF F-18 C3 incompleto:\n - " . implode("\n - ", $fallas)
        );
        $this->assertStringContainsString('FRENOS NEUM', $html);
        $this->assertStringNotContainsString('FRENOS HIDRÁULICOS ASISTIDOS', $html);
    }

    public function testListaPdfC2LSoloHidraulicos(): void
    {
        $insp = $this->inspeccionF18Completa();
        $veh = $insp->vehiculo;
        $veh->set('tipo_vehiculo', 'C2L');
        $insp->set('vehiculo', $veh);

        $html = $this->renderListaHtml($insp);
        $fallas = F18ListaPdfValidador::fallas(F18ListaPdfValidador::validar($html, 'C2L'));
        $this->assertSame(
            [],
            $fallas,
            "PDF F-18 C2L incompleto:\n - " . implode("\n - ", $fallas)
        );
        $this->assertStringContainsString('FRENOS HIDRÁULICOS', $html);
        $this->assertStringNotContainsString('FRENOS NEUMÁTICOS', $html);
        $this->assertStringNotContainsString('MEDICIONES COMPLEMENTARIAS', $html);
        $this->assertStringNotContainsString('CÁMARA FREN', $html);
        $this->assertStringNotContainsString('VARILLA<br/>cm', $html);
    }

    public function testListaPdfC2SoloNeumaticos(): void
    {
        $insp = $this->inspeccionF18Completa();
        $veh = $insp->vehiculo;
        $veh->set('tipo_vehiculo', 'C2');
        $insp->set('vehiculo', $veh);

        $html = $this->renderListaHtml($insp);
        $fallas = F18ListaPdfValidador::fallas(F18ListaPdfValidador::validar($html, 'C2'));
        $this->assertSame(
            [],
            $fallas,
            "PDF F-18 C2 incompleto:\n - " . implode("\n - ", $fallas)
        );
        $this->assertStringContainsString('FRENOS NEUM', $html);
        $this->assertStringNotContainsString('FRENOS HIDRÁULICOS ASISTIDOS', $html);
    }

    public function testValidadorDetectaHtmlVacio(): void
    {
        $fallas = F18ListaPdfValidador::fallas(F18ListaPdfValidador::validar('<html></html>'));
        $this->assertNotEmpty($fallas);
        $this->assertFalse(F18ListaPdfValidador::esCompleto('<html></html>'));
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
        $view->setTemplate('pdf_lista_f18');
        $view->set([
            'inspeccion' => $inspeccion,
            'tipoFormulario' => 'F18_CAMION',
            'logoDataUri' => '',
            'firmaDataUri' => '',
        ]);

        return $view->render();
    }

    private function inspeccionF18Completa(): Entity
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
                'requisito' => 'Obs prueba F18 ' . $i,
            ]);
        }

        $prop = new Entity(['id' => 1, 'nombre' => 'PROPIETARIO PRUEBA F18']);
        $veh = new Entity([
            'id' => 1,
            'tipo_vehiculo' => 'C3',
            'marca' => 'INTERNATIONAL',
            'anio' => 2021,
            'niv' => '1HTMMAAL0KH123456',
            'placas' => 'F18TST',
            'propietario' => $prop,
        ]);

        return new Entity([
            'id' => 900018,
            'tipo_formulario' => 'F18_CAMION',
            'folio_dictamen' => 'M-PRUEBA-F18',
            'odometro' => 150000,
            'volante_cm' => 40.6,
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
            'tecnico' => new Entity(['id' => 1, 'nombre' => 'TECNICO PRUEBA', 'numero_equipo' => 'EQ-18']),
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
                'gas_lp_soporte' => 'CUMPLE',
                'gas_lp_etiqueta' => 'CUMPLE',
                'gas_lp_condicion' => 'CUMPLE',
                'gas_lp_cinchos' => 'CUMPLE',
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
                'valvula_proteccion_65psi', 'salpicaderas', 'brazos_torque',
            ], 'CUMPLE')),
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
                'valvula_liberacion_rapida' => 'CUMPLE',
                'valvulas_relevo_linea_azul' => 'CUMPLE',
                'proteccion_camion' => 'CUMPLE',
                'valvulas_control' => 'CUMPLE',
                'componentes_conexiones' => 'CUMPLE',
                'manometro' => 'CUMPLE',
            ]),
            'inspeccion_frenos' => new Entity(array_fill_keys([
                'frenos_abs', 'balatas', 'mecanismo_camara', 'componentes_mecanicos',
                'frenos_tambor', 'freno_estacionamiento', 'hid_luz_indicadora',
                'hid_cables_acoplamiento', 'estac_balata', 'hid_libera_hidraulico',
                'hid_recorrido', 'hid_indicador_advertencia', 'hid_deposito_liquido',
                'hid_pedal', 'hid_lineas_mangueras', 'hid_valvulas_unidirec',
                'hid_abrazaderas', 'hid_booster', 'hid_reserva_vacio', 'hid_bomba_vacio',
                'hid_liquido_condicion', 'hid_cilindros', 'hid_tambores',
                'hid_disco', 'hid_calipers', 'hid_pastas_freno',
            ], 'CUMPLE')),
            'inspeccion_cabina' => new Entity(array_fill_keys([
                'volante', 'operacion_direccion', 'juego_volante', 'topes_direccion',
                'direccion_telescopica', 'columna_direccion', 'barra_acoplamiento',
                'terminales_direccion', 'brazo_pitman', 'junta_transversal', 'caja_direccion',
                'etiqueta_fabricante', 'visera_sol', 'interruptores',
                'luz_tablero', 'manometro_aire', 'proteccion_camion', 'freno_emergencia',
                'espejos', 'desempanante',
            ], 'CUMPLE')),
        ]);
    }
}
