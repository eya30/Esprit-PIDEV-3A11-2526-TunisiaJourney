<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du produit',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Tapis berbère artisanal'
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez votre produit...', 
                    'rows' => 4
                ],
                'required' => false,
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => Produit::CATEGORIES,
                'placeholder' => '-- Choisir une catégorie --',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => '0', 
                    'min' => 0
                ],
                'required' => true,
            ])
            ->add('poids', IntegerType::class, [
                'label' => 'Poids (g)',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => '0', 
                    'min' => 0
                ],
                'required' => false,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => '0.00', 
                    'step' => '0.01'
                ],
                'required' => true,
                'scale' => 2,
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible à la vente',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
            ])
        ; // <--- Le point-virgule ne doit être qu'ici, à la toute fin !
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Produit::class]);
    }
}