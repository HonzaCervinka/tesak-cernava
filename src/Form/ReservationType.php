<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('room', EntityType::class, [
                'label' => 'Pokoj',
                'class' => Room::class,
                'choice_label' => 'name',
                'query_builder' => static fn (RoomRepository $r) => $r->createQueryBuilder('room')
                    ->orderBy('room.position', 'ASC')->addOrderBy('room.id', 'ASC'),
                'placeholder' => 'Vyberte pokoj',
            ])
            ->add('guestName', TextType::class, ['label' => 'Jméno hosta'])
            ->add('arrival', DateType::class, [
                'label' => 'Příjezd',
                'widget' => 'single_text',
            ])
            ->add('departure', DateType::class, [
                'label' => 'Odjezd',
                'widget' => 'single_text',
            ])
            ->add('guests', IntegerType::class, ['label' => 'Počet osob', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Telefon', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'required' => false])
            ->add('note', TextareaType::class, ['label' => 'Poznámka', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reservation::class]);
    }
}
