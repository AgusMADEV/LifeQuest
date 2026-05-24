# LifeQuest  
## Documento completo de concepto y planificación del TFG

> Nota de vigencia: este documento es de planificación conceptual. Para rutas y arquitectura implementada, tomar como referencia README.md e INSTALL.md.

---

## 1. Nombre del proyecto

# **LifeQuest**

## Eslogan

> **Convierte tus metas en resultados.**

## Módulo principal

# **Modo Batalla**

> Un modo de concentración donde el usuario elige una tarea, activa un temporizador y trabaja sin distracciones para avanzar en sus objetivos.

---

## 2. Idea general

**LifeQuest** es una aplicación web gamificada de productividad personal que permite organizar metas, hábitos, proyectos y tareas en un tablero centralizado.

La aplicación busca ayudar al usuario a convertir objetivos grandes en acciones diarias, medibles y conectadas entre sí.

No se plantea como un videojuego tradicional, ni como una app medieval o infantil, sino como un **sistema moderno de productividad personal con gamificación ligera**.

La idea principal es:

> Crear un tablero único donde el usuario pueda ver qué quiere conseguir, qué tiene que hacer hoy, qué hábitos debe mantener y cómo está progresando.

---

## 3. Concepto resumido

LifeQuest combina:

- gestión de metas
- gestión de hábitos
- gestión de proyectos
- tareas pendientes
- estadísticas
- recompensas
- rachas
- puntos
- niveles
- modo concentración
- dashboard central

El objetivo es que el usuario deje de tener sus metas repartidas entre varias aplicaciones y pueda centralizarlo todo en un único sistema.

---

## 4. Descripción académica para el TFG

> **LifeQuest es una aplicación web gamificada de productividad personal que permite gestionar metas, hábitos, proyectos y tareas mediante un dashboard centralizado. La aplicación incorpora mecánicas de gamificación como puntos, niveles, rachas, recompensas y un modo de concentración llamado Modo Batalla, con el objetivo de favorecer la organización, la constancia y la medición del progreso del usuario.**

Otra versión más formal:

> **Desarrollo de una aplicación web para la gestión integral de metas, hábitos y proyectos personales mediante un sistema gamificado de seguimiento, recompensas y estadísticas de progreso.**

---

## 5. Problema que resuelve

Muchas personas tienen objetivos importantes, pero no consiguen convertirlos en acciones concretas.

Problemas habituales:

- Tener demasiadas metas y poca claridad.
- No saber qué priorizar.
- No medir el progreso.
- Abandonar hábitos por falta de seguimiento.
- Usar demasiadas herramientas separadas.
- No conectar tareas diarias con objetivos importantes.
- Falta de motivación visual.
- Falta de constancia.
- No tener un sistema que obligue a revisar, ejecutar y medir.

LifeQuest busca resolver estos problemas mediante un tablero único donde todo esté conectado.

---

## 6. Propuesta de valor

LifeQuest ofrece:

- Un dashboard central con todo lo importante.
- Metas conectadas a proyectos y tareas.
- Hábitos vinculados al progreso personal.
- Estadísticas claras.
- Sistema de puntos, XP y niveles.
- Rachas para reforzar la constancia.
- Modo Batalla para ejecutar sin distracciones.
- Tiendita para recompensas personales.
- Zona Peligrosa para acciones críticas.
- Diseño moderno, limpio y usable.

---

## 7. Público objetivo

LifeQuest está pensado para personas que quieren mejorar su organización, disciplina y progreso personal.

Usuarios ideales:

- Estudiantes.
- Desarrolladores.
- Creadores de contenido.
- Emprendedores.
- Profesionales.
- Personas que trabajan por objetivos.
- Personas que quieren mejorar hábitos.
- Personas que necesitan convertir metas grandes en tareas diarias.
- Personas interesadas en productividad, disciplina y crecimiento personal.

---

## 8. Diferencia respecto a una plantilla de Notion

La inspiración visual puede venir de dashboards de productividad tipo Notion, pero LifeQuest debe ser una aplicación propia y dinámica.

### Una plantilla de Notion

- Organiza información.
- Es principalmente manual.
- No tiene backend propio.
- No tiene lógica de gamificación programada.
- No calcula estadísticas complejas automáticamente.
- No tiene sesiones de concentración propias.
- No tiene autenticación desarrollada por nosotros.
- No tiene una base de datos relacional creada para el proyecto.

### LifeQuest

- Tendrá usuarios reales.
- Guardará datos en una base de datos MySQL.
- Tendrá backend propio en PHP.
- Calculará estadísticas automáticamente.
- Relacionará metas, proyectos, hábitos y tareas.
- Sumará puntos y experiencia.
- Tendrá sistema de niveles.
- Tendrá Modo Batalla con temporizador.
- Tendrá recompensas canjeables.
- Tendrá zona de acciones críticas.
- Será responsive.
- Será defendible como aplicación web completa.

---

## 9. Estilo gráfico

El estilo visual elegido para LifeQuest será:

# **Minimalismo táctico gamificado**

También puede describirse como:

> Dashboard moderno de productividad con gamificación ligera.

## Características visuales

- Diseño limpio.
- Mucho espacio en blanco.
- Tarjetas claras.
- Tipografía moderna.
- Iconografía sencilla.
- Acentos de color controlados.
- Barras de progreso.
- Métricas visibles.
- Estética actual.
- Sensación de orden, foco y disciplina.

## Lo que se quiere transmitir

- Claridad.
- Foco.
- Control.
- Progreso.
- Disciplina.
- Ejecución.
- Motivación.
- Resultados.

## Lo que se debe evitar

- Estética medieval.
- Magos, cofres, espadas o castillos.
- Exceso de fantasía.
- Diseño infantil.
- Pantallas sobrecargadas.
- Demasiados colores.
- Demasiados elementos en una sola vista.

---

## 10. Paleta de colores propuesta

### Opción principal: modo claro moderno

```css
--bg-main: #F8FAFC;
--bg-card: #FFFFFF;
--bg-soft: #E5E7EB;
--primary: #16A34A;
--secondary: #2563EB;
--accent: #F59E0B;
--danger: #DC2626;
--text-main: #0F172A;
--text-muted: #64748B;
--border: #E2E8F0;
```

### Opción modo oscuro futura

```css
--bg-main: #0F172A;
--bg-card: #111827;
--bg-soft: #1E293B;
--primary: #22C55E;
--secondary: #3B82F6;
--accent: #F59E0B;
--danger: #EF4444;
--text-main: #F8FAFC;
--text-muted: #94A3B8;
--border: #334155;
```

---

## 11. Funcionalidades principales

---

# 11.1. Usuarios

El sistema permitirá que cada usuario tenga su propio espacio privado.

## Funciones

- Registro.
- Inicio de sesión.
- Cierre de sesión.
- Sesión protegida.
- Perfil básico.
- Nivel.
- XP.
- Puntos.
- Racha actual.

## Campos principales

- Nombre.
- Email.
- Contraseña cifrada.
- Avatar opcional.
- Nivel.
- XP.
- Puntos.
- Racha.
- Fecha de registro.

---

# 11.2. Dashboard principal

El dashboard será la pantalla central de LifeQuest.

Debe ser limpio y mostrar solo lo importante.

## Elementos del dashboard

- Saludo del usuario.
- Nivel y XP.
- Racha actual.
- Prioridad del día.
- Áreas de vida.
- Metas principales.
- Hábitos de hoy.
- Tareas pendientes.
- Stats rápidas.
- Acceso a Modo Batalla.
- Acceso a La Tiendita.
- Acceso a Zona Peligrosa.

## Estructura visual recomendada

```text
[Header: saludo + botón nueva acción]

[Nivel / XP]    [Racha]    [Prioridad del día]

[Áreas de vida]     [Metas principales]

[Hábitos de hoy]    [Tareas pendientes]    [Stats rápidas]

[Modo Batalla]      [La Tiendita]          [Zona Peligrosa]
```

---

# 11.3. Áreas de vida

Las áreas de vida sirven para clasificar los objetivos del usuario.

## Ejemplos

- Salud.
- Estudios.
- Trabajo.
- Finanzas.
- Relaciones.
- Desarrollo personal.
- Creatividad.
- Ocio.
- Familia.
- Marca personal.

## Funciones

- Crear área.
- Editar área.
- Eliminar área.
- Asignar color.
- Asignar icono.
- Ver metas asociadas.
- Ver hábitos asociados.
- Calcular progreso del área.

---

# 11.4. Metas

Las metas representan objetivos importantes.

## Tipos de metas

- Diarias.
- Semanales.
- Mensuales.
- Trimestrales.
- Anuales.
- De futuro.

## Campos de una meta

- Título.
- Descripción.
- Área de vida.
- Tipo.
- Prioridad.
- Estado.
- Progreso.
- Fecha de inicio.
- Fecha límite.
- XP de recompensa.
- Puntos de recompensa.

## Estados posibles

- No iniciada.
- En progreso.
- Pausada.
- Completada.
- Cancelada.

## Ejemplo

```text
Meta: Finalizar el TFG
Área: Estudios
Tipo: Anual
Estado: En progreso
Progreso: 40%
```

---

# 11.5. Proyectos

Los proyectos permiten dividir una meta en bloques de trabajo.

## Funciones

- Crear proyecto.
- Editar proyecto.
- Eliminar proyecto.
- Asociar proyecto a una meta.
- Asociar proyecto a un área de vida.
- Calcular progreso según tareas completadas.

## Ejemplo

```text
Meta: Finalizar el TFG
Proyecto: Desarrollo de LifeQuest

Tareas:
- Crear base de datos
- Crear login
- Crear dashboard
- Crear módulo de hábitos
- Crear Modo Batalla
```

---

# 11.6. Tareas / Pendientes

Las tareas son acciones concretas y ejecutables.

## Campos de una tarea

- Título.
- Descripción.
- Proyecto asociado.
- Meta asociada.
- Área de vida.
- Prioridad.
- Estado.
- Fecha límite.
- Tiempo estimado.
- XP de recompensa.
- Puntos de recompensa.

## Estados posibles

- Pendiente.
- En progreso.
- Completada.
- Cancelada.

## Prioridades

- Baja.
- Media.
- Alta.
- Crítica.

## Ejemplo

```text
Tarea: Crear tabla users
Proyecto: Desarrollo de LifeQuest
Prioridad: Alta
Recompensa: +10 XP / +5 puntos
```

---

# 11.7. Hábitos

Los hábitos son acciones repetidas que ayudan al usuario a mantener constancia.

## Ejemplos

- Entrenar.
- Leer.
- Estudiar.
- Beber agua.
- Meditar.
- Dormir mejor.
- Revisar planificación.
- Trabajar en el TFG.

## Campos de un hábito

- Nombre.
- Descripción.
- Área de vida.
- Meta asociada.
- Frecuencia.
- Racha actual.
- Mejor racha.
- Estado.
- XP de recompensa.
- Puntos de recompensa.

## Funciones

- Crear hábito.
- Editar hábito.
- Eliminar hábito.
- Marcar hábito como completado.
- Registrar cumplimiento diario.
- Calcular racha.
- Calcular porcentaje de cumplimiento.

---

# 11.8. Stats / Estadísticas

Las estadísticas mostrarán el progreso del usuario.

## Métricas iniciales

- Tareas completadas.
- Hábitos completados.
- Metas en progreso.
- Proyectos activos.
- XP ganado.
- Puntos acumulados.
- Racha actual.
- Tiempo enfocado.
- Sesiones de Modo Batalla.
- Progreso general.

## Visualizaciones posibles

- Tarjetas de métricas.
- Barras de progreso.
- Gráficos de barras.
- Gráficos circulares.
- Línea temporal de progreso.
- Calendario de actividad.

## Librería recomendada

- Chart.js.

---

# 11.9. Sistema de gamificación

La gamificación debe ser sencilla, elegante y útil.

## Elementos

- XP.
- Puntos.
- Niveles.
- Rachas.
- Logros futuros.
- Recompensas.
- Tiendita.
- Mensajes de progreso.

## Ejemplo de recompensas por acción

```text
Completar tarea fácil: +10 XP / +5 puntos
Completar tarea media: +25 XP / +15 puntos
Completar tarea difícil: +50 XP / +30 puntos
Completar hábito diario: +10 XP / +5 puntos
Finalizar Modo Batalla: +40 XP / +20 puntos
Completar proyecto: +250 XP / +100 puntos
Completar meta importante: +500 XP / +250 puntos
```

## Ejemplo de niveles

```text
Nivel 1: 0 XP
Nivel 2: 100 XP
Nivel 3: 250 XP
Nivel 4: 500 XP
Nivel 5: 900 XP
Nivel 10: 3000 XP
```

---

# 11.10. Modo Batalla

El Modo Batalla será la funcionalidad diferencial de LifeQuest.

Consiste en una pantalla de concentración donde el usuario elige una tarea y trabaja en ella durante un tiempo determinado.

## Objetivo

Reducir distracciones y fomentar la ejecución.

## Flujo de uso

1. El usuario elige una tarea pendiente.
2. Activa Modo Batalla.
3. Se inicia un temporizador.
4. La interfaz muestra solo la tarea actual.
5. El usuario trabaja sin distracciones.
6. Al finalizar, registra el resultado.
7. El sistema suma XP y puntos.
8. Se actualizan las estadísticas.

## Pantalla de Modo Batalla

Debe incluir:

- Nombre de la tarea.
- Temporizador.
- Botón iniciar.
- Botón pausar.
- Botón finalizar.
- Resultado de sesión.
- XP ganado.
- Puntos ganados.
- Mensaje motivador.

## Ejemplo visual

```text
MODO BATALLA

Tarea actual:
Crear el sistema de login

Tiempo:
25:00

Reglas:
- Sin distracciones.
- Sin cambiar de tarea.
- Solo ejecutar.

[Comenzar]
```

---

# 11.11. La Tiendita

La Tiendita será una sección para gestionar recompensas personales.

No será una tienda medieval ni de fantasía, sino un espacio moderno de recompensas.

## Funciones

- Crear recompensa.
- Editar recompensa.
- Eliminar recompensa.
- Canjear recompensa con puntos.
- Registrar historial de canjes.

## Ejemplos de recompensas

```text
Café especial: 100 puntos
Descanso de 30 minutos: 150 puntos
Ver una película: 300 puntos
Tarde libre: 500 puntos
Comprar algo para el setup: 1000 puntos
```

## Campos de una recompensa

- Nombre.
- Descripción.
- Coste en puntos.
- Categoría.
- Estado.
- Fecha de creación.

---

# 11.12. Zona Peligrosa

La Zona Peligrosa será un espacio para acciones críticas.

## Acciones posibles

- Eliminar cuenta.
- Reiniciar progreso.
- Borrar proyecto.
- Borrar meta.
- Limpiar historial.
- Reiniciar estadísticas.
- Eliminar todos los hábitos.

## Requisitos

- Confirmación doble.
- Mensajes claros.
- Color rojo.
- Registro de acciones críticas.
- Evitar borrados accidentales.

## Ejemplo

```text
Esta acción no se puede deshacer.
Para continuar, escribe: ELIMINAR PROYECTO.
```

---

## 12. MVP del proyecto

El MVP será la primera versión funcional y defendible del TFG.

## Funcionalidades obligatorias

1. Registro de usuarios.
2. Login.
3. Logout.
4. Dashboard principal.
5. CRUD de áreas de vida.
6. CRUD de metas.
7. CRUD de proyectos.
8. CRUD de tareas.
9. CRUD de hábitos.
10. Registro diario de hábitos.
11. Sistema básico de XP.
12. Sistema básico de puntos.
13. Sistema básico de niveles.
14. Modo Batalla con temporizador.
15. Stats básicas.
16. La Tiendita básica.
17. Zona Peligrosa básica.
18. Diseño responsive.
19. Seguridad básica.
20. Documentación.

---

## 13. Funcionalidades futuras

Estas funcionalidades pueden añadirse después o dejarse como mejoras futuras en la memoria.

- Notificaciones.
- Recordatorios por email.
- Integración con calendario.
- Exportación a PDF.
- Exportación a CSV.
- IA para sugerir metas o tareas.
- Modo oscuro.
- Logros avanzados.
- Plantillas de objetivos.
- Recomendaciones semanales.
- PWA instalable.
- Ranking personal.
- Modo equipo o grupos.
- Integración con Google Calendar.
- App móvil nativa.

---

## 14. Tecnologías recomendadas

## Frontend

- HTML.
- CSS.
- JavaScript.
- Fetch API.
- Chart.js.

## Backend

- PHP orientado a objetos.

## Base de datos

- MySQL.

## Servidor

- Apache.
- XAMPP, Laragon o servidor Ubuntu.
- Posible VPS para despliegue.

## Herramientas de apoyo

- Visual Studio Code.
- Git.
- GitHub.
- phpMyAdmin.
- Figma.
- Draw.io.
- Notion o Trello para planificación.

---

## 15. Estructura inicial del proyecto

```text
LifeQuest/
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   └── logout.php
│
├── app/
│   ├── Database/
│   │   └── connection.php
│   ├── Controllers/
│   │   └── AuthController.php
│   ├── Models/
│   │   └── User.php
│   └── Services/
│       └── GamificationService.php
│
├── assets/
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   └── app.js
│   └── img/
│
├── config/
│   └── config.php
│
├── database/
│   └── schema.sql
│
└── README.md
```

---

## 16. Base de datos propuesta

## Tabla `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    points INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Tabla `life_areas`

```sql
CREATE TABLE life_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Tabla `goals`

```sql
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'future') DEFAULT 'monthly',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('not_started', 'in_progress', 'paused', 'completed', 'cancelled') DEFAULT 'not_started',
    progress INT DEFAULT 0,
    start_date DATE,
    due_date DATE,
    xp_reward INT DEFAULT 50,
    points_reward INT DEFAULT 25,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (area_id) REFERENCES life_areas(id)
);
```

## Tabla `projects`

```sql
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_id INT,
    area_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
    progress INT DEFAULT 0,
    start_date DATE,
    due_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (goal_id) REFERENCES goals(id),
    FOREIGN KEY (area_id) REFERENCES life_areas(id)
);
```

## Tabla `tasks`

```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT,
    goal_id INT,
    area_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    estimated_minutes INT DEFAULT 0,
    due_date DATE,
    xp_reward INT DEFAULT 10,
    points_reward INT DEFAULT 5,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (goal_id) REFERENCES goals(id),
    FOREIGN KEY (area_id) REFERENCES life_areas(id)
);
```

## Tabla `habits`

```sql
CREATE TABLE habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_id INT,
    goal_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    frequency ENUM('daily', 'weekly', 'custom') DEFAULT 'daily',
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    xp_reward INT DEFAULT 10,
    points_reward INT DEFAULT 5,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (area_id) REFERENCES life_areas(id),
    FOREIGN KEY (goal_id) REFERENCES goals(id)
);
```

## Tabla `habit_logs`

```sql
CREATE TABLE habit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    user_id INT NOT NULL,
    completed_date DATE NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Tabla `battle_sessions`

```sql
CREATE TABLE battle_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT,
    title VARCHAR(150) NOT NULL,
    duration_minutes INT NOT NULL,
    result ENUM('completed', 'partial', 'failed') DEFAULT 'partial',
    notes TEXT,
    xp_earned INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    started_at DATETIME,
    ended_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id)
);
```

## Tabla `rewards`

```sql
CREATE TABLE rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    cost_points INT NOT NULL,
    category VARCHAR(100),
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Tabla `reward_redemptions`

```sql
CREATE TABLE reward_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reward_id INT NOT NULL,
    user_id INT NOT NULL,
    redeemed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reward_id) REFERENCES rewards(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Tabla `danger_logs`

```sql
CREATE TABLE danger_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(150) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## 17. Lógica de progreso

## Al completar una tarea

- Cambia el estado a completada.
- Guarda la fecha de finalización.
- Suma XP.
- Suma puntos.
- Actualiza progreso del proyecto.
- Actualiza progreso de la meta.
- Actualiza estadísticas.

## Al completar un hábito

- Registra cumplimiento del día.
- Actualiza racha.
- Suma XP.
- Suma puntos.
- Actualiza porcentaje de cumplimiento.
- Actualiza estadísticas.

## Al finalizar Modo Batalla

- Registra la sesión.
- Suma tiempo enfocado.
- Suma XP.
- Suma puntos.
- Puede completar o avanzar una tarea.
- Actualiza estadísticas.

## Al canjear una recompensa

- Comprueba puntos disponibles.
- Resta puntos.
- Registra el canje.
- Muestra historial.

---

## 18. Pantallas principales

## 1. Landing

Página pública del proyecto.

Debe incluir:

- Nombre.
- Eslogan.
- Descripción breve.
- Botón de registro.
- Botón de login.
- Mockup o imagen del dashboard.

## 2. Registro

Formulario para crear cuenta.

Campos:

- Nombre.
- Email.
- Contraseña.
- Confirmar contraseña.

## 3. Login

Formulario para iniciar sesión.

Campos:

- Email.
- Contraseña.

## 4. Dashboard

Pantalla principal del usuario.

Debe mostrar:

- Nivel.
- XP.
- Puntos.
- Racha.
- Prioridad del día.
- Áreas.
- Metas.
- Hábitos.
- Tareas.
- Stats.
- Accesos rápidos.

## 5. Áreas de vida

Gestión de áreas personales.

## 6. Metas

Gestión de metas.

## 7. Proyectos

Gestión de proyectos relacionados con metas.

## 8. Tareas

Gestión de tareas y pendientes.

## 9. Hábitos

Gestión y seguimiento de hábitos.

## 10. Modo Batalla

Pantalla de concentración.

## 11. Stats

Pantalla de estadísticas.

## 12. La Tiendita

Pantalla de recompensas.

## 13. Zona Peligrosa

Pantalla de acciones críticas.

---

## 19. Seguridad

Medidas básicas necesarias:

- Contraseñas cifradas con `password_hash()`.
- Verificación con `password_verify()`.
- Sesiones PHP.
- Validación de formularios.
- Sanitización de datos.
- Consultas preparadas con PDO.
- Control de acceso a páginas privadas.
- Confirmaciones dobles en acciones críticas.
- No mostrar errores internos al usuario final.
- Separación entre lógica, presentación y datos.

---

## 20. Plan de desarrollo

---

# Fase 1: Definición

- Cerrar nombre del proyecto.
- Cerrar alcance del MVP.
- Definir estilo visual.
- Redactar requisitos iniciales.

# Fase 2: Diseño

- Crear wireframes.
- Diseñar dashboard.
- Definir paleta.
- Definir tipografía.
- Definir componentes visuales.

# Fase 3: Base de datos

- Crear modelo entidad-relación.
- Crear script SQL.
- Crear datos de prueba.

# Fase 4: Backend inicial

- Crear estructura del proyecto.
- Crear conexión con base de datos.
- Crear modelo User.
- Crear AuthController.
- Crear registro.
- Crear login.
- Crear logout.
- Proteger dashboard.

# Fase 5: Dashboard

- Crear layout general.
- Crear sidebar.
- Crear tarjetas.
- Mostrar datos del usuario.
- Preparar diseño responsive.

# Fase 6: Módulos principales

- Áreas de vida.
- Metas.
- Proyectos.
- Tareas.
- Hábitos.

# Fase 7: Gamificación

- XP.
- Puntos.
- Niveles.
- Rachas.
- Recompensas.

# Fase 8: Funciones especiales

- Modo Batalla.
- Stats.
- La Tiendita.
- Zona Peligrosa.

# Fase 9: Pruebas

- Pruebas de login.
- Pruebas de CRUD.
- Pruebas de gamificación.
- Pruebas de seguridad.
- Pruebas responsive.

# Fase 10: Memoria y defensa

- Documentación técnica.
- Capturas.
- Diagramas.
- Presentación.
- Guion de defensa.

---

## 21. Orden de desarrollo inmediato

El orden recomendado para empezar es:

1. Crear estructura de carpetas.
2. Crear `schema.sql`.
3. Crear `config.php`.
4. Crear conexión PDO.
5. Crear modelo `User`.
6. Crear `AuthController`.
7. Crear `register.php`.
8. Crear `login.php`.
9. Crear `logout.php`.
10. Crear `dashboard.php`.
11. Crear `styles.css`.
12. Probar registro e inicio de sesión.

El primer objetivo funcional será:

> Usuario se registra → inicia sesión → entra a un dashboard protegido.

---

## 22. Requisitos funcionales iniciales

## RF01

El sistema permitirá registrar usuarios.

## RF02

El sistema permitirá iniciar sesión.

## RF03

El sistema permitirá cerrar sesión.

## RF04

El sistema protegerá las páginas privadas.

## RF05

El sistema permitirá crear áreas de vida.

## RF06

El sistema permitirá crear metas.

## RF07

El sistema permitirá crear proyectos.

## RF08

El sistema permitirá crear tareas.

## RF09

El sistema permitirá marcar tareas como completadas.

## RF10

El sistema sumará XP y puntos al completar tareas.

## RF11

El sistema permitirá crear hábitos.

## RF12

El sistema permitirá marcar hábitos como completados.

## RF13

El sistema calculará rachas.

## RF14

El sistema permitirá activar Modo Batalla.

## RF15

El sistema registrará sesiones de Modo Batalla.

## RF16

El sistema mostrará estadísticas básicas.

## RF17

El sistema permitirá crear recompensas.

## RF18

El sistema permitirá canjear recompensas.

## RF19

El sistema incluirá una Zona Peligrosa.

---

## 23. Requisitos no funcionales iniciales

## RNF01

La aplicación será responsive.

## RNF02

Las contraseñas estarán cifradas.

## RNF03

El sistema usará consultas preparadas.

## RNF04

La interfaz será clara, moderna y usable.

## RNF05

El código estará organizado por carpetas.

## RNF06

El sistema separará lógica, datos y presentación.

## RNF07

El dashboard cargará de forma eficiente.

## RNF08

Las acciones críticas requerirán confirmación.

## RNF09

La aplicación será accesible desde navegador móvil.

## RNF10

El proyecto estará documentado.

---

## 24. Riesgos del proyecto

## Riesgo 1: Alcance demasiado grande

LifeQuest tiene muchos módulos.

### Solución

Desarrollar primero el MVP y dejar extras como mejoras futuras.

## Riesgo 2: Dashboard sobrecargado

Demasiada información puede hacer que la app sea confusa.

### Solución

Mostrar solo lo esencial y usar tarjetas limpias.

## Riesgo 3: Gamificación superficial

Los puntos no deben ser solo decoración.

### Solución

Conectar XP, puntos, tareas, hábitos, metas y estadísticas.

## Riesgo 4: Copiar demasiado la inspiración

La idea puede recordar a plantillas de Notion.

### Solución

Crear una aplicación propia con backend, base de datos, lógica y diseño original.

## Riesgo 5: Estadísticas demasiado complejas

Calcular muchas métricas puede retrasar el desarrollo.

### Solución

Empezar con estadísticas básicas.

---

## 25. Defensa del proyecto

En la defensa se puede seguir este recorrido:

1. Explicar el problema.
2. Presentar LifeQuest como solución.
3. Mostrar landing.
4. Registrar usuario.
5. Iniciar sesión.
6. Entrar al dashboard.
7. Crear área de vida.
8. Crear meta.
9. Crear proyecto.
10. Crear tarea.
11. Activar Modo Batalla.
12. Completar tarea y ganar XP.
13. Crear hábito.
14. Marcar hábito completado.
15. Ver estadísticas.
16. Canjear recompensa.
17. Mostrar Zona Peligrosa.
18. Explicar base de datos.
19. Explicar seguridad.
20. Explicar mejoras futuras.

---

## 26. Frase final para la memoria

> **LifeQuest propone una forma moderna y gamificada de gestionar el progreso personal. A través de un tablero centralizado, el usuario puede organizar metas, proyectos, hábitos y tareas, ejecutar sesiones de concentración mediante Modo Batalla y medir su avance con estadísticas, puntos, niveles y recompensas.**

---

## 27. Próximo paso

El siguiente paso será empezar la base técnica:

1. Crear la estructura del proyecto.
2. Crear la base de datos.
3. Crear conexión con MySQL.
4. Crear registro.
5. Crear login.
6. Crear dashboard protegido.

Una vez hecho esto, se empezarán a añadir los módulos principales:

- Áreas de vida.
- Metas.
- Proyectos.
- Tareas.
- Hábitos.
- Modo Batalla.
- Stats.
- La Tiendita.
- Zona Peligrosa.
