<?php

namespace App\Form;

use App\Entity\Galerie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

class AddGalerieFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('featuredImageFile', VichFileType::class,[
                'label' => 'Fichier (image ou vidéo)',
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '50M',
                        mimeTypes: [
                            // Images
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                            // Vidéos
                            'video/mp4',
                            'video/quicktime',
                            'video/x-msvideo', // avi
                        ],
                        mimeTypesMessage: 'Veuillez téléverser une image (JPG, PNG, WEBP, GIF) ou une vidéo (MP4, MOV, AVI) valide.'
                    ),
                ],
            ])

            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Galerie::class,
        ]);
    }
}
