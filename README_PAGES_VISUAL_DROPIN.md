# LifeQuest - Pantallas y rutas visuales

Resumen de la estructura visual actual y rutas de compatibilidad.

## Archivos incluidos

- `public/areas.php`
- `public/goals.php`
- `assets/css/styles.css`

## Cambios

- Áreas, Metas, Retos y Misiones usan la misma sidebar de LifeQuest.
- Topbar con buscador, XP, LifeCoins y perfil.
- Formularios y listados adaptados a tarjetas modernas.
- Nomenclatura consistente: Metas (`goals`), Retos (`projects`), Misiones (`tasks`).
- `public/goals.php` centraliza secciones internas por query string (`section=goals|projects|tasks`).
- Retos y misiones se abren directamente desde `public/goals.php` usando `section=projects` o `section=tasks`.

## Instalación

1. Copia la carpeta `LifeQuest` sobre tu proyecto actual.
2. Acepta sobrescribir archivos.
3. Prueba:

```text
http://localhost/LifeQuest/public/areas.php
http://localhost/LifeQuest/public/goals.php
http://localhost/LifeQuest/public/goals.php?section=projects
http://localhost/LifeQuest/public/goals.php?section=tasks
```

Notas:

- Para nuevas integraciones, apunta directamente a `public/goals.php` con `section=projects` o `section=tasks`.
