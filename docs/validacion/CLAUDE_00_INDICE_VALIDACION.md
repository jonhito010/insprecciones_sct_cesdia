# CLAUDE.md — ÍNDICE MAESTRO DE VALIDACIÓN · SISTEMA DE INSPECCIONES NOM-068-SCT-2-2014 (CESDIA)

> Usar este archivo como entrada principal. Contiene la matriz comparativa entre los 6 formatos para validar que el sistema y el formulario tengan la estructura correcta por tipo de vehículo.

## Archivos de este paquete
| Archivo | Formato | Vehículo | Folio |
|---|---|---|---|
| CLAUDE_F-04_ORDEN_SERVICIO.md | F-04 REV.01 | Orden de servicio / contrato | — |
| CLAUDE_F-17_TRACTO_CAMION.md | F-17 REV.01 | Tracto-camión | M |
| CLAUDE_F-18_CAMION_C2_C3.md | F-18 REV.01 | Camión C-2, C-3 | M |
| CLAUDE_F-19_REMOLQUE_S2_S3.md | F-19 REV.01 | Remolque/Semirremolque S-2, S-3 | A- |
| CLAUDE_F-20_DOLLY.md | F-20 REV.01 | Dolly | A |
| CLAUDE_F-21_AUTOBUS.md | F-21 REV.01 | Autobús | M |

## Matriz comparativa de secciones (✓ = el formato la incluye)

| Sección | F-17 | F-18 | F-19 | F-20 | F-21 |
|---|---|---|---|---|---|
| LUCES (faros delanteros, parabrisas…) | ✓ | ✓ | ✓ (solo demarcadoras) | — | ✓ |
| PARTE TRASERA | ✓ | ✓ | ✓ | ✓ (SI CUENTA) | ✓ |
| LLANTAS DELANTERAS (1/2, 3,2 mm) | ✓ | ✓ | — | — | ✓ |
| LLANTAS (grupos, 1,6 mm) | 3/4·5/6·7/8·9/10 | 3/4·5/6·7/8·9/10 | 1/2 a 11/12 | 1/2·5/6·7/8 | 3/4·5/6·7·8 |
| SUSPENSION DELANTERA | ✓ | ✓ | — | — | ✓ |
| SISTEMA DE DIRECCION | ✓ | ✓ | — | — | ✓ |
| VIGAS Y MONTAJE DEL CHASIS | ✓ | ✓ | ✓ | ✓ | — (dentro de susp. trasera: bastidor 57) |
| SISTEMA DE COMBUSTIBLE (diesel/gasolina) | ✓ | ✓ | — | — | ✓ |
| SISTEMA DE COMBUSTIBLE GAS LP (NOM 3) | — | ✓ | — | — | — |
| SISTEMA DE ESCAPE | ✓ | ✓ | — | — | ✓ |
| CONEXIONES manitas aire/eléctricas (60/54) | ✓ | — | — | — | — |
| SUSPENSION (trasera / única) | ✓ (8) | ✓ (7) | ✓ (8) | ✓ (9) | ✓ (8, incl. bastidor 57) |
| BATERIA | ✓ | ✓ | — | — | ✓ |
| FRENOS NEUMATICOS | ✓ (20) | ✓ (18) | ✓ (10) | ✓ (11) | ✓ (20) |
| FRENO DE ESTACIONAMIENTO (22) | — | ✓ | — | — | — |
| FRENOS HIDRAULICOS (26) | — | ✓ | — | — | — |
| FRENOS HIDRAULICOS ASISTIDOS (27/28) | — | ✓ | — | — | — |
| SISTEMA DE VACIO (29/30) | — | ✓ | — | — | — |
| FRENOS HIDR. DE TAMBOR (31) | — | ✓ | — | — | — |
| FRENOS HIDR. DE DISCO (32) | — | ✓ | — | — | — |
| FRENOS ELECTRICOS (63) | ✓ | — | ✓ | — | — |
| SISTEMA DE ACOPLAMIENTO quinta rueda (79) | ✓ | — | — | ✓ | — |
| CHASIS tanque (57) | — | — | ✓ | — | — |
| CAJAS GRANO/RESIDUOS (58) | — | — | ✓ | — | — |
| PLATAFORMAS PLANAS (59) | — | — | ✓ | — | — |
| CAJAS PARA GRAVA (60) | — | — | ✓ | — | — |
| SUJECION DE CARGA (61) | — | — | ✓ | — | — |
| OTRO TIPO DE CARROCERIA (60) | — | — | ✓ | — | — |
| CABINA | ✓ (10) | ✓ (10) | — | — | ✓ (11: + luces interiores 53, ventanas laterales 56) |

## Matriz de campos de encabezado y tablas complementarias

| Elemento | F-17 | F-18 | F-19 | F-20 | F-21 |
|---|---|---|---|---|---|
| KILOMETRAJE | ✓ | ✓ | ✗ | ✗ | ✓ (×2 en el formato) |
| Prefijo de FOLIO | M | M | A- | A | M |
| VOLANTE / HOLGURA (cm) | ✓ | ✓ | ✗ | ✗ | ✓ |
| Tabla llantas (dibujo/presión/cámara/varilla) | 10 filas | 10 filas | 12 filas | 8 filas | 10 filas |
| Tabla tuercas/birlos/maza/balero | 10 filas | 10 filas | 12 filas | 8 filas | 10 filas |
| Medición XXXV (tiempo 350→620 KPa) | ✓ | ✓ | ✗ | ✗ | ✓ |
| Medición XXXVIII (caída PSI en 1 min) | ✓ | ✓ | ✗ | ✗ | ✓ |
| Medición XXXIX con dispositivo de cierre | ✓ | ✗ | ✗ | ✗ | ✗ |
| Medición XXXIX sin dispositivo de cierre | ✓ | ✗ | ✗ | ✗ | ✗ |
| OBSERVACIONES (PUNTO NOM + REQUISITO, 6 filas) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Dictamen CUMPLE / NO CUMPLE | ✓ | ✓ | ✓ | ✓ | ✓ |
| VERIFICADOR | ✓ | ✓ | ✓ | ✓ | ✓ |

## Estructura de datos común por renglón (todos los formatos)
| Campo | Detalle |
|---|---|
| punto_nom | int (2, 3, 4, 13-15, 18, 21, 22, 26-32, 34-42, 45, 46, 51-54, 56-65, 68-70, 72, 73, 75, 77-79) |
| tipo_inspeccion | flags D / V / M (algunos conceptos llevan V+M, algunos ninguno) |
| concepto | texto literal del formato |
| seccion | encabezado gris del formato |
| orden | posición en el formato (debe respetarse en el PDF) |
| resultado | cumple / no_cumple / na (exclusivos) |

## Errores comunes a buscar en el sistema actual
1. **Kilometraje pedido en F-19/F-20** → no debe existir para vehículos de arrastre.
2. **Prefijo de folio incorrecto** → M para F-17/F-18/F-21, A para F-19/F-20.
3. **Número de posiciones de llanta fijo en 10** → F-19 necesita 12 y F-20 solo 8.
4. **Llantas traseras del autobús como 7/8 y 9/10** → en F-21 son grupos individuales 7 y 8.
5. **Mediciones XXXIX aplicadas a todos** → solo F-17 las tiene (×2).
6. **Secciones de frenos hidráulicos/gas LP visibles en todos** → solo F-18.
7. **Acoplamiento (quinta rueda) faltante en dolly** → F-20 sí lo lleva.
8. **Carrocerías (58-61) faltantes** → solo F-19.
9. **Falta el campo TIPO AS-1 o AS-10 del parabrisas** (renglón separado en F-17/F-18/F-21).
10. **Conceptos sin punto NOM o con punto NOM incorrecto** → validar contra las tablas de cada archivo.
11. **F-04:** formulario debe capturar los 7 datos del solicitante + tipo de vehículo que selecciona el formato de inspección.
12. **Dictamen** debe ser excluyente (CUMPLE o NO CUMPLE) + nombre del verificador.
