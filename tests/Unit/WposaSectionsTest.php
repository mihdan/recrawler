<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WposaSectionsTest
{
    public function sectionIsRegisteredOnItsTabPage(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_section(['id' => 'logs', 'tab' => 'general', 'title' => 'Logs']);
        $wposa->admin_init();

        $sections = array_column($GLOBALS['__wp_settings_sections'], 'page', 'id');

        Assert::same($sections['recrawler_logs'], 'recrawler_general');
    }

    public function fieldRendersInSubsectionButSavesToTabOption(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_section(['id' => 'logs', 'tab' => 'general', 'title' => 'Logs']);
        $wposa->add_field('general', ['id' => 'enable', 'type' => 'switcher', 'section' => 'logs']);
        $wposa->admin_init();

        $field = $GLOBALS['__wp_settings_fields'][0];

        Assert::same($field['page'], 'recrawler_general');
        Assert::same($field['section'], 'recrawler_logs');
        Assert::same($field['args']['section'], 'recrawler_general');
    }

    public function fieldWithoutSectionKeepsOldBehaviour(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_field('general', ['id' => 'ping_delay', 'type' => 'number']);
        $wposa->admin_init();

        $field = $GLOBALS['__wp_settings_fields'][0];

        Assert::same($field['page'], 'recrawler_general');
        Assert::same($field['section'], 'recrawler_general');
    }
}
