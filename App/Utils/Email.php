<?php

declare(strict_types=1);

namespace App\Utils;

use App\Application;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Monolog\Logger;

class Email
{
    private PHPMailer $mailer;
    private Application $app;
    private Logger $logger;

    /**
     * Initialize Email service with application and logger dependencies.
     *
     * @param Application $app Application instance for configuration access
     * @param Logger $logger Logger instance for email sending logs
     */
    public function __construct(Application $app, Logger $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with SMTP settings and content options.
     */
    private function configure(): void
    {
        //SMTP settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->app->getConfig("SMTP_HOST");
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->app->getConfig("SMTP_USERNAME");
        $this->mailer->Password = $this->app->getConfig("SMTP_PASSWORD");
        $this->mailer->Port = (int) $this->app->getConfig("SMTP_PORT");
        $this->mailer->Timeout = 20;
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
        //General content settings
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isHTML(true);
        $this->mailer->ContentType = 'text/html; charset=UTF-8';
    }

    /**
     * Send an email of specified type to recipient.
     *
     * @param EmailType $type The type of email to send
     * @param string $to Recipient email address
     * @param string|null $subject Optional custom subject line
     * @param array<string, string> $data Template variables for email content
     * @return bool True if email was sent successfully
     * @throws Exception If email sending fails
     * @throws \InvalidArgumentException If unsupported email type provided
     */
    public function send(EmailType $type, string $to, ?string $subject = null, array $data = []): bool
    {
        //Load correct email template
        switch ($type) {
            case EmailType::VERIFICATION:
                // Load template
                $templateSubject = ($subject) ? $subject : 'Aktivera ditt konto';
                $template = $this->loadTemplate(EmailType::VERIFICATION, $data);
                break;
            case EmailType::VERIFICATION_SUCCESS:
                $templateSubject = ($subject) ? $subject : 'Ditt konto är nu aktiverat';
                $template = $this->loadTemplate(EmailType::VERIFICATION_SUCCESS, $data);
                break;
            case EmailType::PASSWORD_RESET:
                $templateSubject = ($subject) ? $subject : 'Återställ ditt lösenord';
                $template = $this->loadTemplate(EmailType::PASSWORD_RESET, $data);
                break;
            case EmailType::PASSWORD_RESET_SUCCESS:
                $templateSubject = ($subject) ? $subject : 'Ditt lösenord är nu återställt';
                $template = $this->loadTemplate(EmailType::PASSWORD_RESET_SUCCESS, $data);
                break;
            case EmailType::WELCOME:
                $templateSubject = ($subject) ? $subject : 'Välkommen som medlem i Sofia Linnea';
                $template = $this->loadTemplate(EmailType::WELCOME, $data);
                break;
            case EmailType::TEST:
                $templateSubject = ($subject) ? $subject : 'Test email';
                $template = $this->loadTemplate(EmailType::TEST, $data);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported email type: {$type->value}");
        }
        $fromName = $this->app->getConfig("SMTP_FROM_NAME");
        $fromMail = $this->app->getConfig("SMTP_FROM_EMAIL");
        try {
            $this->mailer->setFrom($fromMail, $fromName);
            $this->mailer->addAddress($to);
            $replyTo = $this->app->getConfig("SMTP_REPLYTO");
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo);
            }
            $this->mailer->Subject = $templateSubject;
            $this->mailer->Body = $template;
            $this->logger->info("Email::{$type->value} email sent to: {$to}.");
            $this->mailer->send();

            unset($this->mailer);
            return true;
        } catch (Exception $e) {
            $this->logger->warning("Email::{$type->value} email could not be sent to: {$to}. Email body: {$template}");
            throw new Exception("Email could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }

    /**
     * Load and process email template with provided data.
     *
     * @param EmailType $type The email type to load template for
     * @param array<string, string> $data Template variables to replace in content
     * @return string Processed template content
     * @throws \RuntimeException If template file not found or cannot be read
     */
    private function loadTemplate(EmailType $type, array $data = []): string
    {
        $templatePath = $this->app->getRootDir() . "/public/views/emails/{$type->value}.tpl";
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Email template not found: {$type->value}.tpl");
        }

        $template = file_get_contents($templatePath);
        if ($template === false) {
            throw new \RuntimeException("Failed to read email template: {$type->value}.tpl");
        }

        foreach ($data as $key => $value) {
            //Use mb_ereg_replace to support UTF-8 instead of str_replace that is not multibyte safe
            $template = mb_ereg_replace("{{ $key }}", $value, $template);
        }
        return $template;
    }
}
