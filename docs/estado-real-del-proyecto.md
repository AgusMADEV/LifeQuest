# Estado real del proyecto

Este documento resume únicamente lo que está presente en el repositorio y en el flujo real de la aplicación.

## Descripción funcional

LifeQuest es una aplicación web en PHP + MySQL para gestión personal gamificada. El usuario puede registrar y consultar su progreso a través de áreas de vida, metas, retos, misiones y hábitos, con una capa adicional de gamificación basada en XP, niveles, rachas, HP y recompensas.

## Módulos visibles para el usuario

- Autenticación pública: inicio de sesión, registro y cierre de sesión.
- Dashboard: resumen general, objetivo diario, actividad y bloques de progreso.
- Áreas: gestión de áreas de vida y su progreso.
- Metas, retos y misiones: administración unificada desde `public/goals.php`.
- Hábitos: creación, seguimiento diario y estado de hábitos de control y riesgo.
- Progreso: métricas, evolución y consolidación del avance del jugador.
- Perfil: estado del jugador y datos asociados.
- Tienda: recompensas, indulgencias y cosméticos.
- Panel administrativo: acceso independiente para gestión y exploración de base de datos.

## Arquitectura real

- `public/` contiene las rutas principales de la aplicación.
- `admin/` contiene el panel administrativo.
- `app/Controllers/` coordina validación y acciones de formulario.
- `app/Models/` encapsula acceso a base de datos y reglas de negocio.
- `app/Support/` centraliza cálculos reutilizables y utilidades de presentación.
- `database/` contiene el esquema base y las migraciones acumuladas.
- `assets/`, `icons/` y `referencias/` contienen recursos visuales y subidas.

## Tablas y entidades relevantes

- `users`
- `life_areas`
- `goals`
- `projects`
- `tasks`
- `habits`
- `daily_objectives`
- `area_progression`
- `rewards`
- `user_reward_inventory`
- `admin_portal_users`
- `app_settings`

## Reglas de negocio ya presentes

- El objetivo diario se registra una sola vez por usuario y día.
- Los hábitos de control y los hábitos de riesgo no se tratan igual: los segundos afectan a HP.
- La progresión por área se calcula por separado del progreso global.
- La tienda diferencia entre indulgencias y cosméticos.
- Los cosméticos equipables se gestionan por usuario y por categoría.

## Rutas principales

- `public/index.php`
- `public/login.php`
- `public/register.php`
- `public/dashboard.php`
- `public/areas.php`
- `public/goals.php`
- `public/habits.php`
- `public/progress.php`
- `public/profile.php`
- `public/shop.php`
- `admin/login.php`
- `admin/index.php`
- `admin/database.php`

## Qué no debe documentarse como entregado si no aparece en el código

- Funcionalidades que solo existan como boceto, plantilla o intención.
- Pantallas o flujos que no tengan archivo real en `public/` o `admin/`.
- Tablas o migraciones que no estén presentes en `database/`.

## Uso recomendado

Este archivo debe servir como referencia base cuando se actualice el README, el changelog o cualquier memoria del proyecto. Si se amplía la aplicación, primero se actualiza el código y después este documento.