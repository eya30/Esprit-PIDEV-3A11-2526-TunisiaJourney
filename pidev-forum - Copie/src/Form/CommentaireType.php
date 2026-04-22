<?php
namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, ['label' => 'Commentaire', 'attr' => ['class' => 'form-control', 'rows' => 3]])
            ->add('image', FileType::class, ['label' => 'Image (optionnel)', 'mapped' => false, 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('tags', TextType::class, ['label' => 'Tags', 'required' => false, 'attr' => ['class' => 'form-control']]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Commentaire::class]);
    }
}