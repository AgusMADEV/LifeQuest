# Changelog - 22-05-2026

## Resumen

Esta entrada recoge la evolución real de LifeQuest a partir de la base de productividad original y de las ampliaciones de gamificación incorporadas en el proyecto.

La versión actual consolida:

- Autenticación y sesiones protegidas.
- Gestión de áreas, metas, retos, misiones y hábitos.
- Progreso global con XP, niveles, rachas y objetivo diario.
- HP global, progresión por área y hábitos de riesgo.
- Tienda pública con indulgencias y catálogo cosmético.
- Portal administrativo separado del flujo de usuario.

## Added

- `HP` global del personaje en modelo y UI.
- Progresión por área mediante `area_progression`.
- Hábitos negativos con penalización de HP.
- Objetivo diario con registro persistente en `daily_objectives`.
- Tienda pública en `public/shop.php`.
- Inventario cosmético para recompensas equipables.
- Catálogo base de indulgencias y cosméticos con lógica de reinyectado cuando falta contenido por defecto.
- Portal administrativo con acceso independiente y utilidades de base de datos.

## Changed

- `public/goals.php` actúa como punto de entrada unificado para metas, retos y misiones.
- `public/dashboard.php` y `public/progress.php` reutilizan cálculos comunes de evolución y objetivo diario.
- `public/habits.php` integra el control de hábitos de riesgo y el estado diario de hoy.
- La tienda pública separa claramente indulgencias y cosméticos.
- La nomenclatura de dominio se mantiene consistente entre tablas, modelos y vistas.

## Fixed

- Se corrigió la sincronización de rachas al desmarcar un check-in para evitar descensos incorrectos a cero.
- Se corrigió el uso de placeholders en consultas de progresión por área para evitar errores PDO.
- Se normalizó el comportamiento de los hábitos de control para que la penalización y el estado diario se calculen de forma consistente.

## Database

Migraciones presentes en el repositorio:

- `database/hp_migration.sql`
- `database/negative_habits_migration.sql`
- `database/area_progression_migration.sql`
- `database/daily_objectives_migration.sql`
- `database/shop_indulgence_migration.sql`
- `database/shop_inventory_migration.sql`
- `database/shop_reward_images_migration.sql`
- `database/admin_portal_auth_migration.sql`
- `database/habit_control_reward_migration.sql`

## Lectura de la evolución

La evolución del proyecto no debe leerse como una lista de ideas, sino como una secuencia de ampliaciones sobre un núcleo ya funcional. Primero se consolidó la gestión principal de usuario y contenido; después se añadieron capas de gamificación y, finalmente, módulos de apoyo como la tienda, la progresión por área y el portal administrativo.

La descripción completa de esa evolución está en [docs/evolucion-del-proyecto.md](docs/evolucion-del-proyecto.md).
