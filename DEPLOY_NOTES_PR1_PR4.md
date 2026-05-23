# Notas de despliegue - PR1 a PR4

## Objetivo

Desplegar HP, habitos negativos, progresion por area y tienda de indulgencias con bajo riesgo y rollback claro.

## 1. Pre-deploy

- Confirmar backup de base de datos (dump completo).
- Confirmar rama y commit objetivo en entorno.
- Confirmar que config/config.php tiene credenciales correctas.

## 2. Orden de migraciones

Aplicar en este orden:
1. database/hp_migration.sql
2. database/negative_habits_migration.sql
3. database/area_progression_migration.sql
4. database/shop_indulgence_migration.sql

Motivo:
- Habitos negativos y tienda usan HP.
- Progresion por area depende de acciones que ya otorgan XP.

## 3. Flags recomendados por fase

Fase inicial segura:
- FEATURE_HP_SYSTEM = true
- FEATURE_NEGATIVE_HABITS = false
- FEATURE_AREA_PROGRESSION = false
- FEATURE_INDULGENCE_SHOP = false

Activacion progresiva:
- Paso 1: activar FEATURE_NEGATIVE_HABITS.
- Paso 2: activar FEATURE_AREA_PROGRESSION.
- Paso 3: activar FEATURE_INDULGENCE_SHOP.

Activacion total (estado objetivo):
- FEATURE_HP_SYSTEM = true
- FEATURE_NEGATIVE_HABITS = true
- FEATURE_AREA_PROGRESSION = true
- FEATURE_INDULGENCE_SHOP = true

## 4. Smoke test post-deploy

Validar en este orden:
1. Login y carga de dashboard.
2. Carga de habitos, perfil, progreso y tienda.
3. Habito normal: cambia XP y points.
4. Habito de riesgo: cambia solo HP.
5. Racha sidebar coherente tras marcar/desmarcar.
6. Nivel por areas visible tras acciones con area.
7. Tienda: canjea indulgencia, descuenta points, aplica HP, respeta limite semanal.

## 5. Observabilidad minima

- Revisar logs de PHP y Apache/Nginx durante 15-30 minutos.
- Vigilar especialmente:
  - consultas de area_progression,
  - canjes en reward_redemptions,
  - flujo de toggle de habitos.

## 6. Rollback rapido

Si falla una fase recien activada:
1. Poner en false el flag de esa fase.
2. Si es necesario revertir schema, usar rollback correspondiente:
   - database/shop_indulgence_migration_rollback.sql
   - database/area_progression_migration_rollback.sql
   - database/negative_habits_migration_rollback.sql
   - database/hp_migration_rollback.sql
3. Restaurar backup si hay inconsistencia de datos.

## 7. Riesgos conocidos y mitigacion

- Racha inconsistentes:
  - Mitigado con recalculo y sincronizacion desde habitos activos no negativos.
- Error HY093 PDO por placeholders repetidos:
  - Mitigado usando placeholders unicos en queries criticas.
- Diferencias de entorno por migraciones parciales:
  - Mitigado con chequeos de columnas/tablas en modelos sensibles.

## 8. Criterio de salida de despliegue

Se considera estable cuando:
- No hay fatal errors.
- Flujo base y nuevas funciones pasan smoke test.
- No hay regresion visible en XP, points, racha y navegacion.
