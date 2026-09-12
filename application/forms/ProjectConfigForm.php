<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Forms;

use Icinga\Application\Config;
use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Api\S3\S3Client;
use ipl\Web\Compat\CompatForm;

class ProjectConfigForm extends CompatForm
{
    public const TOKEN_UNCHANGED = '__unchanged__';

    public function __construct(
        private readonly Config $config,
        private readonly ?string $projectKey = null
    ) {
        $this->translationDomain = 'hcloud';
    }

    private function section(): string
    {
        return ProjectRegistry::SECTION_PREFIX . (string) $this->projectKey;
    }

    private function isEdit(): bool
    {
        return $this->projectKey !== null && $this->projectKey !== '';
    }

    protected function assemble(): void
    {
        $existing = $this->isEdit() ? $this->config->getSection($this->section()) : null;
        $hasToken = $existing !== null && (string) $existing->get('token', '') !== '';

        $this->addElement('text', 'key', [
            'label' => $this->translate('Key'),
            'description' => $this->translate(
                'Identifies the project in the database and on the command line. Letters, digits, dash'
                . ' and underscore.'
            ),
            'value' => $this->projectKey ?? '',
            'required' => true,
            'disabled' => $this->isEdit() ?: null,
        ]);

        $this->addElement('text', 'name', [
            'label' => $this->translate('Name'),
            'description' => $this->translate('Shown in the interface.'),
            'value' => $existing !== null ? (string) $existing->get('name', '') : '',
            'required' => true,
        ]);

        $this->addElement('password', 'token', [
            'label' => $this->translate('API Token'),
            'description' => $hasToken
                ? $this->translate('Leave empty to keep the stored token.')
                : $this->translate('A Hetzner Cloud API token with Read permission.'),
            'required' => ! $hasToken,
            'autocomplete' => 'new-password',
        ]);

        $this->addElement('checkbox', 'enabled', [
            'label' => $this->translate('Enabled'),
            'description' => $this->translate('Only enabled projects are synced.'),
            'value' => $existing === null ? true : (bool) $existing->get('enabled', true),
        ]);

        $hasSecretKey = $existing !== null && (string) $existing->get('s3_secret_key', '') !== '';

        $this->addElement('text', 's3_access_key', [
            'label' => $this->translate('Object Storage Access Key'),
            'description' => $this->translate(
                'S3 access key of this project. Object Storage has no Hetzner API, so it is read over S3'
                . ' with its own credentials. Leave empty to skip Object Storage.'
            ),
            'value' => $existing !== null ? (string) $existing->get('s3_access_key', '') : '',
        ]);

        $this->addElement('password', 's3_secret_key', [
            'label' => $this->translate('Object Storage Secret Key'),
            'description' => $hasSecretKey
                ? $this->translate('Leave empty to keep the stored secret key.')
                : $this->translate('S3 secret key belonging to the access key above.'),
            'autocomplete' => 'new-password',
        ]);

        $this->addElement('text', 's3_locations', [
            'label' => $this->translate('Object Storage Locations'),
            'description' => $this->translate(
                'Comma separated locations to look for buckets in. Buckets are bound to a location and'
                . ' each one has to be asked separately.'
            ),
            'value' => $existing !== null && (string) $existing->get('s3_locations', '') !== ''
                ? (string) $existing->get('s3_locations')
                : implode(', ', S3Client::DEFAULT_LOCATIONS),
            'placeholder' => implode(', ', S3Client::DEFAULT_LOCATIONS),
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->isEdit() ? $this->translate('Save Changes') : $this->translate('Add Project'),
        ]);

        if ($this->isEdit()) {
            $this->addElement('submit', 'remove', [
                'label' => $this->translate('Remove'),
                'class' => 'btn-remove',
                'formnovalidate' => true,
            ]);
        }
    }

    public function onSuccess(): void
    {
        if ($this->isEdit() && $this->getPopulatedValue('remove') !== null) {
            $this->config->removeSection($this->section());
            $this->config->saveIni();

            return;
        }

        $key = $this->isEdit() ? (string) $this->projectKey : trim((string) $this->getValue('key'));
        if ($key === '' || preg_match('/^[A-Za-z0-9_-]+$/', $key) !== 1) {
            $this->addMessage($this->translate('The key may only contain letters, digits, dash and underscore.'));

            return;
        }

        $section = ProjectRegistry::SECTION_PREFIX . $key;
        $existing = $this->config->getSection($section);

        $token = (string) $this->getValue('token');
        if ($token === '') {
            $token = (string) $existing->get('token', '');
        }

        $secretKey = (string) $this->getValue('s3_secret_key');
        if ($secretKey === '') {
            $secretKey = (string) $existing->get('s3_secret_key', '');
        }

        $this->config->setSection($section, [
            'name' => (string) $this->getValue('name'),
            'token' => $token,
            'enabled' => $this->getValue('enabled') ? 1 : 0,
            's3_access_key' => trim((string) $this->getValue('s3_access_key')),
            's3_secret_key' => $secretKey,
            's3_locations' => trim((string) $this->getValue('s3_locations')),
        ]);

        $this->config->saveIni();
    }
}
