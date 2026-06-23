<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Client;
use App\Entity\Galerie;
use App\Entity\Intervention;
use App\Entity\InterventionFile;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use App\Repository\ClientRepository;
use App\Repository\InterventionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\ClientCrudController;
use App\Controller\Admin\InterventionCrudController;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public function index(): Response
    {
        $clientRepo = $this->em->getRepository(Client::class);
        $interventionRepo = $this->em->getRepository(Intervention::class);
        $contactRepo = $this->em->getRepository(Contact::class);
        $galerieRepo = $this->em->getRepository(Galerie::class);

        // 1) Construire la frise des 6 derniers mois (clé = "Y-m")
        $now = new \DateTimeImmutable('first day of this month 00:00:00');

        $months = []; // ex: ['2025-05' => ['label' => 'Mai 2025', 'contacts' => 0, 'photos' => 0], ...]
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->sub(new \DateInterval('P'.$i.'M'));
            $key   = $month->format('Y-m');

            // Pour un label lisible (ex: "Novembre 2025")
            $label = \IntlDateFormatter::formatObject(
                $month,
                'MMMM yyyy',
                'fr_FR'
            );

            $months[$key] = [
                'label'    => ucfirst($label),
                'contacts' => 0,
                'photos'   => 0,
            ];
        }

        // 2) Date mini pour les requêtes (début du plus ancien mois)
        $firstKey  = array_key_first($months);
        $fromDate  = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $firstKey.'-01 00:00:00');

        // 3) Récupérer tous les contacts depuis cette date
        $contacts = $contactRepo->createQueryBuilder('c')
            ->where('c.createdAt >= :from')
            ->setParameter('from', $fromDate)
            ->getQuery()
            ->getResult();

        foreach ($contacts as $contact) {
            /** @var Contact $contact */
            if (!$contact->getCreatedAt()) {
                continue;
            }
            $key = $contact->getCreatedAt()->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['contacts']++;
            }
        }

        // 4) Récupérer les photos depuis cette date
        $photos = $galerieRepo->createQueryBuilder('g')
            ->where('g.createdAt >= :from')
            ->setParameter('from', $fromDate)
            ->getQuery()
            ->getResult();

        foreach ($photos as $photo) {
            /** @var Galerie $photo */
            // ⚠️ adapte le nom du champ date si besoin (createdAt, uploadedAt, etc.)
            if (!method_exists($photo, 'getCreatedAt') || !$photo->getCreatedAt()) {
                continue;
            }
            $key = $photo->getCreatedAt()->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['photos']++;
            }
        }

        // 5) Extraire les tableaux pour le JS
        $chartLabels       = array_column($months, 'label');       // ["Juin 2025", ...]
        $chartContacts     = array_column($months, 'contacts');    // [3, 5, ...]
        $chartPhotos       = array_column($months, 'photos');      // [1, 0, ...]

        // 6) Stats globales + ce mois-ci
        $contactsTotal     = $contactRepo->count([]);
        $photosTotal       = $galerieRepo->count([]);

        $lastKey           = array_key_last($months);
        $contactsThisMonth = $months[$lastKey]['contacts'];
        $photosThisMonth   = $months[$lastKey]['photos'];

        // dashboard dynamique
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        $startOfYear = new \DateTimeImmutable('first day of january this year 00:00:00');
        $endOfYear = new \DateTimeImmutable('last day of december this year 23:59:59');

        $paidRevenueThisMonth = $interventionRepo->getPaidRevenueBetween($startOfMonth, $endOfMonth);
        $invoicedRevenueThisMonth = $interventionRepo->getInvoicedRevenueBetween($startOfMonth, $endOfMonth);
        $paidRevenueThisYear = $interventionRepo->getPaidRevenueBetween($startOfYear, $endOfYear);
        $pendingRevenue = $interventionRepo->getPendingRevenue();
        $unpaidInvoicesCount = $interventionRepo->countUnpaidInvoices();

        $interventionsThisMonth = $interventionRepo->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.interventionDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $monthlyRevenue = $interventionRepo->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.price), 0)')
            ->where('i.interventionDate BETWEEN :start AND :end')
            ->andWhere('i.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->setParameter('status', 'done')
            ->getQuery()
            ->getSingleScalarResult();

        $pendingInterventions = $interventionRepo->count([
            'status' => 'planned',
        ]);

        $toInvoiceInterventions = $interventionRepo->count([
            'status' => 'to_invoice',
        ]);

        $recentClients = $clientRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $topClients = $clientRepo->getTopClients();

        $recentInterventions = $interventionRepo->findBy([], ['interventionDate' => 'DESC'], 5);

        $revenueLabels = [];
        $revenueData = [];

        for ($i = 11; $i >= 0; $i--) {

            $monthStart = (new \DateTimeImmutable('first day of this month'))
                ->modify("-{$i} month");

            $monthEnd = $monthStart
                ->modify('last day of this month')
                ->setTime(23, 59, 59);

            $revenue = $interventionRepo->createQueryBuilder('i')
                ->select('COALESCE(SUM(i.price),0)')
                ->where('i.paidAt BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $revenueLabels[] = ucfirst(
                \IntlDateFormatter::formatObject(
                    $monthStart,
                    'MMM yyyy',
                    'fr_FR'
                )
            );

            $revenueData[] = (float) $revenue;
        }

        $urgentInterventionsCount = $interventionRepo->count([
            'priority' => 'urgent',
        ]);

        $criticalInterventionsCount = $interventionRepo->count([
            'priority' => 'critical',
        ]);

        $pendingQuotesCount = $interventionRepo->count([
            'quoteStatus' => 'sent',
        ]);

        $remindersSentCount = $interventionRepo->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.lastReminderAt IS NOT NULL')
            ->andWhere('i.paymentStatus != :paid')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult();

        

        $today = new \DateTimeImmutable();

        $weekStart = new \DateTimeImmutable();

        $weekEnd = $weekStart->modify('+7 days');

        $weekInterventions = $interventionRepo->createQueryBuilder('i')
            ->where('i.scheduledAt BETWEEN :start AND :end')
            ->andWhere('i.status != :done')
            ->setParameter('start', $weekStart)
            ->setParameter('end', $weekEnd)
            ->setParameter('done', 'done')
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();

        $todayStart = new \DateTimeImmutable('today 00:00:00');
        $todayEnd = new \DateTimeImmutable('today 23:59:59');

        $todayInterventions = $interventionRepo->createQueryBuilder('i')
            ->where('i.scheduledAt BETWEEN :start AND :end')
            ->andWhere('i.status != :done')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->setParameter('done', 'done')
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
        $todayInterventionsCount = count($todayInterventions);

        $invoicesToRemind = $interventionRepo->createQueryBuilder('i')
            ->where('i.invoiceNumber IS NOT NULL')
            ->andWhere('i.paymentStatus != :paid')
            ->setParameter('paid', 'paid')
            ->orderBy('i.invoiceSentAt', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'contactsCount'      => $contactsTotal,
            'photosCount'        => $photosTotal,
            'contactsThisMonth'  => $contactsThisMonth,
            'photosThisMonth'    => $photosThisMonth,
            'lastContacts'       => $contactRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'topClients' => $topClients,
            'urgentInterventionsCount' => $urgentInterventionsCount,
            'criticalInterventionsCount' => $criticalInterventionsCount,
            'pendingQuotesCount' => $pendingQuotesCount,
            'remindersSentCount' => $remindersSentCount,
            'todayInterventions' => $todayInterventions,
            'weekInterventions' => $weekInterventions,
            'todayInterventionsCount' => $todayInterventionsCount,
            'invoicesToRemind' => $invoicesToRemind,

            // Données pour le graphique
            'chartLabels'        => $chartLabels,
            'chartContacts'      => $chartContacts,
            'chartPhotos'        => $chartPhotos,
            'monthlyRevenue' => $monthlyRevenue,
            'interventionsThisMonth' => $interventionsThisMonth,
            'pendingInterventions' => $pendingInterventions,
            'toInvoiceInterventions' => $toInvoiceInterventions,
            'recentClients' => $recentClients,
            'recentInterventions' => $recentInterventions,
            'paidRevenueThisMonth' => $paidRevenueThisMonth,
            'paidRevenueThisYear' => $paidRevenueThisYear,
            'pendingRevenue' => $pendingRevenue,
            'unpaidInvoicesCount' => $unpaidInvoicesCount,
            'invoicedRevenueThisMonth' => $invoicedRevenueThisMonth,
            'revenueLabels' => $revenueLabels,
            'revenueData' => $revenueData,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/images/logo_header.png" class="admin-logo">')
            ->setFaviconPath('favicon.svg')
            ->renderContentMaximized()
            ->setTranslationDomain('admin');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->renderContentMaximized()
            ->showEntityActionsInlined();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-globe', 'app_home');

        yield MenuItem::section('Clients');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Client::class);
        yield MenuItem::linkToRoute(
            'Recherche globale',
            'fa fa-search',
            'admin_global_search'
        );

        yield MenuItem::section('Interventions');
        yield MenuItem::linkToCrud('Interventions', 'fa fa-screwdriver-wrench', Intervention::class);

        yield MenuItem::section('Fichiers interventions');
        yield MenuItem::linkToCrud('Photos / rapports', 'fa fa-folder-open', InterventionFile::class);
        
        yield MenuItem::section('Contacts');
        yield MenuItem::linkToCrud('Emails reçus', 'fa fa-envelope-open', Contact::class);



        yield MenuItem::section();
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-right-from-bracket');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin.css');
    }



    #[Route('/admin/search', name: 'admin_global_search')]
    public function search(
        Request $request,
        ClientRepository $clientRepository,
        InterventionRepository $interventionRepository
    ): Response {
        $q = trim($request->query->get('q', ''));

        $clients = [];
        $interventions = [];

        if ($q !== '') {
            $clients = $clientRepository->createQueryBuilder('c')
                ->where('c.firstName LIKE :q')
                ->orWhere('c.lastName LIKE :q')
                ->orWhere('c.email LIKE :q')
                ->orWhere('c.phone LIKE :q')
                ->setParameter('q', '%'.$q.'%')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            $interventions = $interventionRepository->createQueryBuilder('i')
                ->leftJoin('i.client', 'c')
                ->where('i.type LIKE :q')
                ->orWhere('i.problem LIKE :q')
                ->orWhere('i.diagnostic LIKE :q')
                ->orWhere('i.actionsDone LIKE :q')
                ->orWhere('i.invoiceNumber LIKE :q')
                ->orWhere('i.quoteNumber LIKE :q')
                ->orWhere('c.firstName LIKE :q')
                ->orWhere('c.lastName LIKE :q')
                ->orWhere('c.email LIKE :q')
                ->setParameter('q', '%'.$q.'%')
                ->setMaxResults(20)
                ->getQuery()
                ->getResult();
        }
        $clientResults = [];

        foreach ($clients as $client) {
            $clientResults[] = [
                'entity' => $client,
                'url' => $this->adminUrlGenerator
                    ->setController(ClientCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($client->getId())
                    ->generateUrl(),
            ];
        }

        $interventionResults = [];

        foreach ($interventions as $intervention) {
            $interventionResults[] = [
                'entity' => $intervention,
                'url' => $this->adminUrlGenerator
                    ->setController(InterventionCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($intervention->getId())
                    ->generateUrl(),
            ];
        }

        $groupedInterventions = [];

        foreach ($interventionResults as $result) {
            $intervention = $result['entity'];
            $client = $intervention->getClient();

            $clientKey = $client->getId();

            if (!isset($groupedInterventions[$clientKey])) {
                $groupedInterventions[$clientKey] = [
                    'client' => $client,
                    'clientUrl' => $this->adminUrlGenerator
                        ->setController(ClientCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($client->getId())
                        ->generateUrl(),
                    'interventions' => [],
                ];
            }

            $groupedInterventions[$clientKey]['interventions'][] = $result;
        }

        

        return $this->render('admin/search/index.html.twig', [
            'query' => $q,
            'clients' => $clientResults,
            'interventions' => $interventionResults,
            'groupedInterventions' => $groupedInterventions,
        ]);
    }
}
