<?php

namespace App\Http\Controllers\Diag;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SessionDiagController extends Controller
{
    public function show(Request $request)
    {
        if (app()->environment('production')) {
            $key = (string) $request->query('key', '');
            $expected = (string) env('DIAG_TOKEN', '');
            if ($expected === '' || !hash_equals($expected, $key)) {
                abort(403);
            }
        }

        $data = [
            'app' => [
                'env' => app()->environment(),
                'url' => config('app.url'),
            ],
            'request' => [
                'host' => $request->getHost(),
                'is_secure' => $request->isSecure(),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
            ],
            'headers' => [
                'x_forwarded_proto' => (string) $request->header('X-Forwarded-Proto'),
                'x_forwarded_host'  => (string) $request->header('X-Forwarded-Host'),
                'x_forwarded_port'  => (string) $request->header('X-Forwarded-Port'),
            ],
            'session_config' => [
                'driver'     => config('session.driver'),
                'connection' => config('session.connection'),
                'store'      => config('session.store'),
                'lifetime'   => config('session.lifetime'),
                'domain'     => config('session.domain'),
                'path'       => config('session.path'),
                'secure'     => config('session.secure'),
                'same_site'  => config('session.same_site'),
                'http_only'  => config('session.http_only'),
                'cookie'     => config('session.cookie'),
            ],
            'cookies_seen' => array_keys($request->cookies->all()),
            'csrf' => [
                'token'         => csrf_token(),
                'session_token' => $request->session()->token(),
            ],
        ];

        return response()->json($data);
    }
}

