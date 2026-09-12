<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Web\Widget\CapacityBar;
use PHPUnit\Framework\TestCase;

final class CapacityBarTest extends TestCase
{
    public function testItRendersTheShareOfTheTotal(): void
    {
        $html = (new CapacityBar('Usage', 129747648512, 1073741824000))->render();

        $this->assertStringContainsString('120.8 GiB / 1000.0 GiB (12.1 %)', $html);
        $this->assertStringContainsString('value="12.1"', $html);
        $this->assertStringContainsString('state-ok', $html);
    }

    public function testItCarriesNoInlineStyle(): void
    {
        $html = (new CapacityBar('Usage', 50, 100))->render();

        $this->assertStringNotContainsString('style=', $html);
    }

    public function testItEscalatesAsTheBarFills(): void
    {
        $this->assertStringContainsString('state-ok', (new CapacityBar('t', 70, 100))->render());
        $this->assertStringContainsString('state-warning', (new CapacityBar('t', 80, 100))->render());
        $this->assertStringContainsString('state-critical', (new CapacityBar('t', 95, 100))->render());
    }

    public function testItCapsAtFullEvenWhenOverBooked(): void
    {
        $html = (new CapacityBar('t', 150, 100))->render();

        $this->assertStringContainsString('value="100"', $html);
        $this->assertStringContainsString('state-critical', $html);
    }

    public function testItDeclinesToRenderWithoutAKnownTotal(): void
    {
        $this->assertNull(CapacityBar::tryFor('t', 100, null));
        $this->assertNull(CapacityBar::tryFor('t', 100, 0));
        $this->assertNull(CapacityBar::tryFor('t', null, 100));
        $this->assertInstanceOf(CapacityBar::class, CapacityBar::tryFor('t', '100', '200'));
    }
}
