<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název'])
            ->add('slug', TextType::class, ['label' => 'Slug (klíč galerie)'])
            ->add('description', TextareaType::class, ['label' => 'Popis', 'required' => false])
            ->add('features', CollectionType::class, [
                'label' => 'Vlastnosti',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('price', IntegerType::class, ['label' => 'Cena (Kč)', 'required' => false])
            ->add('priceFrom', CheckboxType::class, ['label' => 'Cena "od"', 'required' => false])
            ->add('priceUnit', TextType::class, ['label' => 'Jednotka ceny', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Pořadí']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Room::class]);
    }
}
