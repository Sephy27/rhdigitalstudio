<?php

namespace App\Controller\Admin;

use App\Entity\Intervention;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\DocumentMailer;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DocumentNumberGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\InterventionFileCrudController;

class InterventionCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }
    public static function getEntityFqcn(): string
    {
        return Intervention::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Intervention')
            ->setEntityLabelInPlural('Interventions')
            ->setPageTitle(Crud::PAGE_INDEX, 'Interventions');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('client', 'Client');

        yield DateTimeField::new('scheduledAt', 'Date du rendez-vous')
            ->setFormat('dd/MM/yyyy HH:mm');
        
        yield DateTimeField::new('interventionDate', 'Intervention réalisée')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm')
            ->formatValue(function ($value) {
                return $value ? $value->format('d/m/Y H:i') : '-';
            });

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Dépannage informatique' => 'Dépannage informatique',
                'Réinstallation Windows' => 'Réinstallation Windows',
                'Optimisation PC' => 'Optimisation PC',
                'Suppression virus' => 'Suppression virus',
                'Installation périphérique' => 'Installation périphérique',
                'Assistance à domicile' => 'Assistance à domicile',
                'Création site web' => 'Création site web',
                'Maintenance site web' => 'Maintenance site web',
            ]);

        yield TextareaField::new('problem', 'Problème signalé')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield TextareaField::new('diagnostic', 'Diagnostic')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield TextareaField::new('actionsDone', 'Actions réalisées')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield TextareaField::new('recommendations', 'Recommandations technicien')
            ->hideOnIndex()
            ->setNumOfRows(4);

        yield TextareaField::new('replacedParts', 'Pièces remplacées')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setNumOfRows(4);

        yield TextField::new('duration', 'Durée')
            ->hideOnIndex();

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

       
        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('status', 'Statut')
                ->formatValue(function ($value) {
                    return match ($value) {
                        'planned' => '<span class="badge-status badge-planned">● Planifiée</span>',
                        'in_progress' => '<span class="badge-status badge-progress">● En cours</span>',
                        'done' => '<span class="badge-status badge-done">● Terminée</span>',
                        'cancelled' => '<span class="badge-status badge-cancelled">● Annulée</span>',
                        'to_invoice' => '<span class="badge-status badge-invoice">● À facturer</span>',
                        default => '<span class="badge-status">● Non défini</span>',
                    };
                })
                ->renderAsHtml();
        } else {
            yield ChoiceField::new('status', 'Statut')
                ->hideOnForm()
                ->setChoices([
                    'Planifiée' => 'planned',
                    'En cours' => 'in_progress',
                    'Terminée' => 'done',
                    'Annulée' => 'cancelled',
                    'À facturer' => 'to_invoice',
                ]);
        }

        yield TextareaField::new('internalNotes', 'Notes internes')
            ->formatValue(fn ($value) => $value ?: '-')
            ->hideOnIndex();


        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnIndex()
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('paidAt', 'Payée le')
            ->hideOnIndex()
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('priority', 'Priorité')
                ->formatValue(function ($value) {
                    return match ($value) {
                        'low' => '<span class="badge-status badge-low">● Basse</span>',
                        'normal' => '<span class="badge-status badge-normal">● Normale</span>',
                        'urgent' => '<span class="badge-status badge-urgent">● Urgente</span>',
                        'critical' => '<span class="badge-status badge-critical">● Critique</span>',
                        default => '<span class="badge-status">● Non définie</span>',
                    };
                })
                ->renderAsHtml();
        } else {
            yield ChoiceField::new('priority', 'Priorité')
                ->setChoices([
                    'Basse' => 'low',
                    'Normale' => 'normal',
                    'Urgente' => 'urgent',
                    'Critique' => 'critical',
                ]);
        }

        yield TextField::new('device', 'Matériel concerné')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield TextField::new('serialNumber', 'Numéro de série')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield ChoiceField::new('operatingSystem', 'Système')
            ->setChoices([
                'Windows 11' => 'Windows 11',
                'Windows 10' => 'Windows 10',
                'macOS' => 'macOS',
                'Linux' => 'Linux',
                'Android' => 'Android',
                'iOS' => 'iOS',
                'Autre' => 'Autre',
            ])
            ->hideOnIndex();

        yield TextareaField::new('replacedParts', 'Pièces remplacées')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield ChoiceField::new('paymentMethod', 'Mode de paiement')
            ->setChoices([
                'Espèces' => 'cash',
                'Carte bancaire' => 'card',
                'Virement' => 'transfer',
                'Chèque' => 'check',
            ])
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('quoteReference', 'Référence devis')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(fn ($value) => $value ?: '-');

        yield TextField::new('invoiceReference', 'Référence facture')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(fn ($value) => $value ?: '-');

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('paymentStatus', 'Paiement')
                ->formatValue(function ($value) {
                    return match ($value) {
                        'paid' => '<span class="badge-status badge-done">● Payé</span>',
                        'pending' => '<span class="badge-status badge-planned">● En attente</span>',
                        'deposit' => '<span class="badge-status badge-progress">● Acompte</span>',
                        'unpaid' => '<span class="badge-status badge-cancelled">● Impayé</span>',
                        default => '<span class="badge-status">● Non défini</span>',
                    };
                })
                ->renderAsHtml();
        } else {
            yield ChoiceField::new('paymentStatus', 'Paiement')
                ->hideOnForm()
                ->setChoices([
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'Acompte' => 'deposit',
                    'Impayé' => 'unpaid',
                ]);
        }
        yield ChoiceField::new('quoteStatus', 'Statut devis')
            ->hideOnForm()
            ->setChoices([
                'Brouillon' => 'draft',
                'Envoyé' => 'sent',
                'Accepté' => 'accepted',
                'Refusé' => 'refused',
            ])
            ->renderAsBadges([
                'draft' => 'secondary',
                'sent' => 'warning',
                'accepted' => 'success',
                'refused' => 'danger',
            ]);
        
        yield Field::new('filesGallery', 'Photos / Documents')
            ->onlyOnDetail()
            ->setVirtual(true)
            ->formatValue(function ($value, Intervention $intervention) {
                $files = $intervention->getFiles();

                if ($files->isEmpty()) {
                    return '<span class="text-muted">Aucun fichier joint</span>';
                }

                $html = '<div style="display:flex;gap:14px;flex-wrap:wrap;">';

                foreach ($files as $file) {
                    $url = '/uploads/interventions/'.$file->getFileName();
                    $title = htmlspecialchars($file->getTitle() ?: $file->getFileName());

                    $html .= '
                        <div style="width:160px;text-align:center;">
                            <a href="'.$url.'" target="_blank">
                                <img src="'.$url.'" style="width:160px;height:110px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
                            </a>
                            <div style="font-size:12px;margin-top:6px;">'.$title.'</div>
                        </div>
                    ';
                }

                $html .= '</div>';

                return $html;
            })
            ->setTemplatePath('admin/field/raw_html.html.twig');

        yield DateTimeField::new('lastReminderAt', 'Dernière relance')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm')
            ->formatValue(function ($value) {
                return $value ? $value->format('d/m/Y H:i') : '-';
            });

        yield TextField::new('timeline', 'Chronologie du dossier')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/timeline.html.twig');
    }

    public function configureActions(Actions $actions): Actions 
    {

        $quotePdf = Action::new('quotePdf', 'Devis PDF', 'fa fa-file-pdf')
            ->linkToRoute('admin_intervention_quote_pdf', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->setCssClass('btn btn-secondary');

        $invoicePdf = Action::new('invoicePdf', 'Facture PDF', 'fa fa-file-invoice')
            ->linkToRoute('admin_intervention_invoice_pdf', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->setCssClass('btn btn-success');

        $acceptQuote = Action::new('acceptQuote', 'Accepter le devis', 'fa fa-check')
            ->linkToRoute('admin_intervention_accept_quote', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->setCssClass('btn btn-primary')
            ->displayIf(fn (Intervention $intervention) => $intervention->getQuoteStatus() === 'sent');
        
        $sendQuote = Action::new('sendQuote', 'Envoyer devis', 'fa fa-paper-plane')
            ->linkToRoute('admin_intervention_send_quote', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getQuoteSentAt() === null
            );

        $quoteSent = Action::new('quoteSent', 'Devis envoyé', 'fa fa-check')
            ->linkToUrl('#')
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getQuoteSentAt() !== null
            )
            ->setCssClass('btn btn-success');

        $sendInvoice = Action::new('sendInvoice', 'Envoyer facture', 'fa fa-paper-plane')
            ->linkToRoute('admin_intervention_send_invoice', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getInvoiceSentAt() === null
                && $intervention->getInvoiceNumber() !== null
                && $intervention->getStatus() === 'done'
            );

        $invoiceSent = Action::new('invoiceSent', 'Facture envoyée', 'fa fa-check')
            ->linkToUrl('#')
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getInvoiceSentAt() !== null
            )
            ->setCssClass('btn btn-success');

        $sendReminder = Action::new('sendReminder', 'Relancer', 'fa fa-envelope')
            ->linkToRoute('admin_intervention_send_reminder', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getInvoiceSentAt() !== null
                && $intervention->getPaymentStatus() !== 'paid'
            )
            ->setCssClass('btn btn-warning');

        $markPaid = Action::new('markPaid', 'Marquer payée', 'fa fa-money-bill')
            ->linkToRoute('admin_intervention_mark_paid', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getPaymentStatus() !== 'paid'
                && $intervention->getInvoiceSentAt() !== null
            );

        $startIntervention = Action::new('startIntervention', 'Démarrer', 'fa fa-play')
            ->linkToRoute('admin_intervention_start', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention)
                => $intervention->getStatus() === 'planned'
                && $intervention->getQuoteStatus() === 'accepted'
            );

        $finishIntervention = Action::new('finishIntervention', 'Terminer', 'fa fa-check')
            ->linkToRoute('admin_intervention_finish', function (Intervention $intervention): array {
                return ['id' => $intervention->getId()];
            })
            ->displayIf(fn (Intervention $intervention) =>
                $intervention->getStatus() === 'in_progress'
            );

        $viewFiles = Action::new('viewFiles', 'Voir les fichiers', 'fa fa-folder-open')
            ->linkToUrl(function (Intervention $intervention) {
                $files = $intervention->getFiles();

                if ($files->count() === 1) {
                    $file = $files->first();

                    return $this->adminUrlGenerator
                        ->setController(InterventionFileCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($file->getId())
                        ->generateUrl();
                }

                return $this->adminUrlGenerator
                    ->setController(InterventionFileCrudController::class)
                    ->setAction(Action::INDEX)
                    ->set('filters[intervention][comparison]', '=')
                    ->set('filters[intervention][value]', $intervention->getId())
                    ->generateUrl();
            });

        $reportPdf = Action::new('reportPdf', 'Rapport PDF')
        ->linkToRoute('admin_intervention_report_pdf', function (Intervention $intervention) {
            return [
                'id' => $intervention->getId(),
            ];
        })
        ->setIcon('fa fa-file-pdf')
        ->setCssClass('btn btn-secondary')
        ->displayIf(fn (Intervention $intervention) => $intervention->getStatus() === 'done');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $quotePdf)
            ->add(Crud::PAGE_DETAIL, $invoicePdf)
            ->add(Crud::PAGE_INDEX, $acceptQuote)
            ->add(Crud::PAGE_DETAIL, $acceptQuote)
            ->add(Crud::PAGE_INDEX, $sendQuote)
            ->add(Crud::PAGE_DETAIL, $sendQuote)
            ->add(Crud::PAGE_INDEX, $quoteSent)
            ->add(Crud::PAGE_DETAIL, $quoteSent)
            ->add(Crud::PAGE_INDEX, $sendInvoice)
            ->add(Crud::PAGE_DETAIL, $sendInvoice)
            ->add(Crud::PAGE_INDEX, $invoiceSent)
            ->add(Crud::PAGE_DETAIL, $invoiceSent)
            ->add(Crud::PAGE_INDEX, $markPaid)
            ->add(Crud::PAGE_DETAIL, $markPaid)
            ->add(Crud::PAGE_INDEX, $sendReminder)
            ->add(Crud::PAGE_DETAIL, $sendReminder)
            ->add(Crud::PAGE_INDEX, $startIntervention)
            ->add(Crud::PAGE_INDEX, $finishIntervention)
            ->add(Crud::PAGE_DETAIL, $viewFiles)
            ->add(Crud::PAGE_INDEX, $reportPdf)
            ->add(Crud::PAGE_DETAIL, $reportPdf)
            ->add(Crud::PAGE_DETAIL, $startIntervention)
            ->add(Crud::PAGE_DETAIL, $finishIntervention)
            


            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter une intervention')
                    ->setIcon('fa fa-plus');
            })

            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action
                    ->setLabel('Voir')
                    ->setIcon('fa fa-eye');
            })

            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Modifier')
                    ->setIcon('fa fa-pen');
            })

            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Supprimer')
                    ->setIcon('fa fa-trash');
            })

            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Modifier');
            })

            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Supprimer');
            })

            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour à la liste');
            })

            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action
                    ->setLabel('Créer');
            })

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action
                    ->setLabel('Enregistrer');
            })
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
            
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters

            ->add(EntityFilter::new('client', 'Client'))

            ->add(ChoiceFilter::new('status', 'Statut')
                ->setChoices([
                    'Planifiée' => 'planned',
                    'En cours' => 'in_progress',
                    'Terminée' => 'done',
                    'Annulée' => 'cancelled',
                ]))

            ->add(ChoiceFilter::new('priority', 'Priorité')
                ->setChoices([
                    'Normale' => 'normal',
                    'Urgente' => 'urgent',
                ]))

            ->add(ChoiceFilter::new('type', 'Type')
                ->setChoices([
                    'Assistance à domicile' => 'home_support',
                    'Réparation atelier' => 'workshop',
                    'Création site web' => 'website',
                    'Maintenance' => 'maintenance',
                ]))

            ->add(DateTimeFilter::new('interventionDate', 'Date intervention'))
            ->add(ChoiceFilter::new('paymentStatus', 'Paiement')
                ->setChoices([
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'Acompte' => 'deposit',
                    'Impayé' => 'unpaid',
                ]));

    }

    #[Route('/admin/intervention/{id}/accept-quote', name: 'admin_intervention_accept_quote')]
    public function acceptQuote(
        Intervention $intervention,
        DocumentNumberGenerator $numberGenerator,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {
        $intervention->setQuoteStatus('accepted');
        $intervention->setStatus('planned');

        if (!$intervention->getInvoiceNumber()) {
            $intervention->setInvoiceNumber($numberGenerator->generateInvoiceNumber());
            $intervention->setInvoiceAt(new \DateTimeImmutable());
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le devis a été accepté et la facture a été générée automatiquement.');

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    #[Route('/admin/intervention/{id}/send-quote', name: 'admin_intervention_send_quote')]
    public function sendQuote(
        Intervention $intervention,
        DocumentMailer $documentMailer,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $documentMailer->sendQuote($intervention);

        $intervention->setQuoteSentAt(new \DateTimeImmutable());
        

        if ($intervention->getQuoteStatus() === 'draft') {
            $intervention->setQuoteStatus('sent');
        }
        $entityManager->flush();

        $this->addFlash('success', 'Le devis a été envoyé par email.');

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    #[Route('/admin/intervention/{id}/send-invoice', name: 'admin_intervention_send_invoice')]
    public function sendInvoice(
        Intervention $intervention,
        DocumentMailer $documentMailer,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $documentMailer->sendInvoice($intervention);

        $intervention->setInvoiceSentAt(new \DateTimeImmutable());
        $intervention->setStatus('to_invoice');

        $entityManager->flush();

        $this->addFlash('success', 'La facture a été envoyée par email.');

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    #[Route('/admin/intervention/{id}/send-reminder', name: 'admin_intervention_send_reminder')]
    public function sendReminder(
        Intervention $intervention,
        DocumentMailer $documentMailer,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $documentMailer->sendReminder($intervention);

        $intervention->setLastReminderAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'Relance envoyée au client.');

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    #[Route('/admin/intervention/{id}/mark-paid', name: 'admin_intervention_mark_paid')]
    public function markPaid(
        Intervention $intervention,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $intervention->setPaymentStatus('paid');
        $intervention->setPaidAt(new \DateTimeImmutable());
        $intervention->setStatus('done');

        $entityManager->flush();

        $this->addFlash('success', 'La facture a été marquée comme payée.');

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    #[Route('/admin/intervention/{id}/start', name: 'admin_intervention_start')]
    public function startIntervention(
        Intervention $intervention,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $intervention->setStatus('in_progress');

        if (!$intervention->getStartedAt()) {
            $intervention->setStartedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();

        $this->addFlash('success', 'Intervention démarrée.');

        return $this->redirect(
            $request->headers->get('referer') ?? '/admin'
        );
    }

    #[Route('/admin/intervention/{id}/finish', name: 'admin_intervention_finish')]
    public function finishIntervention(
        Intervention $intervention,
        EntityManagerInterface $entityManager,
        Request $request
    ): RedirectResponse {

        $intervention->setStatus('done');

        if (!$intervention->getCompletedAt()) {
            $intervention->setCompletedAt(new \DateTimeImmutable());
        }

        if (!$intervention->getInterventionDate()) {
            $intervention->setInterventionDate(new \DateTimeImmutable());
        }

        $entityManager->flush();

        $this->addFlash('success', 'Intervention terminée.');

        return $this->redirect(
            $request->headers->get('referer') ?? '/admin'
        );
    }
}
