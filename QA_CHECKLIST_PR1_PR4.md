# QA Rapido PR1-PR4 (10 minutos)

Objetivo: validar HP, habitos negativos, progresion por area y tienda de indulgencias sin romper lo existente.

## 0) Precondiciones (1 min)

- Aplicar migraciones:
  - `database/hp_migration.sql`
  - `database/negative_habits_migration.sql`
  - `database/area_progression_migration.sql`
  - `database/shop_indulgence_migration.sql`
- Verificar flags en `config/config.php`:
  - `FEATURE_HP_SYSTEM = true`
  - `FEATURE_NEGATIVE_HABITS = true`
  - `FEATURE_AREA_PROGRESSION = true`
  - `FEATURE_INDULGENCE_SHOP = true`

Resultado esperado:
- No errores SQL.
- App inicia con login normal.

## 1) Smoke de navegacion base (1 min)

- Abrir dashboard, habitos, progreso, perfil y tienda desde el sidebar.

Resultado esperado:
- Todas las paginas cargan.
- No fatal errors ni pantallas en blanco.

## 2) PR1 - HP base (1 min)

- En dashboard y perfil, confirmar que se ve HP actual y maximo.

Resultado esperado:
- HP visible en dashboard y perfil.
- No rompe XP, puntos ni racha.

## 3) PR2 - Habitos negativos solo HP (3 min)

- En habitos, crear un habito normal con area.
- Marcar y desmarcar hoy.
- Verificar:
  - XP y points suben/bajan como antes.
- Crear un habito de riesgo (checkbox activo) con `hp_penalty`.
- Marcar y desmarcar hoy.
- Verificar:
  - Solo cambia HP.
  - XP y points no cambian en habito de riesgo.

Resultado esperado:
- Habito normal: afecta XP/points.
- Habito de riesgo: afecta HP unicamente.
- Sin errores en mensajes ni transacciones.

## 4) Racha global estable (1 min)

- Tras toggles de habito normal y de riesgo, volver a dashboard.

Resultado esperado:
- Racha del sidebar sigue visible y coherente.
- Habitos de riesgo no inflan racha global.

## 5) PR3 - Niveles por area (2 min)

- Completar al menos 1 accion con `area_id` (habito normal o tarea con area).
- Volver a dashboard y revisar bloque "Nivel por areas".

Resultado esperado:
- Se muestra tarjeta "Nivel por areas".
- Aparece al menos una area con nivel y barra de XP.
- XP global sigue funcionando en paralelo.

## 6) PR4 - Tienda de indulgencias (2 min)

- Abrir tienda.
- Verificar listado de indulgencias.
- Canjear una indulgencia asequible.
- Intentar repetir hasta llegar al limite semanal de una indulgencia.

Resultado esperado:
- Descuenta LifeCoins.
- Aplica efecto HP (si corresponde).
- Respeta limite semanal y deshabilita canje cuando se alcance.

## 7) Criterio de salida

Aprobado si:
- No hay errores fatales.
- Flujos base (login, dashboard, habitos, progreso, perfil, tienda) funcionan.
- Cada PR cumple su comportamiento esperado.
- No hay regresiones visibles en XP, points o racha.

## 8) Si algo falla

- Capturar: pagina, accion, mensaje exacto y hora.
- Revisar migraciones aplicadas y flags.
- Si bloquea operativa: usar rollback puntual de la migracion afectada.
