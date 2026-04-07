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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Quantite', IntegerType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Ex : 2', 'min' => 1],
            ])
            ->add('DateC', DateType::class, [
                'label'  => false,
                'widget' => 'single_text',
            ])
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
                    'Carte bancaire'  => 'Carte bancaire',
                    'Virement'        => 'Virement',
                    'Paiement à la livraison' => 'Paiement à la livraison',
                ],
            ])
            ->add('Statut', ChoiceType::class, [
                'label'   => false,
                'choices' => [
                    'En attente'  => 'En attente',
                    'Confirmée'   => 'Confirmée',
                    'Expédiée'    => 'Expédiée',
                    'Livrée'      => 'Livrée',
                    'Annulée'     => 'Annulée',
                ],
            ])
            ->add('Total', NumberType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Calculé automatiquement', 'readonly' => true],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Commande::class]);
    }
}
