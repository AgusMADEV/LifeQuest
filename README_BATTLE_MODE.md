# LifeQuest - Modo Batalla

Este paquete añade el módulo **Modo Batalla**.

## Archivos incluidos

- `app/Models/BattleSession.php`
- `app/Controllers/BattleController.php`
- `public/battle.php`
- `assets/js/battle.js`
- `assets/css/battle.css`
- `database/battle_sessions_migration.sql`

## Qué permite

- Seleccionar una misión pendiente.
- Iniciar un temporizador de concentración.
- Pausar y reiniciar la sesión.
- Registrar resultado: completada, parcial o fallida.
- Guardar historial de sesiones.
- Sumar XP y LifeCoins.
- Completar la misión si el resultado es "Completada".
- Recalcular progreso de retos y metas asociados.

## Instalación

1. Copia la carpeta `LifeQuest` sobre tu proyecto actual.
2. Si no tienes la tabla `battle_sessions`, importa:

```text
LifeQuest/database/battle_sessions_migration.sql
```

3. Abre:

```text
http://localhost/LifeQuest/public/battle.php
```

## Nota

Este módulo necesita que ya exista la tabla `tasks`.
Si todavía no tienes misiones/tareas creadas, podrás usar una sesión libre o crear misiones en:

```text
http://localhost/LifeQuest/public/tasks.php
```
