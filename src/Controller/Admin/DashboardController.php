<?php

namespace App\Controller\Admin;

use App\Entity\{Student, Payment, PaymentCheck, Institute, Service};
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        return $this->redirectToRoute('admin_student_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Lr4');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Микросервис', 'fa fa-home');
        yield MenuItem::linkToCrud('Студенты', 'fas fa-list', Student::class);
        yield MenuItem::linkToCrud('Оплата', 'fas fa-list', Payment::class);
        yield MenuItem::linkToCrud('Чеки', 'fas fa-list', PaymentCheck::class);
        yield MenuItem::linkToCrud('Специальности', 'fas fa-list', Institute::class);
        yield MenuItem::linkToCrud('Услуги', 'fas fa-list', Service::class);
    }
}
