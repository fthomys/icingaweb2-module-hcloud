<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Check;

use Icinga\Module\Hcloud\Check\CheckResult;
use PHPUnit\Framework\TestCase;

final class CheckResultTest extends TestCase
{
    public function testAnOkResultRendersPluginOutput(): void
    {
        $result = CheckResult::ok('all good');

        $this->assertSame(CheckResult::OK, $result->getState());
        $this->assertSame('OK - all good', $result->render());
    }

    public function testPerfDataIsAppendedAfterAPipe(): void
    {
        $output = CheckResult::ok('fine')
            ->addPerfData('servers', 12)
            ->addPerfData('failed', 0, 1, 5)
            ->render();

        $this->assertSame("OK - fine | 'servers'=12;; 'failed'=0;1;5", $output);
    }

    public function testCriticalOutranksWarningRegardlessOfOrder(): void
    {
        $a = CheckResult::ok('x')->raiseTo(CheckResult::WARNING)->raiseTo(CheckResult::CRITICAL);
        $b = CheckResult::ok('x')->raiseTo(CheckResult::CRITICAL)->raiseTo(CheckResult::WARNING);

        $this->assertSame(CheckResult::CRITICAL, $a->getState());
        $this->assertSame(CheckResult::CRITICAL, $b->getState());
    }

    public function testUnknownRanksBelowWarningButAboveOk(): void
    {
        $this->assertSame(
            CheckResult::WARNING,
            CheckResult::ok('x')->raiseTo(CheckResult::UNKNOWN)->raiseTo(CheckResult::WARNING)->getState()
        );

        $this->assertSame(
            CheckResult::UNKNOWN,
            CheckResult::ok('x')->raiseTo(CheckResult::UNKNOWN)->getState()
        );
    }

    public function testDetailsAreRenderedOnFollowingLines(): void
    {
        $output = CheckResult::ok('summary')
            ->addDetail('first')
            ->addDetail('second')
            ->render();

        $this->assertSame("OK - summary\nfirst\nsecond", $output);
    }

    public function testFloatPerfDataDropsTrailingZeroes(): void
    {
        $this->assertStringContainsString("'usage'=12.5;;", CheckResult::ok('x')->addPerfData('usage', 12.5)->render());
        $this->assertStringContainsString("'usage'=12;;", CheckResult::ok('x')->addPerfData('usage', 12.0)->render());
    }
}
