<?php
namespace App\Form;

use App\Entity\Forum;
use App\Entity\Publication;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label'    => 'Titre',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Commence par une lettre, min. 3 caractères...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 5,
                    'placeholder' => 'Contenu de la publication...',
                ],
            ])
            ->add('forum', EntityType::class, [
                'class'        => Forum::class,
                'choice_label' => 'nom',
                'label'        => 'Forum',
                'required'     => false,
                'placeholder'  => '-- Sélectionnez un forum --',
                'attr'         => ['class' => 'form-control custom-select'],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $form->add('imageFile', FileType::class, [
                'label'    => 'Image (fichier)',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'id'     => 'imgFileInput',
                    'accept' => 'image/jpeg,image/png,image/gif,image/webp',
                    'style'  => 'position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;',
                ],
            ]);

            $form->add('videoFile', FileType::class, [
                'label'    => 'Vidéo (fichier)',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'id'     => 'videoFileInput',
                    'accept' => 'video/mp4,video/webm,video/ogg,video/avi,video/mov',
                    'style'  => 'position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;',
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'is_edit'    => false,
        ]);
    }
}