<?php

namespace App\Form;

use App\Entity\Food;
use App\Entity\FreshUser;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label'=>'Nom de la recette : ',
                'attr'=>['class'=>'input ml-1 bg-white border-gray-500 h-10']
            ])
            ->add('submit',SubmitType::class, [
                'label'=>'Créer',
                'attr'=>['class'=>'btn btn-primary text-white mt-5 w-52']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
