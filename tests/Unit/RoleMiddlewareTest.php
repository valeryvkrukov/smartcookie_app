<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_when_user_is_not_authenticated(): void
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/admin/users', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next, 'admin');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', $response->headers->get('Location'));
    }

    public function test_redirects_when_user_role_is_not_allowed(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/admin/users', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next, 'admin');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', $response->headers->get('Location'));
    }

    public function test_allows_request_for_authorized_role(): void
    {
        $user = User::factory()->tutor()->create();

        $this->actingAs($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/tutor/calendar', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next, 'tutor');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
