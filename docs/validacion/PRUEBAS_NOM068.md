# PRUEBAS_NOM068.md · Guía de validación funcional y ajustes

> Pasos 6 y 7 de `CIERRE_NOM068.md`. Prerequisitos ya cumplidos: migraciones aplicadas,
> tests 12/12 en verde.
> Flujo: capturar → generar PDF → comparar contra oficial → registrar hallazgos → corregir en lote.

---

## 1. Flujo por formato

Para cada uno de los 5 formatos:

1. Crear inspección en `/inspecciones/add` seleccionando el tipo de vehículo.
2. Llenar el formulario **completo** (todos los campos, no dejar a medias — el objetivo
   es verificar que todo aparezca, guarde y se imprima).
3. Guardar → reabrir la inspección → confirmar que los valores persistieron.
4. Generar PDFs:
   - `/inspecciones/{id}/pdf-lista` → lista de inspección NOM
   - `/inspecciones/{id}/pdf` → orden F-04
5. Abrir lado a lado con el PDF oficial de `docs/validacion/` y comparar (sección 3).

Orden recomendado de pruebas: **F-19 primero** (bug crítico de carrocerías), luego F-21,
F-20, F-18, F-17.

---

## 2. Casos de prueba por formato

### F-19 · Remolque S-3 (folio A-) — 🔑 PRUEBA CRÍTICA
- [ ] No pide kilometraje ni volante/holgura
- [ ] **Carrocerías:** capturar valores DISTINTOS a propósito:
  - Grano/residuos → piso = CUMPLE
  - Grava → piso = NO CUMPLE
  - Otro tipo → piso = N/A
  - (lo mismo con laterales)
- [ ] Guardar → recargar → **cada sección conservó SU valor** (no se pisan)
- [ ] Mangueras/tubería (NOM 34) visible en bloque de frenos
- [ ] Freno de emergencia/estacionamiento NO visibles
- [ ] Tabla de llantas y rines hasta 12 posiciones

### F-21 · Autobús AB (folio M)
- [ ] Etiquetas **LLANTA 7** y **LLANTA 8** individuales (no "4 EXTERNA/INTERNA")
- [ ] Válvula manual de control de freno de remolque visible en frenos neumáticos
- [ ] Luces interiores (53) y ventanas laterales entintado (56) en cabina
- [ ] Volante/holgura en cm capturables
- [ ] Bastidor/chasis/largeros (57) en suspensión trasera

### F-20 · Dolly D2 (folio A)
- [ ] Sin kilometraje
- [ ] 8 filas de llantas numeradas 1…8 + tabla de rines
- [ ] Quinta rueda / acoplamiento visible (5 conceptos NOM 79)
- [ ] Luces de freno y peligro con "(SI CUENTA)"
- [ ] Sin ABS, sin mediciones XXXV/XXXVIII

### F-18 · Camión C-3 (folio M)
- [ ] Gas LP (NOM 3) visible con sus 4 conceptos
- [ ] Frenos hidráulicos organizados en 6 secciones: estacionamiento (22),
      hidráulicos (26), asistidos (27/28), vacío (29/30), tambor (31), disco (32)
- [ ] "Protección del camión" NO en frenos de aire, SÍ en cabina
- [ ] Solo mediciones XXXV y XXXVIII (sin XXXIX)
- [ ] Luces intermitentes separadas de luces de peligro

### F-17 · Tracto-camión T3 (folio M)
- [ ] Volante/holgura en cm
- [ ] Luces intermitentes separadas
- [ ] Gas LP NO visible; convertidor NO visible; acoplamientos extra
      (ojo lanza, barra tracción, cadenas, capacidad) NO visibles
- [ ] Manitas de aire y eléctricas visibles
- [ ] Quinta rueda visible (5 conceptos)
- [ ] Mediciones XXXV, XXXVIII y XXXIX ×2 visibles

### Comunes a los 5
- [ ] Folio con prefijo correcto (M: F-17/18/21 · A: F-19/20)
- [ ] Dictamen: radio excluyente CUMPLE / NO CUMPLE
- [ ] Estatus del registro separado (ACTIVA / CANCELADA)
- [ ] Observaciones: 6 filas PUNTO NOM + REQUISITO
- [ ] Tabla de rines capturable (tuercas / birlos / maza / balero)

### F-04 · Orden de servicio
- [ ] Crear orden en `/ordenes-servicio/add` con fecha de contrato ≠ fecha de inspección
- [ ] Ligar a una inspección y regenerar `/inspecciones/{id}/pdf`
- [ ] El PDF usa `fecha_contrato` (día/mes/año) y muestra los 7 datos del solicitante
- [ ] Texto estático completo (NOM, prestador 5 viñetas, solicitante 5 viñetas, firmas)

---

## 3. Checklist de comparación de PDF (por formato)

Abrir el PDF generado junto al oficial de `docs/validacion/` y revisar en este orden:

1. **Encabezado:** logo CESDIA · código F-XX REV.01 · folio con prefijo ·
   kilometraje (o su ausencia en F-19/F-20)
2. **Secciones:** mismo orden y mismos nombres que el oficial
3. **Conceptos:** ninguno falta · los capturados muestran su marca en
   Cumple / No Cumple / N/A · punto NOM correcto por renglón
4. **Tablas pie:** filas completas SIEMPRE (F-17/18/21 = 10 · F-19 = 12 · F-20 = 8,
   vacías las no usadas) · volante/holgura cm (solo motrices) · mediciones según formato
5. **Cierre:** 6 filas de observaciones · dictamen marcado (CUMPLE o NO CUMPLE) ·
   verificador · F-19 imprime folio `A-{consecutivo}`

---

## 4. Registro de hallazgos

Anotar cada diferencia en `docs/validacion/HALLAZGOS_PRUEBAS.md` (o un txt) con este formato:

```
[FORMATO] [ORIGEN] Descripción concreta

Ejemplos:
[F-19] [FORMULARIO] La sección "Sujeción de carga" no aparece en captura
[F-19] [PDF] Folio imprime "A123" en vez de "A-123"
[F-21] [PDF] Tabla de llantas muestra 8 filas, deben ser 10
[F-17] [FORMULARIO] holgura_cm no guarda el valor al recargar
```

Reglas:
- `[FORMULARIO]` = problema de captura (templates/element/Inspeccion/) ·
  `[PDF]` = problema de impresión (pdf_lista.php / pdf.php)
- Una línea por hallazgo, concreta y verificable
- NO corregir al vuelo; primero completar las 5 pruebas
- Si hay duda de si algo es error o comportamiento correcto, consultar el
  `CLAUDE_F-XX_*.md` correspondiente antes de anotarlo como error

---

## 5. Corrección en lote (Claude Code)

Con la lista completa de hallazgos, dar este prompt:

```
Encontré estas diferencias al probar contra los formatos oficiales
de docs/validacion/:

[pegar aquí la lista completa de HALLAZGOS_PRUEBAS.md]

Corrige cada una. Reglas:
- Commit por hallazgo: fix(nom068): {formato} {descripción corta}
- Antes de cada corrección consulta el CLAUDE_F-XX correspondiente
  para confirmar el comportamiento esperado
- No toques nada que no esté en la lista
- No modifiques migraciones ya aplicadas; si un fix requiere cambio de
  schema, crea un nuevo alter con rollback y avísame para aplicarlo
- Al final corre los tests (deben seguir 12/12) y dame el resumen
```

Después de la corrección:
- [ ] Regenerar SOLO los PDFs de los formatos afectados
- [ ] Re-verificar los hallazgos corregidos
- [ ] Si hubo nuevo alter de schema: respaldo → aplicar → limpiar cache

---

## 6. Cierre final

- [ ] 5 formatos probados sin hallazgos pendientes
- [ ] F-04 probado con orden ligada
- [ ] Tests 12/12 en verde tras las correcciones
- [ ] Respaldo final: código (tar.gz) + BD (mysqldump) post-pruebas
- [ ] Marcar `DIAGNOSTICO_VALIDACION.md`, `CIERRE_NOM068.md` y este archivo
      como cerrados (nota con fecha al inicio de cada uno)
