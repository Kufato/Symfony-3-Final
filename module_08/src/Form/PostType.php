<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;  
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    // buildForm() is the method used to define the form fields.
    // $builder is the tool used to add fields.
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class);
            // We do NOT add "created" as it is handled automatically in the Post entity's constructor
        ;
    }

    // configureOptions() links this form to the Post entity.
    // When the form is submitted, Symfony will automatically populate a Post object with the data entered by the user.
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
