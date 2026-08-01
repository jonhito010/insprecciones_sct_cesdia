<?php
declare(strict_types=1);
/**
 * Uso puntual: lee el transcript de Cursor donde está el HTML de marcas y genera config/vehiculo_marcas.php
 */
$transcript = 'C:/Users/jonhi/.cursor/projects/c-xampp-ocho-htdocs-inspecciones-sct/agent-transcripts/4fcf607a-c6bb-4537-961c-af2490b69a28/4fcf607a-c6bb-4537-961c-af2490b69a28.jsonl';
$outFile = dirname(__DIR__) . '/config/vehiculo_marcas.php';

if (!is_readable($transcript)) {
    fwrite(STDERR, "No se encuentra el transcript: {$transcript}\n");
    exit(1);
}

$text = '';
$fh = fopen($transcript, 'rb');
while (($line = fgets($fh)) !== false) {
    if (strpos($line, 'en marcas hacer un select') === false) {
        continue;
    }
    $j = json_decode($line, true);
    if (!is_array($j) || ($j['role'] ?? '') !== 'user') {
        continue;
    }
    $content = $j['message']['content'] ?? [];
    foreach ($content as $block) {
        if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
            $text = $block['text'];
            break 2;
        }
    }
}
fclose($fh);

if ($text === '') {
    fwrite(STDERR, "No se encontró el mensaje con marcas en el transcript.\n");
    exit(1);
}

// Quitar wrapper <user_query> si existe
$text = preg_replace('/^<user_query>\s*/i', '', $text);
$text = preg_replace('/<\/user_query>\s*$/i', '', $text);

$names = [];

$optPos = strpos($text, '<option value=');
$prefix = $optPos === false ? $text : substr($text, 0, $optPos);
$prefix = preg_replace('/<\/option>\s*$/', '', rtrim($prefix));

foreach (preg_split("/\r\n|\n|\r/", $prefix) as $rawLine) {
    $line = trim($rawLine);
    if ($line === '') {
        continue;
    }
    if (strpos($line, '<') !== false) {
        $line = trim(preg_replace('/<.*$/s', '', $line) ?? '');
    }
    if ($line === '') {
        continue;
    }
    if (stripos($line, 'en marcas hacer un select') !== false) {
        if (preg_match('/nombres\s+(.+)$/u', $line, $mm)) {
            $rest = trim($mm[1]);
            if (preg_match('/^(\d+)\|\s*(.+)$/', $rest, $m2)) {
                $names[] = trim($m2[2]);
            } else {
                $names[] = $rest;
            }
        }
        continue;
    }
    if (preg_match('/^(\d+)\|\s*(.+)$/', $line, $m2)) {
        $names[] = trim($m2[2]);
        continue;
    }
    $names[] = $line;
}

if ($optPos !== false) {
    $suffix = substr($text, $optPos);
    if (preg_match_all('/<option[^>]*>([^<]*)<\/option>/u', $suffix, $m)) {
        foreach ($m[1] as $label) {
            $label = trim(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($label !== '') {
                $names[] = $label;
            }
        }
    }
}

$names = array_values(array_unique(array_filter($names, static fn ($n) => $n !== '')));
sort($names, SORT_NATURAL | SORT_FLAG_CASE);

$pairs = [];
foreach ($names as $n) {
    $pairs[$n] = $n;
}

$export = var_export($pairs, true);
$php = <<<PHP
<?php
declare(strict_types=1);
/**
 * Marcas de vehículo (valor enviado = nombre visible, sin IDs numéricos).
 * Generado por bin/build_vehiculo_marcas_from_transcript.php
 */
return {$export};

PHP;

file_put_contents($outFile, $php);
echo 'Escrito ' . count($pairs) . ' marcas en ' . $outFile . PHP_EOL;
