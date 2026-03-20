<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function public(Request $request, string $path): StreamedResponse
    {
        $normalizedPath = ltrim($path, '/');

        abort_if(
            $normalizedPath === '' || str_contains($normalizedPath, '..'),
            404
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($normalizedPath), 404);

        return $disk->response($normalizedPath);
    }
}
