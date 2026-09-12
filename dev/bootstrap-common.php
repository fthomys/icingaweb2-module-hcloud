<?php

declare(strict_types=1);

/**
 * @param list<string> $candidates
 */
function hcloud_dev_locate(string $envVar, array $candidates, string $what): string
{
    $configured = getenv($envVar);
    if (is_string($configured) && $configured !== '' && is_dir($configured)) {
        return rtrim($configured, '/');
    }

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return $candidate;
        }
    }

    fwrite(STDERR, sprintf("hcloud: cannot locate %s. Set %s to point at it.\n", $what, $envVar));
    exit(1);
}

/**
 * @return list<string>
 */
function hcloud_dev_require_icinga_libraries(): array
{
    $libdir = hcloud_dev_locate(
        'ICINGAWEB_LIBDIR',
        ['/usr/share/icinga-php', '/usr/local/share/icinga-php', '/usr/share/icingaweb2/vendor'],
        'the Icinga PHP library bundles (icinga-php-library, icinga-php-thirdparty)'
    );

    $loaded = [];
    foreach (['ipl/vendor/autoload.php', 'vendor/vendor/autoload.php'] as $relative) {
        $autoload = $libdir . '/' . $relative;
        if (is_file($autoload)) {
            require_once $autoload;
            $loaded[] = $autoload;
        }
    }

    if ($loaded === []) {
        fwrite(STDERR, "hcloud: no Composer autoloader found below $libdir.\n");
        exit(1);
    }

    return $loaded;
}

function hcloud_dev_register_psr4(string $prefix, string $baseDir): void
{
    $prefix = rtrim($prefix, '\\') . '\\';
    $baseDir = rtrim($baseDir, '/') . '/';

    spl_autoload_register(static function (string $class) use ($prefix, $baseDir): void {
        if (! str_starts_with($class, $prefix)) {
            return;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

function hcloud_dev_register_icingaweb(): void
{
    $basedir = hcloud_dev_locate(
        'ICINGAWEB_BASEDIR',
        ['/usr/share/icingaweb2', '/usr/local/share/icingaweb2'],
        "Icinga Web's installation directory"
    );

    hcloud_dev_register_psr4('Icinga', $basedir . '/library/Icinga');

    $functions = $basedir . '/library/Icinga/Application/functions.php';
    if (is_file($functions)) {
        require_once $functions;
    }

    $modulesConfigured = getenv('ICINGAWEB_MODULEDIR');
    $modulesDir = is_string($modulesConfigured) && $modulesConfigured !== ''
        ? rtrim($modulesConfigured, '/')
        : $basedir . '/modules';

    if (! is_dir($modulesDir)) {
        return;
    }

    spl_autoload_register(static function (string $class) use ($modulesDir): void {
        if (! str_starts_with($class, 'Icinga\\Module\\')) {
            return;
        }

        $relative = substr($class, strlen('Icinga\\Module\\'));
        $slash = strpos($relative, '\\');
        if ($slash === false) {
            return;
        }

        $moduleName = substr($relative, 0, $slash);
        $file = sprintf(
            '%s/%s/library/%s/%s.php',
            $modulesDir,
            strtolower($moduleName),
            $moduleName,
            str_replace('\\', '/', substr($relative, $slash + 1))
        );

        if (is_file($file)) {
            require_once $file;
        }
    });
}

function hcloud_dev_bootstrap(): void
{
    $moduleDir = dirname(__DIR__);

    hcloud_dev_require_icinga_libraries();
    hcloud_dev_register_icingaweb();
    hcloud_dev_register_psr4('Icinga\\Module\\Hcloud', $moduleDir . '/library/Hcloud');
    hcloud_dev_register_psr4('Tests\\Icinga\\Module\\Hcloud', $moduleDir . '/test/php');
}
