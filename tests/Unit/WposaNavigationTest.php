<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WposaNavigationTest
{
    private function makeWposa(): WPOSA
    {
        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_tab(['id' => 'logs', 'title' => 'Logs']);

        return $wposa;
    }

    public function defaultsToFirstTab(): void
    {
        unset($_GET['tab']);

        Assert::same($this->makeWposa()->get_current_tab(), 'recrawler_general');
    }

    public function readsTabFromQuery(): void
    {
        $_GET['tab'] = 'logs';

        Assert::same($this->makeWposa()->get_current_tab(), 'recrawler_logs');

        unset($_GET['tab']);
    }

    public function unknownTabFallsBackToFirst(): void
    {
        $_GET['tab'] = 'nonexistent';

        Assert::same($this->makeWposa()->get_current_tab(), 'recrawler_general');

        unset($_GET['tab']);
    }

    public function tabUrlCarriesPageAndTab(): void
    {
        Assert::same(
            $this->makeWposa()->get_tab_url('recrawler_logs'),
            'https://example.com/wp-admin/admin.php?page=recrawler&tab=logs'
        );
    }
}
