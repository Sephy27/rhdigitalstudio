<?php

namespace App\Controller\Admin;

use App\Entity\Galerie;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Form\Type\VichFileType;


class GalerieCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Galerie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Média')
            ->setEntityLabelInPlural('Galerie')
            ->setSearchFields(['name'])
            ->setAutofocusSearch()
            ->setSearchMode(SearchMode::ALL_TERMS)
            ->setPaginatorPageSize(12)
            ->setPaginatorRangeSize(2)
            ->setPaginatorUseOutputWalkers(true)
            ->setPaginatorFetchJoinCollection(true)
            // tri par défaut : position puis date
            ->setDefaultSort([
                'position'  => 'ASC',
                'createdAt' => 'DESC',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')
            ->hideOnForm()
            ->hideOnDetail()
            ->hideOnIndex();

        $name = TextField::new('name', 'Nom');

        $position = IntegerField::new('position', 'Ordre')
            ->onlyOnIndex()
            ->setHelp('Plus le nombre est petit, plus la photo apparaît en haut.');

        // Champ fichier (upload) – uniquement dans les formulaires
        $fileField = Field::new('featuredImageFile', 'Fichier (image ou vidéo)')
        ->setFormType(VichFileType::class)
        ->setFormTypeOptions([
            'allow_delete' => false,
            'download_uri' => false,
            'required' => $pageName === Crud::PAGE_NEW,
        ])
        ->onlyOnForms();
            

        // Aperçu image/vidéo en index
        $previewIndex = TextField::new('featuredImage', 'Aperçu')
            ->onlyOnIndex()
            ->setTemplatePath('admin/fields/media_preview.html.twig');

        // Aperçu image/vidéo en show
        $previewDetail = TextField::new('featuredImage', 'Aperçu')
            ->onlyOnDetail()
            ->setTemplatePath('admin/fields/media_preview.html.twig');

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $name,
                $position,
                $previewIndex,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $name,
                $position,
                $previewDetail,
                $createdAt,
            ];
        }

        // Formulaires NEW / EDIT
        return [
            $name,
            $position,
            $fileField,
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        // Bouton "Monter"
        $moveUp = Action::new('moveUp', '↑', 'fa fa-arrow-up')
            ->linkToCrudAction('moveUp')
            ->setHtmlAttributes([
                'title' => 'Monter',
            ]);

        // Bouton "Descendre"
        $moveDown = Action::new('moveDown', '↓', 'fa fa-arrow-down')
            ->linkToCrudAction('moveDown')
            ->setHtmlAttributes([
                'title' => 'Descendre',
            ]);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $moveUp)
            ->add(Crud::PAGE_INDEX, $moveDown)

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Ajouter un Média')
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

    /**
     * Monte le média d’un cran (échange les positions).
     */
    public function moveUp(AdminContext $context): Response
    {
    $id = $context->getRequest()->query->get('entityId');
    if (!$id) {
        return $this->redirectToIndex();
    }

    $repo = $this->em->getRepository(Galerie::class);
    $galerie = $repo->find($id);

    if (!$galerie instanceof Galerie) {
        return $this->redirectToIndex();
    }

    $currentPos = $galerie->getPosition() ?? 0;
    $targetPos  = $currentPos - 1;

    if ($targetPos < 0) {
        return $this->redirectToIndex();
    }

    // Élément qui occupe déjà la position cible
    $other = $repo->findOneBy(['position' => $targetPos]);
    if ($other instanceof Galerie) {
        $other->setPosition($currentPos);
    }

    $galerie->setPosition($targetPos);
    $this->em->flush();

    return $this->redirectToIndex();
}

    /**
     * Descend le média d’un cran (échange les positions).
     */
    public function moveDown(AdminContext $context): Response
{
    $id = $context->getRequest()->query->get('entityId');
    if (!$id) {
        return $this->redirectToIndex();
    }

    $repo = $this->em->getRepository(Galerie::class);
    $galerie = $repo->find($id);

    if (!$galerie instanceof Galerie) {
        return $this->redirectToIndex();
    }

    $currentPos = $galerie->getPosition() ?? 0;
    $targetPos  = $currentPos + 1;

    $other = $repo->findOneBy(['position' => $targetPos]);
    if ($other instanceof Galerie) {
        $other->setPosition($currentPos);
    }

    $galerie->setPosition($targetPos);
    $this->em->flush();

    return $this->redirectToIndex();
}

private function redirectToIndex(): Response
{
    $url = $this->adminUrlGenerator
        ->setController(self::class)
        ->setAction(Action::INDEX)
        ->generateUrl();

    return $this->redirect($url);
}

private function getNextPosition(): int
{
    $qb = $this->em->createQueryBuilder()
        ->select('COALESCE(MAX(g.position), -1)')
        ->from(Galerie::class, 'g');

    $max = (int) $qb->getQuery()->getSingleScalarResult();

    // ex : si aucune ligne -> -1, donc prochaine position = 0
    return $max + 1;
}




public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
{
    if (!$entityInstance instanceof Galerie) {
        parent::persistEntity($entityManager, $entityInstance);
        return;
    }

    // Si c'est une nouvelle Galerie, on lui attribue la prochaine position libre
    if ($entityInstance->getId() === null) {
        $entityInstance->setPosition($this->getNextPosition());
    }

    parent::persistEntity($entityManager, $entityInstance);
}


}
