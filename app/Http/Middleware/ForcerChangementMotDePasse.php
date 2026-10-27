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
            'employe.mot-de-passe.edit', 'employe.mot-de-passe.update',
        ], true)) {
            $route = $request->user()->employe !== null
                ? 'employe.mot-de-passe.edit'
                : 'client.mot-de-passe.edit';

            return redirect()->route($route);
        }

        return $next($request);
    }
}
