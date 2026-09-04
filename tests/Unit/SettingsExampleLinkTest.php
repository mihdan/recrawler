<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Views\HelpTab;
use Mihdan\ReCrawler\Views\Settings;
use Mihdan\ReCrawler\Views\WPOSA;
use ReflectionProperty;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SettingsExampleLinkTest
{
    /**
     * Получить `desc` поля `index_now.api_key` так, как его регистрирует Settings.
     */
    private function getApiKeyDesc(): string
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $settings = new Settings($wposa, new HelpTab());
        $settings->setup_vars();
        $settings->setup_fields();

        $property = new ReflectionProperty(WPOSA::class, 'fields_array');
        $property->setAccessible(true);
        $fields = $property->getValue($wposa);

        foreach ($fields['recrawler_index_now'] as $field) {
            if ($field['id'] === 'api_key') {
                return (string) $field['desc'];
            }
        }

        Assert::fail('Field index_now.api_key was not registered.');
    }

    public function exampleLinkUsesStylesheetClass(): void
    {
        Assert::true(str_contains($this->getApiKeyDesc(), 'class="recrawler-example-link"'));
    }

    public function exampleLinkHasNoInlineStyle(): void
    {
        $desc = $this->getApiKeyDesc();

        Assert::false(str_contains($desc, 'style='));
        Assert::false(str_contains($desc, '#2271b1'));
    }
}
