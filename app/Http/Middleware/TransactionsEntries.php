<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class TransactionsEntries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('transactions_entry')
        ->info([
            'Request: ' . $request->url(),
            'Method: ' . $request->method(),
            'Body: ' . json_encode($request->all()),
            'User: ' . ($request->user() ? json_encode($request->user()) : 'No user'),
        ]);

        return $next($request);
    }
}