<?php
/**
 * Editar inspección — reutiliza el formulario de `add.php`.
 * El controlador ya envía la misma entidad y catálogos; `add.php` detecta edición con `isset($inspeccion->id)`.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Inspeccion $inspeccion
 */

include __DIR__ . DIRECTORY_SEPARATOR . 'add.php';
