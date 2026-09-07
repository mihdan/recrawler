<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use ArrayObject;
use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Providers\Yandex\YandexWebmaster;
use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;

#[Test]
final class YandexWebmasterSchedulePingTest
{
    /**
     * Собрать провайдера на настоящем WPOSA и вернуть коллектор задач,
     * в который пишет заглушка as_enqueue_async_action.
     */
    private function collectScheduledPings(string $selected_host, int $post_id): ArrayObject
    {
        _reset_wp_mocks();

        _set_wp_override('get_option', static function ($option, $default = false) use ($selected_host) {
            if ($option === 'recrawler_yandex_webmaster') {
                return [
                    'host_id'  => $selected_host,
                    'host_ids' => serialize([
                        'https:kobzarev.com:443',
                        'https:www.kobzarev.com:443',
                        'https:example.com:443',
                    ]),
                ];
            }

            return $default;
        });

        $scheduled = new ArrayObject();

        _set_wp_override('as_enqueue_async_action', static function ($hook, $args = [], $group = '') use ($scheduled) {
            $scheduled->append(['hook' => $hook, 'args' => $args]);
            return 1;
        });

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        (new YandexWebmaster(new Logger(), $wposa))->schedule_ping($post_id);

        return $scheduled;
    }

    public function schedulesOneTaskForTheSelectedHost(): void
    {
        $scheduled = $this->collectScheduledPings('https:www.kobzarev.com:443', 42);

        Assert::count($scheduled, 1);
        Assert::same($scheduled[0]['hook'], 'recrawler/webmaster/ping/yandex-webmaster');
        Assert::same($scheduled[0]['args']['post_id'], 42);
        Assert::same($scheduled[0]['args']['host_id'], 'https:www.kobzarev.com:443');
    }

    public function schedulesNothingWithoutSelectedHost(): void
    {
        Assert::count($this->collectScheduledPings('', 42), 0);
    }
}
