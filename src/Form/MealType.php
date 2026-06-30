<?php

namespace App\Form;

use App\Entity\Meal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MealType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název'])
            ->add('price', IntegerType::class, ['label' => 'Cena (Kč)'])
            ->add('priceUnit', TextType::class, ['label' => 'Jednotka ceny (např. /dítě/den)', 'required' => false])
            ->add('note', TextType::class, ['label' => 'Poznámka (např. týdenní cena)', 'required' => false])
            ->add('highlighted', CheckboxType::class, ['label' => 'Zvýraznit jako kartu (balíček)', 'required' => false])
            ->add('features', CollectionType::class, [
                'label' => 'Odrážky (jen u karty)',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'required' => false,
            ])
            ->add('position', IntegerType::class, ['label' => 'Pořadí']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Meal::class]);
    }
}
