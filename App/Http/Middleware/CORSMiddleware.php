<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class CORSMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response)
    {
        $origin = $request->header('ORIGIN');
        if ($origin === null) return;

        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE');
        $response->setHeader('Access-Control-Max-Age', '3600');
        $response->setHeader('Access-Control-Allow-Headers', 'X-Chunk-Num');
        $response->setHeader('Access-Control-Expose-Headers', 'Content-Disposition');

        if ($request->method == 'OPTIONS')
            $response->send();
    }
}
