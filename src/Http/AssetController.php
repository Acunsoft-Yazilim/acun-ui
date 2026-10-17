<?php

namespace Acun\Ui\Http;

use Acun\Ui\Support\Assets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GET /acun-ui/acun-ui-boot.js and /acun-ui/acun-ui.js: the runtime's browser builds,
 * served from the package like Livewire serves its own JS. The tags carry
 * ?id=<content hash>, so the files can be cached for a year; a changed build gets a new URL.
 */
final class AssetController
{
    public function __invoke(Request $request, string $file): BinaryFileResponse
    {
        $path = Assets::distPath($file);

        abort_unless(in_array($file, Assets::FILES, true) && is_file($path), 404);

        $response = new BinaryFileResponse($path, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setMaxAge(31536000);
        $response->setExpires(now()->addYear());
        $response->setEtag(Assets::hash($path));
        $response->isNotModified($request);

        return $response;
    }
}
