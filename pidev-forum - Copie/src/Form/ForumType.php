<?php
namespace App\Form;

use App\Entity\Forum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du forum',
                'required' => true,
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: Aventures Europe',
                ],
            ])
            ->add('theme', ChoiceType::class, [
                'label'       => 'Thème',
                'required'    => true,
                'placeholder' => '-- Sélectionnez un thème --',
                'choices'     => [
                    'Voyage entre amis'      => 'Voyage entre amis',
                    'Voyage de noces'        => 'Voyage de noces',
                    'Voyage en famille'      => 'Voyage en famille',
                    'Voyage solo'            => 'Voyage solo',
                    'Voyage culturel'        => 'Voyage culturel',
                    'Voyage aventure'        => 'Voyage aventure',
                    'Voyage gastronomique'   => 'Voyage gastronomique',
                    'Voyage bien-être'       => 'Voyage bien-être',
                    'Voyage écotourisme'     => 'Voyage écotourisme',
                    'Voyage road trip'       => 'Voyage road trip',
                    'Voyage balnéaire'       => 'Voyage balnéaire',
                    'Voyage sportif'         => 'Voyage sportif',
                ],
                'attr' => ['class' => 'form-control custom-select'],
            ])
            ->add('status', ChoiceType::class, [
                'label'       => 'Statut',
                'required'    => true,
                'placeholder' => '-- Sélectionnez un statut --',
                'choices'     => [
                    'Actif'   => 'actif',
                    'Inactif' => 'inactif',
                ],
                'attr' => ['class' => 'form-control custom-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Forum::class,
        ]);
    }
}