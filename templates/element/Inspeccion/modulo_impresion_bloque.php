<?php
/**
 * Tabla de datos del módulo impresión (reutilizable en PDF y en plantillas HTML).
 *
 * @var \Cake\View\View $this
 * @var list<array{label: string, value: string}> $filas
 * @var string|null $tituloBloque Si no es null ni vacío, se muestra un encabezado sobre la tabla.
 */
$filas = $filas ?? [];
$tituloBloque = $tituloBloque ?? null;
?>
<style>
.cesdia-mi-bloque { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #000; }
.cesdia-mi-titulo {
  margin: 0 0 10px 0;
  font-size: 11pt;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.cesdia-mi-bloque table.modulo {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}
.cesdia-mi-bloque table.modulo td {
  border: 1px solid #000;
  padding: 6px 8px;
  vertical-align: middle;
}
.cesdia-mi-bloque table.modulo td.lbl {
  width: 42%;
  text-align: left;
  text-transform: uppercase;
  font-weight: bold;
  font-size: 8.5pt;
}
.cesdia-mi-bloque table.modulo td.val {
  width: 58%;
  text-align: center;
  text-transform: uppercase;
  font-size: 8.5pt;
  word-wrap: break-word;
}
.cesdia-mi-bloque table.modulo tr.obs td.val {
  text-align: left;
  text-transform: none;
  font-size: 8.25pt;
  line-height: 1.35;
}
</style>
<?php if ($tituloBloque !== null && $tituloBloque !== '') : ?>
  <h2 class="cesdia-mi-titulo"><?= h($tituloBloque) ?></h2>
<?php endif; ?>
<div class="cesdia-mi-bloque">
  <table class="modulo">
    <?php foreach ($filas as $row) :
        $isObs = ($row['label'] === 'OBSERVACIONES');
        ?>
    <tr class="<?= $isObs ? 'obs' : '' ?>">
      <td class="lbl"><?= h($row['label']) ?></td>
      <td class="val"><?= h($row['value']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
