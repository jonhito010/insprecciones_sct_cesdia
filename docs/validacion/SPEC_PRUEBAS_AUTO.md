# SPEC_PRUEBAS_AUTO.md · Comando de pruebas automáticas NOM-068

> Especificación para implementar `php bin/cake.php pruebas_nom068`.
> Automatiza el Paso 6 de PRUEBAS_NOM068.md: crea inspecciones de prueba por formato,
> valida persistencia y estructura, y genera los PDFs para revisión visual.
> La comparación visual final contra los PDFs oficiales sigue siendo manual (Paso 7).

---

## Comando

```bash
php bin/cake.php pruebas_nom068 [--formato=F19] [--keep] [--pdf]
```

| Opción | Efecto |
|---|---|
| (sin opciones) | Corre las pruebas de los 5 formatos + F-04, en transacción con rollback (no deja basura en BD) |
| `--formato=F19` | Solo ese formato (valores: F17, F18, F19, F20, F21, F04) |
| `--keep` | NO hace rollback: deja las inspecciones de prueba guardadas para revisarlas en la UI |
| `--pdf` | Además genera los PDFs en `tmp/pruebas_nom068/` (implica `--keep`) |

Prefijo identificable en datos de prueba: folio/observaciones con marca `PRUEBA-AUTO` para
poder localizarlas y borrarlas después (`--cleanup` opcional que elimine todo lo marcado).

---

## Pruebas por formato

Cada prueba: crear inspección vía las Tables reales (no SQL directo, para que corran
validaciones, beforeMarshal y asociaciones) → guardar → **releer de BD** → asertar.

### T1 · F-19 Remolque S-3 (folio A) — CRÍTICA
1. Crear inspección tipo S3 con carrocerías con valores DISTINTOS:
   - grano_piso = CUMPLE, grava_piso = NO CUMPLE, otro_piso = N/A
   - grano/grava/otro laterales igualmente distintos
2. Guardar, releer y asertar que cada campo conservó SU valor (bug P0.1).
3. Asertar: folio inicia con `A`; NO acepta/usa odómetro; sin volante_cm/holgura_cm.
4. Asertar: acepta hasta 12 posiciones de llanta; rechaza la 13.
5. Asertar campos de mangueras (NOM 34) presentes en el set de captura del formato.

### T2 · F-21 Autobús AB (folio M)
1. Crear inspección AB con llantas completas.
2. Asertar etiquetas de slots: posiciones finales etiquetadas `LLANTA 7` y `LLANTA 8`
   individuales (usar el helper/clase de etiquetas de P2.1, p. ej. Nom068Formato).
3. Asertar: folio M; odómetro requerido; volante_cm/holgura_cm aceptan decimales y persisten.
4. Asertar que `valvula_control_remolque` está en el set de campos capturables de AB.
5. Asertar `luces_interiores` y ventanas laterales en el set de cabina; `bastidor_largeros` presente.

### T3 · F-20 Dolly D2 (folio A)
1. Crear inspección D2 con 8 llantas numeradas 1…8.
2. Asertar: acepta 8, rechaza la 9; sin odómetro; folio A.
3. Asertar acoplamiento (5 conceptos NOM 79) en el set del formato.
4. Asertar que ABS y mediciones XXXV/XXXVIII NO están en el set de D2.
5. Variante D1: crear con 4 llantas; asertar normalización a 4 filas.

### T4 · F-18 Camión C-3 (folio M)
1. Crear inspección C3 con Gas LP (4 campos gaslp_*) y frenos hidráulicos.
2. Asertar persistencia de gaslp_* y de los grupos 22/26/27-28/29-30/31/32.
3. Asertar: `proteccion_camion` NO en el set de frenos de aire de C3, SÍ en el de cabina.
4. Asertar: XXXIX NO en el set; XXXV y XXXVIII SÍ.
5. Asertar `luces_intermitentes` y `luces_peligro` como campos separados que persisten
   con valores distintos.

### T5 · F-17 Tracto T3 (folio M)
1. Crear inspección T3 completa.
2. Asertar: gas LP, convertidor y acoplamientos extra (ojo_lanza, barra_traccion,
   cadenas_sujetadores, capacidad_arrastre) NO en el set de T3.
3. Asertar manitas (conexiones aire/eléctricas) y quinta rueda SÍ en el set.
4. Asertar XXXV, XXXVIII y XXXIX (ambas) capturables y persistentes.
5. Asertar volante_cm/holgura_cm persisten.

### T6 · Comunes (se corre dentro de cada formato)
- Dictamen: guardar CUMPLE y NO CUMPLE; asertar que `resultado` legacy se sincroniza
  (CUMPLE→APROBADO, NO CUMPLE→RECHAZADO).
- Estatus: cancelar registro; asertar estatus_registro=CANCELADA sin tocar dictamen.
- Observaciones: guardar 6 filas (punto_nom + requisito + orden); releer y asertar
  orden y contenido; asertar que acepta filas vacías.
- Rines: guardar tabla completa (tuercas/birlos/maza/balero) según posiciones del
  formato; releer y asertar.

### T7 · F-04 Orden de servicio
1. Crear orden con fecha_contrato distinta a la fecha del día.
2. Ligar a una inspección; releer y asertar la asociación.
3. Asertar los 7 datos del solicitante accesibles desde la orden
   (directos o vía propietario/vehículo).

### T8 · Generación de PDF (solo con --pdf)
- Para cada inspección de prueba, invocar la generación real de
  pdf-lista y pdf (F-04) y asertar:
  - No lanza excepción; archivo > 10 KB.
  - Guardar en `tmp/pruebas_nom068/{formato}_lista.pdf` y `{formato}_f04.pdf`.
- (La comparación contra oficiales sigue siendo visual.)

---

## Salida esperada del comando

```
NOM-068 · Pruebas automáticas
==============================
[T1] F-19 Remolque S-3 ......... OK (8 aserciones)
[T2] F-21 Autobús AB ........... OK (9 aserciones)
[T3] F-20 Dolly D2/D1 .......... OK (7 aserciones)
[T4] F-18 Camión C-3 ........... FALLA
     ✗ proteccion_camion presente en set de frenos aire C3
[T5] F-17 Tracto T3 ............ OK (8 aserciones)
[T6] Comunes ................... OK (por formato)
[T7] F-04 Orden ................ OK (3 aserciones)
------------------------------
Resultado: 6/7 OK · 1 falla · rollback aplicado
PDFs: tmp/pruebas_nom068/ (si --pdf)
```

- Exit code 0 si todo OK, 1 si hay fallas (para poder encadenarlo en scripts).
- Cada falla con descripción concreta reutilizable como hallazgo:
  `[F-18] [FORMULARIO] proteccion_camion visible en frenos de aire`.

---

## Reglas de implementación

1. Comando en `src/Command/PruebasNom068Command.php`.
2. Usar SIEMPRE las Tables/entidades reales del sistema (validación y hooks activos);
   prohibido INSERT directo.
3. Transacción global con rollback por defecto; `--keep`/`--pdf` la confirman.
4. Los "sets de campos por formato" se leen de la fuente real
   (TipoVehiculoRequisitos / Nom068Formato), no de listas duplicadas en el comando —
   si un template diverge de esas clases, eso se detecta en la prueba visual, no aquí;
   documentar esta limitación en el encabezado del comando.
5. No modificar código de producción para hacer pasar pruebas; si una prueba revela
   un bug, reportarlo como falla y detenerse.
6. Commit único: `test(nom068): comando pruebas_nom068 automatizadas`.
7. Al terminar, ejecutar el comando completo y pegar la salida real en el reporte.
