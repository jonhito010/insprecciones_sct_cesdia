<?php
declare(strict_types=1);

namespace App\Pdf;

/**
 * Limpia HTML exportado con pdf2htmlEX para impresión/PDF (sidebar, carga, scripts).
 */
final class Pdf2htmlExCleaner
{
    public static function quitarChrome(string $html): string
    {
        $html = (string)preg_replace('#<div id="sidebar">.*?</div>\s*#s', '', $html);
        $html = (string)preg_replace('#<div class="loading-indicator">.*?</div>\s*#s', '', $html);
        $html = (string)preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);

        return $html;
    }
}
