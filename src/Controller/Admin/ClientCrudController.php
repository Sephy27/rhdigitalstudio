<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;



class ClientCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }
    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureFields(string $pageName): iterable
    {
    
    yield Field::new('contactCard', 'Coordonnées client')
        ->onlyOnDetail()
        ->setVirtual(true)
        ->formatValue(function ($value, Client $client) {
            $fullName = trim(($client->getFirstName() ?? '').' '.($client->getLastName() ?? ''));
            $lastIntervention = null;
            $total = 0;

            foreach ($client->getInterventions() as $intervention) {
                $total += (float) ($intervention->getPrice() ?? 0);
                if (
                    $intervention->getInterventionDate()
                    && (
                        !$lastIntervention
                        || $intervention->getInterventionDate() > $lastIntervention
                    )
                ) {
                    $lastIntervention = $intervention->getInterventionDate();
                }
            }
            return '
                <div class="client-contact-card">
                    <div class="client-contact-header">
                        <div class="client-contact-avatar">
                            <i class="fa fa-user"></i>
                        </div>

                        <div>
                            <h3>'.htmlspecialchars($fullName ?: 'Client sans nom').'</h3>
                            <span>'.htmlspecialchars($client->getClientType() ?: 'Client').'</span>
                        </div>
                    </div>

                    <div class="client-contact-separator"></div>

                    <div class="client-contact-list">
                        <div>
                            <i class="fa fa-envelope"></i>
                            <a href="mailto:'.htmlspecialchars($client->getEmail() ?? '').'">
                                '.htmlspecialchars($client->getEmail() ?: 'Email non renseigné').'
                            </a>
                        </div>

                        <div>
                            <i class="fa fa-phone"></i>
                            <a href="tel:'.htmlspecialchars($client->getPhone() ?? '').'">
                                '.htmlspecialchars($client->getPhone() ?: 'Téléphone non renseigné').'
                            </a>
                        </div>

                        <div>
                            <i class="fa fa-location-dot"></i>
                            <span>'.nl2br(htmlspecialchars($client->getAddress() ?: 'Adresse non renseignée')).'</span>
                        </div>
                        
                    </div>
                    <div class="client-contact-separator"></div>
                    <div class="client-contact-stats">
                        <div>
                            <strong>📅 Client depuis</strong>
                            <span>'.$client->getCreatedAt()?->format('d/m/Y').'</span>
                        </div>

                        <div>
                            <strong>🔧 Dernière intervention</strong>
                            <span>'.($lastIntervention ? $lastIntervention->format('d/m/Y') : '-').'</span>
                        </div>

                        <div>
                            <strong>💰 Valeur client</strong>
                            <span>'.number_format($total, 2, ',', ' ').' €</span>
                        </div>
                    </div>
                </div>
            ';
        })
        ->setTemplatePath('admin/field/raw_html.html.twig');
    
    yield Field::new('clientStats', 'Résumé client')
        ->onlyOnDetail()
        ->setVirtual(true)
        ->formatValue(function ($value, Client $client) {
            $interventions = $client->getInterventions();

            $totalInterventions = $interventions->count();

            $totalFacture = 0;
            $facturesPayees = 0;
            $facturesImpayees = 0;

            foreach ($interventions as $intervention) {
                $totalFacture += (float) ($intervention->getPrice() ?? 0);

                if ($intervention->getPaymentStatus() === 'paid') {
                    $facturesPayees++;
                }

                if (
                    $intervention->getInvoiceNumber()
                    && $intervention->getPaymentStatus() !== 'paid'
                ) {
                    $facturesImpayees++;
                }
            }

            return '
                <div class="client-stats-grid">
                    <div class="client-stat-card">
                        <span>Interventions</span>
                        <strong>'.$totalInterventions.'</strong>
                    </div>

                    <div class="client-stat-card">
                        <span>Total facturé</span>
                        <strong>'.number_format($totalFacture, 2, ',', ' ').' €</strong>
                    </div>

                    <div class="client-stat-card">
                        <span>Factures payées</span>
                        <strong>'.$facturesPayees.'</strong>
                    </div>

                    <div class="client-stat-card">
                        <span>Impayées</span>
                        <strong>'.$facturesImpayees.'</strong>
                    </div>
                </div>
            ';
        })
        ->setTemplatePath('admin/field/raw_html.html.twig');

    yield Field::new('clientInterventions', 'Historique interventions')
        ->onlyOnDetail()
        ->setVirtual(true)
        ->formatValue(function ($value, Client $client) {
            $interventions = $client->getInterventions();

            if ($interventions->isEmpty()) {
                return '<span class="text-muted">Aucune intervention pour ce client.</span>';
            }
            
            $html = '
                <div class="client-history-scroll">
                        <table class="table client-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Prix</th>
                                    <th>Paiement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ';

                    foreach ($interventions as $intervention) {
                        $date = $intervention->getInterventionDate()
                            ? $intervention->getInterventionDate()->format('d/m/Y H:i')
                            : '-';
                        $status = match ($intervention->getStatus()) {
                            'planned' => '📅 Planifiée',
                            'in_progress' => '🔵 En cours',
                            'done' => '✅ Terminée',
                            default => $intervention->getStatus(),
                        };

                        $payment = match ($intervention->getPaymentStatus()) {
                            'paid' => '💰 Payé',
                            'pending' => '⌛ En attente',
                            default => $intervention->getPaymentStatus(),
                        };
                        $url = $this->adminUrlGenerator
                            ->setController(InterventionCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($intervention->getId())
                            ->generateUrl();
                        $html .= '
                            <tr>
                                <td>'.$date.'</td>
                                <td>'.htmlspecialchars($intervention->getType() ?? '-').'</td>
                                <td>'.$status.'</td>
                                <td>'.number_format((float) ($intervention->getPrice() ?? 0), 2, ',', ' ').' €</td>
                                <td>'.$payment.'</td>
                                <td>
                                    <a href="'.$url.'" class="client-view-icon" title="Voir l\'intervention">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        ';
                    }

                $html .= '</tbody></table></div>';

            return $html;
        })
        ->setTemplatePath('admin/field/raw_html.html.twig');

    yield Field::new('clientDocuments', 'Documents client')
        ->onlyOnDetail()
        ->setVirtual(true)
        ->formatValue(function ($value, Client $client) {
            $interventions = $client->getInterventions();

            if ($interventions->isEmpty()) {
                return '<span class="text-muted">Aucun document pour ce client.</span>';
            }

            $html = '
                <div class="client-history-scroll">
                    <table class="table client-history-table">
                        <thead>
                            <tr>
                                <th>Intervention</th>
                                <th>Devis</th>
                                <th>Facture</th>
                                <th>Rapport</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
            ';

            foreach ($interventions as $intervention) {
                $quoteUrl = $this->adminUrlGenerator
                    ->setRoute('admin_intervention_quote_pdf', [
                        'id' => $intervention->getId(),
                    ])
                    ->generateUrl();

                $invoiceUrl = $this->adminUrlGenerator
                    ->setRoute('admin_intervention_invoice_pdf', [
                        'id' => $intervention->getId(),
                    ])
                    ->generateUrl();

                $reportUrl = $this->adminUrlGenerator
                    ->setRoute('admin_intervention_report_pdf', [
                        'id' => $intervention->getId(),
                    ])
                    ->generateUrl();

                $documentStatus = match ($intervention->getPaymentStatus()) {
                    'paid' => '<span class="badge-status badge-done">● Payée</span>',
                    'pending' => '<span class="badge-status badge-planned">● En attente</span>',
                    'unpaid' => '<span class="badge-status badge-cancelled">● Impayée</span>',
                    default => '<span class="badge-status">● Non défini</span>',
                };

                $html .= '
                    <tr>
                        <td>#'.$intervention->getId().' - '.htmlspecialchars($intervention->getType() ?? '-').'</td>

                        <td>
                            <a href="'.$quoteUrl.'" target="_blank" class="client-view-link">
                                <i class="fa fa-file-pdf"></i>
                                '.($intervention->getQuoteNumber() ?: 'Devis PDF').'
                            </a>
                        </td>

                        <td>
                            <a href="'.$invoiceUrl.'" target="_blank" class="client-view-link">
                                <i class="fa fa-file-invoice"></i>
                                '.($intervention->getInvoiceNumber() ?: 'Facture PDF').'
                            </a>
                        </td>

                        <td>
                            <a href="'.$reportUrl.'" target="_blank" class="client-view-link">
                                <i class="fa fa-file-lines"></i>
                                Rapport PDF
                            </a>
                        </td>
                        <td>'.$documentStatus.'</td>
                    </tr>
                ';
            }

            $html .= '
                        </tbody>
                    </table>
                </div>
            ';

            return $html;
        })
        ->setTemplatePath('admin/field/raw_html.html.twig');

    yield Field::new('clientActivity', 'Activité récente')
        ->onlyOnDetail()
        ->setVirtual(true)
        ->formatValue(function ($value, Client $client) {
            $events = [];

            foreach ($client->getInterventions() as $intervention) {
                if ($intervention->getScheduledAt()) {
                    $events[] = [
                        'date' => $intervention->getScheduledAt(),
                        'label' => '📅 Intervention planifiée',
                        'text' => '#'.$intervention->getId().' - '.$intervention->getType(),
                    ];
                }

                if ($intervention->getCompletedAt()) {
                    $events[] = [
                        'date' => $intervention->getCompletedAt(),
                        'label' => '✅ Intervention terminée',
                        'text' => '#'.$intervention->getId().' - '.$intervention->getType(),
                    ];
                }

                if ($intervention->getInvoiceAt()) {
                    $events[] = [
                        'date' => $intervention->getInvoiceAt(),
                        'label' => '📄 Facture générée',
                        'text' => $intervention->getInvoiceNumber() ?: 'Facture intervention #'.$intervention->getId(),
                    ];
                }

                if ($intervention->getPaidAt()) {
                    $events[] = [
                        'date' => $intervention->getPaidAt(),
                        'label' => '💰 Paiement reçu',
                        'text' => number_format((float) ($intervention->getPrice() ?? 0), 2, ',', ' ').' €',
                    ];
                }
            }

            usort($events, fn ($a, $b) => $b['date'] <=> $a['date']);

            if (empty($events)) {
                return '<span class="text-muted">Aucune activité récente.</span>';
            }

            $html = '<div class="client-activity-list">';

            foreach (array_slice($events, 0, 8) as $event) {
                $html .= '
                    <div class="client-activity-item">
                        <div class="client-activity-date">
                            '.$event['date']->format('d/m/Y H:i').'
                        </div>
                        <div>
                            <strong>'.htmlspecialchars($event['label']).'</strong>
                            <span>'.htmlspecialchars($event['text']).'</span>
                        </div>
                    </div>
                ';
            }

            $html .= '</div>';

            return $html;
        })
        ->setTemplatePath('admin/field/raw_html.html.twig');
    
    yield TextField::new('firstName', 'Prénom')
        ->hideOnDetail();
    yield TextField::new('lastName', 'Nom')
        ->hideOnDetail();
    yield TextField::new('company', 'Entreprise')
        ->hideOnIndex()
        ->hideOnDetail()
        ->formatValue(fn ($value) => $value ?: '-');

    yield EmailField::new('email', 'Email')
        ->hideOnDetail();
    yield TelephoneField::new('phone', 'Téléphone')
        ->hideOnDetail();

    yield ChoiceField::new('clientType', 'Type client')
        ->hideOnDetail()
        ->setChoices([
            'Particulier' => 'Particulier',
            'Professionnel' => 'Professionnel',
            'Association' => 'Association',
        ]);

    yield TextareaField::new('address', 'Adresse')
        ->hideOnIndex()
        ->hideOnDetail()
        ->formatValue(fn ($value) => $value ?: '-');
    yield TextareaField::new('notes', 'Notes internes')
        ->hideOnIndex()
        ->formatValue(fn ($value) => $value ?: '-');

    yield DateTimeField::new('createdAt', 'Créé le')
        ->hideOnForm()
        ->setFormat('dd/MM/yyyy HH:mm');



    
    }
    

    

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter un Client')
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
            });
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients');
    }

}
