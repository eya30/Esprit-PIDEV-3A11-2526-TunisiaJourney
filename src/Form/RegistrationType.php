<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Calcul de l'année max autorisée (il faut avoir ≥ 18 ans)
        $maxYear = (int)(new \DateTime())->format('Y') - 18;

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Votre nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Votre prénom'],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'exemple@email.com'],
            ])
            ->add('telephone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'attr'     => ['placeholder' => '06XXXXXXXX'],
            ])
            ->add('dateNaissance', DateType::class, [
                // ── Champ mappé directement sur $dateNaissance de l'entité ──
                // Plus besoin de mapped:false ni de parsing manuel dans le contrôleur.
                'label'    => 'Date de naissance',
                'required' => false,
                'widget'   => 'single_text',   // rend un <input type="date"> → calendrier natif du navigateur
                'html5'    => true,
                'attr'     => [
                    // Empêche de saisir une date trop récente directement dans l'input
                    'max' => (new \DateTime("-18 years"))->format('Y-m-d'),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => ['placeholder' => 'Votre adresse'],
            ])
            ->add('mot_de_passe', RepeatedType::class, [
                'type'           => PasswordType::class,
                'mapped'         => false,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'second_options' => [
                    'label' => 'Confirmer',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Length([
                        'min'        => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}