<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\ClientCrudController;

class ContactCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }
    
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm()
            ->hideOnDetail()
            ->hideOnIndex();
        yield TextField::new('fullName', 'Nom / Prénom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('subject', 'Sujet');
        yield TextField::new('message', 'Message');
        yield DateTimeField::new('createdAt', 'Crée le');
        yield ChoiceField::new('status', 'Statut')
        ->setChoices([
            'Nouveau' => 'new',
            'Contacté' => 'contacted',
            'En cours' => 'in_progress',
            'Converti en client' => 'converted',
            'Archivé' => 'archived',
        ]);

        
    }
    
    public function configureActions(Actions $actions): Actions
    {
        $convertToClient = Action::new('convertToClient', 'Convertir en client', 'fa fa-user-plus')
            ->linkToRoute('admin_contact_convert_client', function (Contact $contact): array {
                return [
                    'id' => $contact->getId(),
                ];
            })
            ->displayIf(function (Contact $contact) {
                return $contact->getStatus() !== 'converted';
            });
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $convertToClient)
            ->add(Crud::PAGE_DETAIL, $convertToClient)

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter un email')
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

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter un contact')
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
    #[Route('/admin/contact/{id}/convert-client', name: 'admin_contact_convert_client')]
    public function convertToClient(
        Contact $contact,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        $existingClient = $entityManager
            ->getRepository(Client::class)
            ->findOneBy(['email' => $contact->getEmail()]);

        if ($existingClient) {
            $this->addFlash('warning', 'Un client existe déjà avec cette adresse email.');

            return $this->redirectToRoute('admin', [
                'crudControllerFqcn' => self::class,
                'crudAction' => 'index',
            ]);
        }

        $fullName = trim((string) $contact->getFullName());
        $parts = explode(' ', $fullName, 2);

        $client = new Client();
        $client->setFirstName($parts[0] ?? '');
        $client->setLastName($parts[1] ?? '');
        $client->setEmail($contact->getEmail());
        $client->setPhone($contact->getPhone());
        $client->setClientType($contact->getClientType() ?: 'Particulier');

        $client->setNotes(
            "Client créé depuis une demande de contact.\n\n" .
            "Service demandé : " . ($contact->getService() ?: 'Non précisé') . "\n" .
            "Budget : " . ($contact->getBudget() ?: 'Non précisé') . "\n" .
            "Urgence : " . ($contact->getUrgency() ?: 'Non précisée') . "\n\n" .
            "Message initial :\n" . $contact->getMessage()
        );

        $contact->setStatus('converted');

        $entityManager->persist($client);
        $entityManager->flush();

        $this->addFlash('success', 'Le contact a bien été converti en client.');

        $url = $adminUrlGenerator
            ->setController(ClientCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
