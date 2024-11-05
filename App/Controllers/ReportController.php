<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use PDO;

class ReportController extends BaseController
{
    private View $view;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->view = new View($this->app);
    }

    public function show(): void
    {
        $data = [
            "title" => "Rapporter",
        ];
        $this->view->render('viewRapporter', $data);
        return;
    }

    public function showPaymentReport(): void
    {
        $data = [
            "title" => "RapporterXXX",
        ];

        $currentYear = (int) date('Y');
        $years = [];
        $placeholders = [];

        $year_param = $this->request->getParsedBody()['yearRadio'];

        // Build arrays of years and placeholders based on year_param
        for ($i = 0; $i < $year_param; $i++) {
            $years[] = $currentYear - $i;
            $placeholders[] = ':year' . $i;
        }

        $query = "SELECT m.* FROM medlem m 
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM betalning b 
                    WHERE b.medlem_id = m.id 
                    AND b.avser_ar IN (" . implode(',', $placeholders) . ")
                )";

        $stmt = $this->conn->prepare($query);
        // Bind all year parameters
        foreach ($years as $index => $year) {
            $stmt->bindParam(':year' . $index, $year, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $member) {
            print_r($member);
            echo "<br><br>";
        }

        return;
    }
}
