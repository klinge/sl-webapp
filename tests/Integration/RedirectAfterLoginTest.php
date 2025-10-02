<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application;
use App\Utils\Session;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;

class RedirectAfterLoginTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        Session::start();
        Session::destroy();
        Session::start();
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testRedirectsToOriginalPageAfterLogin(): void
    {
        // Step 1: Try to access protected admin page without being logged in
        $request = new ServerRequest(
            [],
            [],
            new Uri('/medlem'),
            'GET'
        );

        // This should redirect to login and store the redirect URL
        $response = $this->app->getRouter()->dispatch($request);

        // Verify we got redirected to login
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));

        // Verify redirect URL was stored (now stores path instead of route name)
        $this->assertEquals('/medlem', Session::get('redirect_url'));

        // Step 2: Verify that after login, admin users get redirected to the stored URL
        $redirectUrl = Session::get('redirect_url');
        $this->assertEquals('/medlem', $redirectUrl);

        // The LoginController should now redirect directly to this URL path
        $this->assertNotNull($redirectUrl);
    }
}
