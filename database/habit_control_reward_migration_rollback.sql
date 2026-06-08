-- Rollback rebalance de recompensas para habitos en control

UPDATE habits
SET xp_reward = 0,
    points_reward = 0
WHERE is_negative = 1;