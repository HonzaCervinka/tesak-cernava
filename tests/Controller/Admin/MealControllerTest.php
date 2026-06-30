<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Meal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MealControllerTest extends WebTestCase
{
    private function loginAdmin(KernelBrowser $client): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'meal-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('meal-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
            $em->flush();
        }
        $client->loginUser($user);
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/meals');
        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateHighlightedMealWithFeatures(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existing = $em->getRepository(Meal::class)->findOneBy(['name' => 'Testovací balíček']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $crawler = $client->request('GET', '/admin/meals/new');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="meal[_token]"]')->attr('value');

        try {
            $client->request('POST', '/admin/meals/new', [
                'meal' => [
                    'name' => 'Testovací balíček',
                    'price' => 748,
                    'priceUnit' => '/dítě/den',
                    'note' => 'Týdně 2 990 Kč',
                    'highlighted' => '1',
                    'position' => 9,
                    'features' => ['3× teplé jídlo', 'Pitný režim'],
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/meals');

            $em->clear();
            $meal = $em->getRepository(Meal::class)->findOneBy(['name' => 'Testovací balíček']);
            self::assertNotNull($meal);
            self::assertTrue($meal->isHighlighted());
            self::assertSame(748, $meal->getPrice());
            self::assertSame(['3× teplé jídlo', 'Pitný režim'], $meal->getFeatures());
        } finally {
            $em->clear();
            $leftover = $em->getRepository(Meal::class)->findOneBy(['name' => 'Testovací balíček']);
            if ($leftover) {
                $em->remove($leftover);
                $em->flush();
            }
        }
    }
}
