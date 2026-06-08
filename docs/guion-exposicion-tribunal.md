# Guion de exposicion - LifeQuest

Autor: Agustin Morcillo Aguado  
Duracion objetivo: 15 minutos  
Fecha de preparacion: 8 de junio de 2026

## Como usar este guion

Este documento es el guion principal. La presentacion visual es el propio proyecto en funcionamiento, asi que no hace falta preparar diapositivas. La idea es hablar mientras navegas por la aplicacion y abrir codigo solo en la segunda mitad, cuando toque justificar arquitectura, seguridad, base de datos y reglas de negocio.

Ensayo recomendado:

- Ensayo 1: leer el guion completo con cronometro.
- Ensayo 2: hacer la demo real siguiendo los tiempos.
- Ensayo 3: abrir tambien los archivos de codigo marcados como "Mostrar codigo".
- Objetivo: terminar entre 14:30 y 15:00.

## Resumen de tiempos

| Bloque | Tiempo | Que haces |
| --- | ---: | --- |
| Presentacion y contexto | 0:00-1:30 | Hablas sin tocar codigo. |
| Demo funcional | 1:30-7:30 | Enseñas el proyecto navegando por la app. |
| Codigo y base de datos | 7:30-13:30 | Abres VS Code y enseñas puntos clave. |
| Cierre | 13:30-15:00 | Resumes y das paso a preguntas. |

Regla importante para respetar la rubrica: entre 1:30 y 7:30 no abras codigo salvo que el tribunal lo pida expresamente. En ese bloque se enseña el proyecto funcionando. El codigo se reserva para 7:30-13:30.

## 0:00-1:30 Presentacion personal y contexto

Buenos dias. Soy Agustin Morcillo Aguado, y voy a presentar mi proyecto: LifeQuest.

LifeQuest es una aplicacion web de productividad gamificada desarrollada en PHP y MySQL. La idea principal es convertir la organizacion personal en un sistema mas visual, medible y motivador. En lugar de tener tareas, habitos y objetivos separados, la aplicacion los conecta en una misma experiencia.

El usuario puede organizar su vida por areas, crear metas, dividirlas en retos y misiones, registrar habitos diarios y ver como todo eso afecta a su progreso. La gamificacion aparece mediante XP, niveles, LifeCoins, rachas, HP, recompensas y tienda.

Durante la exposicion voy a hacer primero una demostracion del proyecto funcionando. Despues explicare como esta organizado el codigo y mostrare las partes tecnicas mas importantes: autenticacion, validacion, transacciones, recompensas y base de datos.

No mostrar codigo aqui. Solo contextualizar.

## 1:30-2:30 Login y dashboard

Ahora entro en la aplicacion desde el login y accedo al dashboard.

El dashboard es la pantalla principal del usuario. Aqui se resume el estado general: nivel, XP, LifeCoins, racha, HP, objetivo diario, tareas de hoy y actividad. No es una pantalla aislada, sino una composicion de distintos modelos del proyecto.

La idea de diseño es que el dashboard responda a una pregunta simple: que tengo pendiente hoy y como afecta eso a mi progreso.

Que enseñar en pantalla:

- `public/login.php`: login de usuario.
- `public/dashboard.php`: estado general, XP, nivel, HP, racha y tareas.
- Sidebar: navegacion hacia areas, metas, habitos, progreso, perfil y tienda.

Referencias de codigo para el bloque tecnico o para preguntas, no para abrir durante la demo:

- `public/dashboard.php:16`: la ruta esta protegida con `AuthController::requireAuth()`.
- `public/dashboard.php:86`: el dashboard construye la evolucion de XP con `XpEvolutionChart::build()`.
- `app/Support/XpEvolutionChart.php:7`: calculo reutilizable del grafico de XP.

Frase corta:

> El dashboard compone informacion de varias partes del sistema: usuario, tareas, habitos, objetivos diarios y progresion por areas.

## 2:30-4:30 Areas, metas, retos y misiones

Ahora paso a las areas de vida y a la pantalla de metas.

En LifeQuest, el dominio esta organizado con cuatro conceptos principales. Las areas representan partes de la vida del usuario, como salud, estudio, trabajo o desarrollo personal. Las metas son objetivos grandes. Los retos son bloques intermedios para avanzar hacia una meta. Y las misiones son acciones concretas, normalmente tareas realizables.

En la base de datos esta correspondencia es clara: areas se guarda en `life_areas`, metas en `goals`, retos en `projects` y misiones en `tasks`.

En `public/goals.php` se unifica la gestion de metas, retos y misiones. La pantalla cambia por secciones, pero mantiene un mismo flujo de trabajo. El usuario puede consultar, crear, editar, eliminar y completar elementos.

Si completo una mision, la aplicacion no solo cambia su estado a completada. Tambien suma XP y LifeCoins, recalcula el nivel del usuario, actualiza la progresion del area y recalcula el progreso del reto y de la meta relacionada.

Que enseñar en pantalla:

- `public/areas.php`: areas de vida.
- `public/goals.php?section=goals`: metas.
- `public/goals.php?section=projects`: retos.
- `public/goals.php?section=tasks`: misiones.
- Completar una mision preparada, si la demo esta lista.

Referencias de codigo para el bloque tecnico o para preguntas, no para abrir durante la demo:

- `public/goals.php:13`: protege la pantalla con `AuthController::requireAuth()`.
- `public/goals.php:42`: crea token CSRF.
- `public/goals.php:74`: valida token CSRF antes de procesar formularios.
- `app/Controllers/TaskController.php:92`: validacion de mision.
- `app/Controllers/TaskController.php:120`: valida que el reto pertenece al usuario.
- `app/Controllers/TaskController.php:133`: valida que la meta pertenece al usuario.
- `app/Controllers/TaskController.php:146`: valida que el area pertenece al usuario.
- `app/Controllers/TaskController.php:161`: calcula recompensa con `RewardCalculator::forTask()`.

Frase corta:

> La jerarquia meta, reto y mision conecta el objetivo a largo plazo con la accion diaria.

## 4:30-6:00 Habitos y seguimiento

Ahora muestro el modulo de habitos.

Los habitos estan divididos en habitos positivos y habitos de control. Los habitos positivos representan acciones que el usuario quiere repetir, como entrenar, estudiar o leer. Cuando se completan, suman XP, LifeCoins y racha.

Los habitos de control estan pensados para comportamientos que el usuario quiere reducir o vigilar. En este caso, el sistema permite registrar el dia como controlado o como recaida parcial. Si se registra una recaida parcial, se descuenta HP.

Esto es importante porque la gamificacion no solo premia. Tambien refleja consecuencias. El HP funciona como una medida del estado del jugador dentro del sistema.

Que enseñar en pantalla:

- `public/habits.php`: pestaña de habitos positivos.
- `public/habits.php`: pestaña de habitos de control.
- Registro de un estado diario.
- Estadisticas por semana o mes.

Referencias de codigo para el bloque tecnico o para preguntas, no para abrir durante la demo:

- `app/Controllers/HabitController.php:93`: recibe el cambio diario con `toggleToday()`.
- `app/Controllers/HabitController.php:105`: valida datos del habito.
- `app/Models/Habit.php:224`: `toggleToday()` implementa la regla principal.
- `app/Models/Habit.php:260`: calcula multiplicador de recompensa por estado.
- `app/Models/Habit.php:273`: inicia transaccion.
- `app/Models/Habit.php:361`: descuenta o devuelve HP si el habito es de control.
- `app/Models/Habit.php:369`: recalcula rachas.
- `app/Models/Habit.php:400`: confirma transaccion.
- `app/Models/Habit.php:513`: `applyUserHpDelta()` modifica HP.

Frase corta:

> El HP convierte los habitos de control en una mecanica persistente, no solo visual.

## 6:00-7:30 Progreso, perfil, tienda y administracion

Ahora cierro la parte funcional mostrando progreso, tienda y administracion.

La vista de progreso consolida metricas del usuario y muestra evolucion. Para evitar duplicar calculos, el grafico de XP esta centralizado en `app/Support/XpEvolutionChart.php`, que tambien se reutiliza en el dashboard.

La tienda permite gastar LifeCoins. Hay indulgencias y cosmeticos. Las indulgencias tienen coste, limite semanal y pueden recuperar HP. Los cosmeticos forman parte del inventario del usuario y pueden equiparse por categoria.

Por ultimo, el proyecto tiene un panel administrativo separado en `admin/`. Desde ahi se puede consultar base de datos, revisar jugadores, gestionar tienda y ajustar valores de balance como XP base, multiplicadores y costes. Esto demuestra que el proyecto tambien se ha pensado desde el mantenimiento.

Que enseñar en pantalla:

- `public/progress.php`: evolucion y metricas.
- `public/profile.php`: perfil, avatar y estado del jugador.
- `public/shop.php`: indulgencias, cosmeticos e inventario.
- `admin/database.php`: portal administrativo, sin mostrar credenciales.

Referencias de codigo para el bloque tecnico o para preguntas, no para abrir durante la demo:

- `public/shop.php:28`: genera CSRF token.
- `public/shop.php:36`: valida CSRF token.
- `app/Models/Reward.php:243`: `getShopItems()` carga catalogo.
- `app/Models/Reward.php:343`: `redeemIndulgence()` canjea indulgencias.
- `app/Models/Reward.php:477`: `redeemCosmetic()` compra cosmeticos.
- `app/Models/Reward.php:573`: `equipCosmetic()` equipa cosmeticos.
- `admin/database.php:16`: exige sesion admin.
- `admin/session_guard.php:10`: comprueba expiracion por inactividad.

Transicion:

> Una vez visto el funcionamiento, paso a explicar como esta construido por dentro.

## 7:30-8:45 Estructura del codigo

Ahora si abro VS Code.

La estructura principal del proyecto esta separada en carpetas por responsabilidad.

`public/` contiene las pantallas principales que ve el usuario: `dashboard.php`, `areas.php`, `goals.php`, `habits.php`, `progress.php`, `profile.php` y `shop.php`.

`admin/` contiene el portal administrativo, separado del flujo publico.

`app/Controllers/` contiene clases que validan entradas y coordinan acciones. Un ejemplo es `TaskController`, que valida una mision antes de crearla, actualizarla o completarla.

`app/Models/` contiene acceso a datos y reglas de persistencia. Aqui estan modelos como `Task`, `Habit`, `Reward`, `User`, `Goal`, `Project` o `AreaProgression`.

`app/Support/` guarda utilidades reutilizables, como `RewardCalculator`, `XpEvolutionChart`, `AvatarLibrary` y calculos de racha.

`app/Database/connection.php` centraliza la conexion PDO. Se configura con modo de excepciones, fetch asociativo y consultas preparadas sin emulacion.

Mostrar codigo:

- `app/Database/connection.php:17`: creacion de `PDO`.
- `app/Database/connection.php:18`: errores como excepciones.
- `app/Database/connection.php:19`: fetch asociativo por defecto.
- `app/Database/connection.php:20`: prepared statements reales sin emulacion.

Frase corta:

> Las vistas muestran, los controladores validan, los modelos ejecutan reglas de negocio y `Support` concentra calculos reutilizables.

## 8:45-9:45 Seguridad y validacion

En autenticacion, `AuthController` valida registro y login. Las contrasenas se guardan usando `password_hash()` en el modelo `User`, y el login comprueba con `password_verify()`. Ademas, cuando el usuario inicia sesion se ejecuta `session_regenerate_id(true)`, que ayuda a prevenir fijacion de sesion.

Las paginas privadas llaman a `AuthController::requireAuth()`. Por ejemplo, `dashboard.php`, `goals.php`, `habits.php` y `shop.php` empiezan comprobando que el usuario esta autenticado.

Tambien hay uso de tokens CSRF en acciones sensibles. En `goals.php` y `shop.php`, antes de procesar formularios, se comprueba que el token de sesion coincide con el token enviado.

Un punto importante es que los controladores no aceptan IDs sin comprobarlos. Por ejemplo, en `TaskController`, si una mision se asocia a un reto, una meta o un area, se comprueba que esa entidad exista y pertenezca al usuario actual.

Mostrar codigo:

- `app/Controllers/AuthController.php:53`: `login()`.
- `app/Controllers/AuthController.php:64`: `password_verify()`.
- `app/Controllers/AuthController.php:68`: `session_regenerate_id(true)`.
- `app/Controllers/AuthController.php:81`: `requireAuth()`.
- `app/Models/User.php:30`: `password_hash()`.
- `public/goals.php:42`: creacion de CSRF token.
- `public/goals.php:74`: validacion de CSRF token.
- `app/Controllers/TaskController.php:120`: validacion de reto por usuario.
- `app/Controllers/TaskController.php:133`: validacion de meta por usuario.
- `app/Controllers/TaskController.php:146`: validacion de area por usuario.

Frase corta:

> No basta con que un ID exista en la base de datos; tiene que pertenecer al usuario autenticado.

## 9:45-11:15 Ejemplo tecnico principal: completar una mision

Voy a abrir `app/Models/Task.php`, en la funcion `complete()`.

Esta funcion es el mejor ejemplo tecnico del proyecto porque concentra varias reglas importantes.

Primero, busca la mision por id y usuario. Esto evita que un usuario pueda completar una mision que no le pertenece. Despues comprueba que la mision no estuviera ya completada.

A continuacion abre una transaccion con `beginTransaction()`. Esto es importante porque completar una mision afecta a varias tablas y valores: la propia mision, el usuario, el nivel, los puntos, la progresion del area, el progreso del reto, el progreso de la meta y el objetivo diario.

Dentro de la transaccion, la mision cambia a `completed` y se marca `completed_at`. Luego se consulta el XP y los puntos actuales del usuario, se suman las recompensas de la mision y se recalcula el nivel con una regla sencilla: cada 1000 XP se sube de nivel.

Despues se llama a `areaProgression->addXp()`, para sumar XP al area relacionada, y a `refreshRelatedProgress()`, que recalcula el progreso del reto y de la meta. Finalmente se comprueba si se ha completado el objetivo diario y se aplica un bonus si corresponde.

Si todo sale bien, se hace `commit()`. Si algo falla, se hace `rollBack()`.

Mostrar codigo:

- `app/Models/Task.php:176`: `findByIdAndUser()`.
- `app/Models/Task.php:287`: empieza `complete()`.
- `app/Models/Task.php:300`: `beginTransaction()`.
- `app/Models/Task.php:305`: actualiza tarea a `completed`.
- `app/Models/Task.php:324`: calcula nuevo XP.
- `app/Models/Task.php:326`: recalcula nivel.
- `app/Models/Task.php:341`: suma XP a progresion por area.
- `app/Models/Task.php:347`: recalcula progreso relacionado.
- `app/Models/Task.php:350`: revisa objetivo diario.
- `app/Models/Task.php:352`: `commit()`.
- `app/Models/Task.php:370`: `rollBack()`.
- `app/Models/Task.php:426`: `refreshProjectProgress()` recalcula reto.
- `app/Models/Task.php:474`: `refreshGoalProgress()` recalcula meta.
- `app/Models/Task.php:532`: `checkAndAwardDailyObjective()`.

Frase clave:

> Completar una mision es una operacion atomica del sistema de gamificacion.

## 11:15-12:15 Ejemplo tecnico secundario: habitos de control y HP

Ahora abro `app/Models/Habit.php`, en la funcion `toggleToday()`.

Esta funcion gestiona el registro diario de un habito. En un habito positivo, el flujo es simple: si no estaba completado, lo completa; si ya estaba completado, lo desmarca.

Pero en los habitos de control hay mas logica. El estado puede ser `completed`, `partial` o sin registro. `completed` significa que el dia se mantuvo bajo control. `partial` representa una recaida parcial. Y sin registro significa que no hay estado para ese dia.

El codigo calcula un multiplicador de recompensa segun el estado anterior y el nuevo estado. Asi puede sumar o restar XP y LifeCoins de forma proporcional.

La parte mas representativa es el HP. Si el habito pasa a `partial`, se llama a `applyUserHpDelta()` con una penalizacion negativa. Si se revierte esa recaida parcial, se devuelve el HP. Despues se recalculan rachas y se actualiza el usuario.

Mostrar codigo:

- `app/Models/Habit.php:224`: empieza `toggleToday()`.
- `app/Models/Habit.php:260`: multiplicador de recompensa.
- `app/Models/Habit.php:273`: `beginTransaction()`.
- `app/Models/Habit.php:361`: descuenta o devuelve HP.
- `app/Models/Habit.php:369`: recalcula rachas.
- `app/Models/Habit.php:372`: aplica recompensas.
- `app/Models/Habit.php:400`: `commit()`.
- `app/Models/Habit.php:420`: `rollBack()`.
- `app/Models/Habit.php:513`: `applyUserHpDelta()`.
- `app/Models/Habit.php:550`: `recalculateStreaks()`.

Frase clave:

> Esta parte demuestra que la gamificacion no es decorativa: modifica el estado persistente del usuario.

## 12:15-12:45 Recompensas y calculos reutilizables

Otro archivo importante es `app/Support/RewardCalculator.php`.

Aqui se centraliza el calculo de XP y LifeCoins para habitos, misiones y metas. Por ejemplo, una mision depende de su prioridad y del tiempo estimado. La prioridad puede multiplicar la recompensa y el esfuerzo tambien.

Esto evita repetir formulas en varias partes del codigo. Si en el futuro quiero ajustar el balance del proyecto, puedo cambiar la logica en un punto o modificar valores desde `app_settings` en el panel admin.

Mostrar codigo:

- `app/Support/RewardCalculator.php:11`: `forHabit()`.
- `app/Support/RewardCalculator.php:32`: `forTask()`.
- `app/Support/RewardCalculator.php:46`: `forGoal()`.
- `app/Support/RewardCalculator.php:67`: multiplicador por prioridad.
- `app/Support/RewardCalculator.php:77`: multiplicador por esfuerzo.
- `app/Support/RewardCalculator.php:102`: `clamp()` limita valores extremos.
- `app/Support/RewardCalculator.php:129`: lee ajustes con `AppSettings`.

Frase corta:

> Las formulas de recompensa no estan repartidas por toda la aplicacion; estan centralizadas.

## 12:45-13:30 Base de datos

Paso ahora al esquema de base de datos en `database/schema.sql`.

La tabla central es `users`, donde se guarda el usuario, su email, contrasena hasheada, avatar, nivel, XP, LifeCoins, HP, HP maximo y racha.

Alrededor de `users` estan las tablas principales del dominio: `life_areas`, `goals`, `projects`, `tasks`, `habits` y `habit_logs`.

La base de datos utiliza claves foraneas para relacionar los datos con el usuario y con las entidades superiores. Por ejemplo, una tarea puede estar asociada a un proyecto, una meta y un area. Si se borra un usuario, sus datos se eliminan con `ON DELETE CASCADE`. Si se borra un area o una meta asociada, algunas relaciones pasan a `NULL` para no romper registros historicos.

Tambien hay restricciones unicas importantes. `habit_logs` tiene `unique_habit_day`, que evita duplicar el registro de un mismo habito en el mismo dia. `area_progression` tiene `unique_user_area`, que evita duplicar la progresion del mismo usuario en la misma area. Y `user_reward_inventory` evita que el usuario tenga duplicado el mismo cosmetico.

Mostrar codigo SQL:

- `database/schema.sql:7`: tabla `users`.
- `database/schema.sql:22`: tabla `life_areas`.
- `database/schema.sql:33`: tabla `goals`.
- `database/schema.sql:52`: tabla `projects`.
- `database/schema.sql:69`: tabla `tasks`.
- `database/schema.sql:91`: tabla `habits`.
- `database/schema.sql:112`: tabla `habit_logs`.
- `database/schema.sql:119`: `unique_habit_day`.
- `database/schema.sql:140`: tabla `rewards`.
- `database/schema.sql:165`: tabla `user_reward_inventory`.
- `database/schema.sql:172`: `unique_user_reward_inventory`.
- `database/schema.sql:187`: tabla `area_progression`.
- `database/schema.sql:195`: `unique_user_area`.
- `database/schema.sql:200`: tabla `app_settings`.

Frase corta:

> La base de datos esta centrada en `users`; desde ahi salen areas, metas, retos, misiones, habitos, recompensas, progresion e insignias.

## 13:30-15:00 Cierre

Para terminar, LifeQuest es una aplicacion web que une productividad personal y gamificacion.

Desde el punto de vista funcional, permite registrarse, iniciar sesion, gestionar areas, metas, retos, misiones y habitos, consultar progreso, personalizar perfil, usar tienda y acceder a un panel administrativo.

Desde el punto de vista tecnico, el proyecto esta organizado en rutas publicas, controladores, modelos, soporte reutilizable, conexion centralizada y un esquema de base de datos con relaciones claras.

Y desde el punto de vista de desarrollo, las reglas principales estan implementadas en codigo real: completar misiones recalcula progreso y recompensas; los habitos gestionan rachas y HP; la tienda usa inventario y limites; y el admin permite mantener el sistema.

Como mejoras futuras, anadiria una bateria mas amplia de tests automatizados, una gestion de migraciones mas guiada, mejoras de accesibilidad y analiticas comparativas para que el usuario pueda revisar semanas o meses con mas detalle.

Con esto cierro la presentacion de LifeQuest. Muchas gracias por vuestra atencion; quedo a vuestra disposicion para las preguntas.

## Orden rapido para abrir codigo en directo

Si tienes poco tiempo, abre solo esto:

1. `app/Database/connection.php:17` - conexion PDO segura.
2. `app/Controllers/AuthController.php:53` - login.
3. `public/goals.php:42` y `public/goals.php:74` - CSRF.
4. `app/Controllers/TaskController.php:92` - validacion de mision.
5. `app/Models/Task.php:287` - completar mision.
6. `app/Models/Habit.php:224` - habitos de control.
7. `app/Support/RewardCalculator.php:32` - recompensa de mision.
8. `database/schema.sql:69` y `database/schema.sql:112` - tablas de misiones y logs de habitos.

## Donde estan la mayoria de funciones

- `app/Models/`: reglas de negocio y persistencia. Es la carpeta mas importante para defender tecnicamente.
- `app/Controllers/`: validacion de formularios y coordinacion de acciones.
- `public/*.php`: pantallas y funciones auxiliares de vista.
- `app/Support/`: calculos reutilizables, recompensas, XP, avatares y rachas.
- `admin/database.php` y `app/Models/AdminDatabaseManager.php`: gestion administrativa.

## Preguntas probables del tribunal

### 1. Por que elegiste PHP y MySQL?

Porque encajan bien con el entorno del proyecto y con XAMPP. PHP permite construir una aplicacion web completa de forma directa y MySQL ofrece una base relacional adecuada para representar usuarios, metas, tareas, habitos y recompensas.

### 2. Que parte consideras mas compleja?

La parte mas compleja es la logica de gamificacion cuando una accion afecta a varias entidades. Por ejemplo, completar una mision actualiza tarea, usuario, XP, nivel, progreso por area, progreso de reto, progreso de meta, objetivo diario e insignias. Por eso esta implementado con transacciones.

### 3. Como evitas que un usuario modifique datos de otro?

En controladores y modelos se comprueba el `user_id`. Antes de actualizar o completar una mision se busca con `findByIdAndUser()`. Las consultas incluyen tanto el id del recurso como el id del usuario autenticado.

### 4. Que medidas de seguridad tiene?

El login usa `password_hash()` y `password_verify()`. Al iniciar sesion se regenera el id de sesion. Las rutas privadas llaman a `AuthController::requireAuth()`. Las consultas usan PDO preparado. En formularios sensibles hay tokens CSRF.

### 5. Por que separaste controladores y modelos?

Para separar responsabilidades. El controlador valida la entrada y decide que accion ejecutar. El modelo consulta o modifica la base de datos. Asi el codigo es mas facil de revisar, mantener y ampliar.

### 6. Como se calcula el nivel del usuario?

El nivel se calcula a partir del XP acumulado. En varias operaciones se usa una regla de 1000 XP por nivel, con `intdiv($newXp, 1000) + 1`, asegurando como minimo nivel 1.

### 7. Que diferencia hay entre habitos positivos y de control?

Los positivos refuerzan conductas deseadas y suman recompensa cuando se completan. Los de control registran conductas que se quieren reducir. Si hay recaida parcial, se descuenta HP; si se revierte, se devuelve.

### 8. Como evitas duplicar un habito completado el mismo dia?

La tabla `habit_logs` tiene una restriccion unica `unique_habit_day` sobre `habit_id` y `completed_date`. Ademas, el modelo comprueba si ya existe registro antes de insertar o actualizar.

### 9. Que papel tiene la tienda?

La tienda cierra el ciclo de gamificacion. El usuario gana LifeCoins mediante acciones productivas y despues puede canjearlas por indulgencias o cosmeticos. Las indulgencias tienen limites semanales y los cosmeticos se guardan en inventario.

### 10. Que mejorarias si tuvieras mas tiempo?

Anadiria una capa de tests automatizados mas completa, una gestion de migraciones mas guiada, mejoras de accesibilidad y analiticas comparativas para que el usuario pueda revisar semanas o meses con mas detalle.

## Mini guion si te quedas sin tiempo

LifeQuest tiene tres ideas tecnicas principales: separacion de responsabilidades en codigo, base de datos relacional coherente y reglas de negocio reales. Las rutas publicas muestran la aplicacion, los controladores validan, los modelos actualizan datos y `Support` concentra calculos reutilizables. En la base de datos, el usuario se relaciona con areas, metas, retos, misiones, habitos y recompensas. Y en la logica, acciones como completar una mision o registrar un habito modifican XP, nivel, monedas, HP, rachas y progreso. Con esto cierro la defensa del proyecto.
