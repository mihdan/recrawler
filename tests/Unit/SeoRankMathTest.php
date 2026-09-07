<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\RankMath;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoRankMathTest
{
    public function detectsNoindex(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('rankmath_robots', ['noindex']);

        Assert::true((new RankMath())->is_post_noindex(42));
    }

    public function detectsIndexablePost(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('rankmath_robots', ['index']);

        Assert::false((new RankMath())->is_post_noindex(42));
    }

    public function treatsEmptyMetaAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('rankmath_robots', []);

        Assert::null((new RankMath())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('rankmath_robots', 'throw');

        Assert::null((new RankMath())->is_post_noindex(42));
    }
}
