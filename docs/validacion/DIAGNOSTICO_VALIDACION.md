# Diagnóstico de validación NOM-068 · Sistema CESDIA

> Solo diagnóstico. Sin correcciones aplicadas.
> Fecha: 2026-08-01
> Fuentes: `docs/validacion/`, `config/schema/`, `src/Validation/TipoVehiculoRequisitos.php`, `templates/element/Inspeccion/`

## Modelo actual (contexto)

- Los conceptos NOM **no** se guardan como filas EAV (`punto_nom` / `concepto` / `resultado`).
- Son **columnas fijas** `enum('CUMPLE','NO CUMPLE','N/A')` en subtablas hasOne.
- Las llantas sí son **filas** en `inspeccion_llantas` (`numero_llanta` + `posicion` EXTERNA/INTERNA).
- Existe `inspeccion_rines` (tuercas/maza/balero) en BD **sin UI de captura**.
- F-04 **no** tiene tabla ni template de captura; el PDF se arma desde la inspección.

---

## Hallazgos transversales (F-17 … F-21)

| Problema | Detalle |
|---|---|
| Dictamen | BD/UI: `APROBADO` / `RECHAZADO` / `CANCELADO`. Formato oficial: **CUMPLE / NO CUMPLE** excluyentes. |
| Observaciones | Textarea libre. Formato: **6 filas** PUNTO NOM + REQUISITO. |
| Verificador | Cubierto vía `tecnico_id` (nombre), no como campo literal “VERIFICADOR” en el cierre. |
| Holgura / volante (cm) | Encabezado pide cm/cm. Solo existe `juego_volante` = select Cumple etiquetado “DISTANCIA”. **Faltan valores numéricos en cm.** |
| Tabla tuercas/birlos/maza/balero | Tabla complementaria del PDF. `inspeccion_rines` existe; **no hay formulario**. Parcial: `rin_sujetadores` en cada llanta. |
| Punto NOM en captura | No se muestra ni se guarda por renglón; vive en TXT/PDF. |
| Cámara / varilla | Sí en cabecera (`tipo_camara_frenado`, `varilla_ll{1_2…7_8}_*`). Varilla fija a pares 1-2…7-8. |

---

## F-04 — Orden de servicio

### Conceptos / datos faltantes en BD

- No hay entidad `ordenes_servicio` ni campos propios de contrato (día/mes/año de celebración separados de `fecha_inspeccion`).
- Acreditación/aprobación UV viven en `unidades_inspeccion` (OK como catálogo).
- Los 7 del solicitante se cubren de forma **indirecta**: propietario (nombre, dirección, RFC) + vehículo (placas, NIV, año/modelo, tipo).

### Formulario: faltan / sobran

- **Falta** pantalla de captura F-04 dedicada (solo PDF desde inspección).
- **Falta** captura explícita de fecha de contrato como día/mes/año de celebración (hoy = fecha de inspección).
- Tipo de vehículo **sí** dispara F-17…F-21 (`choose_tipo` + `TipoVehiculoRequisitos`) — cumple el requisito crítico del índice.

### Estructura

- No aplica folio M/A, llantas ni mediciones.

---

## F-17 — Tracto-camión (folio M)

### Conceptos faltantes o degradados en BD/UI

| Punto NOM | Sección | Hallazgo |
|---|---|---|
| 53 | LUCES | Falta renglón separado **LUCES INTERMITENTES / DE PELIGRO** (solo está `luces_peligro`; F-21 sí tiene ambos). |
| 45 | DIRECCION | `volante` / `juego_volante` sin medición en **cm** (solo Cumple). |
| — | OBSERVACIONES | Sin estructura 6× PUNTO NOM + REQUISITO. |
| — | DICTAMEN | Enum distinto a CUMPLE/NO CUMPLE. |

Mediciones XXXV / XXXVIII / XXXIX **sí existen** en `inspeccion_sistema_aire` y XXXIX solo se muestra en F-17 (correcto).

### Formulario: faltan

- Holgura/volante en cm.
- Luces intermitentes como concepto aparte.
- Observaciones estructuradas + dictamen oficial.
- Tabla de rines (maza/balero) aparte.

### Formulario: sobran

- `inspeccion_chasis.combustible_gas_lp` visible en F-17 (Gas LP es **solo F-18**).
- `convertidor` en chasis (no está en lista F-17).
- Acoplamiento extra: `ojo_lanza`, `barra_traccion`, `cadenas_sujetadores`, `capacidad_arrastre` (formato lista 5 conceptos NOM 79).
- Frenos de estacionamiento genéricos en bloque “emergencia/estacionamiento” (F-17 no tiene sección FRENO ESTACIONAMIENTO 22 como F-18).

### Errores de estructura

| Tema | Esperado | Actual |
|---|---|---|
| Folio | **M** | OK (UI bloquea A). |
| Kilometraje | Obligatorio | OK (`odometro` si folio M). |
| Filas llantas/tabla | 10 en formato | Dinámico: **T2=6**, **T3=10** (T2 incompleto vs PDF de 10 filas). |
| Grupos checklist | 1/2 + 3/4·5/6·7/8·9/10 | OK en T3; T2 recorta. |
| Mediciones | XXXV, XXXVIII, XXXIX×2 | OK en UI F-17. |
| AS-1/AS-10 | Renglón | OK (`parabrisas_tipo`). |
| Manitas / quinta rueda | Sí | OK. |

---

## F-18 — Camión C-2/C-3 (folio M)

### Conceptos faltantes / degradados

| Punto NOM | Sección | Hallazgo |
|---|---|---|
| 53 | LUCES | Igual que F-17: falta **LUCES INTERMITENTES** separado. |
| 45 | DIRECCION | Sin cm de holgura/volante. |
| 22 / 26–32 | Frenos | Columnas `hid_*` + `estac_balata` existen; UI agrupa todo en un solo bloque “Frenos hidráulicos” sin secciones oficiales (estacionamiento / hidráulicos / asistidos / vacío / tambor / disco). |
| — | Dictamen / obs. | Igual que transversal. |

Gas LP NOM **3** (`gaslp_*`): columnas y UI OK y exclusivas F-18.

### Formulario: faltan

- Holgura cm; luces intermitentes; observaciones 6 filas; dictamen CUMPLE/NO CUMPLE; tabla rines.

### Formulario: sobran

- Campo genérico `combustible_gas_lp` además del desglose `gaslp_*`.
- `convertidor` en chasis.
- Bloque “Freno de emergencia y estacionamiento” genérico **además** del bloque hidráulico/estacionamiento NOM 22 (posible duplicidad).
- `proteccion_camion` en aire ampliado F-18: el formato F-18 **no** lista “SISTEMA DE PROTECCIÓN DEL CAMIÓN” en frenos neumáticos (sí está en F-17/F-21).

### Errores de estructura

| Tema | Esperado | Actual |
|---|---|---|
| Folio / km | M + odómetro | OK. |
| Llantas | 10 filas | **C2=6**, **C3=10**. |
| Mediciones | XXXV + XXXVIII; **sin XXXIX** | OK (XXXIX solo F-17). |
| Sin manitas / sin quinta rueda | — | OK. |
| Gas LP + frenos hidr. | Solo F-18 | OK (visibles). |
| Viga oscilante | No en F-18 | OK (excluida en suspensión). |

---

## F-19 — Remolque S-2/S-3 (folio A-)

### Conceptos faltantes / degradados

| Punto NOM | Sección | Hallazgo |
|---|---|---|
| 34 | FRENOS NEUM. | `mangueras_tuberia` está en chasis; en UI remolque el aire reducido **no** lista mangueras en el bloque de frenos. Cobertura parcial. |
| 58–61 / 57 / 60 | Carrocerías | Columnas y UI existen; **solape de campos**: `piso` y `laterales` se reutilizan entre grano / grava / otro tipo → un valor pisa otro al guardar. |
| — | Dictamen / obs. | Transversal. |

### Formulario: faltan

- Observaciones estructuradas / dictamen oficial.
- Tabla rines 12 filas.
- Filas de **varilla 9-10 y 11-12** (solo hay hasta 7-8).
- Prefijo folio documental **A-** (sistema usa `A`; el guion puede ir en el consecutivo, pero no se fuerza `A-`).

### Formulario: sobran

- `freno_emergencia` / `freno_estacionamiento` (no son secciones del F-19).
- Cámara de frenado + varilla 1-2…7-8 visibles igual que motriz (revisar si deben mostrarse igual en arrastre).

### Errores de estructura

| Tema | Esperado | Actual |
|---|---|---|
| Folio | **A-** | Prefijo **A** (sin forzar guion). |
| Kilometraje | **No** | OK (oculto si folio A). |
| Holgura | **No** | OK (sin cabina). |
| Filas llantas | **12** en formato | **S2=8**, **S3=12**. S2 incompleto vs PDF de 12. |
| Mediciones XXXV/XXXVIII/XXXIX | **No** | OK (no se muestran). |
| Carrocerías 57–61 | Sí | Presentes, con colisión de columnas compartidas. |

---

## F-20 — Dolly (folio A)

### Conceptos faltantes / degradados

| Punto NOM | Sección | Hallazgo |
|---|---|---|
| 77/78 | LLANTAS | Checklist de **3 grupos** OK; falta la **tabla de mediciones de 8 filas** del formato (dibujo/presión/cámara/varilla × 8). |
| 79 | ACOPLAMIENTO | 5 conceptos OK en UI reducida. |
| 64 | ABS | Correctamente ausente en Dolly. |

### Formulario: faltan

- 8 filas de tabla complementaria de llantas/rines (hoy 3 filas de grupo).
- Observaciones 6 filas / dictamen CUMPLE-NO CUMPLE.
- Inconsistencia interna: `textoAyuda()` habla de D1=4 / D2=8 llantas vs `TIPOS` fija **3** para ambos.

### Formulario: sobran

- Varilla fija 1-2…7-8 y cámara (no alineadas a 8 posiciones numeradas 1…8).

### Errores de estructura

| Tema | Esperado | Actual |
|---|---|---|
| Folio | **A** | OK. |
| Kilometraje | **No** | OK. |
| Filas llantas | **8** en tabla | **3** grupos (1/2, 5/6, 7/8). Gap estructural principal. |
| Sin rin artillería | Sí | OK. |
| Quinta rueda | Sí | OK. |
| Mediciones XXXV/XXXVIII/XXXIX | **No** | OK. |

---

## F-21 — Autobús (folio M)

### Conceptos faltantes / degradados

| Punto NOM | Sección | Hallazgo |
|---|---|---|
| 45 | DIRECCION | Sin cm holgura/volante. |
| 57 | SUSP. TRASERA | `bastidor_largeros` OK y exclusivo UI. |
| 53 / 56 | CABINA | `luces_interiores` y ventanas laterales OK en cabina. |
| 39 | FRENOS | `valvula_control_remolque` (manual remolque) **no** se muestra en F-21 (solo en bloque conexiones F-17); el formato F-21 sí la lista en frenos neumáticos. |
| — | Dictamen / obs. | Transversal. |

### Formulario: faltan

- Holgura cm; segundo campo de kilometraje (formato lo imprime ×2; BD tiene uno solo — menor si es solo impresión).
- `valvula_control_remolque` en captura F-21.
- Observaciones estructuradas / dictamen oficial.
- Etiquetas de grupos **LLANTA 7** y **LLANTA 8** individuales.

### Formulario: sobran

- (Menores) campos genéricos compartidos; espejos correctamente en cabina para F-21.

### Errores de estructura

| Tema | Esperado | Actual |
|---|---|---|
| Folio / km | M + odómetro | OK (un solo `odometro`). |
| Tabla llantas | **10** filas | **AB = 8** slots (1…4 E/I). |
| Grupos traseros | 3/4 · 5/6 · **7** · **8** (individuales) | Modelo E/I de eje 4 = “Llanta 4 EXTERNA/INTERNA”, **no** etiquetas “LLANTA 7” / “LLANTA 8”. |
| Mediciones | XXXV + XXXVIII; sin XXXIX | OK. |
| AS-1/AS-10 | Sí | OK. |
| Sin Gas LP / sin quinta rueda | — | OK. |
| Sin viga oscilante | — | OK. |

---

## Matriz rápida vs índice `CLAUDE_00_INDICE_VALIDACION.md`

| # | Error común del índice | Estado en sistema |
|---|---|---|
| 1 | Km en F-19/F-20 | **Correcto** (no se pide con folio A) |
| 2 | Prefijo folio M/A | **Mayormente OK**; F-19 no fuerza `A-` |
| 3 | Llantas fijas 10; F-19=12; F-20=8 | **Falla**: dinámico por tipo; Dolly=3; T2/C2=6; AB=8 |
| 4 | F-21 grupos 7 y 8 individuales | **Falla** (pares E/I genéricos) |
| 5 | XXXIX solo F-17 | **Correcto** |
| 6 | Frenos hidr./Gas LP solo F-18 | **Correcto** (con ruido de campos genéricos en F-17) |
| 7 | Acoplamiento en Dolly | **Correcto** |
| 8 | Carrocerías solo F-19 | **Correcto** (con colisión de columnas) |
| 9 | AS-1/AS-10 | **Correcto** en F-17/18/21 |
| 10 | Punto NOM incorrecto/ausente | No hay catálogo en BD; riesgo en PDF/`camposTXT` |
| 11 | F-04 7 datos + tipo | **Parcial** (sin entidad/formulario propio) |
| 12 | Dictamen excluyente + verificador | **Parcial** (enum distinto; técnico sí) |

---

## Archivos clave revisados

- `docs/validacion/CLAUDE_00_INDICE_VALIDACION.md` y `CLAUDE_F-0{4,17,18,19,20,21}_*.md`
- `config/schema/alter_inspeccion_*.sql`
- `src/Validation/TipoVehiculoRequisitos.php`
- `templates/Inspecciones/add.php`, `choose_tipo.php`
- `templates/element/Inspeccion/formularios/f1{7,8,9,20,21}_*.php`
- `templates/element/Inspeccion/{llantas,frenos,cabina,suspension,iluminacion,carroceria,acoplamiento,chasis_aire,resultado}.php`
