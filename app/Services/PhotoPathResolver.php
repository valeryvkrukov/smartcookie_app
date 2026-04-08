<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

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

        // Strip leading "storage/" to get the path relative to the public disk
        $diskPath = str_starts_with($photoPath, 'storage/')
            ? substr($photoPath, strlen('storage/'))
            : $photoPath;

        // Return null if the file doesn't exist — let the caller use a fallback
        if (!Storage::disk('public')->exists($diskPath)) {
            return null;
        }

        return asset('storage/' . $diskPath);
    }
}
