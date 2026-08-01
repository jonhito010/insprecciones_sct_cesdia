# FIX_DESAJUSTES_UI_BD.md · Rines por llanta y mediciones XXXIX

> Correcciones de 2 desajustes entre formulario ↔ validación ORM ↔ esquema MySQL,
> detectados durante las pruebas funcionales (2026-08-01).
> Ejecutar junto con `HALLAZGOS_PRUEBAS.md` para regenerar PDFs una sola vez.

---

## FIX 1 · Rines: de pares a llanta individual

### Problema
- La UI de rines (P2.5) captura **una fila por llanta** (1, 2, 3 … 8/10/12),
  como pide la tabla del formato oficial (una fila por llanta).
- La columna `par_rines` en `inspeccion_rines` es `ENUM('1-2','3-4','5-6','7-8','9-10','11-12')`.
- El formulario manda `"7"`; MySQL no lo acepta → guardado falla o se trunca.

### Quién tiene razón
**El formulario.** El formato oficial mide por llanta individual: una llanta puede
tener la maza chorreada de aceite y su pareja no. El enum de pares es legado.

### Corrección
1. **Nuevo alter con rollback** (`config/schema/alter_inspeccion_rines_numero_llanta_fx1.sql`
   + `rollback/`):
   - Agregar `numero_llanta` TINYINT UNSIGNED NULL (1-12).
   - **NO eliminar** `par_rines` (deprecada, se evalúa al final del proyecto).
2. **Migración de datos existentes** (en el mismo script):
   - Cada registro con `par_rines = 'N-M'` genera **2 filas**: una con
     `numero_llanta = N` y otra con `numero_llanta = M`, copiando los mismos
     valores (tuercas, birlos, maza, balero). Interpretación conservadora.
   - Registrar en log de migración cuántas filas se expandieron.
3. **Código:**
   - `InspeccionRinesTable`: validar `numero_llanta` entero 1-12; límite superior
     según formato (10 motrices, 12 remolque, 8 dolly) usando la fuente real
     (`Nom068Formato` / `TipoVehiculoRequisitos`).
   - Entidad, formulario (`templates/element/Inspeccion/rines.php`) y PDF
     (`pdf_lista.php`): usar `numero_llanta`.
   - Guardado nuevo: solo `numero_llanta`; `par_rines` queda NULL en filas nuevas.
4. **Unicidad:** índice único (`inspeccion_id`, `numero_llanta`) para evitar
   duplicados por llanta.

### Verificación
- [ ] Guardar rines de llanta 7 en un T3 → persiste y se relee.
- [ ] Registro legacy con `par_rines='7-8'` migrado a 2 filas (7 y 8).
- [ ] PDF imprime la tabla de rines por llanta con las filas completas (10/12/8).
- [ ] No se puede guardar 2 filas con el mismo `numero_llanta` en la misma inspección.

---

## FIX 2 · Mediciones XXXIX: validación PHP incorrecta

### Problema
- `presion_cierre_con_disp` y `presion_cierre_sin_disp` (F-17, mediciones XXXIX
  en PSI) son `DECIMAL` en BD — correcto, son números (ej. 60, 45).
- Pero en `InspeccionSistemaAireTable` están incluidas en la lista de campos
  que solo admiten `CUMPLE` / `NO CUMPLE` / `N/A`.
- Resultado: guardar `60` es rechazado por el ORM aunque la columna lo soporta.

### Quién tiene razón
**La columna.** XXXIX es una medición numérica del formato oficial F-17:
- XXXIX-a: presión en PSI cuando la válvula de suministro del remolque se cerró
  automáticamente CON dispositivo de cierre.
- XXXIX-b: ídem SIN dispositivo de cierre.

### Corrección (solo PHP, sin cambio de schema)
1. En `InspeccionSistemaAireTable`, **sacar** ambas columnas de la lista de campos
   enum CUMPLE/NO CUMPLE/NA.
2. Agregar validación numérica: `decimal`, rango 0–150 (PSI), `allowEmpty` (el
   campo puede quedar vacío si no aplica).
3. Confirmar que el formulario F-17 las renderiza como input numérico (no select)
   y que el PDF las imprime como número en los recuadros XXXIX.
4. Confirmar que estas mediciones **solo** aparecen en F-17 (no F-18/F-21).

### Verificación
- [ ] Guardar 60 y 45 en un T3 → persiste, se relee y se imprime en el PDF.
- [ ] F-18 y F-21 no muestran los campos XXXIX.
- [ ] XXXV y XXXVIII no fueron afectadas (validar que sigan guardando).

---

## Reglas de ejecución (Claude Code)

1. Commit por fix:
   - `fix(nom068): rines por llanta individual (alter + migracion datos)`
   - `fix(nom068): XXXIX validacion numerica en sistema aire`
2. **NO modificar migraciones ya aplicadas.** El alter de rines es un script NUEVO.
3. **NO aplicar el alter a la BD** — dejarlo listo y avisar; la aplicación es manual:
   ```bash
   mysqldump -uadmin -p inspecciones > ~/respaldo_pre_fx1_$(date +%Y%m%d).sql
   mysql -uadmin -p inspecciones < config/schema/alter_inspeccion_rines_numero_llanta_fx1.sql
   rm -f tmp/cache/models/*
   ```
4. Al terminar ambos fixes: correr tests (deben seguir 12/12) y
   `php bin/cake.php pruebas_nom068 --formato=F17` si el comando ya existe.
5. Reportar: archivos tocados, filas que expandirá la migración de rines
   (query de conteo), y confirmación de tests.
