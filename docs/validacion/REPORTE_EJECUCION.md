# REPORTE_EJECUCION.md · Plan corrección NOM-068

> Ejecución continua P0→P3 (sin pausas). Fecha: 2026-08-01.  
> Repo git inicializado en `inspecciones_sct/` (no existía previamente). Baseline: `28c8d91`.

## 1. Tabla de sub-tareas

| Código | Descripción | Estado | Commit |
|---|---|---|---|
| P0.1 | Separar columnas carrocería F-19 | **hecho** | `39819f2` |
| P1.1 | Dictamen CUMPLE/NO CUMPLE + estatus registro | **hecho** | `ca17420` |
| P1.2 | Observaciones estructuradas 6 filas | **hecho** | `7a1d9b9` |
| P1.3 | Volante / Holgura cm | **hecho** | `c4ef010` |
| P2.1 | Etiquetas LLANTA 7 y 8 (F-21) | **hecho** | `4b78fdf` |
| P2.2 | PDF filas completas 10/12/8 | **hecho** | `c35cb1a` |
| P2.3 | Dolly D1=4 / D2=8 mediciones | **hecho** | `2cbe4b6` (código en `4b78fdf`) |
| P2.4 | Folio F-19 `A-{consecutivo}` impreso | **hecho** | `a8f9bbd` (lógica en `Nom068Formato` / pdf) |
| P2.5 | UI `inspeccion_rines` | **hecho** | `2f99dcc` |
| P3.1 | Conceptos faltantes (luces, mangueras, válvula) | **hecho** | `25cd7ec` |
| P3.2 | Ocultar sobrantes + hidráulicos F-18 | **hecho** | `932e471` (+ frenos en `25cd7ec`) |
| P3.3 | Entidad/captura `ordenes_servicio` F-04 | **hecho** | `efe480c` |
| P3.4 | Catálogo `nom_conceptos` | **omitido** (evaluación) | `0afea42` → `docs/validacion/EVAL_NOM_CONCEPTOS_P34.md` |

## 2. Migraciones SQL

| Script | Rollback | Aplicada a BD |
|---|---|---|
| `config/schema/alter_inspeccion_carroceria_secciones_p01.sql` | `rollback/alter_inspeccion_carroceria_secciones_p01.sql` | **Pendiente** |
| `config/schema/alter_inspecciones_dictamen_estatus_p11.sql` | `rollback/alter_inspecciones_dictamen_estatus_p11.sql` | **Pendiente** |
| `config/schema/create_inspeccion_observaciones_p12.sql` | `rollback/create_inspeccion_observaciones_p12.sql` | **Pendiente** |
| `config/schema/alter_inspecciones_volante_holgura_p13.sql` | `rollback/alter_inspecciones_volante_holgura_p13.sql` | **Pendiente** |
| `config/schema/create_ordenes_servicio_p33.sql` | `rollback/create_ordenes_servicio_p33.sql` | **Pendiente** |

**Motivo:** MySQL no estaba accesible en `127.0.0.1:3306` durante la ejecución (`ERROR 2003`).

**Cómo aplicar:**

```bash
cd /var/www/html/inspecciones_sct
# Opción A — comando Cake (tras levantar MySQL):
php bin/cake.php patch_nom068_schema

# Opción B — manual:
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspeccion_carroceria_secciones_p01.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspecciones_dictamen_estatus_p11.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/create_inspeccion_observaciones_p12.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspecciones_volante_holgura_p13.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/create_ordenes_servicio_p33.sql
# Luego limpiar tmp/cache/models/*
```

Hasta aplicar migraciones: la UI de dictamen/observaciones/volante/F-04 cae a fallbacks (p. ej. resultado legacy si no existe columna `dictamen`).

## 3. Archivos modificados por fase

### P0
- `config/schema/alter_inspeccion_carroceria_secciones_p01.sql` (+ rollback)
- `templates/element/Inspeccion/carroceria.php`
- `src/Model/Table/InspeccionCarroceriaTable.php`
- `templates/Inspecciones/pdf_lista.php`
- `src/Pdf/RemolqueHtmlPdf.php`

### P1
- Schema P1.1/P1.2/P1.3 (+ rollbacks)
- `src/Model/Table/InspeccionesTable.php`, `InspeccionObservacionesTable.php`
- `src/Model/Entity/Inspeccion.php`
- `src/Controller/InspeccionesController.php`
- `src/Model/Table/SubtablasInspeccion.php`
- `templates/element/Inspeccion/resultado.php`
- `templates/Inspecciones/{add,index,pdf_lista}.php`

### P2
- `src/Validation/Nom068Formato.php` (nuevo)
- `src/Validation/TipoVehiculoRequisitos.php`
- `tests/TestCase/Validation/TipoVehiculoRequisitosTest.php`
- `templates/Inspecciones/pdf_lista.php`
- `templates/element/Inspeccion/rines.php` (nuevo)
- `templates/element/Inspeccion/formularios/f17…f21_*.php`
- `templates/Inspecciones/add.php`

### P3
- `templates/element/Inspeccion/{iluminacion,frenos,chasis_aire,acoplamiento,cabina}.php`
- `config/schema/create_ordenes_servicio_p33.sql` (+ rollback)
- `src/Model/{Table/Entity}/Orden*`, `OrdenesServicioController`, templates F-04
- `config/routes.php`, `templates/Inspecciones/pdf.php`
- `src/Command/PatchNom068SchemaCommand.php`
- `docs/validacion/EVAL_NOM_CONCEPTOS_P34.md`

## 4. Decisiones ante ambigüedad del plan

1. **Dictamen vs `resultado`:** se agregaron `dictamen` + `estatus_registro` sin borrar `resultado`. `beforeMarshal` sincroniza `resultado` legacy (APROBADO/RECHAZADO/CANCELADO) para no romper listados/filtros antiguos.
2. **Observaciones libres:** el textarea `observaciones` se mantiene como *notas internas* y **no se imprime** en el PDF; las 6 filas estructuradas sí.
3. **Dolly P2.3:** captura D1=4 / D2=8 filas numeradas `LLANTA 1…8` (EXTERNA); el PDF F-20 siempre imprime 8 filas (vacías si faltan). Se abandonaron los 3 grupos checklist como único esquema de captura.
4. **F-18 `proteccion_camion`:** quitada del bloque aire/frenos; **conservada en CABINA** (igual F-17), conforme a la corrección #2 del plan.
5. **P3.4 `nom_conceptos`:** omitido a propósito; ver evaluación.
6. **Git:** se inicializó repo local solo para poder cumplir commits por sub-tarea (no había `.git`).

## 5. Pendientes / riesgos

- **Migraciones pendientes** hasta levantar MySQL y correr `patch_nom068_schema`.
- Scripts con `DELIMITER` / `CALL` pueden requerir cliente `mysql` CLI (el comando Cake intenta `mysqli::multi_query`; si falla, usar CLI).
- Inspecciones Dolly antiguas con patrón 3 grupos (1/5/7) pueden verse como filas “extra” al editar hasta normalizar.
- `P2.3`/`P2.4` tienen commits `--allow-empty` de trazabilidad; el código útil está en commits anteriores citados.
- Autorización menú F-04: ruta `/ordenes-servicio` disponible; puede faltar enlace en el layout/nav.
- Tests PHPUnit no ejecutados aquí (sin vendor en sandbox / BD caída).

## 6. Instrucciones para probar

1. Levantar MySQL y aplicar migraciones (`php bin/cake.php patch_nom068_schema`).
2. Limpiar `tmp/cache/models/` si el comando no lo hizo.
3. Crear **una inspección por formato**:
   - F-17 T3, F-18 C3, F-19 S3, F-20 D2, F-21 AB.
4. En cada una verificar:
   - Folio M/A correcto; F-19 PDF muestra `A-…`.
   - Dictamen radio CUMPLE/NO CUMPLE + estatus ACTIVA/CANCELADA.
   - 6 filas de observaciones.
   - F-17/18/21: VOLANTE/HOLGURA cm.
   - F-19: carrocería con valores distintos en grano/grava/otro sin pisarse.
   - F-21: etiquetas LLANTA 7 y LLANTA 8.
   - Dolly: 8 (D2) o 4 (D1) filas de llanta + rines.
   - Tabla rines capturable.
5. Generar PDFs:
   - `/inspecciones/{id}/pdf` (F-04 orden) — con orden ligada debe usar `fecha_contrato`.
   - `/inspecciones/{id}/pdf-lista` (lista NOM) — verificar pie 10/12/8 filas y dictamen CUMPLE/NO CUMPLE.
6. Crear una orden en `/ordenes-servicio/add`, ligarla a una inspección y regenerar PDF F-04.
