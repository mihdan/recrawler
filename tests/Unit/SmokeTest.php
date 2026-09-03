<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Utils;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SmokeTest
{
    public function pluginSlugIsExposed(): void
    {
        Assert::same(Utils::get_plugin_slug(), 'recrawler');
    }

    public function pluginPrefixIsExposed(): void
    {
        Assert::same(Utils::get_plugin_prefix(), 'recrawler');
    }
}
