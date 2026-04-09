<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Titre', TextType::class, [
                'label' => 'Titre de l\'activité',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Atelier peinture']
            ])
            ->add('Description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('TypeActivite', TextType::class, [
                'label' => 'Type d\'activité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('HeureDebut', TextType::class, [
                'label' => 'Heure de début',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 09:00']
            ])
            ->add('Duree', TextType::class, [
                'label' => 'Durée',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 2h30']
            ])
            ->add('NomAnimateur', TextType::class, [
                'label' => 'Nom de l\'animateur',
                'attr' => ['class' => 'form-control']
            ])
            ->add('CapaciteM', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('Prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'Titre',
                'label' => 'Événement associé',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Choisissez un événement'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
        ]);
    }
}