<?php
namespace App\Controller\Moderator;

use App\Repository\PaymentStatisticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/statistics')]
#[IsGranted('ROLE_MODERATOR')]
class StatisticsController extends AbstractController
{
    #[Route('/', name: 'moderator_statistics_index')]
    public function index(PaymentStatisticsRepository $statsRepository): Response
    {
        $statsByDirection = $statsRepository->getPaymentStatsByDirection();
        $totalStats = $statsRepository->getTotalStats();

        return $this->render('moderator/statistics/index.html.twig', [
            'stats' => $statsByDirection,
            'total_paid' => $totalStats['total_paid'] ?? 0,
            'total_unpaid' => $totalStats['total_unpaid'] ?? 0,
            'total_payments' => $totalStats['total_payments'] ?? 0,
        ]);
    }

    #[Route('/export/csv', name: 'moderator_statistics_export_csv')]
    public function exportCsv(PaymentStatisticsRepository $statsRepository): Response
    {
        $stats = $statsRepository->getPaymentStatsByDirection();

        $csvData = "Направление;Оплачено;Не оплачено;Всего;% оплачено\n";

        foreach ($stats as $stat) {
            $csvData .= sprintf(
                "%s;%d;%d;%d;%.2f%%\n",
                $stat->getDirection(),
                $stat->getPaidCount(),
                $stat->getUnpaidCount(),
                $stat->getTotalCount(),
                $stat->getPaidPercentage()
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="payment_statistics_' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
