<?php
namespace App\Controller\Master;

use App\Repository\MasterReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/master')]
#[IsGranted('ROLE_MASTER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'master_dashboard')]
    public function index(MasterReportRepository $reportRepository): Response
    {
        $studentPayments = $reportRepository->getStudentPaymentDetails();
        $statusSummary = $reportRepository->getStatusSummary();
        $topServices = $reportRepository->getTopServices();

        // Подсчет итогов
        $totalPayments = count($studentPayments);
        $totalAmount = array_sum(array_map(
            fn($item) => (float) $item['total_amount'],
            $statusSummary
        ));

        return $this->render('master/dashboard/index.html.twig', [
            'student_payments' => $studentPayments,
            'status_summary' => $statusSummary,
            'top_services' => $topServices,
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
        ]);
    }

    #[Route('/export/csv', name: 'master_export_csv')]
    public function exportCsv(MasterReportRepository $reportRepository): Response
    {
        $data = $reportRepository->getStudentPaymentDetails();

        $csvContent = "Студент;Направление;Услуга;Статус;№ договора\n";

        foreach ($data as $item) {
            $csvContent .= sprintf(
                "%s;%s;%s;%s;%s\n",
                $item->getStudentName(),
                $item->getDirection(),
                $item->getServiceName(),
                $item->getStatusText(),
                $item->getContractNumber() ?? 'Не указан'
            );
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="master_report_%s.csv"', date('Y-m-d_H-i'))
        );

        return $response;
    }

    #[Route('/api/data', name: 'master_api_data', methods: ['GET'])]
    public function apiData(MasterReportRepository $reportRepository): Response
    {
        $data = $reportRepository->getStudentPaymentDetails();

        $formattedData = array_map(function ($item) {
            return [
                'student' => $item->getStudentName(),
                'direction' => $item->getDirection(),
                'service' => $item->getServiceName(),
                'status' => $item->getStatus(),
                'status_text' => $item->getStatusText(),
                'contract_number' => $item->getContractNumber(),
            ];
        }, $data);

        return $this->json([
            'data' => $formattedData,
            'total' => count($data),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
