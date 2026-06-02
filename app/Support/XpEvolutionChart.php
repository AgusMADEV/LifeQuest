<?php

declare(strict_types=1);

final class XpEvolutionChart
{
    public static function build(
        array $tasks,
        array $habits,
        array $habitLogs,
        array $dailyObjectives,
        DateTimeImmutable $periodStartDate,
        DateTimeImmutable $periodEndDate,
        string $metricPeriod,
        string $todayDateKey,
        int $lineChartWidth,
        int $lineChartHeight,
        int $axisStep
    ): array {
        $xpByDate = [];
        for ($cursor = $periodStartDate; $cursor <= $periodEndDate; $cursor = $cursor->modify('+1 day')) {
            $xpByDate[$cursor->format('Y-m-d')] = 0;
        }

        foreach ($tasks as $task) {
            if ((string) ($task['status'] ?? '') !== 'completed') {
                continue;
            }

            $completedAtRaw = (string) ($task['completed_at'] ?? '');
            if ($completedAtRaw === '') {
                continue;
            }

            try {
                $completedAt = new DateTimeImmutable($completedAtRaw);
            } catch (Throwable $exception) {
                continue;
            }

            if ($completedAt < $periodStartDate || $completedAt > $periodEndDate) {
                continue;
            }

            $dateKey = $completedAt->format('Y-m-d');
            if (isset($xpByDate[$dateKey])) {
                $xpByDate[$dateKey] += (int) ($task['xp_reward'] ?? 0);
            }
        }

        $habitMap = [];
        foreach ($habits as $habit) {
            $habitMap[(int) ($habit['id'] ?? 0)] = $habit;
        }

        foreach ($habitLogs as $habitId => $dateMap) {
            $habit = $habitMap[(int) $habitId] ?? null;
            if (!$habit) {
                continue;
            }

            $habitXp = (int) ($habit['xp_reward'] ?? 0);
            foreach ($dateMap as $date => $done) {
                if ($done && isset($xpByDate[$date])) {
                    $xpByDate[$date] += $habitXp;
                }
            }
        }

        foreach ($dailyObjectives as $dailyObjective) {
            $objectiveDateRaw = (string) ($dailyObjective['objective_date'] ?? '');
            if ($objectiveDateRaw === '') {
                continue;
            }

            try {
                $objectiveDate = new DateTimeImmutable($objectiveDateRaw);
            } catch (Throwable $exception) {
                continue;
            }

            if ($objectiveDate < $periodStartDate || $objectiveDate > $periodEndDate) {
                continue;
            }

            $dateKey = $objectiveDate->format('Y-m-d');
            if (isset($xpByDate[$dateKey])) {
                $xpByDate[$dateKey] += (int) ($dailyObjective['xp_bonus_awarded'] ?? 0);
            }
        }

        $xpLinePoints = [];
        $cumulativeXp = 0;
        $periodXpGain = 0;

        foreach ($xpByDate as $date => $gain) {
            $dailyGain = (int) $gain;
            $periodXpGain += $dailyGain;
            $cumulativeXp += $dailyGain;

            $dateObj = new DateTimeImmutable($date);
            $xpLinePoints[] = [
                'label' => $metricPeriod === 'week'
                    ? ['L', 'M', 'X', 'J', 'V', 'S', 'D'][(int) $dateObj->format('N') - 1]
                    : $dateObj->format('j'),
                'value' => $cumulativeXp,
                'gain' => $dailyGain,
                'is_future' => $date > $todayDateKey,
            ];
        }

        $maxLineValue = max(array_map(static fn(array $point): int => (int) $point['value'], $xpLinePoints));
        $axisMax = max($axisStep * 2, (int) (ceil($maxLineValue / $axisStep) * $axisStep));

        $linePadX = 28;
        $linePadTop = 20;
        $linePadBottom = 38;
        $linePlotWidth = $lineChartWidth - ($linePadX * 2);
        $linePlotHeight = $lineChartHeight - $linePadTop - $linePadBottom;
        $lineCount = max(1, count($xpLinePoints) - 1);
        $lineCoords = [];

        foreach ($xpLinePoints as $index => $point) {
            $x = $linePadX + (int) round(($linePlotWidth / $lineCount) * $index);
            $y = $linePadTop + (int) round((1 - ((int) $point['value'] / max(1, $axisMax))) * $linePlotHeight);
            $lineCoords[] = [
                'x' => $x,
                'y' => $y,
                'label' => $point['label'],
                'value' => (int) $point['value'],
                'gain' => (int) ($point['gain'] ?? 0),
                'is_future' => (bool) ($point['is_future'] ?? false),
            ];
        }

        $axisTicks = [];
        for ($axisValue = 0; $axisValue <= $axisMax; $axisValue += $axisStep) {
            $axisY = $linePadTop + (int) round((1 - ($axisValue / max(1, $axisMax))) * $linePlotHeight);
            $axisTicks[] = [
                'value' => $axisValue,
                'y' => $axisY,
                'label' => self::formatAxisXp($axisValue),
            ];
        }

        $linePolyline = '';
        $futureLinePolyline = '';
        $lineAreaPath = '';
        $futureAreaPath = '';
        $futureAreaStartX = 0;
        $futureAreaEndX = 0;
        $firstFutureIndex = null;

        foreach ($lineCoords as $index => $point) {
            if (!empty($point['is_future'])) {
                $firstFutureIndex = $index;
                break;
            }
        }

        $realLineCoords = $lineCoords;
        $futureLineCoords = [];

        if ($firstFutureIndex !== null) {
            $realLineCoords = array_slice($lineCoords, 0, $firstFutureIndex);
            $futureStart = max(0, $firstFutureIndex - 1);
            $futureLineCoords = array_slice($lineCoords, $futureStart);
        }

        if (!empty($realLineCoords)) {
            $linePolyline = implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $realLineCoords));
        }

        if (!empty($lineCoords)) {
            $firstPoint = $lineCoords[0];
            $lastPoint = $lineCoords[count($lineCoords) - 1];
            $lineAreaPath = 'M' . $firstPoint['x'] . ' ' . ($lineChartHeight - $linePadBottom)
                . ' L' . $firstPoint['x'] . ' ' . $firstPoint['y']
                . ' L' . implode(' L', array_map(static fn(array $p): string => $p['x'] . ' ' . $p['y'], $lineCoords))
                . ' L' . $lastPoint['x'] . ' ' . ($lineChartHeight - $linePadBottom)
                . ' Z';
        }

        if (!empty($futureLineCoords)) {
            $futureLinePolyline = implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $futureLineCoords));

            $futureFirstPoint = $futureLineCoords[0];
            $futureLastPoint = $futureLineCoords[count($futureLineCoords) - 1];
            $futureAreaStartX = (int) $futureFirstPoint['x'];
            $futureAreaEndX = (int) $futureLastPoint['x'];
            $futureAreaPath = 'M' . $futureFirstPoint['x'] . ' ' . ($lineChartHeight - $linePadBottom)
                . ' L' . $futureFirstPoint['x'] . ' ' . $futureFirstPoint['y']
                . ' L' . implode(' L', array_map(static fn(array $p): string => $p['x'] . ' ' . $p['y'], $futureLineCoords))
                . ' L' . $futureLastPoint['x'] . ' ' . ($lineChartHeight - $linePadBottom)
                . ' Z';
        }

        return [
            'xpLinePoints' => $xpLinePoints,
            'periodXpGain' => $periodXpGain,
            'linePadX' => $linePadX,
            'linePadTop' => $linePadTop,
            'linePadBottom' => $linePadBottom,
            'axisTicks' => $axisTicks,
            'lineCoords' => $lineCoords,
            'linePolyline' => $linePolyline,
            'futureLinePolyline' => $futureLinePolyline,
            'lineAreaPath' => $lineAreaPath,
            'futureAreaPath' => $futureAreaPath,
            'futureAreaStartX' => $futureAreaStartX,
            'futureAreaEndX' => $futureAreaEndX,
            'chartTotalXp' => (int) (($xpLinePoints[count($xpLinePoints) - 1]['value'] ?? 0)),
        ];
    }

    private static function formatAxisXp(int $value): string
    {
        if ($value >= 1000) {
            $compact = $value / 1000;
            $formatted = fmod($compact, 1.0) === 0.0
                ? (string) (int) $compact
                : rtrim(rtrim(number_format($compact, 1, '.', ''), '0'), '.');

            return $formatted . 'K';
        }

        return number_format($value, 0, ',', '.');
    }
}
