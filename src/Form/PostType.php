<?php

namespace App\Form;

use App\Entity\Post;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
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
            ->add('content', CKEditorType::class, [
                "label" => "Speak Your Mind...",
                'config' => [
                    'extraPlugins' => 'codesnippet',
                ],
            ])
            ->add('tags', HiddenType::class, )
            ->add('isDraft', HiddenType::class, ["mapped" => false])
            ->add('reply', HiddenType::class, ["mapped" => false])
            ->add('tagInput', TextType::class, ["label" => "Add Tags", "required" => false, "mapped" => false])
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
