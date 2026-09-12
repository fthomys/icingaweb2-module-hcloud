<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Forms;

use Icinga\Application\Config;
use ipl\Validator\BetweenValidator;
use ipl\Web\Compat\CompatForm;

class SyncConfigForm extends CompatForm
{
    public function __construct(private readonly Config $config)
    {
        $this->translationDomain = 'hcloud';
    }

    protected function assemble(): void
    {
        $this->addElement('text', 'retention_metrics', [
            'label' => $this->translate('Metric retention (hours)'),
            'description' => $this->translate(
                'How many hours of metric samples to keep. Older samples are pruned after each sync.'
            ),
            'value' => (string) ($this->config->get('sync', 'retention_metrics') ?? 48),
            'required' => true,
            'validators' => [new BetweenValidator(['min' => 1, 'max' => 8760])],
        ]);

        $this->addElement('text', 'retention_actions', [
            'label' => $this->translate('Action retention (days)'),
            'description' => $this->translate('How many days of Hetzner action history to keep.'),
            'value' => (string) ($this->config->get('sync', 'retention_actions') ?? 30),
            'required' => true,
            'validators' => [new BetweenValidator(['min' => 1, 'max' => 3650])],
        ]);

        $this->addElement('text', 'per_page', [
            'label' => $this->translate('Page size'),
            'description' => $this->translate('Objects per API request. Hetzner caps this at 50.'),
            'value' => (string) ($this->config->get('sync', 'per_page') ?? 50),
            'required' => true,
            'validators' => [new BetweenValidator(['min' => 1, 'max' => 50])],
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Save Changes'),
        ]);
    }

    public function onSuccess(): void
    {
        $this->config->setSection('sync', [
            'retention_metrics' => (int) $this->getValue('retention_metrics'),
            'retention_actions' => (int) $this->getValue('retention_actions'),
            'per_page' => (int) $this->getValue('per_page'),
        ]);

        $this->config->saveIni();
    }
}
