<?php

namespace App\Form;

use App\Entity\AvisAct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class AvisActType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── nom supprimé : rempli automatiquement depuis la session ──
            ->add('note', HiddenType::class, [
                'label'    => false,
                'required' => false,
            ])
            ->add('commentaire', TextareaType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Partagez votre expérience...',
                    'rows'        => 4,
                    'class'       => 'form-control',
                ],
            ])
            ->add('imageFile', VichImageType::class, [
                'label'        => false,
                'required'     => false,
                'allow_delete' => false,
                'download_uri' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AvisAct::class]);
    }
}