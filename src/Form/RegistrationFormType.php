<?php

namespace App\Form;

use App\Entity\FreshUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class,[
                'label'=>'E-mail : ',
                'attr'=>['class'=>'ml-1 input bg-white border-gray-500']
            ])
            ->add("firstname", TextType::class, [
                'label'=>'Prénom : ',
                'attr'=>['class'=>'ml-1 input bg-white border-gray-500 mt-5']
            ])
            ->add("name", TextType::class, [
                'label'=>'Nom : ',
                'attr'=>['class'=>'ml-1 input bg-white border-gray-500 mt-5']
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label'=>'Accepter les conditions d\'utilisation',
                'attr'=>['class'=>'ml-1 border-gray-500 checkbox mt-5'],
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label'=>'Mot de passe : ',
                'mapped' => false,
                'attr' => ['class'=>'ml-1 input bg-white border-gray-500 mt-5','autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Notre application est sécurisée, ajouté un mot de passe !',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FreshUser::class,
        ]);
    }
}
