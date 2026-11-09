<?php

namespace Acun\Ui\Http;

use Acun\Ui\Support\Assets;
use Acun\Ui\Support\FontLibrary;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GET /acun-ui/fonts/{family}/{file}.woff2: a file of the font library (Acun\Ui\Support\FontLibrary), served from
 * the package. The tags carry ?id=<content hash>, so it is cached for a year. Open to every origin (a font is fetched
 * in CORS mode, also when a CDN or another host of the application links it).
 */
final class FontController
{
    public function __invoke(Request $request, string $font, string $file): BinaryFileResponse
    {
        $path = FontLibrary::path($font.'/'.$file);

        abort_unless(FontLibrary::has($font) && str_starts_with($file, $font.'-') && is_file($path), 404);

        $response = new BinaryFileResponse($path, 200, [
            'Content-Type' => 'font/woff2',
            'Access-Control-Allow-Origin' => '*',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setExpires(now()->addYear());
        $response->setEtag(Assets::hash($path));
        $response->isNotModified($request);

        return $response;
    }
}
