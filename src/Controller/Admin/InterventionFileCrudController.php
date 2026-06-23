<?php

namespace App\Controller\Admin;

use App\Entity\InterventionFile;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;


class InterventionFileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InterventionFile::class;
    }

    

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('intervention', 'Intervention');

        yield TextField::new('title', 'Titre')
            ->setHelp('Ex : Photo avant nettoyage, capture erreur Windows, rapport diagnostic...');

        yield ChoiceField::new('fileType', 'Type')
            ->setChoices([
                'Photo avant' => 'photo_before',
                'Photo après' => 'photo_after',
                'Capture écran' => 'screenshot',
                'Rapport diagnostic' => 'report',
                'Facture client' => 'invoice',
                'Autre' => 'other',
            ])
            ->renderAsBadges([
                'photo_before' => 'warning',
                'photo_after' => 'success',
                'screenshot' => 'info',
                'report' => 'primary',
                'invoice' => 'secondary',
                'other' => 'light',
            ]);

        yield ImageField::new('fileName', 'Fichier')
            ->setBasePath('/uploads/interventions')
            ->setUploadDir('public/uploads/interventions')
            ->setUploadedFileNamePattern('[year]-[month]-[day]-[slug]-[contenthash].[extension]');

        yield TextareaField::new('notes', 'Notes')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ?: '-');

        yield DateTimeField::new('createdAt', 'Ajouté le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter un fichier')
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
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('intervention');
    }
}
