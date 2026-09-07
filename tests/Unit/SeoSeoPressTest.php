<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\SeoPress;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoSeoPressTest
{
    public function detectsNoindex(): void
    {
        _reset_wp_mocks();
        _set_wp_override('get_post_meta', static fn ($post_id, $key, $single = false) => 'yes');

        Assert::true((new SeoPress())->is_post_noindex(42));
    }

    public function treatsEmptyMetaAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_wp_override('get_post_meta', static fn () => '');

        Assert::null((new SeoPress())->is_post_noindex(42));
    }
}
