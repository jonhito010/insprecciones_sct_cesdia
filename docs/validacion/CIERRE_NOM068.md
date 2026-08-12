# CIERRE_NOM068.md · Pasos pendientes post-ejecución

> Derivado de `REPORTE_EJECUCION.md` (2026-08-01). Las fases P0→P3 se ejecutaron en código,
> pero las migraciones NO se aplicaron a la BD (MySQL estaba caído: ERROR 2003).
> Este documento cubre lo que falta para dar por cerrado el trabajo.
>
> Orden estricto: PASO 1 → 2 → 3 → 4 → 5 → 6 → 7. No saltarse el respaldo.

---

## PASO 1 — Respaldo de BD (manual, NO negociable)

```bash
# Verificar que MySQL esté arriba; si no, levantarlo
systemctl status mysql
sudo systemctl start mysql   # si estaba caído

# Respaldo completo antes de tocar nada
mysqldump -uadmin -p inspecciones > ~/respaldo_pre_nom068_$(date +%Y%m%d_%H%M).sql

# Verificar que el dump no esté vacío
ls -lh ~/respaldo_pre_nom068_*.sql
```

- [ ] MySQL corriendo
- [ ] Dump generado y con tamaño > 0

---

## PASO 2 — Aplicar migraciones

```bash
cd /var/www/html/inspecciones_sct

# Opción A — comando Cake
php bin/cake.php patch_nom068_schema

# Opción B — manual (si A falla con DELIMITER/CALL), EN ESTE ORDEN:
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspeccion_carroceria_secciones_p01.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspecciones_dictamen_estatus_p11.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/create_inspeccion_observaciones_p12.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/alter_inspecciones_volante_holgura_p13.sql
mysql -uadmin -p -h127.0.0.1 inspecciones < config/schema/create_ordenes_servicio_p33.sql

# Limpiar cache de modelos SIEMPRE después de migrar
rm -f tmp/cache/models/*
```

Verificación rápida en MySQL:

```sql
-- Deben existir:
SHOW COLUMNS FROM inspecciones LIKE 'dictamen';
SHOW COLUMNS FROM inspecciones LIKE 'estatus_registro';
SHOW COLUMNS FROM inspecciones LIKE 'volante_cm';
SHOW COLUMNS FROM inspecciones LIKE 'holgura_cm';
SHOW TABLES LIKE 'inspeccion_observaciones';
SHOW TABLES LIKE 'ordenes_servicio';
SHOW COLUMNS FROM inspeccion_carroceria LIKE 'grano_piso';
SHOW COLUMNS FROM inspeccion_carroceria LIKE 'grava_piso';
SHOW COLUMNS FROM inspeccion_carroceria LIKE 'otro_piso';

-- Verificar migración de dictamen en datos existentes:
SELECT resultado, dictamen, estatus_registro, COUNT(*)
FROM inspecciones GROUP BY resultado, dictamen, estatus_registro;
```

- [ ] 5 migraciones aplicadas sin error
- [ ] Columnas y tablas nuevas verificadas
- [ ] Datos legacy migrados (APROBADO→CUMPLE, RECHAZADO→NO CUMPLE, CANCELADO→estatus)
- [ ] Cache de modelos limpiado

Si algo falla: cada script tiene rollback en `config/schema/rollback/`. Aplicar el rollback
correspondiente, restaurar el dump del PASO 1 si es necesario, y revisar el error antes de reintentar.

---

## PASO 3 — Subir el repo a Bitbucket (manual)

El repo git fue inicializado localmente por Claude Code; hoy el único respaldo del código es el VPS.

```bash
cd /var/www/html/inspecciones_sct

# Crear primero el repo vacío "inspecciones-sct" en Bitbucket (workspace beta-invite), luego:
git remote add origin git@bitbucket.org:beta-invite/inspecciones-sct.git
git branch -M main
git push -u origin main

# Verificar que subieron todos los commits de las fases
git log --oneline | head -20
```

- [ ] Repo creado en Bitbucket
- [ ] Push exitoso con todos los commits (28c8d91 baseline → efe480c y posteriores)
- [ ] Verificar que exista `.gitignore` con `config/app_local.php`, `tmp/`, `logs/`, `vendor/`
      (si `app_local.php` con credenciales ya se subió: quitarlo del repo, rotar el password
      de MySQL y hacer push de la corrección)

---

## PASO 4 — Tests

```bash
cd /var/www/html/inspecciones_sct
composer install   # si vendor/ no está completo
vendor/bin/phpunit tests/TestCase/Validation/
```

- [ ] `TipoVehiculoRequisitosTest` en verde
- [ ] Si hay fallas: anotar cuáles y NO continuar a producción hasta resolver

---

## PASO 5 — Tareas de código pendientes (para Claude Code)

Prompt sugerido:

```
Lee docs/validacion/CIERRE_NOM068.md, PASO 5.
Ejecuta las 3 tareas, commit por tarea, y reporta al final.
```

### 5.1 Enlace de menú para F-04
- Agregar en el layout/nav principal el enlace a `/ordenes-servicio` (índice) con
  etiqueta "Órdenes de servicio (F-04)".
- Respetar el patrón de autorización existente del nav (mismo criterio que Inspecciones).

### 5.2 Script de normalización de Dollys legacy
- Inspecciones F-20 antiguas guardaron llantas con patrón de 3 grupos (números 1, 5, 7).
- Crear comando `php bin/cake.php normalize_dolly_llantas` que:
  - Detecte inspecciones Dolly con el patrón viejo.
  - Modo `--dry-run` por defecto: solo lista qué cambiaría.
  - Con `--commit`: renumere al esquema nuevo 1…8 (D2) o 1…4 (D1) sin perder valores.
  - Registre log de cambios en `logs/normalize_dolly.log`.
- NO ejecutar con `--commit` automáticamente; solo dejar el comando listo.

### 5.3 Limpieza de commits de trazabilidad
- P2.3 y P2.4 tienen commits `--allow-empty`. No reescribir historia (ya habrá push);
  solo documentar en `REPORTE_EJECUCION.md` sección 5 que el código real está en
  `4b78fdf` y `a8f9bbd`/`Nom068Formato`. (Si aún no se hizo push, opcional: rebase
  interactivo para eliminarlos — decidir según PASO 3.)

- [ ] 5.1 hecho
- [ ] 5.2 comando creado y probado en dry-run
- [ ] 5.3 documentado

---

## PASO 6 — Pruebas funcionales (manual, una inspección por formato)

Crear una inspección de cada tipo: **F-17 T3 · F-18 C3 · F-19 S3 · F-20 D2 · F-21 AB**.

Checklist por inspección:

### Todas
- [ ] Folio con prefijo correcto (M para 17/18/21, A para 19/20)
- [ ] Dictamen: radio excluyente CUMPLE / NO CUMPLE
- [ ] Estatus del registro separado (ACTIVA / CANCELADA)
- [ ] Observaciones: 6 filas PUNTO NOM + REQUISITO capturables
- [ ] Tabla de rines capturable (tuercas/birlos/maza/balero)

### F-17 (T3)
- [ ] VOLANTE cm y HOLGURA cm capturables
- [ ] Renglón "Luces intermitentes / de peligro" separado de "Luces de peligro"
- [ ] Gas LP NO visible; convertidor NO visible; acoplamientos extra NO visibles
- [ ] Mediciones XXXV, XXXVIII y XXXIX ×2 visibles

### F-18 (C3)
- [ ] Gas LP (NOM 3) visible con sus 4 conceptos
- [ ] Frenos hidráulicos organizados en 6 secciones (22 / 26 / 27-28 / 29-30 / 31 / 32)
- [ ] "Protección del camión" NO está en frenos neumáticos pero SÍ en cabina
- [ ] Solo mediciones XXXV y XXXVIII (sin XXXIX)

### F-19 (S3)
- [ ] Sin kilometraje, sin volante/holgura
- [ ] Carrocerías: capturar valores DISTINTOS en grano/grava/otro tipo, guardar,
      recargar y verificar que NO se pisan (bug P0.1 resuelto)
- [ ] Mangueras/tubería (NOM 34) visible en bloque de frenos
- [ ] Freno emergencia/estacionamiento NO visibles

### F-20 (D2)
- [ ] Sin kilometraje
- [ ] 8 filas de llantas numeradas 1…8 + rines
- [ ] Quinta rueda / acoplamiento visible
- [ ] Luces de freno y peligro con "(SI CUENTA)"

### F-21 (AB)
- [ ] Etiquetas "LLANTA 7" y "LLANTA 8" individuales (no 4 EXTERNA/INTERNA)
- [ ] Válvula manual de control de freno de remolque visible en frenos neumáticos
- [ ] Luces interiores (53) y ventanas laterales entintado (56) en cabina
- [ ] VOLANTE/HOLGURA cm capturables

---

## PASO 7 — Verificación de PDFs (manual)

Para cada una de las 5 inspecciones de prueba:

```
/inspecciones/{id}/pdf-lista   → lista de inspección NOM
/inspecciones/{id}/pdf         → F-04 orden de servicio
```

- [ ] F-17/F-18/F-21: tablas complementarias con 10 filas SIEMPRE (vacías las no usadas)
- [ ] F-19: 12 filas; folio impreso como `A-{consecutivo}`
- [ ] F-20: 8 filas
- [ ] Dictamen impreso marca CUMPLE o NO CUMPLE (recuadros del formato)
- [ ] Observaciones: 6 filas impresas (vacías si no hay datos)
- [ ] F-17/18/21: recuadro VOLANTE cm / HOLGURA cm con valores
- [ ] Comparación visual lado a lado contra los PDFs oficiales de `docs/validacion/`
      (mismo orden de secciones, mismos conceptos, mismos puntos NOM)
- [ ] F-04: crear orden en `/ordenes-servicio/add`, ligar a inspección, regenerar PDF
      y verificar que la fecha del contrato sale de `fecha_contrato` (no de fecha_inspeccion)

---

## Cierre

- [ ] Los 7 pasos completados
- [ ] Commit final: `chore(nom068): cierre validacion post-migracion`
- [ ] Push a Bitbucket
- [ ] Segundo respaldo de BD post-migración:
      `mysqldump -uadmin -p inspecciones > ~/respaldo_post_nom068_$(date +%Y%m%d).sql`
- [ ] Marcar `DIAGNOSTICO_VALIDACION.md` y este archivo como cerrados (nota al inicio con fecha)
