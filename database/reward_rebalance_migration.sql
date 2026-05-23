-- Rebalance opcional de recompensas existentes
-- Alinea datos historicos con la nueva logica de RewardCalculator.

-- HABITS
UPDATE habits
SET xp_reward = CASE
        WHEN is_negative = 1 THEN 0
        WHEN frequency = 'weekly' THEN 14
        WHEN frequency = 'custom' THEN 12
        ELSE 10
    END,
    points_reward = CASE
        WHEN is_negative = 1 THEN 0
        WHEN frequency = 'weekly' THEN 7
        WHEN frequency = 'custom' THEN 6
        ELSE 5
    END;

-- TASKS
UPDATE tasks
SET xp_reward = LEAST(80, GREATEST(6,
        ROUND(12
            * (CASE priority
                WHEN 'low' THEN 0.8
                WHEN 'high' THEN 1.3
                WHEN 'critical' THEN 1.6
                ELSE 1.0
            END)
            * (CASE
                WHEN estimated_minutes <= 0 THEN 0.9
                WHEN estimated_minutes <= 15 THEN 0.95
                WHEN estimated_minutes <= 30 THEN 1.0
                WHEN estimated_minutes <= 60 THEN 1.2
                WHEN estimated_minutes <= 120 THEN 1.45
                ELSE 1.75
            END)
        )
    )),
    points_reward = LEAST(40, GREATEST(3, ROUND(
        LEAST(80, GREATEST(6,
            ROUND(12
                * (CASE priority
                    WHEN 'low' THEN 0.8
                    WHEN 'high' THEN 1.3
                    WHEN 'critical' THEN 1.6
                    ELSE 1.0
                END)
                * (CASE
                    WHEN estimated_minutes <= 0 THEN 0.9
                    WHEN estimated_minutes <= 15 THEN 0.95
                    WHEN estimated_minutes <= 30 THEN 1.0
                    WHEN estimated_minutes <= 60 THEN 1.2
                    WHEN estimated_minutes <= 120 THEN 1.45
                    ELSE 1.75
                END)
            )
        )) * 0.5
    )));

-- GOALS
UPDATE goals
SET xp_reward = LEAST(220, GREATEST(10,
        ROUND(
            (CASE type
                WHEN 'daily' THEN 16
                WHEN 'weekly' THEN 30
                WHEN 'monthly' THEN 50
                WHEN 'quarterly' THEN 70
                WHEN 'yearly' THEN 95
                WHEN 'future' THEN 110
                ELSE 50
            END)
            * (CASE priority
                WHEN 'low' THEN 0.8
                WHEN 'high' THEN 1.3
                WHEN 'critical' THEN 1.6
                ELSE 1.0
            END)
        )
    )),
    points_reward = LEAST(110, GREATEST(5, ROUND(
        LEAST(220, GREATEST(10,
            ROUND(
                (CASE type
                    WHEN 'daily' THEN 16
                    WHEN 'weekly' THEN 30
                    WHEN 'monthly' THEN 50
                    WHEN 'quarterly' THEN 70
                    WHEN 'yearly' THEN 95
                    WHEN 'future' THEN 110
                    ELSE 50
                END)
                * (CASE priority
                    WHEN 'low' THEN 0.8
                    WHEN 'high' THEN 1.3
                    WHEN 'critical' THEN 1.6
                    ELSE 1.0
                END)
            )
        )) * 0.5
    )));
