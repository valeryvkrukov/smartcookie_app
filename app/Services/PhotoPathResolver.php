<?php

namespace App\Services;

class PhotoPathResolver
{
    public static function resolve(?string $photo): ?string
    {
        if (!$photo) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        $photoPath = ltrim($photo, '/');

        if (str_starts_with($photoPath, 'storage/')) {
            return asset($photoPath);
        }

        return asset('storage/'.$photoPath);
    }
}
