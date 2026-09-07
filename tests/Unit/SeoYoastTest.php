<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\Yoast;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoYoastTest
{
    public function detectsNoindex(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', ['index' => 'noindex']);

        Assert::true((new Yoast())->is_post_noindex(42));
    }

    public function detectsIndexablePost(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', ['index' => 'index']);

        Assert::false((new Yoast())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', 'throw');

        Assert::null((new Yoast())->is_post_noindex(42));
    }
}
