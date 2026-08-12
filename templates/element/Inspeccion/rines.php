<?php
/**
 * Tabla complementaria de rines (tuercas/birlos/maza/balero).
 * Filas del formato (10/12/8): 1 y 2 sueltas; desde 3 un solo bloque por par (3/4, 5/6…).
 * C2L (4 simples): 1, 2, 3 y 4 sueltas (sin duales).
 * C2L6 / C2 / T2 / B2: 1 y 2 sueltas; 3/4 y 5/6 en pares.
 * Dolly (D1/D2) y Remolque (S1/S2/S3/S4): todos en pares (duales), igual que F-20.
 * Al guardar, el valor del par se escribe en ambas llantas del PDF.
 * Al cambiar el tipo de vehículo, JS re-renderiza bloques (C2L=4, C2L6/T2/C2/B2=6, T3/C3/B3=10…).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 * @var array<string, string> $cumpleOpts
 * @var string $df
 * @var string $tipoFormulario
 */
use App\Validation\Nom068Formato;
use App\Validation\TipoVehiculoRequisitos;

$filasFormato = Nom068Formato::filasTablaComplementaria((string)($tipoFormulario ?? 'F17_TRACTO'));
$tipoVeh = strtoupper(trim((string)($inspeccion->vehiculo?->tipo_vehiculo ?? '')));
// C2L = 4; C2L6/C2/T2/B2 = 6; C3/T3/B3 = 10; D1 = 4; D2 = 8; si aún no hay tipo, usar filas del formato PDF.
$defTipo = $tipoVeh !== '' ? TipoVehiculoRequisitos::definicion($tipoVeh) : null;
$filas = $defTipo !== null
    ? min($filasFormato, (int)$defTipo['llantas'])
    : $filasFormato;
$tiposConLado = TipoVehiculoRequisitos::tiposConLadosVisibles();
$formF19 = ((string)($tipoFormulario ?? '')) === 'F19_REMOLQUE';
// Remolque F-19: S1–S4 en pares (como Dolly).
$forzarParesComoDolly = $formF19;
$esDolly = $forzarParesComoDolly || Nom068Formato::usaParesComoDolly($tipoVeh);
$rinesSueltas = TipoVehiculoRequisitos::rinesTodasSueltas($tipoVeh);
$rinesData = [];
if (!empty($inspeccion->inspeccion_rines)) {
    foreach ($inspeccion->inspeccion_rines as $r) {
        $num = (int)($r->numero_llanta ?? 0);
        if ($num >= 1) {
            $rinesData[$num] = $r;
            continue;
        }
        $par = (string)($r->par_rines ?? '');
        if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $par, $m)) {
            $rinesData[(int)$m[1]] = $r;
            $rinesData[(int)$m[2]] = $r;
        } elseif (preg_match('/^\d{1,2}$/', $par)) {
            $rinesData[(int)$par] = $r;
        }
    }
}

/**
 * Preferir datos de la 1.ª llanta del par; si está vacía, de la 2.ª.
 *
 * @param array<int, mixed> $rinesData
 */
$resolverFila = static function (int $a, ?int $b = null) use ($rinesData) {
    $rowA = $rinesData[$a] ?? null;
    $rowB = ($b !== null) ? ($rinesData[$b] ?? null) : null;
    $tiene = static function ($row): bool {
        if (!$row) {
            return false;
        }
        foreach (['num_sujetadores', 'sujetadores_cumple', 'maza_cumple', 'balero_cumple'] as $c) {
            $v = $row->{$c} ?? null;
            if ($v !== null && $v !== '') {
                return true;
            }
        }

        return false;
    };

    return $tiene($rowA) ? $rowA : ($rowB ?? $rowA);
};

/** @return list<array{nums:list<int>, titulo:string}> */
$bloques = [];
for ($i = 1; $i <= $filas; $i++) {
    // C2L (simples) o delanteras motriz: filas sueltas.
    if ($rinesSueltas || (!$esDolly && $i <= 2)) {
        $bloques[] = [
            'nums' => [$i],
            'titulo' => TipoVehiculoRequisitos::etiquetaRinFila($tipoVeh, $i),
        ];
        continue;
    }
    if ($i % 2 === 0) {
        continue; // la par se emite con el impar
    }
    $b = min($i + 1, $filas);
    // Dolly: solo "Llanta #N / #M" (sin lados). Motriz: con izq/der.
    if ($esDolly) {
        $titulo = 'Llanta #' . $i . ' / #' . $b;
    } else {
        $ladoA = TipoVehiculoRequisitos::etiquetaPosicionVisible($tipoVeh, $i, 'EXTERNA');
        $ladoB = TipoVehiculoRequisitos::etiquetaPosicionVisible($tipoVeh, $b, 'EXTERNA');
        $conLado = in_array($tipoVeh, $tiposConLado, true);
        $titulo = $conLado
            ? ('Llanta #' . $i . ' / #' . $b . ' — ' . $ladoA . ' · ' . $ladoB)
            : ('Llanta #' . $i . ' / #' . $b);
    }
    $bloques[] = ['nums' => [$i, $b], 'titulo' => $titulo];
}

$dfCls = trim((string)$df);
$rinesSeed = [];
foreach ($rinesData as $num => $row) {
    $rinesSeed[(int)$num] = [
        'id' => !empty($row->id) ? (int)$row->id : null,
        'num_sujetadores' => $row->num_sujetadores ?? '',
        'sujetadores_cumple' => $row->sujetadores_cumple ?? '',
        'maza_cumple' => $row->maza_cumple ?? '',
        'balero_cumple' => $row->balero_cumple ?? '',
    ];
}
?>
<div class="cesdia-card" style="margin-bottom:1.2rem;" id="cesdia-rines-root"
     data-filas-formato="<?= (int)$filasFormato ?>"
     data-tipo-formulario="<?= h((string)($tipoFormulario ?? '')) ?>"
     data-df="<?= h($dfCls) ?>">
  <div class="card-header">
    <span class="card-header-title" id="cesdia-rines-titulo">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
      Tuercas / Birlos / Maza / Balero (<?= (int)$filas ?> filas<?= $filas < $filasFormato ? ' de captura / ' . (int)$filasFormato . ' en PDF' : ' del formato' ?>)
    </span>
  </div>
  <div class="card-body" id="cesdia-rines-body">
    <p style="font-size:12px;color:var(--gmuted);margin:0 0 0.75rem;" id="cesdia-rines-ayuda">
      <?php if ($esDolly) : ?>
        Dolly: un bloque por par de duales (1/2, 3/4…). Mismo valor en ambas filas del PDF.
      <?php elseif ($rinesSueltas) : ?>
        C2L (4 llantas simples): 1, 2, 3 y 4 van sueltas (sin pares).
      <?php else : ?>
        Llantas 1 y 2 van sueltas. Desde la 3, un solo bloque por par (mismo valor en ambas filas del PDF).
      <?php endif; ?>
      Al capturar la <strong>llanta 1</strong>, el valor se copia al resto para un llenado rápido (luego puedes ajustar cada fila).
      Filas vacías se imprimen en blanco.
    </p>
    <div id="cesdia-rines-bloques">
    <?php foreach ($bloques as $bloque) :
        $nums = $bloque['nums'];
        $prim = $nums[0];
        $seg = $nums[1] ?? null;
        $row = $resolverFila($prim, $seg);
        $idxPrim = $prim - 1;
        $idxSeg = $seg !== null ? ($seg - 1) : null;
        $rowIdPrim = ($row && !empty($row->id) && (int)($row->numero_llanta ?? 0) === $prim)
            ? (int)$row->id
            : null;
        $rowSeg = $seg !== null ? ($rinesData[$seg] ?? null) : null;
        $rowIdSeg = ($rowSeg && !empty($rowSeg->id) && (int)($rowSeg->numero_llanta ?? 0) === $seg)
            ? (int)$rowSeg->id
            : null;
        $grupo = 'cesdia-rin-grupo-' . $prim;
    ?>
    <div class="cesdia-section" style="margin-bottom:0.65rem;" data-rin-grupo="<?= (int)$prim ?>">
      <div class="sec-head"><span class="sec-head-title"><?= h($bloque['titulo']) ?></span></div>
      <div class="sec-body">
        <div class="cesdia-grid-4">
          <?php if ($rowIdPrim) : ?>
            <?= $this->Form->hidden("inspeccion_rines.{$idxPrim}.id", ['value' => $rowIdPrim]) ?>
          <?php endif; ?>
          <?= $this->Form->hidden("inspeccion_rines.{$idxPrim}.numero_llanta", ['value' => (string)$prim]) ?>
          <?php if ($idxSeg !== null) : ?>
            <?php if ($rowIdSeg) : ?>
              <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.id", ['value' => $rowIdSeg]) ?>
            <?php endif; ?>
            <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.numero_llanta", ['value' => (string)$seg]) ?>
          <?php endif; ?>

          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines.{$idxPrim}.num_sujetadores", [
              'label' => ['text' => 'Tuercas/birlos #', 'class' => 'cesdia-label'],
              'type' => 'number',
              'min' => 0,
              'max' => 99,
              'class' => 'cesdia-input cesdia-rin-campo' . $df,
              'data-rin-grupo' => $grupo,
              'data-rin-campo' => 'num_sujetadores',
              'value' => $row ? ($row->num_sujetadores ?? '') : '',
            ]) ?>
            <?php if ($idxSeg !== null) : ?>
              <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.num_sujetadores", [
                'class' => 'cesdia-rin-espejo',
                'data-rin-grupo' => $grupo,
                'data-rin-campo' => 'num_sujetadores',
                'value' => $row ? ($row->num_sujetadores ?? '') : '',
              ]) ?>
            <?php endif; ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines.{$idxPrim}.sujetadores_cumple", [
              'label' => ['text' => 'Sujetadores', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select cesdia-rin-campo' . $df,
              'data-rin-grupo' => $grupo,
              'data-rin-campo' => 'sujetadores_cumple',
              'value' => $row ? ($row->sujetadores_cumple ?? '') : '',
            ]) ?>
            <?php if ($idxSeg !== null) : ?>
              <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.sujetadores_cumple", [
                'class' => 'cesdia-rin-espejo',
                'data-rin-grupo' => $grupo,
                'data-rin-campo' => 'sujetadores_cumple',
                'value' => $row ? ($row->sujetadores_cumple ?? '') : '',
              ]) ?>
            <?php endif; ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines.{$idxPrim}.maza_cumple", [
              'label' => ['text' => 'Maza (limpia / chorreada)', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select cesdia-rin-campo' . $df,
              'data-rin-grupo' => $grupo,
              'data-rin-campo' => 'maza_cumple',
              'value' => $row ? ($row->maza_cumple ?? '') : '',
            ]) ?>
            <?php if ($idxSeg !== null) : ?>
              <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.maza_cumple", [
                'class' => 'cesdia-rin-espejo',
                'data-rin-grupo' => $grupo,
                'data-rin-campo' => 'maza_cumple',
                'value' => $row ? ($row->maza_cumple ?? '') : '',
              ]) ?>
            <?php endif; ?>
          </div>
          <div class="cesdia-form-group">
            <?= $this->Form->control("inspeccion_rines.{$idxPrim}.balero_cumple", [
              'label' => ['text' => 'Balero buen estado', 'class' => 'cesdia-label'],
              'options' => $cumpleOpts,
              'empty' => '--',
              'class' => 'cesdia-select cesdia-rin-campo' . $df,
              'data-rin-grupo' => $grupo,
              'data-rin-campo' => 'balero_cumple',
              'value' => $row ? ($row->balero_cumple ?? '') : '',
            ]) ?>
            <?php if ($idxSeg !== null) : ?>
              <?= $this->Form->hidden("inspeccion_rines.{$idxSeg}.balero_cumple", [
                'class' => 'cesdia-rin-espejo',
                'data-rin-grupo' => $grupo,
                'data-rin-campo' => 'balero_cumple',
                'value' => $row ? ($row->balero_cumple ?? '') : '',
              ]) ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
(function () {
  var seed = <?= json_encode($rinesSeed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?> || {};

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function cumpleOptsHtml(selected) {
    var opts = window.CESDIA_CUMPLE_OPTS || {};
    var html = '<option value="">--</option>';
    Object.keys(opts).forEach(function (k) {
      html += '<option value="' + esc(k) + '"' + (String(selected) === String(k) ? ' selected' : '') + '>' + esc(opts[k]) + '</option>';
    });
    return html;
  }

  function collectRinesMap(root) {
    var map = Object.assign({}, seed);
    if (!root) return map;
    root.querySelectorAll('[name^="inspeccion_rines["]').forEach(function (el) {
      var m = String(el.name || '').match(/^inspeccion_rines\[(\d+)\]\[(\w+)\]$/);
      if (!m) return;
      var num = parseInt(m[1], 10) + 1;
      var campo = m[2];
      if (!map[num]) map[num] = {};
      map[num][campo] = el.value;
    });
    return map;
  }

  function tituloBloque(tipo, nums, formTipo) {
    var tiposPares = ['D1', 'D2', 'DL', 'DOLLY', 'S1', 'S2', 'S3', 'S4'];
    var esDolly = tiposPares.indexOf(tipo) !== -1
      || formTipo === 'F19_REMOLQUE';
    if (esDolly) {
      if (nums.length === 1) return 'Llanta #' + nums[0];
      return 'Llanta #' + nums[0] + ' / #' + nums[1];
    }
    var tiposConLado = window.CESDIA_TIPOS_CON_LADOS || window.CESDIA_TIPOS_MOTRIZ || [];
    var posDef = window.CESDIA_POSICION_MOTRIZ || {};
    var posPorTipo = (window.CESDIA_POSICION_POR_TIPO || {})[tipo] || null;
    function ladoDe(n) {
      if (posPorTipo && posPorTipo[String(n)]) return posPorTipo[String(n)];
      return posDef[String(n)] || '';
    }
    var conLado = tiposConLado.indexOf(tipo) !== -1;
    if (nums.length === 1) {
      var lado = conLado ? ladoDe(nums[0]) : null;
      return lado ? ('Llanta #' + nums[0] + ' — ' + lado) : ('Llanta #' + nums[0]);
    }
    if (conLado) {
      return 'Llanta #' + nums[0] + ' / #' + nums[1] + ' — ' + ladoDe(nums[0]) + ' · ' + ladoDe(nums[1]);
    }
    return 'Llanta #' + nums[0] + ' / #' + nums[1];
  }

  function buildBloques(filas, esDolly, todasSueltas) {
    var out = [];
    for (var i = 1; i <= filas; i++) {
      if (todasSueltas || (!esDolly && i <= 2)) {
        out.push([i]);
        continue;
      }
      if (i % 2 === 0) continue;
      out.push([i, Math.min(i + 1, filas)]);
    }
    return out;
  }

  function campoGrupo(idxPrim, idxSeg, grupo, campo, label, esSelect, val, dfCls) {
    var html = '<div class="cesdia-form-group">';
    html += '<label class="cesdia-label" for="inspeccion-rines-' + idxPrim + '-' + campo + '">' + esc(label) + '</label>';
    if (esSelect) {
      html += '<select name="inspeccion_rines[' + idxPrim + '][' + campo + ']" class="cesdia-select cesdia-rin-campo' + dfCls + '" data-rin-grupo="' + grupo + '" data-rin-campo="' + campo + '">';
      html += cumpleOptsHtml(val) + '</select>';
    } else {
      html += '<input type="number" min="0" max="99" name="inspeccion_rines[' + idxPrim + '][' + campo + ']" class="cesdia-input cesdia-rin-campo' + dfCls + '" data-rin-grupo="' + grupo + '" data-rin-campo="' + campo + '" value="' + esc(val) + '">';
    }
    if (idxSeg !== null) {
      html += '<input type="hidden" name="inspeccion_rines[' + idxSeg + '][' + campo + ']" class="cesdia-rin-espejo" data-rin-grupo="' + grupo + '" data-rin-campo="' + campo + '" value="' + esc(val) + '">';
    }
    html += '</div>';
    return html;
  }

  function bindEspejos(root) {
    function syncEspejo(campo) {
      var grupo = campo.getAttribute('data-rin-grupo');
      var nombre = campo.getAttribute('data-rin-campo');
      if (!grupo || !nombre) return;
      root.querySelectorAll('.cesdia-rin-espejo[data-rin-grupo="' + grupo + '"][data-rin-campo="' + nombre + '"]').forEach(function (esp) {
        esp.value = campo.value;
      });
    }
    /** Llanta/grupo 1 → copia a todos los demás bloques (llenado rápido). */
    function syncDesdePrimero(campo) {
      var grupo = campo.getAttribute('data-rin-grupo');
      var nombre = campo.getAttribute('data-rin-campo');
      if (!grupo || !nombre || grupo !== 'cesdia-rin-grupo-1') return;
      var val = campo.value;
      root.querySelectorAll('.cesdia-rin-campo[data-rin-campo="' + nombre + '"]').forEach(function (otro) {
        if (otro === campo) return;
        if (otro.value === val) {
          syncEspejo(otro);
          return;
        }
        otro.value = val;
        if (otro.tagName === 'SELECT') {
          otro.dispatchEvent(new Event('change', { bubbles: true }));
        }
        syncEspejo(otro);
      });
    }
    function onCampo(el) {
      syncEspejo(el);
      syncDesdePrimero(el);
    }
    root.querySelectorAll('.cesdia-rin-campo').forEach(function (el) {
      el.addEventListener('input', function () { onCampo(el); });
      el.addEventListener('change', function () { onCampo(el); });
      syncEspejo(el);
    });
  }

  function renderRines(tipo) {
    var root = document.getElementById('cesdia-rines-root');
    var wrap = document.getElementById('cesdia-rines-bloques');
    var titulo = document.getElementById('cesdia-rines-titulo');
    if (!root || !wrap) return;

    var filasFormato = parseInt(root.getAttribute('data-filas-formato') || String(window.CESDIA_FILAS_RINES_FORMATO || 10), 10) || 10;
    var mapa = window.CESDIA_LLANTAS_POR_TIPO || {};
    var t = String(tipo || '').toUpperCase().trim();
    var llantas = t && mapa[t] != null ? parseInt(mapa[t], 10) : filasFormato;
    var filas = Math.min(filasFormato, llantas || filasFormato);
    var dfCls = root.getAttribute('data-df') || '';
    if (dfCls && dfCls.charAt(0) !== ' ') dfCls = ' ' + dfCls;

    var prev = collectRinesMap(root);
    seed = prev;

    if (titulo) {
      var nota = filas < filasFormato
        ? (filas + ' filas de captura / ' + filasFormato + ' en PDF')
        : (filas + ' filas del formato');
      titulo.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg> Tuercas / Birlos / Maza / Balero (' + nota + ')';
    }

    var formTipo = String(root.getAttribute('data-tipo-formulario') || '').toUpperCase().trim();
    var esDolly = ['D1', 'D2', 'DL', 'DOLLY', 'S1', 'S2', 'S3', 'S4'].indexOf(t) !== -1
      || formTipo === 'F19_REMOLQUE';
    var todasSueltas = t === 'C2L';
    var ayuda = document.getElementById('cesdia-rines-ayuda');
    if (ayuda) {
      var ayudaBase = esDolly
        ? 'Remolque/Dolly: un bloque por par de duales (1/2, 3/4…). Mismo valor en ambas filas del PDF. '
        : (todasSueltas
          ? 'C2L (4 llantas simples): 1, 2, 3 y 4 van sueltas (sin pares). '
          : 'Llantas 1 y 2 van sueltas. Desde la 3, un solo bloque por par (mismo valor en ambas filas del PDF). ');
      ayuda.innerHTML = ayudaBase
        + 'Al capturar la <strong>llanta 1</strong>, el valor se copia al resto para un llenado rápido (luego puedes ajustar cada fila). '
        + 'Filas vacías se imprimen en blanco.';
    }

    var html = '';
    buildBloques(filas, esDolly, todasSueltas).forEach(function (nums) {
      var prim = nums[0];
      var seg = nums.length > 1 ? nums[1] : null;
      var idxPrim = prim - 1;
      var idxSeg = seg !== null ? seg - 1 : null;
      var grupo = 'cesdia-rin-grupo-' + prim;
      var rowA = prev[prim] || {};
      var rowB = seg !== null ? (prev[seg] || {}) : {};
      function tiene(r) {
        return r && (r.num_sujetadores || r.sujetadores_cumple || r.maza_cumple || r.balero_cumple);
      }
      var row = tiene(rowA) ? rowA : (tiene(rowB) ? rowB : rowA);

      html += '<div class="cesdia-section" style="margin-bottom:0.65rem;" data-rin-grupo="' + prim + '">';
      html += '<div class="sec-head"><span class="sec-head-title">' + esc(tituloBloque(t, nums, formTipo)) + '</span></div>';
      html += '<div class="sec-body"><div class="cesdia-grid-4">';
      if (rowA.id) {
        html += '<input type="hidden" name="inspeccion_rines[' + idxPrim + '][id]" value="' + esc(rowA.id) + '">';
      }
      html += '<input type="hidden" name="inspeccion_rines[' + idxPrim + '][numero_llanta]" value="' + prim + '">';
      if (idxSeg !== null) {
        if (rowB.id) {
          html += '<input type="hidden" name="inspeccion_rines[' + idxSeg + '][id]" value="' + esc(rowB.id) + '">';
        }
        html += '<input type="hidden" name="inspeccion_rines[' + idxSeg + '][numero_llanta]" value="' + seg + '">';
      }
      html += campoGrupo(idxPrim, idxSeg, grupo, 'num_sujetadores', 'Tuercas/birlos #', false, row.num_sujetadores || '', dfCls);
      html += campoGrupo(idxPrim, idxSeg, grupo, 'sujetadores_cumple', 'Sujetadores', true, row.sujetadores_cumple || '', dfCls);
      html += campoGrupo(idxPrim, idxSeg, grupo, 'maza_cumple', 'Maza (limpia / chorreada)', true, row.maza_cumple || '', dfCls);
      html += campoGrupo(idxPrim, idxSeg, grupo, 'balero_cumple', 'Balero buen estado', true, row.balero_cumple || '', dfCls);
      html += '</div></div></div>';
    });
    wrap.innerHTML = html;
    bindEspejos(root);
  }

  window.cesdiaRenderRines = renderRines;

  document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('cesdia-rines-root');
    if (!root) return;
    bindEspejos(root);
    var tipoSel = document.getElementById('cesdia-tipo-vehiculo');
    if (tipoSel && String(tipoSel.value || '').trim() !== '') {
      renderRines(tipoSel.value);
    }
  });
})();
</script>
