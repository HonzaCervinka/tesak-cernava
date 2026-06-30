<?php

namespace App\Form;

use App\Entity\Massage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MassageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název'])
            ->add('note', TextType::class, ['label' => 'Poznámka (volitelná)', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Pořadí'])
            ->add('prices', CollectionType::class, [
                'label' => 'Ceny',
                'entry_type' => MassagePriceType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Massage::class]);
    }
}
