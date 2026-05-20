<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/LifeArea.php';

final class LifeAreaController
{
    private LifeArea $lifeAreaModel;

    public function __construct()
    {
        $this->lifeAreaModel = new LifeArea();
    }

    public function index(int $userId): array
    {
        return $this->lifeAreaModel->getAllByUser($userId);
    }

    public function store(int $userId, array $data): array
    {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $icon = trim($data['icon'] ?? '');
        $color = trim($data['color'] ?? '');

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'El nombre del área es obligatorio.'
            ];
        }

        if (mb_strlen($name) > 100) {
            return [
                'success' => false,
                'message' => 'El nombre no puede superar los 100 caracteres.'
            ];
        }

        $created = $this->lifeAreaModel->create(
            $userId,
            $name,
            $description !== '' ? $description : null,
            $icon !== '' ? $icon : null,
            $color !== '' ? $color : '#16A34A'
        );

        return [
            'success' => $created,
            'message' => $created ? 'Área creada correctamente.' : 'No se pudo crear el área.'
        ];
    }

    public function update(int $userId, array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $icon = trim($data['icon'] ?? '');
        $color = trim($data['color'] ?? '');

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Área no válida.'
            ];
        }

        if (!$this->lifeAreaModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'El área no existe o no pertenece a tu usuario.'
            ];
        }

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'El nombre del área es obligatorio.'
            ];
        }

        $updated = $this->lifeAreaModel->update(
            $id,
            $userId,
            $name,
            $description !== '' ? $description : null,
            $icon !== '' ? $icon : null,
            $color !== '' ? $color : '#16A34A'
        );

        return [
            'success' => $updated,
            'message' => $updated ? 'Área actualizada correctamente.' : 'No se pudo actualizar el área.'
        ];
    }

    public function destroy(int $userId, int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Área no válida.'
            ];
        }

        if (!$this->lifeAreaModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'El área no existe o no pertenece a tu usuario.'
            ];
        }

        $deleted = $this->lifeAreaModel->delete($id, $userId);

        return [
            'success' => $deleted,
            'message' => $deleted ? 'Área eliminada correctamente.' : 'No se pudo eliminar el área.'
        ];
    }
}
