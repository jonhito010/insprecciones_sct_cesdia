# VALIDACION_HISTORIAL.md · Guía para validar incidencias del Historial

> Derivado de `NOTAS_HISTORIAL.md` (2026-08-01).
> Objetivo: reproducir cada incidencia, confirmar si es bug real, comportamiento
> esperado o funcionalidad inexistente, y dejar evidencia para la corrección.
> Registrar resultados en la sección final de este mismo archivo.
>
> **Validación técnica ejecutada:** 2026-08-01 (código + BD + generación PDF CLI).
> Pendiente: confirmación visual en navegador con sesión Admin y técnico.

---

## Cómo validar (regla general)

Para cada incidencia:
1. Reproducir el paso exacto en el navegador.
2. Anotar: ¿qué esperabas? ¿qué pasó? (captura de pantalla si aplica).
3. Clasificar: 🐛 BUG confirmado · ✅ FUNCIONA · ⬜ NO EXISTE (funcionalidad nueva) · ❓ DUDA.
4. Solo lo clasificado como 🐛 o ⬜ pasa a corrección/implementación.

---

## INC-1 · Historial: acción VER (visualizar)

**Prueba:**
1. Ir al listado/historial de inspecciones (`/inspecciones`).
2. Buscar el botón/enlace "Ver" en un registro.
3. Abrirlo: ¿muestra la inspección completa en modo solo lectura
   (checklist, llantas, rines, observaciones, dictamen)?

**Criterio de aceptación:**
- Existe la acción y muestra TODO sin permitir edición.

**Resultado:** ☐ 🐛 ☑ ✅ parcial → reclasificado ⬜ ☐ ❓  
**Clasificación final: ⬜**

**Notas:**
- El botón/enlace Ver existe en `templates/Inspecciones/index.php` → `action: view`.
- `view.php` solo muestra: resumen (fecha, resultado, placas, NIV, propietario, técnico), fotos y documentos adjuntos.
- **No** renderiza checklist, llantas, rines, observaciones ni dictamen estructurado.
- La vista incluye botón **Editar** (no es solo lectura estricta).
- Conclusión: la acción existe pero no cumple el criterio “TODO en solo lectura” → funcionalidad incompleta a implementar (⬜).

---

## INC-2 · Historial: acción EDITAR (restricción Admin + ventana de tiempo)

**Prueba:**
1. Con usuario **Admin**: abrir "Editar" en una inspección de hoy → ¿permite?
2. Con usuario **no-Admin** (técnico/capturista): ¿el botón aparece? ¿la URL
   directa `/inspecciones/edit/{id}` lo bloquea o lo deja pasar?
3. Editar una inspección **antigua** (de hace días): ¿el sistema lo permite
   sin restricción?

**Criterio de aceptación (según definición de negocio pendiente):**
- Hoy probablemente NO hay restricción → clasificar como ⬜ (regla por implementar),
  salvo que un no-Admin pueda editar vía URL directa → eso es 🐛 de seguridad.

**⚠️ Prioridad:** la prueba 2 con URL directa es la importante — si pasa, es
vulnerabilidad, no falta de funcionalidad.

**Resultado:** ☐ 🐛 ☐ ✅ ☑ ⬜ ☐ ❓  
**Clasificación final: ⬜** (+ nota seguridad parcial ✅)

**Notas:**
- Botón Editar visible para todos en el historial (sin condicionar a Admin).
- `edit()` solo llama `_asegurarInspeccionVisibleParaSesion`: Admin ve todas; técnico solo las suyas (`tecnico_id` de sesión).
- Técnicos tienen `edit` en acciones permitidas (`AppController::_restringirAccesoPorRol`).
- **No hay ventana de tiempo** ni “solo Admin puede editar”.
- Seguridad cruzada: un técnico **no** debería editar inspecciones ajenas (ForbiddenException) → no es el bug de “cualquiera edita cualquiera”; sí falta la regla de negocio Admin + ventana → ⬜.
- Confirmación en navegador con dos sesiones sigue recomendada para cerrar la prueba 2.

---

## INC-3 · Historial: acción ELIMINAR (destino del registro)

**Prueba:**
1. Eliminar (o cancelar) una inspección de prueba desde el historial.
2. Verificar en BD qué pasó.
3. Verificar el folio: ¿el consecutivo se rompe si se borra?
4. ¿El registro "eliminado" desaparece del listado? ¿Hay filtro para verlo?

**Criterio de aceptación:**
- NUNCA borrado físico (documento normativo ema/SCT).
- Lo correcto: `estatus_registro = CANCELADA` + conservar folio + filtro
  "mostrar cancelados" en el listado.
- Si hoy hace DELETE físico → 🐛 **CRÍTICO** (pérdida de documento y folio).

**Resultado:** ☑ 🐛 ☐ ✅ ☐ ⬜ ☐ ❓  
**Clasificación final: 🐛 CRÍTICO**

**Notas:**
- `InspeccionesController::delete()` hace `$this->Inspecciones->delete($inspeccion)` (borrado físico ORM) y además borra el directorio de fotos.
- Solo Admin ve el botón y puede llamar delete (ForbiddenException a no-Admin) — bien acotado por rol, mal por destino del registro.
- Existe columna `estatus_registro` enum(`ACTIVA`,`CANCELADA`) pero delete no la usa.
- No hay filtro “mostrar cancelados” orientado a soft-delete (hay 1 CANCELADA en BD, probablemente puesta a mano o por otro flujo).
- Pasa a corrección: soft-delete → CANCELADA + conservar folio + filtro en listado.

---

## INC-4 · OS: acreditación y aprobación no se imprimen 🐛 reportado

**Prueba:**
1. Verificar que los datos existan en `unidades_inspeccion`.
2. Si tienen valor: generar `/inspecciones/{id}/pdf` (F-04).
3. ¿Se imprimen los valores o quedan las líneas vacías?

**Criterio de aceptación:**
- El PDF F-04 imprime ambos números en la cláusula del contrato.

**Resultado:** ☑ 🐛 ☐ ✅ ☐ ⬜ ☐ ❓  
**Clasificación final: 🐛**

**Notas:**
- BD (2026-08-01): las UV principales **sí tienen datos**:
  - id=1: acreditación `UVSCTAT 476`, aprobación `UI/SICT/CFM/DOLLY/001`
  - id=2: acreditación `UVSCTAT 476`, aprobación `UI/SICT/CFM/DOLLY/002`
- En `templates/Inspecciones/pdf.php` la variable `$acreditacion` se llena con **`numero_aprobacion`** (o `aprobacion`), **no** con `numero_acreditacion`.
- El texto del contrato imprime solo: `CON NÚMERO DE ACREDITACIÓN: {valor}` — no hay línea separada de APROBACIÓN, y el valor mostrado es el de aprobación mal etiquetado.
- No es bug de datos vacíos: es bug de mapeo/plantilla. Pasa a corrección.

---

## INC-5 · Lista de inspección (✱ asterisco sin detalle)

**Prueba:**
1. Desde el historial, abrir la lista de inspección (`/inspecciones/{id}/pdf-lista`).
2. Verificar que abre, corresponde al formato correcto del vehículo y
   trae los datos capturados.

**Resultado:** ☐ 🐛 ☑ ✅ ☐ ⬜ ☐ ❓  
**Clasificación final: ✅** (con remisión a hallazgos NOM-068)

**Notas:**
- Acción `pdfLista` existe y genera PDF Dompdf (`pdf_lista.php`).
- El asterisco de la nota original no especificaba el defecto.
- Defectos de layout/contenido ya tratados en `HALLAZGOS_PRUEBAS.md` (saltos de página, llantas, F-18 orden, etc.) — no son “acción rota del historial”.
- Si en re-prueba visual aparece un fallo distinto del historial, reclasificar.

---

## INC-6 · Modelo de impresión ("solo para verificar")

**Prueba:**
1. Ubicar la opción "Modelo de impresión" / "Módulo impresión" en el historial.
2. Confirmar qué genera y si está claro que es vista de verificación.

**Criterio de aceptación:**
- Si existe y funciona: ✅, pero evaluar marca de agua "BORRADOR / SOLO VERIFICACIÓN".

**Resultado:** ☐ 🐛 ☑ ✅ ☐ ⬜ ☐ ❓  
**Clasificación final: ✅** (+ mejora recomendada)

**Notas:**
- Enlace “Módulo impresión” → `moduloImpresion` → PDF tabular (`pdf_modulo_impresion.php`).
- Título: “Módulo impresión”. **Sin** marca de agua BORRADOR / SOLO VERIFICACIÓN.
- Mejora (no bloqueante): agregar marca de agua para no confundir con documento oficial. Marcar ⬜ solo si negocio exige la marca como requisito.

---

## INC-7 · Plantilla motriz no funciona 🐛 reportado

**Prueba:**
1. Ubicar “Plantilla motriz” y ejecutarla.
2. Anotar síntoma exacto.
3. Revisar `logs/error.log`.

**Resultado:** ☐ 🐛 ☑ ✅ (generación) ☐ ⬜ ☐ ❓  
**Clasificación final: ✅** con ❓ pendiente de confirmación en navegador

**Notas:**
- Enlace existe para folios `M*` → ruta nombrada `inspeccionesHtmlMotriz` → `htmlMotriz` / FPDI `MotrizFpdiPdf`.
- Plantilla física presente: `templates/Inspecciones/base_motriz.pdf`.
- Generación CLI (2026-08-01): **OK** — PDF válido (`%PDF-`, ~207 KB) sobre inspección motriz reciente.
- Log histórico (jun-2026, Laragon): errores al pedir `/inspecciones/html-motriz/img/...` y firmas como si fueran `id` (plantilla HTML antigua). Flujo actual usa FPDI, no esas URLs de assets.
- Si en el navegador aún falla: pegar el error fresco del log aquí y reclasificar a 🐛.
- Pregunta de negocio abierta: ¿PDF descargable (hoy) o impresión directa a impresora?

**Error log relevante (histórico, no reproducible en CLI actual):**
```
[InvalidParameterException] Unable to coerce `img` / `uploads` to `int`
for id in action Inspecciones::htmlMotriz()
Request URL: /inspecciones/html-motriz/img/motriz_fondo.png
```

---

## INC-8 · Conteo de folios (pregunta: M-56 · A-24 · Cancelados)

**Prueba SQL ejecutada (2026-08-01):**

| tipo | estatus_registro | total |
|------|------------------|-------|
| A | ACTIVA | 6 |
| M | ACTIVA | 10 |
| M | CANCELADA | 1 |

Duplicados:
| folio | count |
|-------|-------|
| A12 | 2 |
| M12 | 3 |

Max numérico extraído del sufijo: M≈123456, A≈1222212 (folios no secuenciales / basura de prueba).

**Resultado:** ☐ ✅ cuadra ☑ ❓ no cuadra  
**Clasificación final: ❓**

**Notas:**
- No coinciden 56 M / 24 A con esta BD (entorno de prueba / datos parciales).
- Hay **folios duplicados** (A12, M12) → problema de integridad real, aparte del conteo esperado.
- 1 cancelada (M). Cancelados no son el grueso del desajuste.
- Antes de corregir “conteo”, definir si 56/24 vienen de otro ambiente o de un reporte de negocio distinto.

---

## Resumen de resultados

| INC | Descripción | Clasificación | Pasa a corrección |
|---|---|---|---|
| 1 | Ver desde historial | ⬜ incompleto (solo resumen) | ☑ |
| 2 | Editar solo Admin + ventana | ⬜ reglas no implementadas | ☑ |
| 3 | Eliminar → destino | 🐛 CRÍTICO (DELETE físico) | ☑ |
| 4 | Acreditación/aprobación en OS | 🐛 mapeo PDF | ☑ |
| 5 | Lista de inspección ✱ | ✅ (hallazgos NOM aparte) | ☐ |
| 6 | Modelo de impresión | ✅ (+ mejora marca agua) | ☐ (opcional) |
| 7 | Plantilla motriz | ✅ CLI / ❓ UI | ☐ (reclasificar si UI falla) |
| 8 | Conteo de folios | ❓ no cuadra + duplicados | ☑ (duplicados) |

## Definiciones de negocio a responder (bloquean INC-2 e INC-3)

| # | Pregunta | Tu decisión |
|---|---|---|
| 1 | ¿Ventana de tiempo para editar? (ej. mismo día / hasta emitir dictamen / siempre con Admin) | _pendiente_ |
| 2 | ¿"Eliminar" = cancelar con motivo? (recomendado: sí, nunca borrado físico) | _pendiente (recomendación técnica: SÍ)_ |
| 3 | ¿Cancelados visibles con filtro en el listado? | _pendiente (recomendación: SÍ)_ |
| 4 | ¿Plantilla motriz = PDF descargable o impresión directa? | _pendiente (hoy: PDF inline/descarga)_ |

## Al terminar

1. Llenar el resumen y las 4 decisiones. ← resumen lleno; **faltan las 4 decisiones de negocio**.
2. Los 🐛 y ⬜ marcados se convierten en el paquete de corrección/implementación
   del módulo Historial (fase nueva, después del cierre NOM-068 actual).
3. Prompt sugerido para Claude Code cuando llegue el momento:
```
Lee docs/validacion/VALIDACION_HISTORIAL.md (resultados y decisiones ya
llenados). Implementa/corrige SOLO lo marcado como 🐛 o ⬜ respetando las
4 decisiones de negocio. Commit por incidencia. Tests al final.
```

### Paquete que ya puede entrar a corrección sin esperar las 4 decisiones

| Prioridad | INC | Acción |
|---|---|---|
| P0 | 3 | Soft-delete → `CANCELADA` (nunca DELETE físico) |
| P0 | 4 | Imprimir `numero_acreditacion` + `numero_aprobacion` en F-04 |
| P1 | 8 | Impedir/limpiar folios duplicados |
| P1 | 1 | Vista Ver completa solo lectura |
| P1 | 2 | Esperar decisiones 1–3 antes de implementar ventana/Admin |
