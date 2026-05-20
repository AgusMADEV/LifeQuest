# Instrucciones de Instalación - QuestBoard

## Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- XAMPP/WAMP/MAMP (recomendado para desarrollo local)

## Pasos de Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd QuestBoard
```

### 2. Configurar la base de datos

1. Crea una base de datos MySQL:
   ```sql
   CREATE DATABASE questboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Importa el esquema de la base de datos:
   ```bash
   mysql -u tu_usuario -p questboard < database/schema.sql
   ```

### 3. Configurar el archivo de configuración

1. Copia el archivo de configuración de ejemplo:
   ```bash
   cp config/config.example.php config/config.php
   ```

2. Edita `config/config.php` con tus credenciales:
   - `DB_HOST`: Host de tu base de datos (generalmente 'localhost')
   - `DB_NAME`: Nombre de la base de datos ('questboard')
   - `DB_USER`: Usuario de MySQL
   - `DB_PASS`: Contraseña de MySQL
   - `APP_URL`: URL base de tu aplicación

### 4. Configurar el servidor web

Para desarrollo local con XAMPP:
- Coloca el proyecto en la carpeta `htdocs/QuestBoard`
- Accede a `http://localhost/QuestBoard/public`

### 5. Verificar la instalación

Visita `http://localhost/QuestBoard/public` en tu navegador para verificar que la aplicación funciona correctamente.

## Problemas Comunes

- **Error de conexión a la base de datos**: Verifica las credenciales en `config/config.php`
- **Página en blanco**: Activa el reporte de errores en PHP para ver los mensajes de error

## Seguridad

**IMPORTANTE**: Nunca subas el archivo `config/config.php` con tus credenciales reales a un repositorio público.
