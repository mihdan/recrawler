<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Seo\Indexability;
use Mihdan\ReCrawler\Seo\SeoPluginInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoIndexabilityTest
{
    /**
     * @param bool|null $answer
     */
    private function detector(string $slug, bool $active, $answer): SeoPluginInterface
    {
        return new class($slug, $active, $answer) implements SeoPluginInterface {
            public bool $asked = false;

            public function __construct(private string $slug, private bool $active, private $answer)
            {
            }

            public function get_slug(): string
            {
                return $this->slug;
            }

            public function is_active(): bool
            {
                return $this->active;
            }

            public function is_post_noindex(int $post_id): ?bool
            {
                $this->asked = true;

                return $this->answer;
            }
        };
    }

    private function publicSite(): void
    {
        _reset_wp_mocks();
        _set_wp_override('get_option', static fn ($option, $default = false) => $option === 'blog_public' ? 1 : $default);
    }

    public function noindexPostIsNotIndexable(): void
    {
        $this->publicSite();

        $indexability = new Indexability(new Logger(), [$this->detector('yoast', true, true)]);

        Assert::false($indexability->is_post_indexable(42));
    }

    public function undeterminedDetectorDefersToTheNextOne(): void
    {
        $this->publicSite();

        $second = $this->detector('rank-math', true, true);
        $indexability = new Indexability(new Logger(), [$this->detector('yoast', true, null), $second]);

        Assert::false($indexability->is_post_indexable(42));
        Assert::true($second->asked);
    }

    public function emptyListFailsOpen(): void
    {
        $this->publicSite();

        Assert::true((new Indexability(new Logger(), []))->is_post_indexable(42));
    }

    public function closedSiteIsNotIndexable(): void
    {
        _reset_wp_mocks();
        _set_wp_override('get_option', static fn ($option, $default = false) => $option === 'blog_public' ? 0 : $default);

        Assert::false((new Indexability(new Logger(), []))->is_post_indexable(42));
    }

    public function inactiveDetectorIsSkipped(): void
    {
        $this->publicSite();

        $inactive = $this->detector('yoast', false, true);

        Assert::true((new Indexability(new Logger(), [$inactive]))->is_post_indexable(42));
        Assert::false($inactive->asked);
    }

    public function filterCanOverrideNoindex(): void
    {
        $this->publicSite();
        _set_wp_override('apply_filters', static fn ($tag, $value, ...$args) => $tag === 'recrawler/is_post_indexable' ? true : $value);

        Assert::true((new Indexability(new Logger(), [$this->detector('yoast', true, true)]))->is_post_indexable(42));
    }
}
