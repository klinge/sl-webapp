<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Application;
use andkab\Turnstile\Turnstile;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;

abstract class AuthBaseController extends BaseController
{
    protected string $turnstileSecret;
    protected string $remoteIp;
    protected Turnstile $turnstile;
    protected Logger $logger;

    //Messages
    protected const RECAPTCHA_ERROR_MESSAGE = 'Kunde inte validera recaptcha. Försök igen.';

    public function __construct(Application $app, ServerRequestInterface $request, Logger $logger)
    {
        parent::__construct($app, $request, $logger);
        $this->logger = $logger;
        $this->turnstileSecret = $this->app->getConfig('TURNSTILE_SECRET_KEY');
        $this->remoteIp = $this->request->getServerParams()['REMOTE_ADDR'];
        $this->turnstile = new Turnstile($this->turnstileSecret);
    }

    /**
     * Validates the reCAPTCHA response.
     *
     * Verifies the Google reCAPTCHA response against the expected hostname
     * and score threshold. Logs the verification result.
     *
     * @return bool True if reCAPTCHA verification succeeds, false otherwise
     */
    protected function validateRecaptcha(): bool
    {
        $verifyResponse = $this->turnstile->verify(
            $this->request->getParsedBody()['cf-turnstile-response'],
            $this->remoteIp
        );
        return $verifyResponse->isSuccess();
    }
}
