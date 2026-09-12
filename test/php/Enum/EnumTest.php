<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Enum;

use Icinga\Module\Hcloud\Enum\HasLabel;
use Icinga\Module\Hcloud\Enum\PrimaryIpAssigneeType;
use Icinga\Module\Hcloud\Enum\ServerStatus;
use Icinga\Module\Hcloud\Enum\TargetHealthStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

final class EnumTest extends TestCase
{
    private const ENUM_DIR = __DIR__ . '/../../../library/Hcloud/Enum';

    /**
     * @return list<array{class-string}>
     */
    public static function enumProvider(): array
    {
        $cases = [];

        foreach ((array) scandir(self::ENUM_DIR) as $entry) {
            if (! is_string($entry) || ! str_ends_with($entry, '.php')) {
                continue;
            }

            $class = 'Icinga\\Module\\Hcloud\\Enum\\' . basename($entry, '.php');
            if (enum_exists($class)) {
                $cases[] = [$class];
            }
        }

        return $cases;
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('enumProvider')]
    public function testEveryEnumIsBackedByStringAndLabelled(string $class): void
    {
        $this->assertTrue(is_subclass_of($class, HasLabel::class), $class . ' does not implement HasLabel.');

        $reflection = new ReflectionEnum($class);
        $this->assertSame('string', (string) $reflection->getBackingType(), $class . ' is not backed by string.');

        $this->assertTrue(
            method_exists($class, 'tryFromValue'),
            $class . ' is missing tryFromValue().'
        );
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('enumProvider')]
    public function testEveryCaseHasALabelAndACssClass(string $class): void
    {
        foreach ($class::cases() as $case) {
            $this->assertInstanceOf(HasLabel::class, $case);
            $this->assertNotSame('', $case->label(), $class . '::' . $case->name . ' has an empty label.');
            $this->assertNotSame('', $case->cssClass(), $class . '::' . $case->name . ' has an empty CSS class.');
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('enumProvider')]
    public function testUnknownValuesResolveToNullInsteadOfBeingNormalised(string $class): void
    {
        $this->assertNull($class::tryFromValue(null));
        $this->assertNull($class::tryFromValue('a-value-hetzner-never-returns'));
        $this->assertNull($class::tryFromValue(''));
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('enumProvider')]
    public function testEveryCaseRoundTripsThroughItsValue(string $class): void
    {
        foreach ($class::cases() as $case) {
            $this->assertSame($case, $class::tryFromValue($case->value));
        }
    }

    public function testPrimaryIpAssigneeTypeAcceptsUnassigned(): void
    {
        $this->assertSame(
            PrimaryIpAssigneeType::Unassigned,
            PrimaryIpAssigneeType::tryFromValue('unassigned'),
            'The API returns "unassigned" since 2026-08-01 even though the spec enum omits it.'
        );
    }

    public function testServerStatusHealthReflectsRunningOnly(): void
    {
        $this->assertTrue(ServerStatus::Running->isHealthy());

        foreach (ServerStatus::cases() as $case) {
            if ($case !== ServerStatus::Running) {
                $this->assertFalse($case->isHealthy(), $case->name . ' must not count as healthy.');
            }
        }
    }

    public function testUnhealthyTargetsAreCritical(): void
    {
        $this->assertSame('state-critical', TargetHealthStatus::Unhealthy->cssClass());
        $this->assertSame('state-ok', TargetHealthStatus::Healthy->cssClass());
    }
}
