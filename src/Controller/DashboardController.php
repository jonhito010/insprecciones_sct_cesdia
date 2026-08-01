<?php
// src/Controller/DashboardController.php
namespace App\Controller;

class DashboardController extends AppController
{
    public function index(): void
    {
        $inspecciones = $this->fetchTable('Inspecciones');
        $anio = (int)($this->request->getQuery('anio') ?? date('Y'));

        // KPIs generales
        $total      = $inspecciones->find()->count();
        $aprobados  = $inspecciones->find()->where(['resultado' => 'APROBADO'])->count();
        $rechazados = $inspecciones->find()->where(['resultado' => 'RECHAZADO'])->count();
        $cancelados = $inspecciones->find()->where(['resultado' => 'CANCELADO'])->count();

        // Inspecciones del mes actual
        $mes_actual = $inspecciones->find()
            ->where([
                'MONTH(fecha_inspeccion)' => date('n'),
                'YEAR(fecha_inspeccion)'  => date('Y'),
            ])->count();

        // Datos por mes para gráfica
        $porMes = $inspecciones->estadisticasPorMes($anio);

        // Últimas 10 inspecciones
        $recientes = $inspecciones->find()
            ->contain(['Vehiculos', 'Tecnicos'])
            ->orderByDesc('fecha_inspeccion')
            ->limit(10)
            ->all();

        // Top técnicos (GROUP BY en SQL para evitar error con tecnico_id null)
        $topTecnicos = $inspecciones->find()
            ->select([
                'tecnico_id',
                'nombre'  => 'Tecnicos.nombre',
                'total'   => $inspecciones->find()->func()->count('*'),
            ])
            ->join(['Tecnicos' => [
                'table'      => 'tecnicos',
                'type'       => 'LEFT',
                'conditions' => 'Tecnicos.id = Inspecciones.tecnico_id',
            ]])
            ->groupBy(['Inspecciones.tecnico_id', 'Tecnicos.nombre'])
            ->orderByDesc('total')
            ->limit(5)
            ->enableHydration(false)
            ->all()
            ->toArray();

        $mesesLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        // Distribución por tipo de formulario NOM-068
        $porFormularioRows = $inspecciones->find()
            ->select([
                'tipo_formulario',
                'total' => $inspecciones->find()->func()->count('*'),
            ])
            ->groupBy('tipo_formulario')
            ->enableHydration(false)
            ->all()
            ->toArray();
        $porFormulario = [];
        foreach ($porFormularioRows as $r) {
            $porFormulario[(string)($r['tipo_formulario'] ?? '')] = (int)($r['total'] ?? 0);
        }

        $this->set(compact(
            'total', 'aprobados', 'rechazados', 'cancelados',
            'mes_actual', 'porMes', 'recientes', 'topTecnicos',
            'mesesLabels', 'anio', 'porFormulario'
        ));
    }
}
