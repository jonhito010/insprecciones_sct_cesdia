# P3.4 · Evaluación catálogo `nom_conceptos` (opcional)

## Decisión

**Omitido en esta ejecución** (no se crea tabla aún).

## Motivo

- El plan lo marca como *opcional recomendado*.
- Hoy la fuente de verdad textual son `webroot/camposTXT/*.txt` + `docs/validacion/CLAUDE_F-*.md` + hardcode en `pdf_lista.php`.
- Introducir `nom_conceptos` sin un import automático desde los TXT arriesga una tercera fuente y más divergencia.

## Recomendación futura

1. Importar desde `camposTXT` a `nom_conceptos` (`formato`, `punto_nom`, `seccion`, `orden`, `concepto`, `tipo_inspeccion`).
2. Hacer que `pdf_lista.php` lea el catálogo (con fallback al hardcode actual).
3. Validar conteos por formato en tests.
