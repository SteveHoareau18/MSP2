<?php

namespace App\Form;

use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RefrigeratorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label'=>'Nom du frigo : ',
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
            'data_class' => Refrigerator::class,
        ]);
    }
}
