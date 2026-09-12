<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Api\S3;

/**
 * AWS Signature Version 4, as Hetzner Object Storage expects it. S3 signs the path as given,
 * without the normalisation and double encoding the generic recipe applies, which is the one
 * place a signer written from the general documentation goes wrong against S3.
 */
final class SignatureV4
{
    public const ALGORITHM = 'AWS4-HMAC-SHA256';
    public const SERVICE = 's3';
    public const EMPTY_PAYLOAD = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    /**
     * @param array<string, string|list<string>> $query
     * @param array<string, string|list<string>> $headers
     */
    public static function canonicalRequest(
        string $method,
        string $path,
        array $query,
        array $headers,
        string $payloadHash
    ): string {
        return implode("\n", [
            strtoupper($method),
            self::canonicalPath($path),
            self::canonicalQuery($query),
            self::canonicalHeaders($headers),
            self::signedHeaders($headers),
            $payloadHash,
        ]);
    }

    public static function credentialScope(string $date, string $region, string $service = self::SERVICE): string
    {
        return implode('/', [$date, $region, $service, 'aws4_request']);
    }

    public static function stringToSign(string $amzDate, string $scope, string $canonicalRequest): string
    {
        return implode("\n", [
            self::ALGORITHM,
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);
    }

    public static function signingKey(
        string $secretKey,
        string $date,
        string $region,
        string $service = self::SERVICE
    ): string {
        $key = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
        $key = hash_hmac('sha256', $region, $key, true);
        $key = hash_hmac('sha256', $service, $key, true);

        return hash_hmac('sha256', 'aws4_request', $key, true);
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    public static function authorization(
        string $accessKey,
        string $secretKey,
        string $region,
        string $date,
        string $amzDate,
        string $canonicalRequest,
        array $headers,
        string $service = self::SERVICE
    ): string {
        $scope = self::credentialScope($date, $region, $service);
        $signature = hash_hmac(
            'sha256',
            self::stringToSign($amzDate, $scope, $canonicalRequest),
            self::signingKey($secretKey, $date, $region, $service)
        );

        return sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM,
            $accessKey,
            $scope,
            self::signedHeaders($headers),
            $signature
        );
    }

    private static function canonicalPath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $segments = explode('/', $path);
        $encoded = array_map(static fn (string $segment): string => rawurlencode($segment), $segments);

        return implode('/', $encoded);
    }

    /**
     * @param array<string, string|list<string>> $query
     */
    private static function canonicalQuery(array $query): string
    {
        $pairs = [];

        foreach ($query as $name => $values) {
            foreach ((array) $values as $value) {
                $pairs[] = [rawurlencode((string) $name), rawurlencode((string) $value)];
            }
        }

        usort($pairs, static function (array $a, array $b): int {
            return $a[0] === $b[0] ? strcmp($a[1], $b[1]) : strcmp($a[0], $b[0]);
        });

        return implode('&', array_map(
            static fn (array $pair): string => $pair[0] . '=' . $pair[1],
            $pairs
        ));
    }

    /**
     * @param array<string, string|list<string>> $headers
     *
     * @return array<string, list<string>>
     */
    private static function normalise(array $headers): array
    {
        $normalised = [];

        foreach ($headers as $name => $values) {
            $key = strtolower(trim((string) $name));

            foreach ((array) $values as $value) {
                $normalised[$key][] = self::collapse((string) $value);
            }
        }

        ksort($normalised);

        return $normalised;
    }

    private static function collapse(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private static function canonicalHeaders(array $headers): string
    {
        $lines = '';

        foreach (self::normalise($headers) as $name => $values) {
            $lines .= $name . ':' . implode(',', $values) . "\n";
        }

        return $lines;
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    public static function signedHeaders(array $headers): string
    {
        return implode(';', array_keys(self::normalise($headers)));
    }
}
