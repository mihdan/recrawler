<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Providers\Yandex\YandexWebmaster;
use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;

#[Test]
final class YandexWebmasterRedirectTest
{
    public function settingsUrlPointsAtTheYandexTab(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $provider = new YandexWebmaster(new Logger(), $wposa);

        Assert::same(
            $provider->get_settings_url(),
            'https://example.com/wp-admin/admin.php?page=recrawler&tab=yandex_webmaster'
        );
    }
}
