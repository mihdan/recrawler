<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\Aioseo;
use Testo\Assert;
use Testo\Test;
use WP_Post;

#[Test]
final class SeoAioseoTest
{
    private function stubPost(): void
    {
        _set_wp_override('get_post', static function () {
            $post = new WP_Post();
            $post->ID = 42;
            $post->post_type = 'post';

            return $post;
        });
    }

    public function postLevelNoindexWins(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', (object) ['robots_default' => false, 'robots_noindex' => true]);

        Assert::true((new Aioseo())->is_post_noindex(42));
    }

    public function postLevelIndexWins(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', (object) ['robots_default' => false, 'robots_noindex' => false]);
        _set_seo_stub('aioseo_post_type_noindex', true);

        Assert::false((new Aioseo())->is_post_noindex(42));
    }

    public function fallsBackToPostTypeSetting(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', (object) ['robots_default' => true, 'robots_noindex' => false]);
        _set_seo_stub('aioseo_post_type_noindex', true);

        Assert::true((new Aioseo())->is_post_noindex(42));
    }

    public function postTypeOnDefaultsIsUndetermined(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', (object) ['robots_default' => true, 'robots_noindex' => false]);
        _set_seo_stub('aioseo_post_type_noindex', true);
        _set_seo_stub('aioseo_post_type_default', true);

        Assert::null((new Aioseo())->is_post_noindex(42));
    }

    public function treatsMissingMetaAsUndetermined(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', null);

        Assert::null((new Aioseo())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', 'throw');

        Assert::null((new Aioseo())->is_post_noindex(42));
    }
}
