<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use PHPUnit\Framework\TestCase;

/**
 * Every URL the module links to must resolve to a controller action that exists.
 *
 * The module once shipped config tabs and detail links whose controllers had never been
 * written, so every one of them rendered "Page not found".
 */
final class RouteCoverageTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    public function testEveryMenuUrlHasAController(): void
    {
        $urls = self::menuUrls();

        $this->assertNotEmpty($urls, 'No menu URLs found in configuration.php.');

        foreach ($urls as $url) {
            $this->assertRouteExists($url, 'configuration.php menu');
        }
    }

    public function testEveryConfigTabHasAnAction(): void
    {
        $source = (string) file_get_contents(self::MODULE_DIR . '/configuration.php');

        preg_match_all("/'url'\s*=>\s*'config\/([a-z-]+)'/", $source, $matches);
        $actions = $matches[1];

        $this->assertNotEmpty($actions, 'No config tabs found in configuration.php.');

        $controller = self::MODULE_DIR . '/application/controllers/ConfigController.php';
        $this->assertFileExists($controller, 'Config tabs are declared but ConfigController is missing.');

        $source = (string) file_get_contents($controller);

        foreach ($actions as $action) {
            $method = self::action($action);
            $this->assertStringContainsString(
                'function ' . $method . '(',
                $source,
                sprintf('Config tab "%s" has no ConfigController::%s().', $action, $method)
            );
        }
    }

    public function testEveryDetailLinkHasAController(): void
    {
        $urls = [];

        foreach (glob(self::MODULE_DIR . '/application/controllers/*Controller.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);

            if (preg_match_all("/return '(hcloud\/[a-z-]+)';/", $source, $matches)) {
                foreach ($matches[1] as $url) {
                    $urls[] = $url;
                }
            }
        }

        $this->assertNotEmpty($urls, 'No detail URLs found; the list views emit none.');

        foreach (array_unique($urls) as $url) {
            $this->assertRouteExists($url, 'detailUrl()');
        }
    }

    private function assertRouteExists(string $url, string $origin): void
    {
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $parts = explode('/', $path);

        $this->assertSame('hcloud', $parts[0], sprintf('%s points outside the module: %s', $origin, $url));

        $controller = self::camel($parts[1] ?? '');
        $file = self::MODULE_DIR . '/application/controllers/' . $controller . 'Controller.php';

        $this->assertFileExists(
            $file,
            sprintf('%s links to "%s" but %sController does not exist.', $origin, $url, $controller)
        );

        $action = self::action($parts[2] ?? 'index');
        $source = (string) file_get_contents($file);
        $inherits = (bool) preg_match('/extends\s+(ResourceController|DetailController)/', $source);

        if (! $inherits || $action !== 'indexAction') {
            $this->assertStringContainsString(
                'function ' . $action . '(',
                $source,
                sprintf('%s links to "%s" but %sController::%s() is missing.', $origin, $url, $controller, $action)
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function menuUrls(): array
    {
        $source = (string) file_get_contents(self::MODULE_DIR . '/configuration.php');

        preg_match_all("/setUrl\('(hcloud\/[a-z-]+)'\)/", $source, $matches);

        return array_values(array_unique($matches[1]));
    }

    private static function action(string $value): string
    {
        return lcfirst(self::camel($value)) . 'Action';
    }

    private static function camel(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $value)));
    }
}
