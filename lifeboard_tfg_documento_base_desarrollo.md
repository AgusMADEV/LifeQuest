# LifeQuest / Videojuego de la Vida  
## Documento base para iniciar el desarrollo del TFG

> Nota de vigencia: este documento contiene secciones conceptuales e históricas. La estructura técnica vigente del proyecto está en README.md e INSTALL.md.

---

## 1. Resumen de la idea

El proyecto consiste en desarrollar una aplicación web, adaptable a navegador y móvil, que funcione como un **tablero personal gamificado** para gestionar metas, hábitos, proyectos, prioridades, tareas y estadísticas de progreso.

La idea nace de combinar dos conceptos:

1. Una app de objetivos con mecánicas de videojuego.
2. Un dashboard moderno tipo “sistema operativo personal”, inspirado en tableros de productividad como Notion, pero convertido en una aplicación propia, dinámica y automatizada.

La propuesta final no será una simple plantilla visual, sino una aplicación web real con:

- Registro e inicio de sesión.
- Base de datos.
- Panel personalizado por usuario.
- Áreas de vida.
- Metas.
- Hábitos.
- Proyectos.
- Tareas.
- Estadísticas.
- Sistema de puntos, experiencia o progreso.
- Modo enfoque llamado **Modo Batalla**.
- Recompensas o inventario personal mediante **La Tiendita**.
- Zona crítica de seguridad llamada **Zona Peligrosa**.

---

## 2. Concepto principal

La aplicación puede entenderse como un:

> **Videojuego de la vida orientado a resultados reales.**

No se plantea como un videojuego tradicional, sino como una herramienta de productividad personal que utiliza mecánicas de gamificación para hacer más visual, motivador y medible el progreso del usuario.

La idea central es:

> Convierte tus metas en resultados con un tablero único que te obliga a enfocarte, ejecutar y medir. Sin ruido. Sin apps extra. Solo disciplina, claridad y avance visible día tras día.

---

## 3. Nombre del proyecto

Nombres posibles:

- **LifeQuest**
- **Videojuego de la Vida**
- **LifeQuest**
- **DisciplineOS**
- **ProgressOS**
- **FocusBoard**
- **LifeCommand**
- **LevelUp OS**
- **Modo Batalla**
- **VidaXP**

### Nombre recomendado

**LifeQuest**

### Subtítulo recomendado

> Convierte tus metas en resultados con un tablero único de enfoque, ejecución y progreso.

---

## 4. Descripción académica del proyecto

Una posible descripción para la memoria del TFG sería:

> Desarrollo de una aplicación web gamificada para la gestión integral de metas, hábitos, proyectos y tareas personales mediante un dashboard centralizado, estadísticas de progreso, recompensas virtuales y un modo de concentración orientado a la ejecución diaria.

Otra versión más sencilla:

> LifeQuest es una aplicación web de productividad personal que permite organizar áreas de vida, metas, proyectos, hábitos y tareas en un único tablero visual, incorporando mecánicas de gamificación para aumentar la motivación, medir el progreso y favorecer la constancia del usuario.

---

## 5. Diferencia respecto a una plantilla de Notion

La idea se inspira en tableros de productividad visuales, pero el TFG debe diferenciarse claramente de una plantilla estática.

### Una plantilla de Notion

- Organiza información.
- Depende mucho de edición manual.
- No suele automatizar la lógica.
- No tiene backend propio.
- No tiene autenticación desarrollada por nosotros.
- No calcula estadísticas complejas de forma personalizada.
- No ejecuta lógica de gamificación propia.

### La aplicación del TFG

- Tendrá usuarios reales.
- Guardará datos en una base de datos.
- Calculará estadísticas automáticamente.
- Relacionará metas, proyectos, hábitos y tareas.
- Actualizará progreso de forma dinámica.
- Tendrá sistema de puntos, experiencia o recompensas.
- Permitirá sesiones de enfoque con Modo Batalla.
- Tendrá una arquitectura propia.
- Será responsive.
- Tendrá seguridad básica.
- Será defendible técnicamente como aplicación web completa.

---

## 6. Objetivo general

Desarrollar una aplicación web gamificada que permita al usuario gestionar su vida personal y profesional a través de un tablero centralizado, conectando metas a largo plazo con proyectos, hábitos y tareas diarias medibles.

---

## 7. Objetivos específicos

- Crear un sistema de autenticación de usuarios.
- Diseñar un dashboard central con la información más importante del usuario.
- Permitir la creación y gestión de áreas de vida.
- Permitir la creación y seguimiento de metas.
- Relacionar metas con proyectos y tareas.
- Crear un sistema de hábitos con seguimiento y rachas.
- Incorporar estadísticas visuales de progreso.
- Implementar un sistema de puntos, experiencia o monedas.
- Crear un modo de concentración llamado **Modo Batalla**.
- Diseñar una sección de recompensas llamada **La Tiendita**.
- Incorporar una **Zona Peligrosa** para acciones críticas.
- Desarrollar una interfaz moderna, clara y responsive.
- Aplicar buenas prácticas de seguridad y organización del código.

---

## 8. Público objetivo

La aplicación está pensada para personas que quieren mejorar su organización, constancia y productividad.

Usuarios ideales:

- Estudiantes.
- Creadores de contenido.
- Emprendedores.
- Profesionales.
- Personas que trabajan por objetivos.
- Personas que quieren mejorar hábitos.
- Usuarios que necesitan convertir metas grandes en acciones diarias.
- Personas interesadas en productividad, disciplina y crecimiento personal.

---

## 9. Problema que resuelve

Muchas personas tienen objetivos importantes, pero fallan en convertirlos en acciones concretas.

Problemas habituales:

- Tener muchas metas pero poca claridad.
- No saber priorizar.
- No medir el progreso.
- Usar demasiadas aplicaciones separadas.
- Abandonar hábitos por falta de seguimiento.
- No conectar tareas diarias con objetivos importantes.
- Falta de motivación visual.
- Ausencia de un sistema que obligue a revisar, ejecutar y medir.

La aplicación busca resolver esto mediante un único tablero donde el usuario pueda ver qué quiere conseguir, qué debe hacer hoy y cómo está avanzando.

---

## 10. Propuesta de valor

LifeQuest ofrece:

- Claridad en una sola vista.
- Gestión de áreas de vida.
- Metas conectadas con proyectos.
- Proyectos desglosados en tareas.
- Hábitos vinculados al progreso personal.
- Estadísticas para medir resultados.
- Gamificación ligera para aumentar la motivación.
- Modo Batalla para trabajar sin distracciones.
- Recompensas personalizadas.
- Zona segura para acciones críticas.

---

## 11. Eslogan del producto

> Menos ruido. Más ejecución. Progreso visible cada día.

Otras opciones:

- **Tu vida, organizada como un sistema de misiones.**
- **Convierte objetivos en acciones y acciones en resultados.**
- **Prioriza, ejecuta, mide, repite.**
- **Un tablero para ganar tu propio juego.**
- **La productividad como sistema, no como intención.**

---

## 12. Funcionalidades principales

### 12.1. Registro e inicio de sesión

El usuario podrá crear una cuenta y acceder a su propio espacio personal.

Funciones:

- Registro.
- Login.
- Logout.
- Contraseña cifrada.
- Sesión privada.
- Edición básica de perfil.

Datos del usuario:

- Nombre.
- Email.
- Contraseña cifrada.
- Fecha de registro.
- Avatar o imagen.
- Nivel.
- Puntos acumulados.
- Racha actual.

---

### 12.2. Dashboard central

El dashboard será la pantalla principal de la aplicación.

Debe mostrar en una sola vista los elementos más importantes:

- Perfil del usuario.
- Nivel o progreso.
- Racha.
- Puntos.
- Prioridad del día.
- Hábitos activos.
- Tareas pendientes.
- Proyectos activos.
- Metas principales.
- Estadísticas rápidas.
- Acceso al Modo Batalla.
- Acceso a La Tiendita.
- Acceso a Zona Peligrosa.

Ejemplo de estructura:

```text
VIDEJUEGO DE LA VIDA / LIFEBOARD

[Perfil + Nivel]       [Reloj / Modo Batalla]       [Prioridades]

[Áreas de vida]        [Metas principales]          [Stats]

[Hábitos]              [Proyectos activos]          [Pendientes]

[La Tiendita]          [Historial]                  [Zona Peligrosa]
```

---

### 12.3. Áreas de vida

Las áreas de vida permiten dividir el progreso del usuario por categorías importantes.

Ejemplos:

- Salud.
- Estudios.
- Trabajo.
- Finanzas.
- Relaciones.
- Familia.
- Creatividad.
- Desarrollo personal.
- Espiritualidad.
- Proyecto principal.
- Ocio.
- Marca personal.

Funciones:

- Crear área.
- Editar área.
- Eliminar área.
- Asignar icono.
- Asignar color.
- Ver metas asociadas.
- Ver hábitos asociados.
- Ver progreso por área.

Ejemplo:

```text
Área: Salud
Metas asociadas: 3
Hábitos activos: 4
Progreso: 68%
```

---

### 12.4. Prioridades

Las prioridades ayudan al usuario a decidir qué es lo más importante.

Tipos de prioridades:

- Prioridad diaria.
- Prioridad semanal.
- Prioridad mensual.
- Prioridades generales de vida.

Funciones:

- Crear prioridades.
- Ordenarlas por importancia.
- Asociarlas a áreas de vida.
- Asociarlas a metas.
- Mostrar la prioridad principal del día en el dashboard.

Ejemplo:

```text
Prioridad del día:
Terminar diseño de base de datos del TFG.
```

---

### 12.5. Metas

Las metas representan objetivos importantes a medio o largo plazo.

Tipos de metas:

- Diarias.
- Semanales.
- Mensuales.
- Trimestrales.
- Anuales.
- De futuro.

Campos de una meta:

- Título.
- Descripción.
- Área de vida.
- Fecha de inicio.
- Fecha límite.
- Estado.
- Porcentaje de progreso.
- Prioridad.
- Dificultad.
- Puntos asociados.
- Proyectos relacionados.

Estados posibles:

- No iniciada.
- En progreso.
- Pausada.
- Completada.
- Cancelada.

Ejemplo:

```text
Meta anual:
Finalizar el TFG.

Meta trimestral:
Tener el MVP desarrollado.

Meta mensual:
Completar backend y base de datos.

Acción diaria:
Programar 1 hora.
```

---

### 12.6. Proyectos

Los proyectos permiten dividir una meta grande en bloques de trabajo.

Campos de un proyecto:

- Título.
- Descripción.
- Meta asociada.
- Área de vida.
- Fecha de inicio.
- Fecha límite.
- Estado.
- Porcentaje de avance.

Estados posibles:

- Activo.
- Completado.
- Pausado.
- Cancelado.

Ejemplo:

```text
Proyecto:
Desarrollo de LifeQuest.

Tareas:
- Crear diseño de base de datos.
- Crear login.
- Crear dashboard.
- Crear módulo de hábitos.
- Crear estadísticas.
- Redactar memoria.
```

---

### 12.7. Tareas / Pendientes

Las tareas son acciones concretas y ejecutables.

Campos de una tarea:

- Título.
- Descripción.
- Proyecto asociado.
- Meta asociada.
- Área de vida.
- Fecha límite.
- Prioridad.
- Estado.
- Puntos.
- Tiempo estimado.

Estados posibles:

- Pendiente.
- En progreso.
- Completada.
- Cancelada.

Niveles de prioridad:

- Baja.
- Media.
- Alta.
- Crítica.

Ejemplo:

```text
[ ] Crear tabla usuarios
[ ] Diseñar pantalla principal
[ ] Programar login
[ ] Crear cálculo de estadísticas
```

---

### 12.8. Hábitos

Los hábitos son acciones repetidas que ayudan a mantener el progreso.

Campos de un hábito:

- Nombre.
- Descripción.
- Área de vida.
- Frecuencia.
- Días activos.
- Racha actual.
- Mejor racha.
- Porcentaje de cumplimiento.
- Estado.

Frecuencias posibles:

- Diario.
- Semanal.
- Personalizado.

Ejemplos:

- Entrenar.
- Leer.
- Estudiar.
- Beber agua.
- Meditar.
- Dormir antes de una hora.
- Revisar planificación.
- Trabajar en el TFG.

Funciones:

- Crear hábito.
- Marcar hábito como completado.
- Ver racha.
- Ver porcentaje semanal.
- Relacionar hábito con una meta.
- Mostrar hábitos del día en el dashboard.

---

### 12.9. Stats / Estadísticas

Las estadísticas permiten medir el progreso real del usuario.

Métricas posibles:

- Tareas completadas.
- Hábitos completados.
- Metas completadas.
- Proyectos activos.
- Proyectos finalizados.
- Racha actual.
- Mejor racha.
- Puntos ganados.
- Experiencia acumulada.
- Tiempo de enfoque.
- Sesiones de Modo Batalla.
- Porcentaje de disciplina.
- Progreso semanal.
- Progreso mensual.
- Rendimiento por área de vida.

Visualizaciones recomendadas:

- Gráfico de barras.
- Gráfico circular.
- Línea de progreso.
- Tarjetas de métricas.
- Calendario de actividad.
- Ranking de áreas más trabajadas.

Librería recomendada:

- Chart.js.

---

### 12.10. Sistema de gamificación

La gamificación debe ser elegante, moderna y útil. No debe convertir la app en un juego infantil.

Elementos de gamificación:

- Puntos.
- Experiencia.
- Niveles.
- Rachas.
- Logros.
- Recompensas.
- Tiendita.
- Estadísticas visibles.
- Mensajes de progreso.

Ejemplo de recompensas:

```text
Completar tarea sencilla: +10 puntos
Completar tarea media: +25 puntos
Completar tarea difícil: +50 puntos
Completar sesión de Modo Batalla: +40 puntos
Mantener racha semanal: +100 puntos
Completar proyecto: +250 puntos
Completar meta importante: +500 puntos
```

Ejemplo de niveles:

```text
Nivel 1: 0 XP
Nivel 2: 100 XP
Nivel 3: 250 XP
Nivel 4: 500 XP
Nivel 5: 900 XP
Nivel 10: 3000 XP
```

---

### 12.11. Modo Batalla

El Modo Batalla será una de las funcionalidades diferenciales del proyecto.

Consiste en un modo de concentración donde el usuario elige una tarea concreta y se centra únicamente en ejecutarla durante un periodo de tiempo.

Objetivo:

- Reducir distracciones.
- Aumentar la ejecución real.
- Registrar tiempo enfocado.
- Recompensar la acción completada.

Funcionamiento:

1. El usuario elige una tarea pendiente.
2. Activa Modo Batalla.
3. Se inicia un temporizador.
4. La interfaz se limpia y muestra solo la tarea actual.
5. Al finalizar, el usuario registra el resultado.
6. La app suma puntos o experiencia.
7. Se actualizan estadísticas.

Elementos de la pantalla:

- Tarea actual.
- Temporizador.
- Botón iniciar.
- Botón pausar.
- Botón finalizar.
- Mensaje motivador.
- Resultado de sesión.
- Puntos ganados.

Ejemplo:

```text
MODO BATALLA ACTIVADO

Objetivo:
Programar módulo de login.

Tiempo:
45:00

Reglas:
- No cambiar de tarea.
- No añadir pendientes nuevos.
- Solo ejecutar.
- Al terminar, registrar resultado.
```

---

### 12.12. La Tiendita

La Tiendita será una sección donde el usuario podrá gestionar recompensas, recursos o inventario personal.

No debe tener estética medieval. Debe parecer una sección moderna de recompensas o recursos.

Posibles usos:

- Recompensas personales.
- Inventario de recursos.
- Ideas guardadas.
- Herramientas.
- Plantillas.
- Objetos desbloqueables.
- Premios creados por el propio usuario.

Ejemplos de recompensas:

```text
Café especial: 100 puntos
Descanso de 30 minutos: 150 puntos
Tarde libre: 500 puntos
Comprar algo para el setup: 1000 puntos
Ver una película: 300 puntos
```

Campos de una recompensa:

- Nombre.
- Descripción.
- Coste en puntos.
- Categoría.
- Estado.
- Fecha de creación.

---

### 12.13. Zona Peligrosa

La Zona Peligrosa será un espacio para acciones críticas.

Acciones posibles:

- Eliminar cuenta.
- Reiniciar progreso.
- Borrar proyecto.
- Borrar meta.
- Limpiar historial.
- Exportar datos.
- Eliminar todos los hábitos.
- Restablecer estadísticas.

Requisitos de seguridad:

- Confirmación doble.
- Mensajes claros.
- Color rojo o alerta.
- Registro de acciones críticas.
- Evitar borrados accidentales.

Ejemplo:

```text
Zona Peligrosa

Esta acción no se puede deshacer.
Para continuar, escribe: ELIMINAR PROYECTO.
```

---

## 13. Funcionalidades mínimas del MVP

Para que el proyecto sea viable, la primera versión debe estar bien limitada.

MVP recomendado:

1. Registro de usuarios.
2. Inicio de sesión.
3. Dashboard principal.
4. CRUD de áreas de vida.
5. CRUD de metas.
6. CRUD de proyectos.
7. CRUD de tareas.
8. CRUD de hábitos.
9. Registro diario de hábitos.
10. Sistema básico de puntos.
11. Sistema básico de niveles.
12. Estadísticas principales.
13. Modo Batalla con temporizador.
14. La Tiendita básica.
15. Zona Peligrosa básica.
16. Diseño responsive.
17. Seguridad básica.
18. Despliegue en servidor o entorno local documentado.

---

## 14. Funcionalidades futuras

Estas funcionalidades se pueden dejar como mejoras futuras si no da tiempo a implementarlas.

- Notificaciones.
- Recordatorios por email.
- Integración con calendario.
- Exportación a PDF.
- Exportación a CSV.
- IA para sugerir objetivos.
- Modo oscuro y modo claro.
- Ranking personal.
- Sistema avanzado de logros.
- Plantillas de objetivos.
- Recomendaciones semanales.
- App móvil nativa.
- PWA instalable.
- Integración con Google Calendar.
- Integración con hábitos de salud.
- Compartir progreso con otros usuarios.
- Modo equipo o grupos.

---

## 15. Estilo gráfico recomendado

El estilo gráfico recomendado no debe ser medieval ni infantil.

Debe ser:

- Moderno.
- Minimalista.
- Digital.
- Limpio.
- Directo.
- Algo agresivo en cuanto a enfoque.
- Inspirado en dashboards actuales.
- Con pequeños elementos gamificados.
- Centrado en resultados.

Nombre del estilo:

> **Minimalismo táctico gamificado**

También se podría llamar:

> **Dashboard brutalista de productividad**

---

## 16. Dirección visual

Sensación que debe transmitir:

- Disciplina.
- Claridad.
- Control.
- Progreso.
- Foco.
- Ejecución.
- Energía.
- Resultados.

Elementos visuales:

- Tarjetas.
- Barras de progreso.
- Reloj o temporizador.
- Stats grandes.
- Iconos simples.
- Secciones bien delimitadas.
- Tipografía fuerte.
- Pocos colores, pero bien usados.
- Animaciones suaves.
- Modo claro u oscuro.

Evitar:

- Castillos.
- Magos.
- Espadas.
- Cofres.
- Pergaminos.
- Mascotas fantásticas.
- Estética medieval.
- Exceso de dibujos.
- Demasiados colores.
- Pantallas sobrecargadas.

---

## 17. Paleta de colores propuesta

### Opción modo oscuro

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
```

### Opción modo claro

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
```

### Opción blanco y negro inspirada en Notion

```css
--bg-main: #FFFFFF;
--bg-card: #F8F8F8;
--border: #E5E5E5;
--text-main: #111111;
--text-muted: #666666;
--primary: #111111;
--success: #16A34A;
--warning: #F59E0B;
--danger: #DC2626;
```

---

## 18. Tecnologías recomendadas

La elección más realista, teniendo en cuenta conocimientos previos, sería:

### Frontend

- HTML.
- CSS.
- JavaScript.
- Bootstrap o Tailwind CSS.
- Fetch API para peticiones dinámicas.
- Chart.js para estadísticas.

### Backend

- PHP orientado a objetos.

### Base de datos

- MySQL.

### Servidor

- Apache.
- PHP.
- MySQL.
- Ubuntu Server o entorno local XAMPP/Laragon.
- Posible despliegue en VPS.

### Herramientas

- Git.
- GitHub.
- Visual Studio Code.
- phpMyAdmin.
- Figma para prototipos.
- Draw.io para diagramas.
- Trello, Notion o GitHub Projects para planificación.

---

## 19. Arquitectura propuesta (actualizada al estado actual)

Estructura actual del proyecto:

```text
LifeQuest/
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── areas.php
│   ├── goals.php
│   ├── habits.php
│   ├── progress.php
│   ├── profile.php
│   ├── shop.php
│   └── partials/
│       ├── sidebar_nav.php
│       ├── sidebar_user_mini.php
│       └── sidebar_bottom.php
│
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── database.php
│   └── session_guard.php
│
├── assets/
│   ├── css/
│   │   ├── styles.css
│   │   └── modules/
│   ├── js/
│   │   └── app.js
│
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Support/
│   └── Database/
│       └── connection.php
│
├── config/
│   ├── config.php
│   └── config.example.php
│
├── database/
│   ├── schema.sql
│   ├── tasks_migration.sql
│   ├── admin_portal_auth_migration.sql
│   └── admin_portal_auth_migration_rollback.sql
│
├── referencias/
│
├── README.md
└── INSTALL.md
```

---

## 20. Base de datos inicial propuesta

### Tabla `users`

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

### Tabla `life_areas`

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

### Tabla `goals`

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

### Tabla `projects`

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

### Tabla `tasks`

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

### Tabla `habits`

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

### Tabla `habit_logs`

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

### Tabla `battle_sessions`

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

### Tabla `rewards`

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

### Tabla `reward_redemptions`

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

### Tabla `danger_logs`

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

## 21. Lógica de progreso

### Al completar una tarea

- Cambia estado de la tarea a completada.
- Registra fecha de finalización.
- Suma XP.
- Suma puntos.
- Actualiza progreso del proyecto relacionado.
- Actualiza progreso de la meta relacionada.
- Actualiza estadísticas generales.

### Al completar un hábito

- Registra cumplimiento del día.
- Actualiza racha.
- Suma XP.
- Suma puntos.
- Actualiza porcentaje de cumplimiento.
- Actualiza estadísticas.

### Al finalizar Modo Batalla

- Registra sesión.
- Suma tiempo enfocado.
- Suma XP o puntos.
- Puede completar o avanzar tarea.
- Actualiza estadísticas.

### Al canjear una recompensa

- Resta puntos.
- Registra canje.
- Muestra historial de recompensas.

---

## 22. Pantallas principales

1. **Landing / Inicio**  
   Presentación del proyecto, eslogan, descripción breve y botones de acceso.

2. **Registro**  
   Formulario para crear cuenta.

3. **Login**  
   Formulario para iniciar sesión.

4. **Dashboard**  
   Pantalla central con perfil, nivel, XP, puntos, prioridades, hábitos, tareas, metas, proyectos y stats.

5. **Áreas de vida**  
   Pantalla para gestionar categorías personales.

6. **Metas**  
   Pantalla para crear y gestionar metas.

7. **Proyectos**  
   Pantalla para gestionar proyectos asociados a metas.

8. **Tareas**  
   Pantalla para gestionar pendientes y tareas accionables.

9. **Hábitos**  
   Pantalla para crear, marcar y analizar hábitos.

10. **Modo Batalla**  
    Pantalla de concentración minimalista y sin distracciones.

11. **Stats**  
    Pantalla de estadísticas y gráficos.

12. **La Tiendita**  
    Pantalla de recompensas, recursos o inventario personal.

13. **Zona Peligrosa**  
    Pantalla para acciones críticas.

---

## 23. Seguridad

Medidas mínimas necesarias:

- Cifrado de contraseñas con `password_hash()`.
- Verificación con `password_verify()`.
- Uso de sesiones.
- Validación de formularios.
- Sanitización de datos.
- Consultas preparadas con PDO.
- Control de acceso a páginas privadas.
- Protección básica contra inyección SQL.
- Confirmaciones en acciones críticas.
- No mostrar errores internos al usuario final.

---

## 24. Plan de desarrollo recomendado

### Fase 1: Definición

- Elegir nombre definitivo.
- Definir alcance del MVP.
- Definir funcionalidades obligatorias.
- Definir funcionalidades futuras.
- Crear primeros bocetos.
- Redactar descripción oficial del proyecto.

### Fase 2: Diseño

- Diseñar wireframes.
- Crear prototipo visual en Figma.
- Definir paleta de colores.
- Definir tipografías.
- Definir iconografía.
- Diseñar dashboard principal.
- Diseñar flujo de usuario.

### Fase 3: Base de datos

- Crear modelo entidad-relación.
- Definir tablas.
- Definir relaciones.
- Crear script SQL.
- Crear datos de prueba.

### Fase 4: Backend

- Crear estructura del proyecto.
- Crear conexión a base de datos.
- Crear autenticación.
- Crear modelos.
- Crear controladores.
- Crear servicios de gamificación.
- Crear servicios de estadísticas.

### Fase 5: Frontend

- Crear landing.
- Crear login y registro.
- Crear dashboard.
- Crear pantallas CRUD.
- Crear Modo Batalla.
- Crear Stats.
- Crear Tiendita.
- Crear Zona Peligrosa.
- Aplicar responsive.

### Fase 6: Integración

- Conectar formularios con backend.
- Usar Fetch/AJAX donde aporte valor.
- Actualizar estadísticas en tiempo real o bajo demanda.
- Probar relaciones entre tareas, proyectos, metas y hábitos.

### Fase 7: Pruebas

- Probar registro.
- Probar login.
- Probar CRUDs.
- Probar sistema de puntos.
- Probar Modo Batalla.
- Probar estadísticas.
- Probar seguridad básica.
- Probar responsive.
- Probar acciones críticas.

### Fase 8: Despliegue

- Preparar servidor.
- Subir archivos.
- Configurar base de datos.
- Configurar permisos.
- Probar en entorno real.
- Documentar despliegue.

### Fase 9: Memoria y defensa

- Documentar análisis.
- Documentar diseño.
- Documentar desarrollo.
- Documentar base de datos.
- Documentar pruebas.
- Preparar capturas.
- Preparar presentación.
- Preparar guion de defensa.

---

## 25. Reparto orientativo por semanas

| Semana | Trabajo principal |
|---|---|
| 1 | Definir alcance final, nombre, requisitos y bocetos iniciales. |
| 2 | Diseñar base de datos, modelo entidad-relación y estructura del proyecto. |
| 3 | Implementar registro, login, sesiones y layout base. |
| 4 | Crear dashboard, áreas de vida y prioridades. |
| 5 | Crear metas, proyectos y tareas. |
| 6 | Crear hábitos, registros de hábitos y rachas. |
| 7 | Crear sistema de puntos, niveles y lógica de progreso. |
| 8 | Crear Modo Batalla y registro de sesiones de enfoque. |
| 9 | Crear estadísticas e integrar Chart.js. |
| 10 | Crear La Tiendita, recompensas y canjes. |
| 11 | Crear Zona Peligrosa, confirmaciones y logs. |
| 12 | Mejorar diseño, responsive y experiencia de usuario. |
| 13 | Pruebas, corrección de errores y optimización. |
| 14 | Despliegue y documentación técnica. |
| 15 | Memoria final, presentación y guion de defensa. |

---

## 26. Puntos que debemos tratar antes de empezar a programar

Antes de iniciar el desarrollo conviene cerrar estos puntos:

1. **Nombre definitivo**  
   Decidir entre LifeQuest, Videojuego de la Vida, LifeQuest, DisciplineOS u otro.

2. **Alcance del MVP**  
   Definir qué funcionalidades entran sí o sí y cuáles quedan como futuras mejoras.

3. **Estilo visual definitivo**  
   Elegir entre modo oscuro moderno, blanco y negro tipo Notion, dashboard claro con acentos de color o estilo híbrido.

4. **Tecnologías definitivas**  
   Confirmar si se usará PHP + MySQL + JavaScript, Laravel, React + API u otra alternativa.

5. **Modelo de datos**  
   Revisar y ajustar tablas antes de programar.

6. **Flujo de usuario**

```text
Registro → Configuración inicial → Dashboard → Crear área → Crear meta → Crear proyecto → Crear tarea → Ejecutar Modo Batalla → Ver estadísticas
```

7. **Sistema de puntos**  
   Definir cuántos puntos da cada acción, cómo se sube de nivel, cómo se canjean recompensas y qué ocurre con las rachas.

8. **Diseño del dashboard**  
   Decidir qué módulos aparecen en la pantalla principal.

9. **Modo Batalla**  
   Definir duración, pausas, recompensas y datos registrados.

10. **La Tiendita**  
    Definir si será recompensas personales, inventario de recursos o ambas cosas.

11. **Zona Peligrosa**  
    Definir qué acciones críticas tendrá desde el MVP.

12. **Estadísticas**  
    Decidir qué métricas se muestran en la primera versión.

---

## 27. Requisitos funcionales iniciales

- **RF01:** El sistema permitirá registrar usuarios.
- **RF02:** El sistema permitirá iniciar sesión.
- **RF03:** El sistema permitirá cerrar sesión.
- **RF04:** El sistema permitirá crear, editar y eliminar áreas de vida.
- **RF05:** El sistema permitirá crear, editar y eliminar metas.
- **RF06:** El sistema permitirá asociar metas a áreas de vida.
- **RF07:** El sistema permitirá crear, editar y eliminar proyectos.
- **RF08:** El sistema permitirá asociar proyectos a metas.
- **RF09:** El sistema permitirá crear, editar y eliminar tareas.
- **RF10:** El sistema permitirá asociar tareas a proyectos y metas.
- **RF11:** El sistema permitirá marcar tareas como completadas.
- **RF12:** El sistema sumará puntos al completar tareas.
- **RF13:** El sistema permitirá crear, editar y eliminar hábitos.
- **RF14:** El sistema permitirá marcar hábitos como completados por día.
- **RF15:** El sistema calculará rachas de hábitos.
- **RF16:** El sistema mostrará estadísticas de progreso.
- **RF17:** El sistema permitirá iniciar sesiones de Modo Batalla.
- **RF18:** El sistema registrará el resultado de una sesión de Modo Batalla.
- **RF19:** El sistema permitirá crear recompensas en La Tiendita.
- **RF20:** El sistema permitirá canjear recompensas con puntos.
- **RF21:** El sistema incluirá una Zona Peligrosa con acciones críticas.

---

## 28. Requisitos no funcionales iniciales

- **RNF01:** La aplicación será responsive.
- **RNF02:** Las contraseñas se almacenarán cifradas.
- **RNF03:** El sistema usará consultas preparadas.
- **RNF04:** La interfaz será clara y moderna.
- **RNF05:** El dashboard deberá cargar de forma eficiente.
- **RNF06:** El sistema deberá separar lógica, datos y presentación.
- **RNF07:** El proyecto deberá estar documentado.
- **RNF08:** El código deberá organizarse en carpetas y módulos.
- **RNF09:** La aplicación deberá ser usable desde navegador móvil.
- **RNF10:** Las acciones críticas requerirán confirmación.

---

## 29. Casos de uso principales

1. **Crear cuenta**  
   El usuario accede a la aplicación, introduce sus datos y crea una cuenta.

2. **Crear área de vida**  
   El usuario crea un área como “Salud” o “Estudios”.

3. **Crear meta**  
   El usuario crea una meta y la asocia a un área de vida.

4. **Crear proyecto**  
   El usuario crea un proyecto relacionado con una meta.

5. **Crear tarea**  
   El usuario crea una tarea accionable dentro de un proyecto.

6. **Completar tarea**  
   El usuario marca una tarea como completada y recibe puntos.

7. **Crear hábito**  
   El usuario crea un hábito recurrente.

8. **Completar hábito diario**  
   El usuario marca un hábito como completado y aumenta su racha.

9. **Activar Modo Batalla**  
   El usuario elige una tarea y activa un temporizador de concentración.

10. **Canjear recompensa**  
    El usuario usa sus puntos para reclamar una recompensa.

11. **Consultar estadísticas**  
    El usuario revisa su progreso semanal o mensual.

12. **Ejecutar acción crítica**  
    El usuario entra en Zona Peligrosa y realiza una acción con confirmación.

---

## 30. Riesgos del proyecto

### Riesgo 1: Alcance demasiado grande

El proyecto tiene muchos módulos.

**Solución:** desarrollar primero el MVP y dejar extras como mejoras futuras.

### Riesgo 2: Dashboard sobrecargado

Demasiada información puede confundir.

**Solución:** usar tarjetas, jerarquía visual y mostrar solo lo importante.

### Riesgo 3: Gamificación superficial

Los puntos no deben ser solo decoración.

**Solución:** conectar puntos, hábitos, tareas, progreso y estadísticas.

### Riesgo 4: Copiar demasiado la referencia

La inspiración viene de un tablero visual.

**Solución:** crear diseño, lógica y arquitectura propias.

### Riesgo 5: Estadísticas complejas

Calcular demasiadas métricas puede retrasar el desarrollo.

**Solución:** empezar con estadísticas básicas.

---

## 31. Cómo defenderlo ante el tribunal

En la defensa se puede mostrar este flujo:

1. Presentar el problema: la gente tiene metas pero no las convierte en acciones.
2. Presentar la solución: un tablero único de vida y productividad.
3. Mostrar registro/login.
4. Mostrar dashboard.
5. Crear área de vida.
6. Crear meta.
7. Crear proyecto.
8. Crear tarea.
9. Activar Modo Batalla.
10. Completar tarea y ganar puntos.
11. Marcar hábito y aumentar racha.
12. Consultar estadísticas.
13. Canjear recompensa.
14. Mostrar Zona Peligrosa.
15. Explicar base de datos.
16. Explicar seguridad.
17. Explicar mejoras futuras.

---

## 32. Frase final para la memoria

> LifeQuest propone una forma diferente de gestionar el progreso personal: convertir metas, hábitos, proyectos y tareas en un sistema visual, medible y gamificado. A través de un dashboard centralizado, el usuario puede priorizar, ejecutar y analizar su avance diario, incorporando mecánicas de puntos, rachas, recompensas y sesiones de concentración para transformar la planificación en resultados reales.

---

## 33. Próximo paso recomendado

El siguiente paso debería ser cerrar la definición del MVP y empezar con el diseño funcional.

Orden recomendado inmediato:

1. Elegir nombre definitivo.
2. Elegir estilo visual definitivo.
3. Definir módulos obligatorios.
4. Diseñar el modelo entidad-relación.
5. Crear wireframe del dashboard.
6. Crear estructura de carpetas.
7. Crear base de datos.
8. Implementar login/registro.
9. Implementar dashboard inicial.
10. Añadir módulos uno por uno.

---

## 34. Decisión recomendada

La opción más fuerte para el TFG es:

> **Una aplicación web tipo dashboard moderno de productividad personal gamificada, inspirada en el concepto de “videojuego de la vida”, con metas, hábitos, proyectos, tareas, estadísticas, Modo Batalla, recompensas y Zona Peligrosa.**

Esta opción es más profesional, más útil y más defendible que una app RPG tradicional con estética medieval.

