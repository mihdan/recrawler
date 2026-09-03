<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WposaMenuTest
{
    public function everyTabGetsSubmenuItem(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_tab(['id' => 'ai', 'title' => 'AI']);
        $wposa->admin_menu();

        $slugs = array_column($GLOBALS['__wp_submenus'], 'menu_slug');

        Assert::true(in_array('recrawler&tab=general', $slugs, true));
        Assert::true(in_array('recrawler&tab=ai', $slugs, true));
    }

    public function tabCanOptOutOfMenu(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_tab(['id' => 'plugins', 'title' => 'Plugins', 'show_in_menu' => false]);
        $wposa->admin_menu();

        $slugs = array_column($GLOBALS['__wp_submenus'], 'menu_slug');

        Assert::false(in_array('recrawler&tab=plugins', $slugs, true));
    }

    public function disabledTabHasNoSubmenuItem(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_tab(['id' => 'ai', 'title' => 'AI', 'disabled' => true]);
        $wposa->admin_menu();

        $slugs = array_column($GLOBALS['__wp_submenus'], 'menu_slug');

        Assert::false(in_array('recrawler&tab=ai', $slugs, true));
    }

    public function highlightsSubmenuOfCurrentTab(): void
    {
        _reset_wp_mocks();
        $_GET['tab'] = 'ai';

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_tab(['id' => 'ai', 'title' => 'AI']);

        Assert::same($wposa->highlight_current_submenu('recrawler'), 'recrawler&tab=ai');

        unset($_GET['tab']);
    }
}
