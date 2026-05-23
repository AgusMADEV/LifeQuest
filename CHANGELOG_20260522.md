# Changelog - 22-05-2026

## Resumen

Se implementa la fase completa PR1-PR4 del nuevo enfoque de LifeQuest:
- HP del personaje.
- Habitos negativos con impacto solo en HP (V1).
- Progresion por area en paralelo al XP global.
- Tienda unica enfocada en indulgencias (base lista para cosmeticos futuros).
- Correccion de bug de racha al desmarcar check-ins.

## Added

- Sistema de HP global del personaje en modelo y UI.
- Flags de feature para despliegue incremental.
- Soporte de habitos negativos en backend y UI.
- Modelo de progresion por area.
- Tabla area_progression y su flujo de actualizacion.
- Nueva pagina de tienda: shop.php.
- Modelo Reward para tienda de indulgencias.
- Lote inicial de indulgencias por usuario (seed controlado).
- Limite semanal de canje por indulgencia.

## Changed

- Dashboard y perfil muestran HP cuando el feature esta activo.
- Sidebar y enlaces del dashboard ahora apuntan a la tienda real.
- Habits toggle maneja casos de habito de riesgo sin alterar XP/puntos en V1.
- Racha global de usuario sincronizada desde habitos activos no negativos.
- Dashboard incluye bloque de nivel por areas cuando la feature esta activa.

## Fixed

- Bug de racha: al desmarcar un check-in la racha podia caer a 0 de forma incorrecta.
  - Solucion: la racha actual se calcula anclada al ultimo dia marcado del habito.
- Error PDO HY093 en AreaProgression por placeholder repetido.
  - Solucion: placeholders nombrados unicos en la consulta de getTopByUser.

## Database

Nuevos scripts de migracion incluidos:
- database/hp_migration.sql
- database/negative_habits_migration.sql
- database/area_progression_migration.sql
- database/shop_indulgence_migration.sql

Scripts de rollback incluidos:
- database/hp_migration_rollback.sql
- database/negative_habits_migration_rollback.sql
- database/area_progression_migration_rollback.sql
- database/shop_indulgence_migration_rollback.sql

## Config Flags

Se usan flags para activar despliegue incremental:
- FEATURE_HP_SYSTEM
- FEATURE_NEGATIVE_HABITS
- FEATURE_AREA_PROGRESSION
- FEATURE_INDULGENCE_SHOP

## Compatibilidad

- Cambios SQL no destructivos (aditivos) para forward rollout.
- Fallbacks en modelos para minimizar roturas cuando una migracion aun no se aplico.
- XP y nivel global se mantienen como fuente principal para no romper pantallas actuales.
