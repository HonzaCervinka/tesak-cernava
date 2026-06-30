<?php

namespace App\Tests\Entity;

use App\Entity\Massage;
use App\Entity\MassagePrice;
use PHPUnit\Framework\TestCase;

final class MassageTest extends TestCase
{
    public function testAddAndRemovePriceMaintainsLink(): void
    {
        $massage = new Massage();
        $price = (new MassagePrice())->setPrice(400)->setMinutes(30);

        $massage->addPrice($price);
        self::assertCount(1, $massage->getPrices());
        self::assertSame($massage, $price->getMassage());

        $massage->removePrice($price);
        self::assertCount(0, $massage->getPrices());
        self::assertNull($price->getMassage());
    }
}
