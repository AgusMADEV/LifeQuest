# LifeQuest - Unificación completa de nombre

El proyecto ahora usa **LifeQuest** de manera consistente en todos los aspectos.

## Qué se ha cambiado

- Nombre visible de la app: `LifeQuest`
- Logo/textos principales en las vistas
- Documentación principal
- `APP_NAME` en `config/config.php`: **LifeQuest**
- `APP_URL`: `http://localhost/LifeQuest/public`
- Nombre de sesión: `lifequest_session`
- **Base de datos**: `lifequest` (anteriormente `questboard`)
- **Usuario BD**: `lifequest` (anteriormente `questboard`)

## Migración de base de datos

Si tienes una instalación existente con la BD `questboard`, sigue estos pasos:

1. **Haz backup** de tu base de datos actual
2. Ejecuta el script de migración: [`database/rename_db_to_lifequest.sql`](database/rename_db_to_lifequest.sql)
3. El script:
   - Crea la nueva BD `lifequest`
   - Crea el usuario `lifequest` 
   - Copia todos los datos de `questboard` → `lifequest`
   - Mantiene tu BD antigua intacta por seguridad

## Instalación nueva

Si instalas desde cero:

1. Ejecuta [`database/schema.sql`](database/schema.sql) - creará la BD `lifequest`
2. Configura [`config/config.php`](config/config.php) con tus credenciales
3. Todo listo ✅

## Notas técnicas

- El usuario final **nunca ve** el nombre interno de la BD
- La unificación mejora la consistencia del código
- Ambos nombres (`questboard` / `lifequest`) son técnicamente válidos para un TFG
