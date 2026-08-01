# CLAUDE.md — F-04 REV.01 · ORDEN DE SERVICIO DE CONDICIONES FISICO-MECANICAS (NOM-068-SCT-2-2014)

> Documento de validación. El sistema y el formulario de captura DEBEN contener todos los campos listados aquí. Marcar ✅ si existe en el sistema, ❌ si falta.

## Identificación del documento
| Atributo | Valor |
|---|---|
| Código de formato | F-04 |
| Revisión | REV. 01 |
| Título | ORDEN DE SERVICIO DE CONDICIONES FISICO-MECANICAS NOM-068-SCT-2-2014 |
| Empresa | CENTRO DE SERVICIO Y DIAGNÓSTICO INTEGRAL AL AUTOTRANSPORTE, S.A. DE C.V. (CESDIA) |
| Naturaleza | Contrato de prestación de servicios / Orden de trabajo de inspección físico-mecánica |

## Campos del encabezado del contrato (capturables)
| # | Campo | Tipo sugerido | Obligatorio |
|---|---|---|---|
| 1 | Día de celebración del contrato | int (1-31) | Sí |
| 2 | Mes de celebración | string / int (1-12) | Sí |
| 3 | Año de celebración | int (4 dígitos) | Sí |
| 4 | Nombre del solicitante (en cláusula del contrato) | string | Sí |
| 5 | Número de acreditación de la UV | string | Sí |
| 6 | Número de aprobación de la UV | string | Sí |

Domicilio fijo de la UV (texto estático, no capturable): PARCELA RUSTICA NÚMERO TRES DEL EJIDO DE SAN FRANCISCO KOBEN, CAMPECHE.

## Sección: DATOS DEL SOLICITANTE (capturables)
| # | Campo | Tipo sugerido | Obligatorio |
|---|---|---|---|
| 1 | NOMBRE | string | Sí |
| 2 | DIRECCIÓN | string | Sí |
| 3 | PLACAS | string | Sí |
| 4 | SERIE (NIV/VIN) | string | Sí |
| 5 | R.F.C. | string (12-13) | Sí |
| 6 | MODELO (año) | string/int | Sí |
| 7 | TIPO DE VEHICULO | catálogo (tracto-camión, camión C-2/C-3, remolque S-2/S-3, dolly, autobús) | Sí |

⚠️ El TIPO DE VEHICULO determina qué lista de inspección se genera después (F-17, F-18, F-19, F-20 o F-21).

## Texto estático obligatorio en el PDF (no capturable, debe imprimirse)

### Recuadro NOM
- Título: NORMA OFICIAL MEXICANA NOM-068-SCT-2-2014
- Descripción: "Transporte terrestre-Servicio de autotransporte federal de pasaje, turismo, carga, sus servicios auxiliares y transporte privado-Condiciones físico-mecánica y de seguridad para la operación en vías generales de comunicación de jurisdicción federal."

### EL PRESTADOR DE SERVICIO (5 viñetas)
1. Realizará sus servicios dentro de las instalaciones.
2. Emitirá un dictamen de Inspección (certificado).
3. Declara que esta unidad cuenta con un seguro de responsabilidad civil.
4. La UV no se hace responsable por fallas mecánicas o electrónicas dentro de sus instalaciones.
5. El personal de la Unidad de Inspección se encuentra libre de cualquier presión comercial, financiera o de otro tipo que pudiera influir en los resultados de la verificación.

### EL SOLICITANTE (5 viñetas)
1. Se apegará estrictamente a los dictámenes y resultados emitidos.
2. Cubrirá el costo de la Inspección en una sola exhibición.
3. Cláusula de confidencialidad de datos técnicos (aviso de divulgación cuando la ley o compromisos contractuales lo requieran).
4. Aviso de divulgación a organizaciones reguladoras (ema, SCT y demás instancias) y autorización de uso de datos a favor de CESDIA a la firma.
5. Derecho de queja/apelación: formato de quejas y apelaciones, vía telefónica o correo ceo@cesdia.com, o ante la Entidad Mexicana de Acreditación 01 55 91484300.

## Firmas
| # | Campo |
|---|---|
| 1 | FIRMA DEL SOLICITANTE |
| 2 | FIRMA PRESTADOR DE SERVICIO |

## Checklist de validación del sistema
- [ ] Existe entidad orden de servicio ligada a cliente/solicitante
- [ ] Captura fecha de contrato (día, mes, año) por separado o como date
- [ ] Captura los 7 campos de DATOS DEL SOLICITANTE
- [ ] Captura número de acreditación y aprobación (pueden ser config global de la UV)
- [ ] El tipo de vehículo es catálogo y dispara el formato de inspección correcto
- [ ] El PDF generado incluye TODO el texto estático (NOM, prestador, solicitante)
- [ ] El PDF incluye ambas líneas de firma
- [ ] Logo CESDIA + código F-04 REV. 01 en encabezado
