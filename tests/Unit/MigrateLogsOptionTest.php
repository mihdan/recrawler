<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Migrations\Migrations;
use Testo\Assert;
use Testo\Test;

#[Test]
final class MigrateLogsOptionTest
{
    public function mergesLogsIntoGeneralKeepingOldValues(): void
    {
        _reset_wp_mocks();

        $saved = [];

        _set_wp_override('get_option', static function ($option, $default = false) {
            if ($option === 'recrawler_logs') {
                return ['enable' => 'off', 'lifetime' => 30];
            }

            if ($option === 'recrawler_general') {
                return ['ping_delay' => 5];
            }

            return $default;
        });

        _set_wp_override('update_option', static function ($option, $value) use (&$saved) {
            $saved[$option] = $value;
            return true;
        });

        Assert::true((new Migrations())->migrate_1_0_0());
        Assert::same($saved['recrawler_general']['enable'], 'off');
        Assert::same($saved['recrawler_general']['lifetime'], 30);
        Assert::same($saved['recrawler_general']['ping_delay'], 5);
    }

    public function doesNothingWhenLogsOptionIsAbsent(): void
    {
        _reset_wp_mocks();

        $saved = [];

        _set_wp_override('get_option', static function ($option, $default = false) {
            return $default;
        });

        _set_wp_override('update_option', static function ($option, $value) use (&$saved) {
            $saved[$option] = $value;
            return true;
        });

        Assert::true((new Migrations())->migrate_1_0_0());
        Assert::same($saved, []);
    }
}
