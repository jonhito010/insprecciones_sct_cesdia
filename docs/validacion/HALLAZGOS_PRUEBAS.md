# HALLAZGOS_PRUEBAS.md

> Registro durante las pruebas de `PRUEBAS_NOM068.md`.
> Formato: `[FORMATO] [FORMULARIO|PDF] Descripción concreta`
> No corregir al vuelo; completar las 5 pruebas primero.

Fuente automática: `php bin/cake.php pruebas_nom068` (spec en `SPEC_PRUEBAS_AUTO.md`).

---

## F-19 · Remolque S-3

- (OK automático) Carrocerías grano/grava/otro con valores distintos persisten (P0.1).
- (OK automático) Folio A, sin odómetro requerido, 12 slots, mangueras NOM 34 en set.
- `[F-19] [FORMULARIO] rines UI (par_rines numérico 1..12) incompatible con enum de pares en BD`

## F-21 · Autobús AB

- (OK automático) Etiquetas LLANTA 7/8, folio M, volante/holgura, válvula remolque, luces interiores, bastidor.
- `[F-21] [FORMULARIO] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD`

## F-20 · Dolly D2

- (OK automático) 8/4 slots, folio A, acoplamiento NOM 79, sin ABS/XXXV/XXXVIII en set.
- `[F-20] [FORMULARIO] rines UI (par_rines numérico 1..8) incompatible con enum de pares en BD`

## F-18 · Camión C-3

- (OK automático) gaslp_*, hidráulicos 22/26/27-28/29-30/31/32, proteccion_camion solo cabina, luces separadas.
- `[F-18] [FORMULARIO] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD`

## F-17 · Tracto T3

- (OK automático) sin gas LP/convertidor/acoplamientos extra; manitas y quinta rueda; volante/holgura.
- `[F-17] [PERSISTENCIA] XXXIX (presion_cierre_* numéricos) rechazados por validación` — columnas `decimal` en BD pero `InspeccionSistemaAireTable` las valida como CUMPLE/NO CUMPLE/N/A.
- `[F-17] [FORMULARIO] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD`

## Comunes / F-04

- (OK automático) Dictamen↔resultado, cancelar sin tocar dictamen, observaciones ×6, F-04 orden con fecha_contrato ≠ hoy y 7 datos del solicitante.
- `[COMUNES] [FORMULARIO] element rines.php escribe par_rines="1".."N" pero la columna es enum('1-2','3-4',…,'11-12')` — la captura por fila del formato no puede persistir hasta migrar el enum (o adaptar la UI a pares).

---

## Salida real del comando (2026-08-01)

```
NOM-068 · Pruebas automáticas
==============================
[T1] F-19 Remolque S-3 ............. FALLA
     ✗ [F-19] [COMUNES] rines UI (par_rines numérico 1..12) incompatible con enum de pares en BD (enum('1-2','3-4','5-6','7-8','9-10','11-12'))
[T2] F-21 Autobús AB .............. FALLA
     ✗ [F-21] [COMUNES] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD (enum('1-2','3-4','5-6','7-8','9-10','11-12'))
[T3] F-20 Dolly D2/D1 .............. FALLA
     ✗ [F-20] [COMUNES] rines UI (par_rines numérico 1..8) incompatible con enum de pares en BD (enum('1-2','3-4','5-6','7-8','9-10','11-12'))
[T4] F-18 Camión C-3 .............. FALLA
     ✗ [F-18] [COMUNES] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD (enum('1-2','3-4','5-6','7-8','9-10','11-12'))
[T5] F-17 Tracto T3 ................ FALLA
     ✗ [F-17] [PERSISTENCIA] XXXIX (presion_cierre_* numéricos) rechazados por validación
     ✗ [F-17] [COMUNES] rines UI (par_rines numérico 1..10) incompatible con enum de pares en BD (enum('1-2','3-4','5-6','7-8','9-10','11-12'))
[T7] F-04 Orden .................... OK (3 aserciones)
------------------------------
Resultado: 1/6 OK · 5 falla · rollback aplicado
[T6] Comunes ................... (incluido en cada formato)
```

Exit code: **1** (hay fallas; P0.1 y aserciones de formato pasaron salvo los hallazgos anteriores).
