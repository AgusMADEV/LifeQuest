<?php

declare(strict_types=1);

final class RewardCalculator
{
    public static function forHabit(string $frequency, bool $isNegative): array
    {
        if ($isNegative) {
            return ['xp' => 0, 'points' => 0];
        }

        $frequencyMultiplier = [
            'daily' => 1.0,
            'weekly' => 1.35,
            'custom' => 1.15,
        ][$frequency] ?? 1.0;

        $xp = (int) round(self::constInt('REWARD_HABIT_BASE_XP', 10) * $frequencyMultiplier);
        $points = (int) round($xp * self::constFloat('REWARD_POINTS_PER_XP', 0.5));

        return [
            'xp' => self::clamp($xp, 5, 40),
            'points' => self::clamp($points, 2, 20),
        ];
    }

    public static function forTask(string $priority, int $estimatedMinutes): array
    {
        $priorityMultiplier = self::priorityMultiplier($priority);
        $effortMultiplier = self::effortMultiplierByMinutes($estimatedMinutes);

        $xp = (int) round(self::constInt('REWARD_TASK_BASE_XP', 12) * $priorityMultiplier * $effortMultiplier);
        $points = (int) round($xp * self::constFloat('REWARD_POINTS_PER_XP', 0.5));

        return [
            'xp' => self::clamp($xp, 6, 80),
            'points' => self::clamp($points, 3, 40),
        ];
    }

    public static function forGoal(string $type, string $priority): array
    {
        $baseByType = [
            'daily' => self::constInt('REWARD_GOAL_BASE_XP_DAILY', 16),
            'weekly' => self::constInt('REWARD_GOAL_BASE_XP_WEEKLY', 30),
            'monthly' => self::constInt('REWARD_GOAL_BASE_XP_MONTHLY', 50),
            'quarterly' => self::constInt('REWARD_GOAL_BASE_XP_QUARTERLY', 70),
            'yearly' => self::constInt('REWARD_GOAL_BASE_XP_YEARLY', 95),
            'future' => self::constInt('REWARD_GOAL_BASE_XP_FUTURE', 110),
        ];

        $base = $baseByType[$type] ?? 50;
        $xp = (int) round($base * self::priorityMultiplier($priority));
        $points = (int) round($xp * self::constFloat('REWARD_POINTS_PER_XP', 0.5));

        return [
            'xp' => self::clamp($xp, 10, 220),
            'points' => self::clamp($points, 5, 110),
        ];
    }

    private static function priorityMultiplier(string $priority): float
    {
        return [
            'low' => 0.8,
            'medium' => 1.0,
            'high' => 1.3,
            'critical' => 1.6,
        ][$priority] ?? 1.0;
    }

    private static function effortMultiplierByMinutes(int $minutes): float
    {
        if ($minutes <= 0) {
            return 0.9;
        }

        if ($minutes <= 15) {
            return 0.95;
        }

        if ($minutes <= 30) {
            return 1.0;
        }

        if ($minutes <= 60) {
            return 1.2;
        }

        if ($minutes <= 120) {
            return 1.45;
        }

        return 1.75;
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private static function constInt(string $name, int $fallback): int
    {
        return defined($name) ? (int) constant($name) : $fallback;
    }

    private static function constFloat(string $name, float $fallback): float
    {
        return defined($name) ? (float) constant($name) : $fallback;
    }
}
