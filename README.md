# LifeQuest

Aplicación web de productividad gamificada en PHP + MySQL para gestionar áreas de vida, metas, retos, misiones, hábitos y progreso personal.

## Estado del proyecto

Implementado y funcional:

- Autenticación: registro, login, logout y sesiones protegidas.
- Áreas de vida: CRUD completo.
- Metas: CRUD completo con relación opcional a áreas.
- Retos: CRUD completo con relación opcional a metas y áreas.
- Misiones: CRUD completo, marcado como completada, recompensas XP/LifeCoins y actualización de progreso en metas/retos.
- Hábitos: creación, check diario y estadísticas por período.
- Dashboard: resumen general, racha semanal y actividad principal.
- Progreso y perfil: métricas, historial y estado del jugador.
- Tienda: canje de recompensas e indulgencias.
- Portal admin: acceso separado en `admin/` con explorador de DB, CRUD visual, consola SQL controlada y sección de balance.

En evolución:

- Modo Batalla y mejoras de estadísticas avanzadas.

## Arquitectura

- `public/`: páginas/rutas principales de la aplicación.
- `app/Controllers/`: validación de entrada y coordinación de lógica.
- `app/Models/`: acceso a base de datos y reglas de persistencia.
- `app/Database/connection.php`: conexión PDO centralizada.
- `database/schema.sql`: esquema completo de la base de datos.
- `assets/css/styles.css`: estilos visuales de la interfaz.

## Compatibilidad de rutas

- `public/goals.php` concentra metas, retos y misiones por sección (`section=goals|projects|tasks`).
- Acceso recomendado a retos y misiones mediante `public/goals.php?section=projects` y `public/goals.php?section=tasks`.
- `admin/index.php` y `admin/login.php` redirigen al panel principal en `admin/database.php?section=db`.

## Configuración rápida

1. Crea la base de datos `lifequest`.
2. Importa `database/schema.sql`.
3. Configura `config/config.php` con tus credenciales reales.
4. Abre `http://localhost/LifeQuest/public`.

Referencia completa en `INSTALL.md`.

## Nomenclatura de dominio

Para mantener coherencia interna y visual:

- Área = `life_areas`
- Meta = `goals`
- Reto = `projects`
- Misión = `tasks`

Esta nomenclatura se usa en base de datos, backend y vistas.