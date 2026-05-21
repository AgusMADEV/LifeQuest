-- Script de migración: Renombrar base de datos de 'questboard' a 'lifequest'
-- ============================================================================
-- Este script te ayuda a migrar tu base de datos existente al nuevo nombre.
-- 
-- INSTRUCCIONES:
-- 1. Haz backup de tu BD actual 'questboard' antes de ejecutar este script
-- 2. Ejecuta este script en phpMyAdmin o desde la consola MySQL
-- 3. Verifica que todo funciona correctamente
-- 4. Opcional: elimina la BD antigua 'questboard' cuando estés seguro

-- Paso 1: Crear nueva base de datos 'lifequest'
CREATE DATABASE IF NOT EXISTS lifequest
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Paso 2: Crear usuario 'lifequest' (si no existe)
-- Cambia 'tu_contraseña' por la misma que usabas en 'questboard'
CREATE USER IF NOT EXISTS 'lifequest'@'localhost' IDENTIFIED BY '159159159';
GRANT ALL PRIVILEGES ON lifequest.* TO 'lifequest'@'localhost';
FLUSH PRIVILEGES;

-- Paso 3: Copiar todas las tablas de 'questboard' a 'lifequest'
-- Ejecuta estos comandos uno por uno, o todo junto

-- Copiar estructura y datos de todas las tablas
CREATE TABLE lifequest.users LIKE questboard.users;
INSERT INTO lifequest.users SELECT * FROM questboard.users;

CREATE TABLE lifequest.life_areas LIKE questboard.life_areas;
INSERT INTO lifequest.life_areas SELECT * FROM questboard.life_areas;

CREATE TABLE lifequest.goals LIKE questboard.goals;
INSERT INTO lifequest.goals SELECT * FROM questboard.goals;

CREATE TABLE lifequest.projects LIKE questboard.projects;
INSERT INTO lifequest.projects SELECT * FROM questboard.projects;

CREATE TABLE lifequest.tasks LIKE questboard.tasks;
INSERT INTO lifequest.tasks SELECT * FROM questboard.tasks;

CREATE TABLE lifequest.habits LIKE questboard.habits;
INSERT INTO lifequest.habits SELECT * FROM questboard.habits;

CREATE TABLE lifequest.habit_logs LIKE questboard.habit_logs;
INSERT INTO lifequest.habit_logs SELECT * FROM questboard.habit_logs;

CREATE TABLE lifequest.battle_sessions LIKE questboard.battle_sessions;
INSERT INTO lifequest.battle_sessions SELECT * FROM questboard.battle_sessions;

-- Si tienes las tablas de rewards:
CREATE TABLE IF NOT EXISTS lifequest.rewards LIKE questboard.rewards;
INSERT INTO lifequest.rewards SELECT * FROM questboard.rewards;

CREATE TABLE IF NOT EXISTS lifequest.reward_redemptions LIKE questboard.reward_redemptions;
INSERT INTO lifequest.reward_redemptions SELECT * FROM questboard.reward_redemptions;

-- Paso 4: Verificar que todo se copió correctamente
-- Ejecuta estas queries para verificar:
-- SELECT COUNT(*) FROM lifequest.users;
-- SELECT COUNT(*) FROM questboard.users;
-- (Los números deben coincidir)

-- Paso 5 (OPCIONAL): Una vez verificado, puedes eliminar la BD antigua
-- ⚠️ SOLO ejecuta esto cuando estés 100% seguro de que todo funciona
-- DROP DATABASE questboard;
-- DROP USER 'questboard'@'localhost';

-- ✅ Migración completada. Ahora tu app usará 'lifequest' como nombre de BD.
