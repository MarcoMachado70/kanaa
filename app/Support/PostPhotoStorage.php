<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PostPhotoStorage
{
    public function store(string $source): string
    {
        $original = imagecreatefromstring(file_get_contents($source));
        if (! $original) {
            throw new RuntimeException('Não foi possível processar a foto.');
        }
        $width = imagesx($original);
        $height = imagesy($original);
        $scale = min(1, 1600 / max($width, $height));
        $image = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        try {
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            imagecopyresampled($image, $original, 0, 0, 0, 0, imagesx($image), imagesy($image), $width, $height);
            ob_start();
            imagejpeg($image, null, 85);
            $bytes = ob_get_clean();
            $path = 'post-photos/'.Str::uuid().'.jpg';
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new RuntimeException('Não foi possível salvar a foto.');
            }

            return $path;
        } finally {
            imagedestroy($original);
            imagedestroy($image);
        }
    }
}
