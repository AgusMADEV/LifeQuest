# Mapa de codigo para tribunal - LifeQuest

Esta guia sirve para abrir rapidamente las lineas importantes durante la defensa. La idea no es ensenar todo el proyecto, sino elegir 4 o 5 puntos fuertes y explicarlos con seguridad.

## 1. Donde vive cada parte del proyecto

| Carpeta | Que contiene | Cuando mencionarla |
| --- | --- | --- |
| `public/` | Pantallas principales del usuario | Demo funcional: dashboard, areas, metas, habitos, progreso, perfil y tienda. |
| `admin/` | Portal administrativo independiente | Mantenimiento, balance, exploracion de base de datos y gestion admin. |
| `app/Controllers/` | Validacion y coordinacion de formularios | Explicar separacion de responsabilidades. |
| `app/Models/` | Persistencia y reglas de negocio | Explicar operaciones reales: completar misiones, habitos, recompensas. |
| `app/Support/` | Calculos reutilizables | Explicar que no se duplican formulas ni utilidades. |
| `app/Database/` | Conexion PDO | Seguridad, prepared statements y conexion centralizada. |
| `database/` | Esquema y migraciones | Base de datos, relaciones y evolucion del proyecto. |
| `assets/`, `icons/`, `referencias/` | CSS, JS, subidas e imagenes | Parte visual y recursos. |

## 2. Lineas imprescindibles para ensenar

### Autenticacion y proteccion de rutas

- `app/Controllers/AuthController.php:16`: `register()` valida registro.
- `app/Controllers/AuthController.php:53`: `login()` valida credenciales.
- `app/Controllers/AuthController.php:64`: `password_verify()` comprueba contrasena.
- `app/Controllers/AuthController.php:68`: `session_regenerate_id(true)` al iniciar sesion.
- `app/Controllers/AuthController.php:81`: `requireAuth()` protege rutas privadas.
- `app/Models/User.php:30`: `password_hash()` guarda la contrasena hasheada.
- `app/Database/connection.php:17`: creacion de `PDO`.
- `app/Database/connection.php:18`: `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- `app/Database/connection.php:20`: `PDO::ATTR_EMULATE_PREPARES => false`.

Frase para decir:

> La autenticacion no guarda contrasenas en plano; se guardan con hash, se verifican con `password_verify`, se regenera la sesion al entrar y las rutas privadas llaman a `requireAuth`.

### Pantalla unificada de metas, retos y misiones

- `public/goals.php:13`: proteccion con `AuthController::requireAuth()`.
- `public/goals.php:42`: generacion de `csrf_token`.
- `public/goals.php:74`: validacion del token CSRF antes de procesar formularios.
- `public/goals.php:298`: funcion `e()` para escapar salida HTML.
- `public/goals.php:442`: helper `csrfField()` para pintar el campo oculto.

Frase para decir:

> `goals.php` es la pantalla operativa donde se unifican metas, retos y misiones, pero las acciones se delegan a controladores.

### Validacion en controladores

- `app/Controllers/GoalController.php:51`: `validate()` de metas.
- `app/Controllers/GoalController.php:75`: valida que el area pertenezca al usuario.
- `app/Controllers/GoalController.php:91`: calcula recompensa de meta con `RewardCalculator::forGoal()`.
- `app/Controllers/ProjectController.php:70`: `validate()` de retos.
- `app/Controllers/ProjectController.php:96`: valida que la meta pertenezca al usuario.
- `app/Controllers/ProjectController.php:109`: valida que el area pertenezca al usuario.
- `app/Controllers/TaskController.php:92`: `validate()` de misiones.
- `app/Controllers/TaskController.php:120`: valida que el reto pertenezca al usuario.
- `app/Controllers/TaskController.php:133`: valida que la meta pertenezca al usuario.
- `app/Controllers/TaskController.php:146`: valida que el area pertenezca al usuario.
- `app/Controllers/TaskController.php:161`: calcula recompensa de mision con `RewardCalculator::forTask()`.

Frase para decir:

> No basta con recibir un ID desde el formulario. Antes de guardar, el controlador comprueba que ese reto, meta o area pertenece al usuario autenticado.

### Completar una mision: mejor ejemplo tecnico

- `app/Controllers/TaskController.php:80`: `complete()` del controlador recibe la accion.
- `app/Models/Task.php:176`: `findByIdAndUser()` busca tarea por ID y usuario.
- `app/Models/Task.php:287`: `complete()` del modelo ejecuta la regla de negocio.
- `app/Models/Task.php:300`: `beginTransaction()` inicia operacion atomica.
- `app/Models/Task.php:305`: cambia la tarea a `completed` y marca `completed_at`.
- `app/Models/Task.php:324`: calcula nuevo XP del usuario.
- `app/Models/Task.php:326`: recalcula nivel con XP acumulado.
- `app/Models/Task.php:341`: suma XP a la progresion por area.
- `app/Models/Task.php:347`: recalcula progreso de reto y meta.
- `app/Models/Task.php:350`: comprueba objetivo diario.
- `app/Models/Task.php:352`: `commit()` confirma la transaccion.
- `app/Models/Task.php:370`: `rollBack()` revierte si falla.
- `app/Models/Task.php:379`: `refreshRelatedProgress()` coordina recalculo de progreso.
- `app/Models/Task.php:426`: `refreshProjectProgress()` recalcula reto.
- `app/Models/Task.php:474`: `refreshGoalProgress()` recalcula meta.
- `app/Models/Task.php:532`: `checkAndAwardDailyObjective()` registra bonus diario si corresponde.

Frase para decir:

> Completar una mision no es solo cambiar un estado. Dentro de una transaccion se actualizan tarea, XP, monedas, nivel, progresion por area, progreso de reto, progreso de meta y objetivo diario.

### Habitos positivos y habitos de control

- `public/habits.php:11`: ruta protegida con `requireAuth()`.
- `app/Controllers/HabitController.php:93`: `toggleToday()` recibe el cambio diario.
- `app/Controllers/HabitController.php:105`: `validate()` de habitos.
- `app/Controllers/HabitController.php:130`: valida area del usuario.
- `app/Controllers/HabitController.php:142`: valida meta del usuario.
- `app/Controllers/HabitController.php:163`: recompensa base con `RewardCalculator::forHabit()`.
- `app/Models/Habit.php:224`: `toggleToday()` implementa la regla de negocio.
- `app/Models/Habit.php:260`: multiplicador de recompensa segun estado.
- `app/Models/Habit.php:273`: `beginTransaction()`.
- `app/Models/Habit.php:361`: aplica descuento o devolucion de HP si el habito es de control.
- `app/Models/Habit.php:369`: recalcula rachas.
- `app/Models/Habit.php:372`: aplica XP y LifeCoins si cambia recompensa.
- `app/Models/Habit.php:400`: `commit()`.
- `app/Models/Habit.php:420`: `rollBack()`.
- `app/Models/Habit.php:444`: `applyUserRewards()` actualiza XP, puntos y nivel.
- `app/Models/Habit.php:513`: `applyUserHpDelta()` modifica HP.
- `app/Models/Habit.php:550`: `recalculateStreaks()` recalcula rachas del habito.
- `app/Models/Habit.php:630`: `syncUserCurrentStreak()` sincroniza racha del usuario.

Frase para decir:

> Los habitos de control no se tratan igual que los positivos: el estado `partial` representa recaida parcial y afecta al HP del usuario.

### Recompensas y balance

- `app/Support/RewardCalculator.php:11`: `forHabit()` calcula recompensa de habitos.
- `app/Support/RewardCalculator.php:32`: `forTask()` calcula recompensa de misiones.
- `app/Support/RewardCalculator.php:46`: `forGoal()` calcula recompensa de metas.
- `app/Support/RewardCalculator.php:67`: `priorityMultiplier()`.
- `app/Support/RewardCalculator.php:77`: multiplicador por esfuerzo en minutos.
- `app/Support/RewardCalculator.php:102`: `clamp()` limita valores extremos.
- `app/Support/RewardCalculator.php:129`: `settings()` lee ajustes desde `AppSettings`.
- `app/Models/AppSettings.php:38`: `getMany()` obtiene ajustes.
- `app/Models/AppSettings.php:65`: `upsertMany()` guarda ajustes desde admin.

Frase para decir:

> Las formulas de recompensa no estan repartidas por toda la aplicacion; estan centralizadas en `RewardCalculator`, y algunos valores pueden ajustarse desde `app_settings`.

### Tienda, indulgencias e inventario

- `public/shop.php:9`: tienda protegida con `requireAuth()`.
- `public/shop.php:28`: genera CSRF token.
- `public/shop.php:36`: valida CSRF token.
- `app/Models/Reward.php:158`: `ensureDefaultIndulgences()` garantiza catalogo base de indulgencias.
- `app/Models/Reward.php:243`: `getShopItems()` carga articulos de tienda.
- `app/Models/Reward.php:343`: `redeemIndulgence()` canjea indulgencias.
- `app/Models/Reward.php:477`: `redeemCosmetic()` compra cosmeticos.
- `app/Models/Reward.php:573`: `equipCosmetic()` equipa cosmeticos.
- `app/Models/Reward.php:725`: `hasTable()` permite compatibilidad con migraciones.
- `app/Models/Reward.php:756`: `hasColumn()` comprueba columnas opcionales.
- `app/Models/Reward.php:783`: `getFloatSetting()` lee multiplicadores configurables.

Frase para decir:

> La tienda convierte los puntos en recompensas. Las indulgencias tienen limites y pueden afectar al HP; los cosmeticos se guardan en inventario y se equipan por categoria.

### Dashboard, progreso y utilidades visuales

- `public/dashboard.php:16`: dashboard protegido con `requireAuth()`.
- `public/dashboard.php:86`: construye grafico de XP con `XpEvolutionChart::build()`.
- `public/dashboard.php:188`: `e()` escapa salida HTML.
- `public/progress.php:14`: progreso protegido con `requireAuth()`.
- `public/progress.php:273`: `buildSparkline()` crea minigrafico.
- `app/Support/XpEvolutionChart.php:7`: `build()` centraliza evolucion de XP.
- `app/Support/XpEvolutionChart.php:216`: `formatAxisXp()` formatea eje del grafico.
- `app/Support/AvatarLibrary.php:24`: `getAvatarSrc()` resuelve avatar publico.
- `app/Support/AvatarLibrary.php:33`: `normalizeAvatar()` valida avatar.
- `app/Support/StreakWeek.php:9`: `buildWeeklyActivityByUser()` calcula actividad semanal.

Frase para decir:

> El dashboard y progreso reutilizan calculos comunes; por ejemplo, la evolucion de XP no esta duplicada, vive en `XpEvolutionChart`.

### Panel administrativo

- `admin/login.php:9`: comprueba si el portal admin esta habilitado.
- `admin/login.php:43`: verifica credenciales admin.
- `admin/login.php:49`: regenera sesion admin.
- `admin/database.php:16`: exige sesion admin.
- `admin/database.php:21`: comprueba expiracion por inactividad.
- `admin/database.php:192`: bloque de cambio de contrasena.
- `admin/session_guard.php:5`: `clearAdminPortalSession()` limpia sesion admin.
- `admin/session_guard.php:10`: `isAdminPortalSessionExpired()` calcula expiracion.
- `app/Models/AdminPortalUser.php:37`: `verifyCredentials()` del admin.
- `app/Models/AdminPortalUser.php:64`: `updatePasswordById()`.
- `app/Models/AdminDatabaseManager.php:18`: resumen de conteos.
- `app/Models/AdminDatabaseManager.php:44`: edicion de stats de jugador.
- `app/Models/AdminDatabaseManager.php:85`: listado de recompensas de tienda.
- `app/Models/AdminDatabaseManager.php:348`: listado de tablas.
- `app/Models/AdminDatabaseManager.php:392`: paginacion de filas.
- `app/Models/AdminDatabaseManager.php:558`: consola SQL controlada.

Frase para decir:

> El admin no comparte la sesion normal del usuario. Tiene login, timeout y utilidades de mantenimiento propias.

## 3. Base de datos: lineas que conviene abrir

- `database/schema.sql:7`: tabla `users`.
- `database/schema.sql:22`: tabla `life_areas`.
- `database/schema.sql:33`: tabla `goals`.
- `database/schema.sql:52`: tabla `projects`.
- `database/schema.sql:69`: tabla `tasks`.
- `database/schema.sql:91`: tabla `habits`.
- `database/schema.sql:112`: tabla `habit_logs`.
- `database/schema.sql:119`: `unique_habit_day` evita duplicar un habito por dia.
- `database/schema.sql:140`: tabla `rewards`.
- `database/schema.sql:156`: tabla `reward_redemptions`.
- `database/schema.sql:165`: tabla `user_reward_inventory`.
- `database/schema.sql:172`: `unique_user_reward_inventory` evita cosmeticos duplicados.
- `database/schema.sql:187`: tabla `area_progression`.
- `database/schema.sql:195`: `unique_user_area` evita progresion duplicada por area.
- `database/schema.sql:200`: tabla `app_settings`.
- `database/schema.sql:207`: tabla `user_badges`.

Frase para decir:

> La base de datos esta centrada en `users`; desde ahi salen areas, metas, retos, misiones, habitos, recompensas, progresion e insignias.

## 4. Donde estan la mayoria de funciones

La mayor concentracion de funciones esta aqui:

1. `app/Models/`: reglas de negocio y persistencia. Es donde estan las funciones mas importantes para defender tecnicamente el proyecto.
2. `app/Controllers/`: validacion de formularios y coordinacion de acciones.
3. `public/*.php`: funciones auxiliares de vista, escape HTML, etiquetas, colores, iconos y renderizado.
4. `app/Support/`: calculos reutilizables, recompensas, XP, avatares y rachas.
5. `admin/database.php` y `AdminDatabaseManager`: gestion administrativa y utilidades de base de datos.

## 5. Orden recomendado para ensenar codigo en directo

1. `app/Database/connection.php:17` - conexion PDO segura.
2. `app/Controllers/AuthController.php:53` - login y sesion.
3. `public/goals.php:42` y `public/goals.php:74` - CSRF en formularios.
4. `app/Controllers/TaskController.php:92` - validacion de mision.
5. `app/Models/Task.php:287` - completar mision.
6. `app/Models/Habit.php:224` - habitos de control y HP.
7. `app/Support/RewardCalculator.php:32` - recompensa de mision.
8. `database/schema.sql:69` y `database/schema.sql:112` - tablas `tasks` y `habit_logs`.

Con este orden cubres seguridad, arquitectura, reglas de negocio y base de datos sin saltar aleatoriamente por el proyecto.
