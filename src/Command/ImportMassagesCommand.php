<?php

namespace App\Command;

use App\Entity\Massage;
use App\Entity\MassagePrice;
use App\Repository\MassageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-massages', description: 'Imports the static massages and their prices into the database.')]
final class ImportMassagesCommand extends Command
{
    /** Each: name, optional note, and a list of [price Kč, minutes]. */
    private const MASSAGES = [
        ['name' => 'Klasická masáž', 'prices' => [[400, 30], [800, 60]]],
        ['name' => 'Zábal z nahřátých obilných zrn', 'note' => '(předehřátí před masáží)', 'prices' => [[250, 20], [450, 40]]],
        ['name' => 'Horké kameny', 'prices' => [[700, 30], [1400, 60]]],
        ['name' => 'Baňkování', 'prices' => [[300, 20]]],
        ['name' => 'Reflexní masáž plosky nohou', 'prices' => [[600, 30], [1200, 60]]],
        ['name' => 'Masáže dětí a těhotných žen', 'prices' => [[400, 30], [800, 60]]],
        ['name' => 'Sportovní masáž', 'prices' => [[500, 30], [1000, 60]]],
        ['name' => 'Akupresurní odblokování krční páteře', 'prices' => [[600, 30], [1200, 60]]],
        ['name' => 'Měkké stabilizační techniky', 'prices' => [[400, 30]]],
        ['name' => 'Energetická masáž Reiki', 'prices' => [[600, 30], [1200, 60]]],
        ['name' => 'Jógovo-rehabilitační cvičení', 'prices' => [[400, 30]]],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MassageRepository $massages,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;
        $position = 1;

        foreach (self::MASSAGES as $data) {
            if (null !== $this->massages->findOneBy(['name' => $data['name']])) {
                $io->writeln(sprintf('skip %s (exists)', $data['name']));
                ++$position;
                continue;
            }

            $massage = (new Massage())
                ->setName($data['name'])
                ->setNote($data['note'] ?? null)
                ->setPosition($position);

            $pricePos = 0;
            foreach ($data['prices'] as [$price, $minutes]) {
                $massage->addPrice(
                    (new MassagePrice())
                        ->setPrice($price)
                        ->setMinutes($minutes)
                        ->setPosition($pricePos++)
                );
            }

            $this->em->persist($massage);
            ++$created;
            ++$position;
        }

        $this->em->flush();
        $io->success(sprintf('Imported %d massages.', $created));

        return Command::SUCCESS;
    }
}
