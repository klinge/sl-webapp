<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlGeneratorService;
use App\Utils\View;
use App\Models\MedlemRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PDO;
use Monolog\Logger;
use League\Container\Container;

class ReportController extends BaseController
{
    private View $view;
    private MedlemRepository $medlemRepo;
    private PDO $conn;

    public function __construct(
        UrlGeneratorService $urlGenerator,
        ServerRequestInterface $request,
        Logger $logger,
        Container $container,
        PDO $conn,
        View $view
    ) {
        parent::__construct($urlGenerator, $request, $logger, $container);
        $this->conn = $conn;
        $this->view = $view;
        $this->medlemRepo = new MedlemRepository($this->conn, $this->logger);
    }

    public function show(): ResponseInterface
    {
        $data = [
            "title" => "Rapporter",
        ];
        return $this->view->render('reports/viewRapporter', $data);
    }

    public function showPaymentReport(): ResponseInterface
    {
        //Set current year
        $currentYear = (int) date('Y');
        //Get the parameter on what years to check from the form
        $year_param = $this->request->getParsedBody()['yearRadio'];

        // Build arrays of years and placeholders based on year_param
        for ($i = 0; $i < $year_param; $i++) {
            $years[] = $currentYear - $i;
        }

        $query = "SELECT m.* FROM medlem m
                WHERE m.standig_medlem != 1
                AND NOT EXISTS (
                    SELECT 1
                    FROM betalning b
                    WHERE b.medlem_id = m.id";

        switch ($year_param) {
            case 1:
                $query .= " AND b.avser_ar = :year0)";
                break;
            case 2:
                $query .= " AND b.avser_ar IN (:year0, :year1))";
                break;
            case 3:
                $query .= " AND b.avser_ar IN (:year0, :year1, :year2))";
                break;
            default:
                throw new \InvalidArgumentException("Invalid year parameter. Must be 1, 2 or 3.");
        }

        $stmt = $this->conn->prepare($query);
        // Bind all year parameters
        $stmt->bindParam(':year0', $currentYear, PDO::PARAM_INT);
        if ($year_param === 2) {
            $stmt->bindValue(':year1', $currentYear - 1, PDO::PARAM_INT);
        }
        if ($year_param === 3) {
            $stmt->bindValue(':year2', $currentYear - 2, PDO::PARAM_INT);
        }

        $stmt->execute();
        $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            "title" => "Rapport: Ej gottstående medlemmar",
            "items" => $result
        ];
        return $this->view->render('reports/viewReportResults', $data);
    }

    public function showMemberEmails(): ResponseInterface
    {
        $mailList = $this->medlemRepo->getEmailForActiveMembers();

        $data = [
            "title" => "Rapport: Email till aktiva medlemmar",
            "items" => $mailList
        ];
        return $this->view->render('reports/viewReportResults', $data);
    }
}
