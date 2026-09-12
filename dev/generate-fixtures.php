<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "usage: php dev/generate-fixtures.php <cloud.spec.json> <hetzner.spec.json>\n");
    exit(1);
}

const CLOUD_PATHS = [
    'locations' => '/locations',
    'server_types' => '/server_types',
    'load_balancer_types' => '/load_balancer_types',
    'images' => '/images',
    'isos' => '/isos',
    'pricing' => '/pricing',
    'servers' => '/servers',
    'volumes' => '/volumes',
    'load_balancers' => '/load_balancers',
    'networks' => '/networks',
    'firewalls' => '/firewalls',
    'floating_ips' => '/floating_ips',
    'primary_ips' => '/primary_ips',
    'certificates' => '/certificates',
    'placement_groups' => '/placement_groups',
    'ssh_keys' => '/ssh_keys',
    'zones' => '/zones',
    'server_actions' => '/servers/actions',
    'zone_rrsets' => '/zones/{id_or_name}/rrsets',
];

const STORAGE_PATHS = [
    'storage_boxes' => '/storage_boxes',
    'storage_box_types' => '/storage_box_types',
    'storage_box_subaccounts' => '/storage_boxes/{id}/subaccounts',
    'storage_box_snapshots' => '/storage_boxes/{id}/snapshots',
];

/**
 * @param array<string, mixed> $schema
 */
function synthesize(array $schema, int $depth = 0): mixed
{
    if ($depth > 12) {
        return null;
    }

    foreach (['oneOf', 'anyOf', 'allOf'] as $combiner) {
        if (isset($schema[$combiner]) && is_array($schema[$combiner])) {
            $first = $schema[$combiner][0] ?? [];

            return is_array($first) ? synthesize($first, $depth + 1) : null;
        }
    }

    $types = (array) ($schema['type'] ?? []);
    $types = array_values(array_filter($types, static fn ($t): bool => $t !== 'null'));
    $type = $types[0] ?? null;

    if ($type === 'object') {
        $out = [];
        foreach ((array) ($schema['properties'] ?? []) as $name => $property) {
            if (is_array($property)) {
                $out[(string) $name] = synthesize($property, $depth + 1);
            }
        }

        return $out;
    }

    if ($type === 'array') {
        $items = $schema['items'] ?? null;

        return is_array($items) ? [synthesize($items, $depth + 1)] : [];
    }

    if (array_key_exists('example', $schema)) {
        return $schema['example'];
    }

    if (isset($schema['enum']) && is_array($schema['enum']) && $schema['enum'] !== []) {
        return $schema['enum'][0];
    }

    return match ($type) {
        'string' => 'example',
        'integer' => 1,
        'number' => 1.0,
        'boolean' => false,
        default => null,
    };
}

/**
 * @param array<string, mixed> $spec
 * @param array<string, string> $paths
 */
function emit(array $spec, array $paths, string $outDir): void
{
    foreach ($paths as $name => $path) {
        $schema = $spec['paths'][$path]['get']['responses']['200']['content']['application/json']['schema'] ?? null;

        if (! is_array($schema)) {
            fwrite(STDERR, "skipped $name ($path): no 200 schema\n");
            continue;
        }

        $payload = synthesize($schema);
        $file = $outDir . '/' . $name . '.json';
        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        printf("wrote %s\n", basename($file));
    }
}

$outDir = dirname(__DIR__) . '/dev/mock';
if (! is_dir($outDir)) {
    mkdir($outDir, 0o755, true);
}

$cloud = json_decode((string) file_get_contents($argv[1]), true);
$storage = json_decode((string) file_get_contents($argv[2]), true);

if (! is_array($cloud) || ! is_array($storage)) {
    fwrite(STDERR, "could not parse the specs\n");
    exit(1);
}

emit($cloud, CLOUD_PATHS, $outDir);
emit($storage, STORAGE_PATHS, $outDir);
