<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\BetalningRepository;
use App\Models\MedlemRepository;
use App\Models\Betalning;
use App\Utils\Sanitizer;
use App\Utils\Session;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Application;
use Monolog\Logger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class BetalningService
{
    /**
     * Initialize BetalningService with required dependencies.
     *
     * @param BetalningRepository $betalningRepo Repository for payment operations
     * @param MedlemRepository $medlemRepo Repository for member operations
     * @param Email $email Email service for notifications
     * @param Application $app Application instance for configuration
     * @param Logger $logger Logger instance for service operations
     */
    public function __construct(
        private BetalningRepository $betalningRepo,
        private MedlemRepository $medlemRepo,
        private Email $email,
        private Application $app,
        private Logger $logger
    ) {
    }

    /**
     * Retrieve all payments with associated member names.
     *
     * @return array<int, mixed> Array of payment records with member information
     */
    public function getAllPayments(): array
    {
        return $this->betalningRepo->findAllWithMemberNames();
    }

    /**
     * Get payment information for a specific member.
     *
     * @param int $memberId The member ID to get payments for
     * @return array<string, mixed> Array containing member and payments data
     * @throws Exception If member not found
     */
    public function getPaymentsForMember(int $memberId): array
    {
        $medlem = $this->medlemRepo->getById($memberId);
        if (!$medlem) {
            throw new Exception('Medlem not found');
        }

        return [
            'medlem' => $medlem,
            'payments' => $this->betalningRepo->getBetalningForMedlem($memberId)
        ];
    }

    /**
     * Create a new payment record.
     *
     * @param array<string, mixed> $postData Payment data from form submission
     * @return BetalningServiceResult Result object with success status and message
     */
    public function createPayment(array $postData): BetalningServiceResult
    {
        // Validate required fields
        if (empty($postData['belopp']) || empty($postData['datum']) || empty($postData['avser_ar'])) {
            return new BetalningServiceResult(
                false,
                'Belopp, datum, and avser_ar are required fields.'
            );
        }

        // Sanitize input
        $sanitizer = new Sanitizer();
        $rules = [
            'medlem_id' => 'string',
            'datum' => ['date', 'Y-m-d'],
            'avser_ar' => ['date', 'Y'],
            'belopp' => 'float',
            'kommentar' => 'string',
        ];
        $cleanValues = $sanitizer->sanitize($postData, $rules);

        // Create payment object
        $betalning = new Betalning();
        $betalning->medlem_id = (int) $cleanValues['medlem_id'];
        $betalning->datum = $cleanValues['datum'];
        $betalning->belopp = (float) $cleanValues['belopp'];
        $betalning->avser_ar = (int) $cleanValues['avser_ar'];
        $betalning->kommentar = $cleanValues['kommentar'] ?? '';

        // Validate data
        if (!$betalning->medlem_id || !$betalning->datum || !$betalning->belopp || !$betalning->avser_ar) {
            return new BetalningServiceResult(false, 'Invalid input');
        }

        try {
            $paymentId = $this->betalningRepo->create($betalning);
            $this->sendWelcomeEmailOnFirstPayment($betalning->medlem_id);

            $this->logger->info('Betalning created successfully. Id: ' . $paymentId .
                '. Registered by: ' . Session::get('user_id'));

            return new BetalningServiceResult(
                true,
                'Betalning created successfully. Id: ' . $paymentId,
                $paymentId
            );
        } catch (Exception $e) {
            $this->logger->warning('Error creating Betalning: ' . $e->getMessage());
            return new BetalningServiceResult(false, 'Error creating Betalning: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payment record by ID.
     *
     * @param int $id The payment ID to delete
     * @return BetalningServiceResult Result object with success status and message
     */
    public function deletePayment(int $id): BetalningServiceResult
    {
        try {
            if (!$this->betalningRepo->deleteById($id)) {
                return new BetalningServiceResult(false, 'Payment not found');
            }

            return new BetalningServiceResult(true, 'Betalning deleted successfully');
        } catch (Exception $e) {
            $this->logger->warning('Error deleting Betalning: ' . $e->getMessage());
            return new BetalningServiceResult(false, 'Error deleting Betalning: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email to member on their first payment if not already sent.
     *
     * @param int $memberId The member ID to send welcome email to
     * @return bool True if email was sent successfully, false otherwise
     */
    private function sendWelcomeEmailOnFirstPayment(int $memberId): bool
    {
        if ($this->app->getConfig('WELCOME_MAIL_ENABLED') !== "1") {
            $this->logger->info('sendWelcomeEmailOnFirstPayment: Sending mail is disabled');
            return false;
        }

        $member = $this->medlemRepo->getById($memberId);
        if (!$member) {
            $this->logger->warning('sendWelcomeEmailOnFirstPayment: Member not found. MemberId: ' . $memberId);
            return false;
        }

        if ($member->skickat_valkomstbrev) {
            return false;
        }

        if (empty($member->email)) {
            $this->logger->warning('sendWelcomeEmailOnFirstPayment: No email address for member. MemberId: ' . $memberId);
            return false;
        }

        $data = ['fornamn' => $member->fornamn, 'efternamn' => $member->efternamn];
        try {
            $this->email->send(EmailType::WELCOME, $member->email, 'Välkommen till föreningen Sofia Linnea', $data);
            $this->logger->info('sendWelcomeEmailOnFirstPayment: Welcome email sent to: ' . $member->email);

            $member->skickat_valkomstbrev = true;
            $this->medlemRepo->save($member);
            return true;
        } catch (PHPMailerException $e) {
            $this->logger->warning('sendWelcomeEmailOnFirstPayment: Failed to send welcome email. Error: ' . $e->getMessage());
            return false;
        }
    }
}
