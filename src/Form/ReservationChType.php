<?php

namespace App\Form;

use App\Entity\ReservationChambre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationChType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idUtilisateur', IntegerType::class, [
                'label' => 'ID Utilisateur',
                'attr' => ['placeholder' => 'Votre ID utilisateur']
            ])
            ->add('idCh', IntegerType::class, [
                'label' => 'ID Chambre',
                'attr' => ['placeholder' => 'ID de la chambre']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['placeholder' => 'AAAA-MM-JJ']
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['placeholder' => 'AAAA-MM-JJ']
            ])
            ->add('nbNuit', IntegerType::class, [
                'label' => 'Nombre de nuits',
                'attr' => ['placeholder' => 'Ex: 3']
            ])
            ->add('prixTotal', TextType::class, [
                'label' => 'Prix total',
                'attr' => ['placeholder' => 'Ex: 275.00']
            ])
            ->add('nbPersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => ['placeholder' => 'Ex: 2']
            ])
            ->add('detailsPrix', TextType::class, [
                'label' => 'Détails du prix',
                'required' => false,
                'attr' => ['placeholder' => 'Détails du calcul du prix']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'Ex: 12345678']
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'attr' => ['placeholder' => 'en_attente, confirmée, annulée']
            ])
            ->add('dateAnnulation', DateType::class, [
                'label' => 'Date d\'annulation',
                'widget' => 'single_text',
                'html5' => false,
                'required' => false,
                'attr' => ['placeholder' => 'AAAA-MM-JJ']
            ])
            ->add('montantRembourse', TextType::class, [
                'label' => 'Montant remboursé',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 100.00']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationChambre::class,
            'csrf_protection' => true,
        ]);
    }
}