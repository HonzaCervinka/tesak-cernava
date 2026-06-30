<?php

namespace App\Command;

use App\Entity\Meal;
use App\Repository\MealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-meals', description: 'Imports the static dining prices into the database.')]
final class ImportMealsCommand extends Command
{
    private const MEALS = [
        ['name' => 'Snídaně — dítě do 12 let', 'price' => 130],
        ['name' => 'Snídaně — dospělý', 'price' => 160],
        [
            'name' => 'Skupiny dětí',
            'price' => 748,
            'unit' => '/dítě/den',
            'highlighted' => true,
            'note' => 'Pondělí–pátek: 2 990 Kč / dítě',
            'features' => [
                '3× denně teplé jídlo',
                '2× denně svačina',
                'Pitný režim po celý den',
                'Na 10 dětí 1 dospělý zdarma (při plném obsazení)',
            ],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MealRepository $meals,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;
        $position = 1;

        foreach (self::MEALS as $data) {
            if (null !== $this->meals->findOneBy(['name' => $data['name']])) {
                $io->writeln(sprintf('skip %s (exists)', $data['name']));
                ++$position;
                continue;
            }

            $meal = (new Meal())
                ->setName($data['name'])
                ->setPrice($data['price'])
                ->setPriceUnit($data['unit'] ?? null)
                ->setNote($data['note'] ?? null)
                ->setHighlighted($data['highlighted'] ?? false)
                ->setFeatures($data['features'] ?? [])
                ->setPosition($position);

            $this->em->persist($meal);
            ++$created;
            ++$position;
        }

        $this->em->flush();
        $io->success(sprintf('Imported %d meals.', $created));

        return Command::SUCCESS;
    }
}
