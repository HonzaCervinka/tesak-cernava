<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Massage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MassageControllerTest extends WebTestCase
{
    private function loginAdmin(KernelBrowser $client): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'massage-admin@example.com']);
        if (!$user) {
            $user = (new User())->setEmail('massage-admin@example.com')->setRoles(['ROLE_ADMIN']);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
            $em->flush();
        }
        $client->loginUser($user);
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/massages');
        self::assertResponseRedirects('/admin/login');
    }

    public function testCreateMassageWithPrices(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existing = $em->getRepository(Massage::class)->findOneBy(['name' => 'Testovací masáž']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $crawler = $client->request('GET', '/admin/massages/new');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="massage[_token]"]')->attr('value');

        try {
            $client->request('POST', '/admin/massages/new', [
                'massage' => [
                    'name' => 'Testovací masáž',
                    'note' => '(test)',
                    'position' => 3,
                    'prices' => [
                        ['price' => 400, 'minutes' => 30],
                        ['price' => 800, 'minutes' => 60],
                    ],
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/massages');

            $em->clear();
            $massage = $em->getRepository(Massage::class)->findOneBy(['name' => 'Testovací masáž']);
            self::assertNotNull($massage);
            self::assertCount(2, $massage->getPrices());
            self::assertSame('(test)', $massage->getNote());
        } finally {
            $em->clear();
            $leftover = $em->getRepository(Massage::class)->findOneBy(['name' => 'Testovací masáž']);
            if ($leftover) {
                $em->remove($leftover);
                $em->flush();
            }
        }
    }
}
