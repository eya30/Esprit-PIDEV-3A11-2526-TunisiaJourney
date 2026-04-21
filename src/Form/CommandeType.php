<?php
namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    IntegerType,
    DateType,
    TextType,
    NumberType,
    ChoiceType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
   // src/Form/CommandeType.php

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
       
        ->add('AdresseLiv', TextType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'Ex : 12 Rue de la Médina, Tunis'],
        ])
        ->add('CodePostal', TextType::class, [
            'label' => false,
            'attr'  => ['placeholder' => 'Ex : 1000'],
        ])
        ->add('ModePaiement', ChoiceType::class, [
            'label'   => false,
            'choices' => [
                'Paiement à la livraison' => 'livraison', // Doit être 'livraison'
              'Carte Bancaire' => 'carte_bancaire',
            ],
        ])
        // ON RETIRE DateC, Statut et Total d'ici car on les gère dans le contrôleur !
    ;
}


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Commande::class]);
    }
}