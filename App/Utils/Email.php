<?php

namespace App\Utils;

use App\Application;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Email
{
    private $mailer;
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure()
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->app->getConfig("SMTP_HOST");
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->app->getConfig("SMTP_USERNAME");
        $this->mailer->Password = $this->app->getConfig("SMTP_PASSWORD");
        $this->mailer->Port = $this->app->getConfig("SMTP_PORT");
        $this->mailer->Timeout = 20;
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
    }

    public function send(EmailType $type, string $to, string $subject = null, array $data = [])
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
            var_dump($replyTo);
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo);
            }
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $templateSubject;
            $this->mailer->Body = $template;

            $this->mailer->send();

            unset($this->mailer);
            return true;
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }

    private function loadTemplate(EmailType $type, array $data = [])
    {
        $template = file_get_contents($this->app->getAppDir() . "/views/emails/{$type->value}.tpl");
        foreach ($data as $key => $value) {
            $template = str_replace("{{ $key }}", $value, $template);
        }
        return $template;
    }
}
