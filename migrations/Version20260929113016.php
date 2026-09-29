<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Data-only: pricing texts edited on the web (short dash in breakfast names, "4 noci" in the school package note).
 * Matches exact old values, so rows already edited in admin are left alone.
 */
final class Version20260929113016 extends AbstractMigration
{
    private const CHANGES = [
        ['name', 'Snídaně — dítě do 12 let', 'Snídaně – dítě do 12 let'],
        ['name', 'Snídaně — dospělý', 'Snídaně – dospělý'],
        ['note', 'Pondělí–pátek: 2 990 Kč / dítě', 'Pondělí–pátek (4 noci): 2 990 Kč / dítě'],
    ];

    public function getDescription(): string
    {
        return 'Update meal pricing texts (breakfast names dash, school package note)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CHANGES as [$column, $old, $new]) {
            $this->addSql("UPDATE meal SET {$column} = :new WHERE {$column} = :old", ['old' => $old, 'new' => $new]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::CHANGES as [$column, $old, $new]) {
            $this->addSql("UPDATE meal SET {$column} = :old WHERE {$column} = :new", ['old' => $old, 'new' => $new]);
        }
    }
}
