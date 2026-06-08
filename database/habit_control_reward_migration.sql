-- Rebalance de recompensas para habitos en control
-- Normaliza registros historicos para que den 5 XP y 3 puntos.

UPDATE habits
SET xp_reward = 5,
    points_reward = 3
WHERE is_negative = 1;