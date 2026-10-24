<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcerChangementMotDePasse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->doitChangerMotDePasse && ! in_array($request->route()?->getName(), [
            'client.mot-de-passe.edit', 'client.mot-de-passe.update',
        ], true)) {
            return redirect()->route('client.mot-de-passe.edit');
        }

        return $next($request);
    }
}
