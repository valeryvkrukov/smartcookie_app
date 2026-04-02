<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckAgreements;
use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckAgreementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_customer_with_pending_agreement(): void
    {
        $customer = User::factory()->customer()->create();
        $agreement = Agreement::create(['name' => 'Terms', 'pdf_path' => 'agreements/terms.pdf']);

        AgreementRequest::create([
            'agreement_id' => $agreement->id,
            'user_id' => $customer->id,
            'status' => 'Awaiting signature',
        ]);

        $this->actingAs($customer);

        $middleware = new CheckAgreements();
        $request = Request::create('/customer/credits', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/customer/agreements', $response->headers->get('Location'));
    }

    public function test_allows_customer_when_no_pending_agreements(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer);

        $middleware = new CheckAgreements();
        $request = Request::create('/customer/agreements', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_allows_non_customer_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $middleware = new CheckAgreements();
        $request = Request::create('/customer/credits', 'GET');
        $next = fn () => new Response('ok');

        $response = $middleware->handle($request, $next);

        $this->assertSame(200, $response->getStatusCode());
    }
}
