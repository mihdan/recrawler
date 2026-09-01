<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Hooks;
use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class HooksTest extends TestCase {

	private $wposa;

	private function make_post(int $id = 1, string $type = 'post'): \WP_Post {
		$post = new \WP_Post();
		$post->ID = $id;
		$post->post_type = $type;
		return $post;
	}

	private function make_comment(int $id = 1, int $approved = 0, int $post_id = 1): \WP_Comment {
		$comment = new \WP_Comment();
		$comment->comment_ID = $id;
		$comment->comment_approved = $approved;
		$comment->comment_post_ID = $post_id;
		return $comment;
	}

	private function setup_defaults() {
		_reset_wp_mocks();
		_set_wp_override('wp_is_post_revision', function () { return false; });
		_set_wp_override('wp_is_post_autosave', function () { return false; });
		_set_wp_override('is_post_publicly_viewable', function () { return true; });
		_set_wp_override('current_time', function () { return 1000; });
		_set_wp_override('get_post_meta', function () { return 0; });
		_set_wp_override('update_post_meta', function () { return true; });
		_set_wp_override('get_comment_meta', function () { return 0; });
		_set_wp_override('update_comment_meta', function () { return true; });
		_set_wp_override('get_term_meta', function () { return 0; });
		_set_wp_override('update_term_meta', function () { return true; });
	}

	public function setUp(): void {
		parent::setUp();
		$this->wposa = $this->createMock(WPOSA::class);
		unset($_REQUEST['meta-box-loader'], $_REQUEST['bulk_edit']);
	}

	public function test_constructor_sets_ping_delay() {
		$this->wposa->method('get_option')
			->with('ping_delay', 'general', 60)
			->willReturn(120);

		$hooks = new Hooks($this->wposa);

		$ref = new \ReflectionProperty(Hooks::class, 'ping_delay');
		$ref->setAccessible(true);

		$this->assertSame(120, $ref->getValue($hooks));
	}

	public function test_setup_hooks_registers_all_hooks() {
		$this->setup_defaults();
		$hooks = new Hooks($this->wposa);
		_expect_wp_mock('add_action', 4);

		$hooks->setup_hooks();
	}

	protected function tearDown(): void {
		parent::tearDown();
		_assert_wp_mock('add_action');
	}

	public function test_post_updated_returns_early_if_not_publish() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
			['ping_on_post_updated', 'general', 'on', 'on'],
			['ping_on_post', 'general', 'on', 'on'],
		]);

		$hooks = new Hooks($this->wposa);
		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('draft', 'draft', $this->make_post());
		$this->assertEmpty($do_action_calls);
	}

	public function test_post_updated_returns_early_if_revision() {
		$this->setup_defaults();
		_set_wp_override('wp_is_post_revision', function () { return true; });
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('publish', 'draft', $this->make_post());
		$this->assertEmpty($do_action_calls);
	}

	public function test_post_updated_returns_early_if_wrong_post_type() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('publish', 'draft', $this->make_post(1, 'page'));
		$this->assertEmpty($do_action_calls);
	}

	public function test_post_updated_fires_action_on_new_post() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
			['ping_on_post', 'general', 'on', 'on'],
		]);

		$hooks = new Hooks($this->wposa);
		$post = $this->make_post();

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('publish', 'draft', $post);

		$this->assertCount(1, $do_action_calls);
		$this->assertSame('recrawler/post_added', $do_action_calls[0]['tag']);
		$this->assertSame(1, $do_action_calls[0]['args'][0]);
		$this->assertSame($post, $do_action_calls[0]['args'][1]);
	}

	public function test_post_updated_fires_action_on_updated_post() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
			['ping_on_post_updated', 'general', 'on', 'on'],
		]);

		$hooks = new Hooks($this->wposa);
		$post = $this->make_post();

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('publish', 'publish', $post);

		$this->assertCount(1, $do_action_calls);
		$this->assertSame('recrawler/post_updated', $do_action_calls[0]['tag']);
		$this->assertSame(1, $do_action_calls[0]['args'][0]);
		$this->assertSame($post, $do_action_calls[0]['args'][1]);
	}

	public function test_post_updated_respects_delay() {
		$this->setup_defaults();
		_set_wp_override('get_post_meta', function () { return 990; });
		$this->wposa->method('get_option')->willReturnMap([
			['post_types', 'general', [], ['post']],
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->post_updated('publish', 'draft', $this->make_post());
		$this->assertEmpty($do_action_calls);
	}

	public function test_comment_updated_returns_early_if_not_approved() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$comment = $this->make_comment();

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->comment_updated('spam', 'approved', $comment);
		$this->assertEmpty($do_action_calls);
	}

	public function test_comment_inserted_returns_early_if_not_approved() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$comment = $this->make_comment(1, 0, 1);

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->comment_inserted(1, $comment);
		$this->assertEmpty($do_action_calls);
	}

	public function test_comment_inserted_fires_action_when_approved() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$comment = $this->make_comment(1, 1, 5);

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->comment_inserted(1, $comment);

		$this->assertCount(1, $do_action_calls);
		$this->assertSame('recrawler/comment_updated', $do_action_calls[0]['tag']);
		$this->assertSame(5, $do_action_calls[0]['args'][0]);
	}

	public function test_term_updated_fires_action() {
		$this->setup_defaults();
		$this->wposa->method('get_option')->willReturnMap([
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);

		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->term_updated(10, 20, 'category');

		$this->assertCount(1, $do_action_calls);
		$this->assertSame('recrawler/term_updated', $do_action_calls[0]['tag']);
		$this->assertSame(10, $do_action_calls[0]['args'][0]);
		$this->assertSame('category', $do_action_calls[0]['args'][1]);
	}

	public function test_term_updated_respects_delay() {
		$this->setup_defaults();
		_set_wp_override('get_term_meta', function () { return 990; });
		$this->wposa->method('get_option')->willReturnMap([
			['ping_delay', 'general', 60, 60],
		]);

		$hooks = new Hooks($this->wposa);
		$do_action_calls = [];
		_set_wp_override('do_action', function ($tag, ...$args) use (&$do_action_calls) {
			$do_action_calls[] = ['tag' => $tag, 'args' => $args];
		});

		$hooks->term_updated(10, 20, 'category');
		$this->assertEmpty($do_action_calls);
	}
}
