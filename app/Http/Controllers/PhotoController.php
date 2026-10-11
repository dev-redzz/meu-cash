<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PhotoController extends Controller
{
    public function __invoke(string $path): Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if(str_contains($path, '..') || ! str_starts_with($path, 'products/'), 404);

        $parts = explode('/', $path);
        if (count($parts) === 3 && ctype_digit($parts[1])) {
            abort_unless((int) $parts[1] === tenant_id(), 404);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
