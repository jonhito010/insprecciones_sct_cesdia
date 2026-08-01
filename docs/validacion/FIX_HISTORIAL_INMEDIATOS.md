# FIX_HISTORIAL_INMEDIATOS.md · Folio único en tiempo real + soft-delete + mapeo F-04

> Correcciones de INC-3, INC-4 e INC-8 de `VALIDACION_HISTORIAL.md` que NO requieren
> decisiones de negocio pendientes. Decisión ya tomada por el dueño del sistema:
> **el folio se captura manualmente, el sistema NO debe aceptar duplicados y debe
> alertar en tiempo real mientras el operador escribe.**

---

## INC-8 · Folio único con validación en tiempo real

### Comportamiento requerido

**Capa 1 — Tiempo real (mientras escribe):**
- Al capturar el folio en alta/edición de inspección, validar contra BD sin
  recargar la página (AJAX con debounce ~400 ms tras dejar de teclear).
- Si el folio YA existe:
  - Marcar el campo en rojo (borde + ícono).
  - Alerta visible junto al campo:
    `⚠ El folio M12 ya existe — Inspección #45 del 15/07/2026 (Técnico: J. López)`
  - **Deshabilitar el botón Guardar** mientras el folio sea duplicado.
- Si el folio está disponible: marca verde discreta ("Folio disponible").
- En edición: excluir la propia inspección de la comparación (que no se
  marque a sí misma como duplicada).

**Capa 2 — Servidor (aunque burlen el JS):**
- `validateUnique` sobre `folio` en `InspeccionesTable` con mensaje:
  `El folio {folio} ya existe (inspección #{id} del {fecha}).`
- Rechazar el guardado con el error visible en el formulario.

**Capa 3 — Base de datos (última barrera):**
- Índice `UNIQUE` sobre `folio` en `inspecciones`.
- Alter nuevo con rollback: `config/schema/alter_inspecciones_folio_unique_fx8.sql`.
- **NO aplicar** hasta limpiar los duplicados existentes (ver abajo).

### Endpoint AJAX
- `GET /inspecciones/validar-folio?folio=M12&excluir={id?}`
- Respuesta JSON: `{disponible: bool, inspeccion: {id, folio, fecha, tecnico} | null}`
- Requiere sesión autenticada (no público).

### Ayuda de captura (UX)
- Junto al campo folio, mostrar referencia de solo lectura:
  `Último M: M13 · Último A: A6` (consulta por prefijo al cargar el formulario).
- Es referencia, no autollenado — el folio sigue siendo decisión del operador.

### Duplicados existentes (bloqueo del índice UNIQUE)
1. Generar la lista de afectadas.
2. Entregar la lista al dueño del sistema y **ESPERAR su decisión de refoliación**.
3. Solo después de refoliar manualmente: aplicar el alter UNIQUE.

---

## INC-3 · Eliminar = soft-delete (nunca borrado físico)

- `delete()` de inspecciones NO debe hacer DELETE físico.
- Comportamiento: `estatus_registro = 'CANCELADA'` + nuevo campo
  `motivo_cancelacion` VARCHAR(255) NULL (alter nuevo con rollback:
  `alter_inspecciones_motivo_cancelacion_fx3.sql`, no aplicar aún).
- Al cancelar desde la UI: pedir motivo (modal o campo) antes de confirmar.
- Listado: excluir CANCELADA por defecto. (El filtro visible "mostrar
  canceladas" queda para la fase Historial; por ahora solo el default seguro.)
- El folio de una cancelada NO se libera: sigue ocupado y la validación de
  unicidad lo sigue contando (el documento existió; el número no se reutiliza).

---

## INC-4 · Mapeo acreditación/aprobación en PDF F-04

- Bug: el PDF imprime `numero_aprobacion` en el espacio de ACREDITACIÓN y
  omite el otro valor.
- Fix en la plantilla del F-04:
  - `CON NÚMERO DE ACREDITACIÓN` ← `unidades_inspeccion.numero_acreditacion`
  - `Y APROBACIÓN` ← `unidades_inspeccion.numero_aprobacion`
- Verificar con una unidad que tenga ambos valores distintos para confirmar
  que no están cruzados.

---

## Reglas de ejecución (Claude Code)

1. Commits:
   - `fix(historial): INC-8 folio unico validacion tiempo real + servidor`
   - `fix(historial): INC-3 soft-delete con motivo de cancelacion`
   - `fix(historial): INC-4 mapeo acreditacion/aprobacion F-04`
2. Los 2 alters nuevos (UNIQUE folio, motivo_cancelacion) se dejan listos con
   rollback y **NO se aplican**; avisar cuando estén.
3. El alter UNIQUE queda además condicionado a la refoliación manual de los
   duplicados — entregarme la lista de la query de INC-8 en el reporte.
4. JS de validación en tiempo real: vanilla o el patrón ya usado en el
   proyecto; sin dependencias nuevas.
5. Tests al final (deben seguir en verde) + agregar caso de prueba de folio
   duplicado al comando `pruebas_nom068` (intentar guardar folio existente
   → debe fallar).
6. Reporte final: archivos tocados, lista de duplicados, y confirmación de
   que Guardar se bloquea en la UI con folio repetido.
