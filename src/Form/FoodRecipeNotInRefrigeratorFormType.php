<?php

namespace App\Form;

use App\Entity\FoodRecipeNotInRefrigerator;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodRecipeNotInRefrigeratorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité : ',
                'attr' => ['placeholder' => 'Quantité', 'class'=>'input border-gray-500 text-black bg-white mb-5']
            ])
            ->add('unit', TextType::class, [
                'label'=> 'Unité : ',
                'attr' => ['placeholder' => 'Litre', 'class'=>'input border-gray-500 text-black bg-white mb-5', 'required' => false],
                'required'=>false
            ])
            ->add('name', TextType::class, [
                'label'=> 'Nom : ',
                'attr' => ['placeholder : ' => 'Lait', 'class'=>'input border-gray-500 text-black bg-white mb-5']
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodRecipeNotInRefrigerator::class,
        ]);
    }
}
