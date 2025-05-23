<?php

namespace App\Form;

use App\Dto\UserDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, EmailType, PasswordType, ChoiceType, CheckboxType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isCreate = $options['is_create'];
        $isAdmin = $options['is_admin'];

        $builder
            ->add('firstname', TextType::class, ['label' => 'Prénom'])
            ->add('lastname', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => ['Homme' => 'M', 'Femme' => 'F'],
                'placeholder' => 'Choisir...',
            ])
            ->add('encrypte', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => $isCreate,
                'mapped' => true,
                'empty_data' => null,
            ])
            ->add('rgpd', CheckboxType::class, [
                'label' => 'J’accepte les conditions',
                'required' => true,
            ]);

        if ($isAdmin) {
            $builder->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'mapped' => true,
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDto::class,
            'is_create' => true,
            'is_admin' => false,
        ]);
    }
}
