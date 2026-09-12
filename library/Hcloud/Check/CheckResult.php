<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Check;

final class CheckResult
{
    public const OK = 0;
    public const WARNING = 1;
    public const CRITICAL = 2;
    public const UNKNOWN = 3;

    /** @var list<string> */
    private array $perfData = [];

    /** @var list<string> */
    private array $details = [];

    public function __construct(
        private int $state,
        private string $summary
    ) {
    }

    public static function ok(string $summary): self
    {
        return new self(self::OK, $summary);
    }

    public function raiseTo(int $state): self
    {
        if (self::severity($state) > self::severity($this->state)) {
            $this->state = $state;
        }

        return $this;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function addDetail(string $detail): self
    {
        $this->details[] = $detail;

        return $this;
    }

    public function addPerfData(
        string $label,
        int|float $value,
        int|float|null $warning = null,
        int|float|null $critical = null
    ): self {
        $this->perfData[] = sprintf(
            "'%s'=%s;%s;%s",
            $label,
            self::number($value),
            $warning === null ? '' : self::number($warning),
            $critical === null ? '' : self::number($critical)
        );

        return $this;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function render(): string
    {
        $prefix = match ($this->state) {
            self::OK => 'OK',
            self::WARNING => 'WARNING',
            self::CRITICAL => 'CRITICAL',
            default => 'UNKNOWN',
        };

        $output = $prefix . ' - ' . $this->summary;

        if ($this->perfData !== []) {
            $output .= ' | ' . implode(' ', $this->perfData);
        }

        if ($this->details !== []) {
            $output .= "\n" . implode("\n", $this->details);
        }

        return $output;
    }

    private static function number(int|float $value): string
    {
        return is_float($value) ? rtrim(rtrim(sprintf('%.3f', $value), '0'), '.') : (string) $value;
    }

    private static function severity(int $state): int
    {
        return match ($state) {
            self::OK => 0,
            self::UNKNOWN => 1,
            self::WARNING => 2,
            self::CRITICAL => 3,
            default => 1,
        };
    }
}
