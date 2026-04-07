<?php
namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType,
    TextareaType,
    NumberType,
    IntegerType,
    FileType,
    ChoiceType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Ex : Tapis berbère, Poterie de Nabeul...'],
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => "Décrivez l'article : matériaux, origine, dimensions...",
                    'rows'        => 4,
                ],
            ])
            ->add('prix', NumberType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Ex : 120'],
            ])
            ->add('stock', IntegerType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Ex : 25'],
            ])
            ->add('poids', IntegerType::class, [
                'label' => false,
                'attr'  => ['placeholder' => 'Ex : 350'],
            ])
            ->add('disponibilite', ChoiceType::class, [
                'label'   => false,
                'choices' => [
                    'En stock' => 1,
                    'Rupture'  => 0,
                ],
            ])
            ->add('image', FileType::class, [
                'label'       => false,
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize'          => '2M',
                        'maxSizeMessage'   => "L'image ne doit pas dépasser 2 Mo.",
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => "Seuls les formats JPG, PNG et WEBP sont acceptés.",
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            // Désactive la validation HTML5 — tout passe par Symfony
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
