<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('creatorId', NumberType::class, ['mapped' => false])
            ->add('content', TextareaType::class, ["label" => "Speak Your Mind..."])
            ->add('tags', HiddenType::class, )
            ->add('sources', HiddenType::class, )
            ->add('tagInput', TextType::class, ["label" => "Add Tags", "required" => false, "mapped" => false])
            ->add('sourceInput', TextType::class, ["label" => "Add Sources", "required" => false, "mapped" => false])
            ->add('submit', SubmitType::class, ["label" => "Post"])

        ;
    }

//    public function configureOptions(OptionsResolver $resolver): void
//    {
//        $resolver->setDefaults([
//            'data_class' => Post::class,
//        ]);
//    }
}
