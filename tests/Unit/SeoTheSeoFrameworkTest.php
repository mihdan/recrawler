<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\TheSeoFramework;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoTheSeoFrameworkTest
{
    public function detectsNoindex(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('tsf_meta', ['noindex' => 'noindex']);

        Assert::true((new TheSeoFramework())->is_post_noindex(42));
    }

    public function detectsIndexablePost(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('tsf_meta', ['noindex' => '']);

        Assert::false((new TheSeoFramework())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('tsf_meta', 'throw');

        Assert::null((new TheSeoFramework())->is_post_noindex(42));
    }

    public function treatsMissingKeyAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('tsf_meta', []);

        Assert::null((new TheSeoFramework())->is_post_noindex(42));
    }
}
