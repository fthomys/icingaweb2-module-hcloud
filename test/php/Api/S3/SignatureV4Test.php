<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Api\S3;

use Icinga\Module\Hcloud\Api\S3\SignatureV4;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Driven by the published AWS Signature Version 4 test suite, which states the canonical
 * request, the string to sign and the authorization header for each case. Signing is the one
 * part of the Object Storage path that cannot be checked against Hetzner without live keys,
 * so it is checked against the specification instead.
 */
final class SignatureV4Test extends TestCase
{
    private const SUITE = __DIR__ . '/aws-sig-v4-test-suite';

    private const ACCESS_KEY = 'AKIDEXAMPLE';
    private const SECRET_KEY = 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY';
    private const REGION = 'us-east-1';
    private const DATE = '20150830';
    private const AMZ_DATE = '20150830T123600Z';
    private const SERVICE = 'service';

    /**
     * @return list<array{string}>
     */
    public static function caseProvider(): array
    {
        $cases = [];

        foreach ((array) glob(self::SUITE . '/*', GLOB_ONLYDIR) as $dir) {
            if (is_string($dir)) {
                $cases[] = [basename($dir)];
            }
        }

        sort($cases);

        return $cases;
    }

    #[DataProvider('caseProvider')]
    public function testTheCanonicalRequestMatchesTheSuite(string $case): void
    {
        [$method, $path, $query, $headers] = self::request($case);

        $this->assertSame(
            self::read($case, 'creq'),
            SignatureV4::canonicalRequest($method, $path, $query, $headers, SignatureV4::EMPTY_PAYLOAD)
        );
    }

    #[DataProvider('caseProvider')]
    public function testTheStringToSignMatchesTheSuite(string $case): void
    {
        $this->assertSame(
            self::read($case, 'sts'),
            SignatureV4::stringToSign(
                self::AMZ_DATE,
                SignatureV4::credentialScope(self::DATE, self::REGION, self::SERVICE),
                self::read($case, 'creq')
            )
        );
    }

    #[DataProvider('caseProvider')]
    public function testTheAuthorizationHeaderMatchesTheSuite(string $case): void
    {
        [, , , $headers] = self::request($case);

        $this->assertSame(
            self::read($case, 'authz'),
            SignatureV4::authorization(
                self::ACCESS_KEY,
                self::SECRET_KEY,
                self::REGION,
                self::DATE,
                self::AMZ_DATE,
                self::read($case, 'creq'),
                $headers,
                self::SERVICE
            )
        );
    }

    public function testTheSigningKeyIsDerivedOverDateRegionAndService(): void
    {
        $this->assertSame(
            hash_hmac(
                'sha256',
                'aws4_request',
                hash_hmac(
                    'sha256',
                    self::SERVICE,
                    hash_hmac(
                        'sha256',
                        self::REGION,
                        hash_hmac('sha256', self::DATE, 'AWS4' . self::SECRET_KEY, true),
                        true
                    ),
                    true
                ),
                true
            ),
            SignatureV4::signingKey(self::SECRET_KEY, self::DATE, self::REGION, self::SERVICE)
        );
    }

    private static function read(string $case, string $extension): string
    {
        $file = sprintf('%s/%s/%s.%s', self::SUITE, $case, $case, $extension);

        return rtrim((string) file_get_contents($file), "\n");
    }

    /**
     * @return array{string, string, array<string, list<string>>, array<string, list<string>>}
     */
    private static function request(string $case): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", self::read($case, 'req')));
        $requestLine = array_shift($lines) ?? '';

        preg_match('#^(\S+) (\S*) HTTP/#', $requestLine, $match);
        $method = $match[1] ?? 'GET';
        $target = $match[2] ?? '/';

        $path = $target;
        $query = [];

        $mark = strpos($target, '?');
        if ($mark !== false) {
            $path = substr($target, 0, $mark);
            $query = self::parseQuery(substr($target, $mark + 1));
        }

        $headers = [];
        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)][] = $value;
        }

        return [$method, rawurldecode($path), $query, $headers];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function parseQuery(string $raw): array
    {
        $query = [];

        foreach (explode('&', $raw) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $query[rawurldecode($name)][] = rawurldecode((string) $value);
        }

        return $query;
    }
}
