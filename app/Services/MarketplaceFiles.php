<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketplaceFiles
{
    public function store(string $dataUrl, string $folder, bool $private = false): string
    {
        if (strlen($dataUrl) > 7_000_000 || ! preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=\r\n]+)$#D', $dataUrl, $matches)) {
            throw ValidationException::withMessages(['photos' => 'Upload a JPEG, PNG or WebP image up to 5 MB.']);
        }
        $bytes = base64_decode($matches[2], true);
        $image = $bytes === false ? false : @getimagesizefromstring($bytes);
        if (! $image || strlen($bytes) > 5 * 1024 * 1024 || ! in_array($image['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) || $image[0] * $image[1] > 40_000_000) {
            throw ValidationException::withMessages(['photos' => 'Invalid image or image exceeds the 5 MB / 40 megapixel limit.']);
        }
        // Re-encode uploads: discard metadata and embedded non-image content.
        $decoded = @imagecreatefromstring($bytes);
        if (! $decoded) {
            throw ValidationException::withMessages(['photos' => 'Image could not be decoded.']);
        }
        ob_start();
        imagejpeg($decoded, null, 88);
        $safe = ob_get_clean();
        imagedestroy($decoded);
        $path = $folder.'/'.Str::uuid().'.jpg';
        if (! Storage::disk($private ? 'local' : 'public')->put($path, $safe)) {
            throw new \RuntimeException('Image storage failed.');
        }

        return $path;
    }

    public function url(?string $path): string
    {
        return $path ? Storage::disk('public')->url($path) : '';
    }

    /** Validate ownership of retained URLs; never accept arbitrary storage paths.
     * @param  list<string>  $photos
     * @param  list<string>  $existing
     * @return array{list<string>, list<string>}
     */
    public function prepare(array $photos, string $folder, array $existing = [], bool $private = false): array
    {
        $paths = [];
        $created = [];
        try {
            foreach ($photos as $photo) {
                $match = array_search($photo, array_map(fn ($path) => $this->url($path), $existing), true);
                if ($match !== false && ! $private) {
                    $paths[] = $existing[$match];
                } else {
                    $path = $this->store($photo, $folder, $private);
                    $paths[] = $path;
                    $created[] = $path;
                }
            }
        } catch (\Throwable $e) {
            Storage::disk($private ? 'local' : 'public')->delete($created);
            throw $e;
        }

        return [$paths, $created];
    }
}
