<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translator;

use App\Translator\LeichteSpracheTranslator;
use App\Translator\PromptLoader;
use App\Translator\TranslationRequest;
use PHPUnit\Framework\TestCase;
use Steg\Client\MockClient;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

final class LeichteSpracheTranslatorTest extends TestCase
{
    private string $promptDir;

    protected function setUp(): void
    {
        $this->promptDir = sys_get_temp_dir().'/ls-agent-translator-'.bin2hex(random_bytes(4));
        mkdir($this->promptDir.'/v1.0', 0o755, true);
        file_put_contents(
            $this->promptDir.'/v1.0/translate.txt',
            'Du bist ein Test-Übersetzer für Leichte Sprache.',
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->promptDir.'/v1.0/translate.txt');
        @rmdir($this->promptDir.'/v1.0');
        @rmdir($this->promptDir);
    }

    public function testTranslateReturnsResultWithMockedResponse(): void
    {
        $client = new MockClient(
            response: 'Das ist Leichte Sprache.',
            model: 'mock-llm',
        );
        $loader = new PromptLoader($this->promptDir, 'v1.0');
        $translator = new LeichteSpracheTranslator($client, $loader);

        $result = $translator->translate(new TranslationRequest(
            originalText: 'Die Bundesregierung hat neue Gesetze beschlossen.',
            temperature: 0.2,
            maxTokens: 1024,
        ));

        self::assertSame('Die Bundesregierung hat neue Gesetze beschlossen.', $result->originalText);
        self::assertSame('Das ist Leichte Sprache.', $result->translatedText);
        self::assertSame('mock-llm', $result->model);
    }

    public function testTranslatePassesSystemPromptUserMessageAndOptions(): void
    {
        /** @var list<ChatMessage>|null $capturedMessages */
        $capturedMessages = null;
        /** @var ?CompletionOptions $capturedOptions */
        $capturedOptions = null;

        $client = (new MockClient())->withCallback(
            static function (array $messages, ?CompletionOptions $options) use (&$capturedMessages, &$capturedOptions): string {
                $capturedMessages = $messages;
                $capturedOptions = $options;

                return 'ok';
            },
        );
        $loader = new PromptLoader($this->promptDir, 'v1.0');
        $translator = new LeichteSpracheTranslator($client, $loader);

        $translator->translate(new TranslationRequest(
            originalText: 'Beamtendeutsch.',
            temperature: 0.55,
            maxTokens: 777,
        ));

        self::assertNotNull($capturedMessages);
        self::assertCount(2, $capturedMessages);

        self::assertSame('system', $capturedMessages[0]->role);
        self::assertSame('Du bist ein Test-Übersetzer für Leichte Sprache.', $capturedMessages[0]->content);

        self::assertSame('user', $capturedMessages[1]->role);
        self::assertSame('Beamtendeutsch.', $capturedMessages[1]->content);

        self::assertNotNull($capturedOptions);
        self::assertSame(0.55, $capturedOptions->temperature);
        self::assertSame(777, $capturedOptions->maxTokens);
    }
}
