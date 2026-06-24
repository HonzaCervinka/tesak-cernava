<?php

namespace App\Command;

use App\Entity\Image;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Service\Image\ImageStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-rooms', description: 'Imports the 8 static rooms and their existing photos into the database.')]
final class ImportRoomsCommand extends Command
{
    private const ROOMS = [
        ['slug' => 'double-shared', 'imageDir' => 'double-shared-bath', 'name' => 'Dvoulůžkový pokoj', 'price' => 800, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 1,
         'features' => ['2 lůžka (možnost přistýlky)', 'Sdílená koupelna, balkon', 'Výhled do přírody', 'Sdílená kuchyňka na chodbě']],
        ['slug' => 'family-large', 'name' => 'Rodinný pokoj', 'price' => 2100, 'from' => false, 'unit' => '/ noc', 'position' => 2,
         'features' => ['Vhodný pro rodiny s dětmi', 'Vlastní koupelna se sprchovým koutem', 'Postýlka do 2 let zdarma', 'Přístup na zahradu s hřištěm']],
        ['slug' => 'apartment-ground', 'name' => 'Velký apartmán', 'price' => 590, 'from' => true, 'unit' => '/ osoba / noc', 'position' => 3,
         'features' => ['Až 9 osob', 'Vlastní kuchyňka i koupelna', 'Ideální pro rodiny a malé skupiny', 'Přízemí s vlastním vstupem']],
        ['slug' => 'single', 'name' => 'Jednolůžkové obsazení', 'price' => 650, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 4,
         'features' => ['Ubytování pro 1 osobu', 'Koupelna, ručníky, WC', 'Balkon s výhledem do přírody']],
        ['slug' => 'double-ensuite', 'name' => 'Pokoj s manželskou postelí', 'price' => 1100, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 5,
         'features' => ['Manželská postel, TV', 'Vlastní koupelna, balkon', 'Společná kuchyňka']],
        ['slug' => 'bunk-4bed', 'name' => 'Čtyřlůžkový pokoj s palandou', 'price' => 1890, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 6,
         'features' => ['Palanda + 2 lůžka (až 4 osoby)', 'Vlastní koupelna, balkon', 'Společná kuchyňka']],
        ['slug' => 'family-double', 'name' => 'Rodinný dvoupokoj', 'price' => 2490, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 7,
         'features' => ['Manž. postel + palanda + 2 lůžka, TV', 'Vlastní koupelna, balkon', 'Dva propojené pokoje']],
        ['slug' => 'apartment-2bedroom', 'name' => 'Apartmán 2 ložnice', 'price' => 3990, 'from' => false, 'unit' => '/ noc (2+ noci)', 'position' => 8,
         'features' => ['Až 11 osob, 2× manž. postel, palanda', 'TV, kuchyňský kout, koupelna, balkon', '2× rozkládací gauč']],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RoomRepository $rooms,
        private readonly ImageStorageInterface $storage,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (self::ROOMS as $data) {
            if (null !== $this->rooms->findOneBy(['slug' => $data['slug']])) {
                $io->writeln(sprintf('skip %s (exists)', $data['slug']));
                continue;
            }

            $room = (new Room())
                ->setSlug($data['slug'])
                ->setName($data['name'])
                ->setFeatures($data['features'])
                ->setPrice($data['price'])
                ->setPriceFrom($data['from'])
                ->setPriceUnit($data['unit'])
                ->setPosition($data['position']);
            $this->em->persist($room);

            $imageDir = $data['imageDir'] ?? $data['slug'];
            $sourceDir = $this->projectDir.'/assets/images/rooms/'.$imageDir;
            $position = 0;
            if (is_dir($sourceDir)) {
                foreach (glob($sourceDir.'/*.webp') ?: [] as $path) {
                    $contents = file_get_contents($path);
                    if (false === $contents) {
                        $io->warning(sprintf('Could not read file, skipping: %s', $path));
                        continue;
                    }
                    $webPath = $this->storage->save($contents, 'rooms/'.$imageDir.'/'.basename($path));
                    $image = (new Image())
                        ->setFilename($webPath)
                        ->setThumbnail($webPath)
                        ->setOriginalName(basename($path))
                        ->setPosition($position);
                    if (0 === $position) {
                        $image->setIsMain(true);
                    }
                    $room->addImage($image);
                    $this->em->persist($image);
                    ++$position;
                }
            }
            ++$created;
        }

        $this->em->flush();
        $io->success(sprintf('Imported %d rooms.', $created));

        return Command::SUCCESS;
    }
}
