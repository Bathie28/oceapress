<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $utilisateur = $request->user();

        if ($utilisateur === null) {
            return redirect()->route('login');
        }

        $relation = match ($role) {
            'client' => 'client',
            'employe' => 'employe',
            'administrateur' => 'administrateur',
            default => null,
        };

        abort_if($relation === null || $utilisateur->{$relation} === null, 403);

        return $next($request);
    }
}
