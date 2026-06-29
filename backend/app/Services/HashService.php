<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class HashService
{
    public function hashSha256(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if (!$path || !is_file($path)) {
            throw new \RuntimeException('Fichier temporaire introuvable pour le calcul du hash.');
        }

        // S'assurer que le chemin résolu est dans le répertoire temporaire système
        $tmpDir = realpath(sys_get_temp_dir());
        $realPath = realpath($path);
        if ($realPath === false || !str_starts_with($realPath, $tmpDir . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Chemin de fichier invalide pour le calcul du hash.');
        }

        // Calcul en stream pour limiter l’usage mémoire
        $ctx = hash_init('sha256');
        $handle = fopen($realPath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Impossible d’ouvrir le fichier pour calculer le hash.');
        }

        while (!feof($handle)) {
            $chunk = fread($handle, 1024 * 1024); // 1MB
            if ($chunk === false) {
                break;
            }
            hash_update($ctx, $chunk);
        }
        fclose($handle);

        return hash_final($ctx);
    }
}

