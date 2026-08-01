<?php
/**
 * Módulo impresión — resumen tabular (carta vertical, Dompdf)
 *
 * @var \Cake\ORM\Entity $inspeccion
 * @var list<array{label: string, value: string}> $filas
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <style>
    @page {
      margin: 12mm;
      size: letter portrait;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 9pt;
      color: #000;
    }
    h1.mi-doc-titulo {
      margin: 0 0 10px 0;
      font-size: 11pt;
      font-weight: bold;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }
  </style>
</head>
<body>
  <h1 class="mi-doc-titulo">Módulo impresión</h1>
  <?= $this->element('Inspeccion/modulo_impresion_bloque', [
      'filas' => $filas,
      'tituloBloque' => null,
  ]) ?>
</body>
</html>
