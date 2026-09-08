<?php

namespace RcnSistemas\Services\Communications;

use InvalidArgumentException;

final class UploadService
{
    public function validate(array $file, array $rules): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo subir el archivo.');
        }
        if ((int) ($file['size'] ?? 0) > (int) ($rules['max_size'] ?? 0)) {
            throw new InvalidArgumentException('El archivo supera el tamaño permitido.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $rules['extensions'] ?? [], true)) {
            throw new InvalidArgumentException('Tipo de archivo no permitido.');
        }
    }

    public function safeName(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    }

    public function store(array $file, string $directory, array $rules = []): string
    {
        $this->validate($file, $rules ?: ['extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'max_size' => 5 * 1024 * 1024]);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new InvalidArgumentException('No se pudo preparar el directorio de archivos.');
        $name = $this->safeName((string) ($file['name'] ?? ''));
        if (!move_uploaded_file((string) $file['tmp_name'], rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name)) throw new InvalidArgumentException('No se pudo guardar el archivo.');
        return $name;
    }
}
