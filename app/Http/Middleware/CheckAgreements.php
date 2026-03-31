<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AgreementRequest;

class CheckAgreements
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'customer') {
            $hasPending = AgreementRequest::where('user_id', auth()->id())
                ->where('status', 'Awaiting signature')
                ->exists();

            // If there are pending agreements and the user is not already on the agreements page, redirect them
            if ($hasPending && !$request->is('customer/agreements*')) {
                return redirect()->route('customer.agreements.index')
                    ->with('error', 'Please sign the required documents to continue.');
            }
        }

        return $next($request);
    }
}
