<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web\Widget;

use Icinga\Module\Hcloud\Pricing\CostCalculator;
use Icinga\Module\Hcloud\Pricing\CostReport;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;

class CostBreakdown extends BaseHtmlElement
{
    protected $tag = 'div';

    /** @var array<string, mixed> */
    protected $defaultAttributes = ['class' => 'hcloud-costs'];

    /**
     * @param array<string, CostReport> $reports
     */
    public function __construct(private readonly array $reports)
    {
    }

    protected function assemble(): void
    {
        if ($this->reports === []) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'empty-state']),
                Text::create(mt('hcloud', 'No project has been synced yet.'))
            ));

            return;
        }

        foreach ($this->reports as $projectName => $report) {
            $this->addHtml(new HtmlElement('h2', Attributes::create([]), Text::create($projectName)));

            if (! $report->hasPricingData) {
                $this->addHtml(new HtmlElement(
                    'p',
                    Attributes::create(['class' => 'hcloud-cost-missing']),
                    Text::create(mt(
                        'hcloud',
                        'No pricing data has been synced for this project, so no cost can be shown.'
                        . ' Run a sync to fetch it.'
                    ))
                ));

                continue;
            }

            if ($report->lines === []) {
                $this->addHtml(new HtmlElement(
                    'p',
                    Attributes::create(['class' => 'empty-state']),
                    Text::create(mt('hcloud', 'This project holds no billable resources.'))
                ));

                continue;
            }

            $this->addHtml($this->table($report));
            $this->addHtml($this->total($report));
        }
    }

    private function table(CostReport $report): Table
    {
        $table = new Table();
        $table->addAttributes(['class' => ['common-table', 'hcloud-cost-table']]);

        $table->getHeader()->add(Table::row([
            mt('hcloud', 'Category'),
            mt('hcloud', 'Type'),
            mt('hcloud', 'Location'),
            mt('hcloud', 'Count'),
            mt('hcloud', 'Monthly net'),
            mt('hcloud', 'Monthly gross'),
        ], null, 'th'));

        foreach ($report->lines as $line) {
            $table->getBody()->add(Table::row([
                self::categoryLabel($line->category),
                $line->label,
                $line->location ?? '-',
                (string) $line->count,
                $line->monthlyNet->toDecimal(),
                $line->monthlyGross->toDecimal(),
            ]));
        }

        return $table;
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            CostCalculator::CATEGORY_SERVER => mt('hcloud', 'Server'),
            CostCalculator::CATEGORY_LOAD_BALANCER => mt('hcloud', 'Load Balancer'),
            CostCalculator::CATEGORY_VOLUME => mt('hcloud', 'Volume'),
            CostCalculator::CATEGORY_PRIMARY_IP => mt('hcloud', 'Primary IP'),
            CostCalculator::CATEGORY_FLOATING_IP => mt('hcloud', 'Floating IP'),
            CostCalculator::CATEGORY_STORAGE_BOX => mt('hcloud', 'Storage Box'),
            CostCalculator::CATEGORY_SNAPSHOT => mt('hcloud', 'Snapshot'),
            CostCalculator::CATEGORY_TRAFFIC => mt('hcloud', 'Traffic'),
            default => $category,
        };
    }

    private function total(CostReport $report): HtmlElement
    {
        $currency = $report->currency === null ? '' : ' ' . $report->currency;

        return new HtmlElement(
            'p',
            Attributes::create(['class' => 'hcloud-cost-total']),
            Text::create(sprintf(
                mt('hcloud', 'Estimated monthly total: %s net, %s gross%s'),
                $report->totalNet->toDecimal(),
                $report->totalGross->toDecimal(),
                $currency
            ))
        );
    }
}
