<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Guards the PHP 8.3 promise: the platform pin stays in place and no 8.4-only syntax slips in. */
class Php83ReadinessTest extends TestCase
{
    public function test_composer_is_pinned_to_php_83(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);

        $this->assertSame('^8.3', $composer['require']['php']);
        $this->assertSame('8.3.0', $composer['config']['platform']['php']);
    }

    public function test_locked_packages_all_support_php_83(): void
    {
        $lock = json_decode((string) file_get_contents(__DIR__.'/../../composer.lock'), true);
        $this->assertSame('8.3.0', $lock['platform-overrides']['php'] ?? null, 'composer.lock must be resolved with the 8.3 platform pin');
    }

    public function test_app_code_avoids_php_84_only_features(): void
    {
        $patterns = [
            'property hooks' => '/\b(?:public|protected|private)\s+[\w\\\\|?]+\s+\$\w+\s*\{\s*(?:get|set)\b/',
            'asymmetric visibility' => '/\b(?:public|protected)\s*\(\s*set\s*\)/',
            'new without parentheses' => '/\bnew\s+[A-Z]\w*(?:\([^()]*\))\s*->/',
            '8.4 array functions' => '/\barray_(?:find|find_key|any|all)\s*\(/',
            '#[\Deprecated] attribute' => '/#\[\\\\?Deprecated\b/',
        ];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../app'));
        foreach (array_merge(iterator_to_array($files), [new \SplFileInfo(__DIR__.'/../../routes/web.php'), new \SplFileInfo(__DIR__.'/../../bootstrap/app.php')]) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $code = (string) file_get_contents($file->getPathname());
            foreach ($patterns as $feature => $regex) {
                $this->assertDoesNotMatchRegularExpression($regex, $code, "{$file->getFilename()} uses {$feature}, which needs PHP 8.4");
            }
        }
    }
}
