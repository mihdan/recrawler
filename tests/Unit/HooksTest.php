<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use ArrayObject;
use Mihdan\ReCrawler\Hooks;
use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Seo\Indexability;
use Mihdan\ReCrawler\Seo\SeoPluginInterface;
use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Test;
use WP_Comment;

#[Test]
final class HooksTest
{
    private function noindexDetector(): SeoPluginInterface
    {
        return new class implements SeoPluginInterface {
            public function get_slug(): string
            {
                return 'yoast';
            }

            public function is_active(): bool
            {
                return true;
            }

            public function is_post_noindex(int $post_id): ?bool
            {
                return true;
            }
        };
    }

    /**
     * Собрать Hooks на настоящих WPOSA/Indexability.
     *
     * @param bool $indexable   Отвечает ли Indexability::is_post_indexable().
     * @param bool $sitePublic  Значение blog_public.
     */
    private function hooks(bool $indexable = true, bool $sitePublic = true): Hooks
    {
        _reset_wp_mocks();

        _set_wp_override('wp_is_post_revision', static fn () => false);
        _set_wp_override('wp_is_post_autosave', static fn () => false);
        _set_wp_override('is_post_publicly_viewable', static fn () => true);
        _set_wp_override('current_time', static fn () => 1000);
        _set_wp_override('get_post_meta', static fn () => 0);
        _set_wp_override('update_post_meta', static fn () => true);
        _set_wp_override('get_comment_meta', static fn () => 0);
        _set_wp_override('update_comment_meta', static fn () => true);
        _set_wp_override('get_term_meta', static fn () => 0);
        _set_wp_override('update_term_meta', static fn () => true);

        _set_wp_override('get_option', static function ($option, $default = false) use ($sitePublic) {
            if ($option === 'blog_public') {
                return $sitePublic ? 1 : 0;
            }

            if ($option === 'recrawler_general') {
                return [
                    'post_types'            => ['post'],
                    'ping_on_post'          => 'on',
                    'ping_on_post_updated'  => 'on',
                    'disable_for_bulk_edit' => 'on',
                ];
            }

            return $default;
        });

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $indexability = new Indexability(new Logger(), $indexable ? [] : [$this->noindexDetector()]);

        return new Hooks($wposa, $indexability);
    }

    private function collectDoActionCalls(): ArrayObject
    {
        $calls = new ArrayObject();

        _set_wp_override('do_action', static function ($tag, ...$args) use ($calls) {
            $calls->append(['tag' => $tag, 'args' => $args]);
        });

        return $calls;
    }

    private function comment(int $id = 1, int $approved = 1, int $postId = 1): WP_Comment
    {
        $comment = new WP_Comment();
        $comment->comment_ID = $id;
        $comment->comment_approved = $approved;
        $comment->comment_post_ID = $postId;

        return $comment;
    }

    public function commentInsertedSkipsNoindexPost(): void
    {
        $hooks = $this->hooks(indexable: false);
        $calls = $this->collectDoActionCalls();

        $hooks->comment_inserted(1, $this->comment(1, 1, 5));

        Assert::count($calls, 0);
    }

    public function commentInsertedFiresActionWhenIndexable(): void
    {
        $hooks = $this->hooks(indexable: true);
        $calls = $this->collectDoActionCalls();

        $hooks->comment_inserted(1, $this->comment(1, 1, 5));

        Assert::count($calls, 1);
        Assert::same($calls[0]['tag'], 'recrawler/comment_updated');
    }

    public function commentUpdatedSkipsNoindexPost(): void
    {
        $hooks = $this->hooks(indexable: false);
        $calls = $this->collectDoActionCalls();

        $hooks->comment_updated('approved', 'hold', $this->comment(1, 1, 7));

        Assert::count($calls, 0);
    }

    public function termUpdatedSkipsClosedSite(): void
    {
        $hooks = $this->hooks(indexable: true, sitePublic: false);
        $calls = $this->collectDoActionCalls();

        $hooks->term_updated(10, 20, 'category');

        Assert::count($calls, 0);
    }

    public function termUpdatedFiresActionOnPublicSite(): void
    {
        $hooks = $this->hooks(indexable: true, sitePublic: true);
        $calls = $this->collectDoActionCalls();

        $hooks->term_updated(10, 20, 'category');

        Assert::count($calls, 1);
        Assert::same($calls[0]['tag'], 'recrawler/term_updated');
    }
}
