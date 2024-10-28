<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Application;
use App\Services\Auth\PasswordService;
use App\Utils\Sanitizer;
use App\Utils\EmailType;
use App\Utils\Email;
use App\Utils\TokenType;
use App\Utils\TokenHandler;
use App\Models\MedlemRepository;
use App\Models\Medlem;
use PDO;

class UserAuthenticationService
{
    private Application $app;
    private PDO $conn;
    private TokenHandler $tokenHandler;
    private MedlemRepository $medlemRepo;
    private PasswordService $passwordSvc;

    public function __construct(PDO $conn, Application $app)
    {
        $this->app = $app;
        $this->conn = $conn;
        $this->tokenHandler = new TokenHandler($this->conn, $app);
        $this->medlemRepo = new MedlemRepository($this->conn, $app);
        $this->passwordSvc = new PasswordService();
    }

    public function registerUser(array $formData): array
    {
        $s = new Sanitizer();
        $rules = ['email' => 'email'];
        $cleanValues = $s->sanitize($formData, $rules);

        $email = $cleanValues['email'];
        $password = $formData['password'];
        $repeatPassword = $formData['verifyPassword'];

        // Check if user exists
        $result = $this->medlemRepo->getMemberByEmail($email);
        if (!$result) {
            $this->app->getLogger()->info("Register member: Failed to register new member. Email does not exist: " . $email);
            return [
                'success' => false,
                'message' => 'Det finns ingen medlem med den emailadressen. Använd den mailadress du angav när du registrerade dina medlemsuppgifter.'
            ];
        }

        $medlem = new Medlem($this->conn, $this->app->getLogger(), $result['id']);

        if ($medlem->password) {
            return [
                'success' => false,
                'message' => 'Ditt konto är redan redan registrerat. Har du glömt ditt lösenord? Prova att byta lösenord.'
            ];
        }

        if (!$this->passwordSvc->passwordsMatch($password, $repeatPassword)) {
            return [
                'success' => false,
                'message' => 'Lösenorden matchar inte!'
            ];
        }

        $passwordErrors = $this->passwordSvc->validatePassword($password, $email);
        if (!empty($passwordErrors)) {
            return [
                'success' => false,
                'message' => $this->passwordSvc->formatPasswordErrors($passwordErrors)
            ];
        }

        $hashedPassword = $this->passwordSvc->hashPassword($password);
        $token = $this->tokenHandler->generateToken();
        if (!$this->tokenHandler->saveToken($token, TokenType::ACTIVATION, $email, $hashedPassword)) {
            return [
                'success' => false,
                'message' => 'Något gick fel vid registreringen. Försök igen.'
            ];
        }

        $mailResult = $this->sendActivationEmail($medlem, $email, $token);
        if (!$mailResult) {
            return [
                'success' => false,
                'message' => 'Kunde inte skicka registreringsmail. Försök igen.'
            ];
        }
        return ['success' => true];
    }

    private function sendActivationEmail(Medlem $medlem, string $email, string $token): bool
    {
        $result = $this->sendAuthenticationEmail(
            $email,
            $token,
            $medlem->fornamn,
            EmailType::VERIFICATION,
            'register-activate'
        );
        return $result;
    }

    public function activateAccount(string $token): array
    {
        $tokenResult = $this->tokenHandler->isValidToken($token, TokenType::ACTIVATION);
        if (!$tokenResult['success']) {
            $this->app->getLogger()->warning(
                "Activate account: failed to activate account. Token given was" . $token
            );
            return $tokenResult;
        }

        $member = $this->medlemRepo->getMemberByEmail($tokenResult['email']);
        $this->saveMembersPassword($tokenResult['hashedPassword'], $member['email']);

        $this->tokenHandler->deleteToken($token);
        $this->tokenHandler->deleteExpiredTokens();

        $this->app->getLogger()->info("Activated account for member: " . $member['email']);

        return [
            'success' => true
        ];
    }

    public function requestPasswordReset(string $email): array
    {
        $member = $this->medlemRepo->getMemberByEmail($email);
        if (!$member) {
            $this->app->getLogger()->info("Reset password called for non-existing user: " . $email);
            return ['success' => false];
        }

        $token = $this->tokenHandler->generateToken();
        if (!$this->tokenHandler->saveToken($token, TokenType::RESET, $email)) {
            return ['success' => false];
        }

        $mailResult = $this->sendPasswordResetEmail($member, $email, $token);
        if (!$mailResult) {
            return [
                'success' => false,
                'message' => 'Kunde inte skicka mail för lösenordsåterställning. Försök igen.'
            ];
        }
        return ['success' => true];
    }

    private function sendPasswordResetEmail(array $member, string $email, string $token): bool
    {
        $result = $this->sendAuthenticationEmail(
            $email,
            $token,
            $member['fornamn'],
            EmailType::PASSWORD_RESET,
            'show-reset-password'
        );
        return $result;
    }

    public function validateResetToken(string $token): array
    {
        return $this->tokenHandler->isValidToken($token, TokenType::RESET);
    }

    public function resetPassword(array $formData): array
    {
        $email = $formData['email'];
        $token = $formData['token'];
        $password = $formData['password'];
        $password2 = $formData['password2'];

        if (!$this->passwordSvc->passwordsMatch($password, $password2)) {
            return [
                'success' => false,
                'message' => 'Lösenorden stämmer inte överens. Försök igen'
            ];
        }

        $passwordErrors = $this->passwordSvc->validatePassword($password, $email);
        if (!empty($passwordErrors)) {
            return [
                'success' => false,
                'message' => $this->passwordSvc->formatPasswordErrors($passwordErrors)
            ];
        }

        $member = $this->medlemRepo->getMemberByEmail($email);
        if (!$member) {
            return [
                'success' => false,
                'message' => 'OJ! Nu blev det ett tekniskt fel. Användaren finns inte.'
            ];
        }

        $hashedPassword = $this->passwordSvc->hashPassword($password);
        $this->saveMembersPassword($hashedPassword, $email);
        $this->tokenHandler->deleteToken($token);

        return ['success' => true];
    }

    private function saveMembersPassword(string $hashedPassword, string $email): bool
    {
        $stmt = $this->conn->prepare("UPDATE medlem SET password = :password WHERE email = :email");
        try {
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $this->app->getLogger()->info("Password updated for member:" . $email);
            return true;
        } catch (\PDOException $e) {
            $this->app->getLogger()->error("Error updating password for member:" . $email .
                " Error: " . $e->getMessage());
            return false;
        }
    }

    private function sendAuthenticationEmail(
        string $email,
        string $token,
        string $firstName,
        EmailType $emailType,
        string $routeName
    ): bool {
        $mailer = new Email($this->app);
        $data = [
            'token' => $token,
            'fornamn' => $firstName,
            'url' => $this->app->getConfig('SITE_ADDRESS') .
                $this->app->getRouter()->generate($routeName, ['token' => $token])
        ];

        try {
            $mailer->send($emailType, $email, data: $data);
            $this->app->getLogger()->info("Sent {$emailType->value} email to: {$email}");
            return true;
        } catch (\Exception $e) {
            $this->app->getLogger()->error("Failed to send {$emailType->value} mail to member with email: " . $email);
            $this->app->getLogger()->error($e->getMessage());
            return false;
        }
    }
}
