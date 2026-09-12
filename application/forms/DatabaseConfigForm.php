<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Forms;

use Icinga\Application\Config;
use Icinga\Data\ResourceFactory;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatForm;

class DatabaseConfigForm extends CompatForm
{
    public function __construct(private readonly Config $config)
    {
        $this->translationDomain = 'hcloud';
    }

    protected function assemble(): void
    {
        $resources = self::databaseResources();

        if ($resources === []) {
            $this->addHtml(new HtmlElement(
                'p',
                Attributes::create(['class' => 'empty-state']),
                Text::create($this->translate(
                    'No database resource exists yet. Create one under Configuration, Resources first.'
                ))
            ));

            return;
        }

        $this->addElement('select', 'resource', [
            'label' => $this->translate('Database'),
            'description' => $this->translate(
                'The Icinga Web database resource holding the hcloud schema.'
            ),
            'required' => true,
            'value' => (string) ($this->config->get('db', 'resource') ?? ''),
            'options' => ['' => $this->translate('Please choose')] + $resources,
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Save Changes'),
        ]);
    }

    public function onSuccess(): void
    {
        $this->config->setSection('db', ['resource' => (string) $this->getValue('resource')]);
        $this->config->saveIni();
    }

    /**
     * @return array<string, string>
     */
    public static function databaseResources(): array
    {
        $resources = [];

        foreach (ResourceFactory::getResourceConfigs() as $name => $resource) {
            if (! is_string($name)) {
                continue;
            }

            if ((string) $resource->get('type') === 'db') {
                $resources[$name] = $name;
            }
        }

        ksort($resources);

        return $resources;
    }
}
