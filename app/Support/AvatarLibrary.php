<?php

declare(strict_types=1);

final class AvatarLibrary
{
    private const AVATAR_DIRECTORY = __DIR__ . '/../../referencias/avatares';
    private const AVATAR_PUBLIC_PATH = '../referencias/avatares';
    private const PREFERRED_DEFAULT = 'jacob.png';

    public static function getOptions(): array
    {
        $files = self::getAvailableFiles();

        return array_map(static function (string $file): array {
            return [
                'file' => $file,
                'label' => self::labelFromFilename($file),
                'src' => self::buildPublicSrc($file),
            ];
        }, $files);
    }

    public static function getAvatarSrc(?string $avatarFile, ?string $fallbackFile = null): ?string
    {
        $resolvedFile = self::normalizeAvatar($avatarFile)
            ?? self::normalizeAvatar($fallbackFile)
            ?? self::getDefaultAvatarFile();

        return $resolvedFile !== null ? self::buildPublicSrc($resolvedFile) : null;
    }

    public static function normalizeAvatar(?string $avatarFile): ?string
    {
        $cleanFile = self::cleanAvatarFilename($avatarFile);

        if ($cleanFile === null) {
            return null;
        }

        return in_array($cleanFile, self::getAvailableFiles(), true) ? $cleanFile : null;
    }

    public static function getDefaultAvatarFile(): ?string
    {
        $availableFiles = self::getAvailableFiles();

        if (in_array(self::PREFERRED_DEFAULT, $availableFiles, true)) {
            return self::PREFERRED_DEFAULT;
        }

        return $availableFiles[0] ?? null;
    }

    public static function getAvailableFiles(): array
    {
        if (!is_dir(self::AVATAR_DIRECTORY)) {
            return [];
        }

        $entries = scandir(self::AVATAR_DIRECTORY) ?: [];
        $files = [];

        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }

            if (!self::isImageFile($entry)) {
                continue;
            }

            $files[] = $entry;
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    private static function cleanAvatarFilename(?string $avatarFile): ?string
    {
        $avatarFile = trim((string) $avatarFile);

        if ($avatarFile === '') {
            return null;
        }

        $avatarFile = basename(str_replace('\\', '/', $avatarFile));

        if ($avatarFile === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $avatarFile) !== 1) {
            return null;
        }

        return $avatarFile;
    }

    private static function isImageFile(string $filename): bool
    {
        return preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename) === 1;
    }

    private static function labelFromFilename(string $filename): string
    {
        $label = pathinfo($filename, PATHINFO_FILENAME);
        $label = str_replace(['-', '_'], ' ', $label);

        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }

    private static function buildPublicSrc(string $filename): string
    {
        return self::AVATAR_PUBLIC_PATH . '/' . rawurlencode($filename);
    }
}