<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translator;

use App\Translator\PromptLoader;
use PHPUnit\Framework\TestCase;

final class PromptLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/ls-agent-prompts-'.bin2hex(random_bytes(4));
        mkdir($this->tmpDir.'/v1.0', 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/v1.0/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->tmpDir.'/v1.0');
        @rmdir($this->tmpDir);
    }

    public function testLoadsExistingPromptAndStripsTrailingWhitespace(): void
    {
        file_put_contents($this->tmpDir.'/v1.0/translate.txt', "Hello prompt.\n\n");
        $loader = new PromptLoader($this->tmpDir, 'v1.0');

        self::assertSame('Hello prompt.', $loader->load('translate'));
    }

    public function testThrowsWhenPromptFileMissing(): void
    {
        $loader = new PromptLoader($this->tmpDir, 'v1.0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('#Prompt file not found#');

        $loader->load('nonexistent');
    }

    public function testThrowsWhenPromptFileEmpty(): void
    {
        file_put_contents($this->tmpDir.'/v1.0/empty.txt', "   \n");
        $loader = new PromptLoader($this->tmpDir, 'v1.0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('#empty or unreadable#');

        $loader->load('empty');
    }
}
