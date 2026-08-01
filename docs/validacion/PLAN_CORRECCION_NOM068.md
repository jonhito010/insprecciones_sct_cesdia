# PLAN_CORRECCION_NOM068.md · Sistema CESDIA

> Plan de corrección derivado de `DIAGNOSTICO_VALIDACION.md` (2026-08-01), validado contra los formatos oficiales F-04…F-21.
> Ejecutar por fases. **Al terminar cada fase: detenerse, mostrar resumen de cambios y esperar confirmación antes de continuar.**
> No aplicar cambios de fases posteriores por adelantado.

## Correcciones al diagnóstico (leer antes de ejecutar)

1. **Filas de llantas (T2=6, C2=6, S2=8, AB=8) NO son errores de captura.** La configuración real del vehículo define cuántas llantas se capturan. El PDF es formato genérico: debe renderizar SIEMPRE el número completo de filas del formato oficial (10 en F-17/F-18/F-21, 12 en F-19, 8 en F-20) dejando en blanco las no usadas. Corregir en el render del PDF, no forzando captura.
2. **F-18 y "PROTECCION DEL CAMION":** quitar el campo del bloque de FRENOS NEUMÁTICOS de F-18, pero **conservarlo en CABINA** (NOM 39, sí aparece en cabina de F-18 igual que F-17). No eliminar la columna.
3. **`CANCELADO`:** es estado del registro de inspección, no un valor del dictamen. Separar dictamen (CUMPLE / NO CUMPLE) de estatus del registro.

---

## FASE P0 — Pérdida de datos (URGENTE)

### P0.1 · F-19: colisión de columnas de carrocería
- Problema: `piso` y `laterales` se comparten entre CAJAS GRANO/RESIDUOS (58), CAJAS PARA GRAVA (60) y OTRO TIPO DE CARROCERIA (60). Un valor pisa otro al guardar.
- Acción:
  - Migración: crear columnas separadas con prefijo por sección:
    - `grano_lados_soporte`, `grano_piso`, `grano_carroceria_remaches`
    - `plataforma_plana`, `plataforma_laterales_estacas`
    - `grava_laterales_soporte`, `grava_piso`, `grava_puertas_tolva`
    - `sujecion_puntos_equipo`, `sujecion_condicion_carga`
    - `otro_piso`, `otro_puertas`, `otro_laterales`, `otro_sujetadores_mangueras`
  - Migrar datos existentes con criterio conservador (copiar valor actual a la sección que corresponda según registros; si es ambiguo, documentar en log de migración).
  - Actualizar entidad, formulario F-19 y PDF.
- Verificación: guardar una inspección F-19 llenando las 3 secciones con valores distintos y confirmar que no se pisan.

---

## FASE P1 — Cumplimiento del documento oficial

### P1.1 · Dictamen CUMPLE / NO CUMPLE
- Separar `dictamen` enum('CUMPLE','NO CUMPLE') del estatus del registro (`ACTIVA`,`CANCELADA`, etc.).
- Migrar datos: APROBADO→CUMPLE, RECHAZADO→NO CUMPLE, CANCELADO→estatus del registro.
- Actualizar UI (radio excluyente) y PDF (marcar el recuadro correspondiente).

### P1.2 · Observaciones estructuradas
- Nueva tabla `inspeccion_observaciones`:
  - `id`, `inspeccion_id` FK, `punto_nom` VARCHAR(10) NULL, `requisito` TEXT, `orden` TINYINT, `created`, `modified`.
- UI: 6 filas (punto NOM + requisito) en la pantalla de cierre. Permitir vacías.
- PDF: renderizar las 6 filas del formato (vacías si no hay datos).
- Mantener el textarea libre actual como campo interno opcional (no se imprime) o eliminarlo — decidir y documentar.

### P1.3 · Volante / Holgura en cm
- Agregar `volante_cm` DECIMAL(5,2) NULL y `holgura_cm` DECIMAL(5,2) NULL.
- Aplica solo a formatos con dirección: F-17, F-18, F-21 (ocultar en F-19/F-20).
- Mantener el select Cumple/No Cumple existente (`juego_volante`) — el formato pide ambos: checklist Y medición.
- UI + PDF (recuadro VOLANTE cm / HOLGURA cm).

---

## FASE P2 — Estructura

### P2.1 · F-21: etiquetas LLANTA 7 y LLANTA 8
- Solo presentación (captura y PDF): el eje trasero final del autobús se etiqueta "LLANTA 7" y "LLANTA 8" individuales, no "Llanta 4 EXTERNA/INTERNA".
- Implementar mapa de etiquetas por tipo de vehículo en un solo lugar (helper o clase de configuración) usado por formulario y PDF.

### P2.2 · PDFs con filas completas del formato
- F-17/F-18/F-21: tablas complementarias de llantas y rines SIEMPRE con 10 filas.
- F-19: 12 filas. F-20: 8 filas.
- Filas sin datos se imprimen vacías. La captura sigue siendo dinámica según configuración real.

### P2.3 · F-20: tabla complementaria de 8 filas
- Captura de mediciones por llanta 1…8 (dibujo mm, presión PSI, cámara tipo, varilla cm) en lugar de 3 filas de grupo.
- Resolver inconsistencia `textoAyuda()` (D1=4/D2=8) vs `TIPOS` (fijo 3): definir configuraciones reales de dolly y alinear ambos.

### P2.4 · Folio F-19 con formato `A-`
- Forzar render `A-{consecutivo}` en documento impreso. Interno puede seguir siendo prefijo A.

### P2.5 · UI de `inspeccion_rines`
- La tabla ya existe en BD. Crear formulario de captura (tuercas #, birlos #, maza limpia/chorreada, balero buen estado) con el número de filas según formato (10/12/8).
- Incluir en PDF (tabla derecha del formato).

---

## FASE P3 — Conceptos y limpieza

### P3.1 · Conceptos faltantes
- F-17 y F-18: agregar renglón separado `luces_intermitentes` (NOM 53) — ya existe `luces_peligro`; el formato lista ambos.
- F-19: mostrar `mangueras_tuberia` (NOM 34) dentro del bloque de frenos neumáticos del remolque.
- F-21: agregar `valvula_control_remolque` (NOM 39) a la captura de frenos neumáticos del autobús.

### P3.2 · Campos que sobran por formato (ocultar en UI, no borrar columnas)
- F-17: `combustible_gas_lp` (Gas LP es solo F-18), `convertidor`, acoplamientos extra (`ojo_lanza`, `barra_traccion`, `cadenas_sujetadores`, `capacidad_arrastre`), bloque genérico de freno de estacionamiento.
- F-18: `combustible_gas_lp` genérico (queda el desglose `gaslp_*`), `convertidor`, duplicidad bloque emergencia/estacionamiento vs NOM 22, `proteccion_camion` FUERA de frenos neumáticos (⚠️ conservar en CABINA).
- F-19: `freno_emergencia` / `freno_estacionamiento`; revisar cámara/varilla heredadas de motriz.
- F-18: reorganizar UI de frenos hidráulicos en las 6 secciones oficiales: FRENO DE ESTACIONAMIENTO (22), FRENOS HIDRAULICOS (26), ASISTIDOS (27/28), SISTEMA DE VACIO (29/30), TAMBOR (31), DISCO (32).

### P3.3 · F-04: entidad propia
- Tabla `ordenes_servicio`: `id`, `inspeccion_id` NULL, `propietario_id`, `vehiculo_id`, `fecha_contrato` DATE, `unidad_inspeccion_id`, firmas/estatus, timestamps.
- Formulario de captura F-04 (hoy solo existe el PDF desde inspección).
- El PDF F-04 toma día/mes/año de `fecha_contrato`, no de `fecha_inspeccion`.

### P3.4 · Catálogo punto NOM (opcional recomendado)
- Los puntos NOM viven en TXT/PDF sin catálogo en BD. Evaluar tabla `nom_conceptos` como fuente única para PDFs y validación futura (riesgo actual: divergencia silenciosa entre `camposTXT` y formatos).

---

## Reglas para la ejecución en Claude Code

1. Una fase por sesión de cambios; commit por sub-tarea (P0.1, P1.1, …) con mensaje `fix(nom068): P0.1 separar columnas carroceria F-19`.
2. Toda migración con script de rollback.
3. No eliminar columnas en ninguna fase (solo agregar/ocultar); las eliminaciones se evalúan al final.
4. Después de cada fase: correr una inspección de prueba por formato (F-17…F-21) y generar los 5 PDFs para revisión visual.
5. No tocar los PDFs oficiales de `docs/validacion/` ni los CLAUDE_F-*.md.
