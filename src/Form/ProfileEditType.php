<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Ce form type n'est plus utilisé par ProfileController.
 * La validation est entièrement gérée par les Assert de l'entité User
 * + ValidatorInterface dans le contrôleur (formulaire HTML natif sans préfixe).
 */
class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label'      => 'Nom',
                'required'   => true,
                'empty_data' => '',
                'attr'       => ['placeholder' => 'Votre nom'],
            ])
            ->add('prenom', TextType::class, [
                'label'      => 'Prénom',
                'required'   => true,
                'empty_data' => '',
                'attr'       => ['placeholder' => 'Votre prénom'],
            ])
            ->add('telephone', TextType::class, [
                'label'      => 'Téléphone',
                'required'   => true,
                'empty_data' => '',
                'attr'       => ['placeholder' => '06XXXXXXXX'],
            ])
            ->add('dateNaissance', DateType::class, [
                'label'    => 'Date de naissance',
                'required' => true,
                'widget'   => 'single_text',
                'html5'    => true,
                'attr'     => [
                    'max' => (new \DateTime('-18 years'))->format('Y-m-d'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'      => 'Email',
                'required'   => true,
                'empty_data' => '',
                'attr'       => ['placeholder' => 'votre@email.com'],
            ])
            ->add('adresse', TextType::class, [
                'label'      => 'Adresse',
                'required'   => true,
                'empty_data' => '',
                'attr'       => ['placeholder' => 'Votre adresse'],
            ]);
        // Toutes les contraintes sont dans User.php via #[Assert\...]
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'profile_edit',
        ]);
    }
}