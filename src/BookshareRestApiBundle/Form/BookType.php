<?php

namespace BookshareRestApiBundle\Form;

use BookshareRestApiBundle\Entity\Subcategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('author', TextType::class)
            ->add('description', TextType::class)
            ->add('publisher', TextType::class)
            ->add('datePublished', TextType::class)
            ->add('imageURL', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }
}
