<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserControllerTest extends WebTestCase
{
    private function makeUser(string $email, array $roles): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $user = (new User())->setEmail($email)->setRoles($roles);
            $user->setPassword($hasher->hashPassword($user, 'pw'));
            $em->persist($user);
            $em->flush();
        }

        return $user;
    }

    private function removeUser(string $email): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }
    }

    public function testAnonymousBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users');
        self::assertResponseRedirects('/admin/login');
    }

    public function testNonAdminUserBlockedFromUserManagement(): void
    {
        $client = static::createClient();
        $client->loginUser($this->makeUser('plain-user@example.com', []));
        $client->request('GET', '/admin/users');
        self::assertResponseStatusCodeSame(403);
    }

    public function testRegularUserCanReachOtherAdminSections(): void
    {
        $client = static::createClient();
        $client->loginUser($this->makeUser('plain-user@example.com', []));
        $client->request('GET', '/admin/meals');
        self::assertResponseIsSuccessful();
    }

    public function testAdminCanCreateUserWithPasswordAndRole(): void
    {
        $client = static::createClient();
        $client->loginUser($this->makeUser('users-admin@example.com', ['ROLE_ADMIN']));
        $this->removeUser('created-user@example.com');

        $crawler = $client->request('GET', '/admin/users/new');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="user[_token]"]')->attr('value');

        try {
            $client->request('POST', '/admin/users/new', [
                'user' => [
                    'email' => 'created-user@example.com',
                    'role' => 'ROLE_ADMIN',
                    'plainPassword' => 'secret123',
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/users');

            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->clear();
            $created = $em->getRepository(User::class)->findOneBy(['email' => 'created-user@example.com']);
            self::assertNotNull($created);
            self::assertContains('ROLE_ADMIN', $created->getRoles());

            $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
            self::assertTrue($hasher->isPasswordValid($created, 'secret123'));
        } finally {
            $this->removeUser('created-user@example.com');
        }
    }

    public function testAdminCanResetPasswordOnEdit(): void
    {
        $client = static::createClient();
        $client->loginUser($this->makeUser('users-admin@example.com', ['ROLE_ADMIN']));
        $target = $this->makeUser('editable-user@example.com', []);
        $id = $target->getId();

        try {
            $crawler = $client->request('GET', '/admin/users/'.$id.'/edit');
            self::assertResponseIsSuccessful();
            $token = $crawler->filter('input[name="user[_token]"]')->attr('value');

            $client->request('POST', '/admin/users/'.$id.'/edit', [
                'user' => [
                    'email' => 'editable-user@example.com',
                    'role' => 'ROLE_ADMIN',
                    'plainPassword' => 'newpass456',
                    '_token' => $token,
                ],
            ]);
            self::assertResponseRedirects('/admin/users');

            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->clear();
            $updated = $em->getRepository(User::class)->findOneBy(['email' => 'editable-user@example.com']);
            self::assertContains('ROLE_ADMIN', $updated->getRoles());
            $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
            self::assertTrue($hasher->isPasswordValid($updated, 'newpass456'));
        } finally {
            $this->removeUser('editable-user@example.com');
        }
    }
}
