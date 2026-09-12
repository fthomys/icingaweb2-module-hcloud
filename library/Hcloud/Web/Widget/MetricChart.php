<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use DateTimeImmutable;
use Exception;
use Icinga\Module\Hcloud\Metric\Downsampler;
use Icinga\Module\Hcloud\Metric\SeriesInfo;
use Icinga\Module\Hcloud\Metric\Unit;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class MetricChart extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-metric'];

    private const WIDTH = 720;
    private const HEIGHT = 180;
    private const PAD_LEFT = 64;
    private const PAD_RIGHT = 8;
    private const PAD_TOP = 8;
    private const PAD_BOTTOM = 22;
    private const GRID_LINES = 4;
    private const MAX_BUCKETS = 216;

    /**
     * Holes, measured in expected samples, that a line is still drawn across.
     */
    private const BRIDGE_BUCKETS = 2;


    /**
     * @param list<SeriesInfo> $series
     * @param array<string, list<array{ts: string, value: float|null}>> $samples Keyed by series name
     */
    public function __construct(
        private readonly string $title,
        private readonly array $series,
        private readonly array $samples,
        private readonly Unit $unit,
        private readonly int $from,
        private readonly int $to,
        private readonly int $step
    ) {
    }

    /**
     * One bucket per expected sample.
     *
     * A fixed bucket count cannot serve a window that holds twelve samples and one that holds
     * five thousand: the first ends up as lone dots in a sea of empty buckets, the second as
     * noise. Sizing the bucket to the configured sample step makes a full bucket the normal
     * case, so a hole in the chart means a hole in the data.
     */
    private function buckets(): int
    {
        $step = max(1, $this->step);
        $span = max(1, $this->to - $this->from);

        return max(2, min(self::MAX_BUCKETS, (int) ceil($span / $step)));
    }

    protected function assemble(): void
    {
        $curves = [];
        $peak = 0.0;
        $hasPoints = false;

        foreach ($this->series as $info) {
            $raw = [];

            foreach ($this->samples[$info->name] ?? [] as $sample) {
                if ($sample['value'] === null) {
                    continue;
                }

                $raw[] = ['ts' => $sample['ts'], 'value' => (float) $sample['value']];
            }

            $points = Downsampler::overTime($raw, $this->buckets(), $this->from, $this->to);

            $filled = 0;
            foreach ($points as $point) {
                if ($point === null) {
                    continue;
                }

                $filled++;
                $peak = max($peak, $point['max']);
            }

            if ($filled >= 2) {
                $hasPoints = true;
            }

            $curves[] = ['info' => $info, 'points' => $points];
        }

        $this->addHtml(new HtmlElement(
            'h3',
            Attributes::create(['class' => 'hcloud-metric-title']),
            Text::create($this->title)
        ));

        if (! $hasPoints) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'hcloud-metric-empty']),
                Text::create(mt('hcloud', 'Not enough samples to draw a chart yet.'))
            ));

            return;
        }

        $max = $this->unit->axisMaximum($peak);

        if ($max <= 0.0) {
            $max = 1.0;
        }

        $plot = new HtmlElement('div', Attributes::create(['class' => 'hcloud-metric-plot']));
        $plot->addHtml($this->svg($curves, $max));
        $plot->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'hcloud-metric-tooltip', 'hidden' => true])
        ));

        $this->addHtml($plot);
        $this->addHtml($this->legend($curves));
    }

    /**
     * @param list<array{
     *     info: SeriesInfo,
     *     points: list<?array{ts: string, min: float, max: float, avg: float}>
     * }> $curves
     */
    private function svg(array $curves, float $max): HtmlElement
    {
        $svg = new HtmlElement('svg', Attributes::create([
            'class' => 'hcloud-metric-chart',
            'viewBox' => sprintf('0 0 %d %d', self::WIDTH, self::HEIGHT),
            'role' => 'img',
            'aria-label' => $this->title,
            'data-hcloud-plot' => sprintf(
                '%d,%d,%d,%d',
                self::PAD_LEFT,
                self::WIDTH - self::PAD_RIGHT,
                self::PAD_TOP,
                self::HEIGHT - self::PAD_BOTTOM
            ),
            'data-hcloud-series' => $this->hoverPayload($curves),
        ]));

        $plotWidth = self::WIDTH - self::PAD_LEFT - self::PAD_RIGHT;
        $plotHeight = self::HEIGHT - self::PAD_TOP - self::PAD_BOTTOM;

        for ($i = 0; $i <= self::GRID_LINES; $i++) {
            $ratio = $i / self::GRID_LINES;
            $y = round(self::PAD_TOP + $plotHeight * $ratio, 2);

            $svg->addHtml(new HtmlElement('line', Attributes::create([
                'class' => 'hcloud-metric-grid',
                'x1' => (string) self::PAD_LEFT,
                'y1' => (string) $y,
                'x2' => (string) (self::WIDTH - self::PAD_RIGHT),
                'y2' => (string) $y,
            ])));

            $svg->addHtml(new HtmlElement(
                'text',
                Attributes::create([
                    'class' => 'hcloud-metric-tick',
                    'x' => (string) (self::PAD_LEFT - 6),
                    'y' => (string) ($y + 3),
                    'text-anchor' => 'end',
                ]),
                Text::create($this->unit->format($max * (1 - $ratio)))
            ));
        }

        foreach ($curves as $index => $curve) {
            $points = $curve['points'];
            if (count($points) < 2) {
                continue;
            }

            $stepX = $plotWidth / (count($points) - 1);

            $x = static fn (int $position): float => round(self::PAD_LEFT + $position * $stepX, 2);
            $y = static fn (float $value): float => round(
                self::PAD_TOP + $plotHeight * (1 - ($value / $max)),
                2
            );

            foreach (self::segments($points, self::BRIDGE_BUCKETS) as $segment) {
                $upper = [];
                $lower = [];
                $average = [];

                foreach ($segment as $position => $point) {
                    $upper[] = $x($position) . ',' . $y($point['max']);
                    $lower[] = $x($position) . ',' . $y($point['min']);
                    $average[] = $x($position) . ',' . $y($point['avg']);
                }

                if (count($average) === 1) {
                    $only = array_key_first($segment);

                    $svg->addHtml(new HtmlElement('circle', Attributes::create([
                        'class' => ['hcloud-metric-dot', 'hcloud-metric-series-' . ($index % 4)],
                        'cx' => (string) $x((int) $only),
                        'cy' => (string) $y($segment[$only]['avg']),
                        'r' => '1.6',
                    ])));

                    continue;
                }

                $svg->addHtml(new HtmlElement('path', Attributes::create([
                    'class' => ['hcloud-metric-band', 'hcloud-metric-band-' . ($index % 4)],
                    'd' => 'M' . implode(' L', $upper) . ' L' . implode(' L', array_reverse($lower)) . ' Z',
                ])));

                $svg->addHtml(new HtmlElement('path', Attributes::create([
                    'class' => ['hcloud-metric-line', 'hcloud-metric-series-' . ($index % 4)],
                    'd' => 'M' . implode(' L', $average),
                ])));
            }
        }

        $svg->addHtml(new HtmlElement('line', Attributes::create([
            'class' => 'hcloud-metric-crosshair',
            'x1' => '0',
            'y1' => (string) self::PAD_TOP,
            'x2' => '0',
            'y2' => (string) (self::PAD_TOP + $plotHeight),
            'hidden' => true,
        ])));

        $baseline = self::PAD_TOP + $plotHeight;

        $svg->addHtml(new HtmlElement(
            'text',
            Attributes::create([
                'class' => 'hcloud-metric-tick',
                'x' => (string) self::PAD_LEFT,
                'y' => (string) ($baseline + 15),
            ]),
            Text::create(self::clock(gmdate('Y-m-d H:i:s', $this->from)))
        ));

        $svg->addHtml(new HtmlElement(
            'text',
            Attributes::create([
                'class' => 'hcloud-metric-tick',
                'x' => (string) (self::WIDTH - self::PAD_RIGHT),
                'y' => (string) ($baseline + 15),
                'text-anchor' => 'end',
            ]),
            Text::create(self::clock(gmdate('Y-m-d H:i:s', $this->to)))
        ));

        return $svg;
    }

    /**
     * Runs of consecutive buckets that actually hold data, keyed by their bucket position.
     *
     * A gap must break the line rather than be spanned by it, otherwise the chart invents a
     * trend across a window where nothing was ever measured.
     *
     * @param list<?array{ts: string, min: float, max: float, avg: float}> $points
     *
     * @return list<array<int, array{ts: string, min: float, max: float, avg: float}>>
     */
    private static function segments(array $points, int $bridge): array
    {
        $segments = [];
        $current = [];
        $pending = 0;

        foreach ($points as $position => $point) {
            if ($point === null) {
                if ($current !== []) {
                    $pending++;

                    if ($pending > $bridge) {
                        $segments[] = $current;
                        $current = [];
                        $pending = 0;
                    }
                }

                continue;
            }

            $current[$position] = $point;
            $pending = 0;
        }

        if ($current !== []) {
            $segments[] = $current;
        }

        return $segments;
    }

    /**
     * @param list<array{
     *     info: SeriesInfo,
     *     points: list<?array{ts: string, min: float, max: float, avg: float}>
     * }> $curves
     */
    private function legend(array $curves): HtmlElement
    {
        $legend = new HtmlElement('ul', Attributes::create(['class' => 'hcloud-metric-legend']));

        foreach ($curves as $index => $curve) {
            $present = array_values(array_filter(
                $curve['points'],
                static fn (?array $point): bool => $point !== null
            ));

            if ($present === []) {
                continue;
            }

            $values = array_column($present, 'avg');
            $peaks = array_column($present, 'max');

            if ($values === [] || $peaks === []) {
                continue;
            }

            $item = new HtmlElement('li', Attributes::create(['class' => 'hcloud-metric-legend-item']));

            $item->addHtml(new HtmlElement('span', Attributes::create([
                'class' => ['hcloud-metric-swatch', 'hcloud-metric-series-' . ($index % 4)],
            ])));

            $item->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-metric-legend-label']),
                Text::create($curve['info']->label)
            ));

            $item->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'hcloud-metric-legend-values']),
                Text::create(sprintf(
                    mt('hcloud', 'now %s, avg %s, max %s'),
                    $this->unit->format((float) end($values)),
                    $this->unit->format(array_sum($values) / count($values)),
                    $this->unit->format((float) max($peaks))
                ))
            ));

            $legend->addHtml($item);
        }

        return $legend;
    }

    /**
     * Values are formatted here, where the unit is known, so the browser never has to
     * reimplement the unit rules.
     *
     * @param list<array{
     *     info: SeriesInfo,
     *     points: list<?array{ts: string, min: float, max: float, avg: float}>
     * }> $curves
     */
    private function hoverPayload(array $curves): string
    {
        $times = [];
        foreach (array_keys($curves[0]['points'] ?? []) as $position) {
            $times[] = self::clock(gmdate('Y-m-d H:i:s', $this->bucketStamp((int) $position)));
        }

        $series = [];
        foreach ($curves as $curve) {
            $values = [];
            foreach ($curve['points'] as $point) {
                $values[] = $point === null ? null : $this->unit->format($point['avg']);
            }

            $series[] = ['l' => $curve['info']->label, 'v' => $values];
        }

        $encoded = json_encode(['t' => $times, 's' => $series], JSON_UNESCAPED_UNICODE);

        return $encoded === false ? '{}' : $encoded;
    }

    private function bucketStamp(int $position): int
    {
        $width = ($this->to - $this->from) / $this->buckets();

        return (int) round($this->from + $width * ($position + 0.5));
    }

    private static function clock(string $timestamp): string
    {
        try {
            return (new DateTimeImmutable($timestamp . ' UTC'))->format('d.m. H:i');
        } catch (Exception $e) {
            return $timestamp;
        }
    }
}
