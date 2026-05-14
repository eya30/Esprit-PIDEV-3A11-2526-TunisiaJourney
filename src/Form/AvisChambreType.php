<?php
// src/Form/AvisChambreType.php

namespace App\Form;

use App\Entity\AvisChambre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisChambreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('noteConfort', ChoiceType::class, [
                'label' => '🛏️ Confort des chambres',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'star-rating']
            ])
            ->add('noteServices', ChoiceType::class, [
                'label' => '📶 Services (wifi, piscine, spa...)',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('noteEquipements', ChoiceType::class, [
                'label' => '📺 Équipements',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('noteProprete', ChoiceType::class, [
                'label' => '🧹 Propreté générale',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('notePersonnel', ChoiceType::class, [
                'label' => '👨‍💼 Service personnel / Accueil',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('noteEmplacement', ChoiceType::class, [
                'label' => '📍 Emplacement',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('noteRestauration', ChoiceType::class, [
                'label' => '🍽️ Restauration',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('notePrixQualite', ChoiceType::class, [
                'label' => '💸 Rapport qualité/prix',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('noteCalme', ChoiceType::class, [
                'label' => '🔇 Calme / bruit',
                'choices' => $this->getChoices(),
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => '💬 Votre commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Partagez votre expérience...'
                ]
            ]);
    }

<<<<<<< HEAD
    /**
     * @return array<string, int>
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function getChoices(): array
    {
        return [
            '⭐ 1 étoile' => 1,
            '⭐⭐ 2 étoiles' => 2,
            '⭐⭐⭐ 3 étoiles' => 3,
            '⭐⭐⭐⭐ 4 étoiles' => 4,
            '⭐⭐⭐⭐⭐ 5 étoiles' => 5
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvisChambre::class,
        ]);
    }
}