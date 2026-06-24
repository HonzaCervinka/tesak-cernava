<?php

namespace App\Tests\Command;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportRoomsCommandTest extends KernelTestCase
{
    public function testImportIsIdempotent(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Clear any rooms left from previous test runs or manual seeding
        foreach ($em->getRepository(Room::class)->findAll() as $room) {
            $em->remove($room);
        }
        $em->flush();
        $em->clear();

        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:import-rooms'));

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        $countAfterFirst = \count($em->getRepository(Room::class)->findAll());
        self::assertSame(8, $countAfterFirst);

        $doubleShared = $em->getRepository(Room::class)->findOneBy(['slug' => 'double-shared']);
        self::assertNotNull($doubleShared, 'double-shared room must exist after import');
        self::assertGreaterThan(0, $doubleShared->getImages()->count(), 'double-shared must have images (photos live in double-shared-bath dir)');

        $em->clear();
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertSame(8, \count($em->getRepository(Room::class)->findAll()), 'second run creates no duplicates');
    }
}
