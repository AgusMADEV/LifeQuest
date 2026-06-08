# LifeQuest

LifeQuest es una aplicación web de productividad gamificada en PHP + MySQL orientada a la gestión de áreas de vida, metas, retos, misiones, hábitos y progreso personal.

## Estado actual

Funcionalidad presente en el código:

- Autenticación de usuario con registro, login, logout y sesiones protegidas.
- Gestión de áreas de vida con CRUD propio.
- Gestión de metas, retos y misiones con `public/goals.php` como punto de entrada unificado.
- Hábitos con control diario, estadísticas y soporte para hábitos de riesgo.
- Dashboard con resumen general, objetivo diario, progreso y bloques de actividad.
- Perfil y progreso con métricas, historial y evolución del jugador.
- Tienda pública con recompensas e indulgencias, más inventario de cosméticos.
- Portal de administración separado en `admin/` con acceso propio, explorador de base de datos y utilidades administrativas.
- Sistema de progresión adicional con HP global, progresión por área y bonus ligados al objetivo diario.

## Documentación canónica

- [Estado real del proyecto](docs/estado-real-del-proyecto.md)
- [Evolución del proyecto](docs/evolucion-del-proyecto.md)
- [Instalación](INSTALL.md)

## Arquitectura

- `public/`: rutas y pantallas principales de la aplicación.
- `admin/`: acceso al panel administrativo.
- `app/Controllers/`: validación de entrada y coordinación de la lógica de negocio.
- `app/Models/`: acceso a datos y reglas de persistencia.
- `app/Support/`: utilidades reutilizables para cálculos y presentación.
- `app/Database/connection.php`: conexión PDO centralizada.
- `config/`: configuración del entorno.
- `database/`: esquema y migraciones.
- `assets/`: CSS, JavaScript y ficheros subidos.
- `icons/` y `referencias/`: recursos visuales del proyecto.

## Rutas principales

- `public/index.php`: entrada pública.
- `public/login.php`, `public/register.php`, `public/logout.php`: autenticación.
- `public/dashboard.php`: panel principal.
- `public/areas.php`: áreas de vida.
- `public/goals.php`: metas, retos y misiones.
- `public/habits.php`: hábitos.
- `public/progress.php`: progreso.
- `public/profile.php`: perfil.
- `public/shop.php`: tienda.
- `admin/login.php`, `admin/index.php`, `admin/database.php`: panel administrativo.

## Compatibilidad de dominio

Para mantener coherencia entre base de datos, backend y vistas:

- Área = `life_areas`
- Meta = `goals`
- Reto = `projects`
- Misión = `tasks`

## Configuración rápida

1. Crea la base de datos `lifequest`.
2. Importa `database/schema.sql`.
3. Configura `config/config.php` con tus credenciales reales.
4. Abre `http://localhost/LifeQuest/public`.

La instalación detallada está en [INSTALL.md](INSTALL.md).