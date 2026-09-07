<?php

foreach (['file', 'phar'] as $protocol) {
    $wrappers = stream_get_wrappers();
    if (in_array($protocol, $wrappers, true)) {
        @stream_wrapper_restore($protocol);
    }
}

$patched = false;
foreach (spl_autoload_functions() as $loader) {
    if (is_array($loader) && is_object($loader[0])) {
        $ref = new \ReflectionClass($loader[0]);
        $name = $ref->getName();
        if ($name === 'Composer\Autoload\ClassLoader' || $ref->isSubclassOf('Composer\Autoload\ClassLoader')) {
            $prop = $ref->getProperty('classMap');
            $prop->setAccessible(true);
            $map = $prop->getValue($loader[0]);
            foreach ($map as $class => $file) {
                if (strpos($file, 'wordpress-stubs/wordpress-stubs.php') !== false) {
                    unset($map[$class]);
                }
            }
            $prop->setValue($loader[0], $map);
            $patched = true;
            break;
        }
    }
}

define('RECRAWLER_VERSION', '1.0.0');
define('RECRAWLER_SLUG', 'recrawler');
define('RECRAWLER_PREFIX', 'recrawler');
define('RECRAWLER_NAME', 'ReCrawler');
define('RECRAWLER_FILE', __DIR__ . '/../../recrawler.php');
define('RECRAWLER_DIR', __DIR__ . '/..');
define('RECRAWLER_BASENAME', 'recrawler/recrawler.php');
define('RECRAWLER_URL', 'https://example.com/wp-content/plugins/recrawler/');
define('ABSPATH', '/tmp/wordpress/');
define('HOUR_IN_SECONDS', 3600);
define('WP_DEBUG', false);

$GLOBALS['__wp_mock_calls'] = [];
$GLOBALS['__wp_overrides'] = [];

function _reset_wp_mocks() {
    $GLOBALS['__wp_mock_calls'] = [];
    $GLOBALS['__wp_overrides'] = [];
    $GLOBALS['__wp_settings_sections'] = [];
    $GLOBALS['__wp_settings_fields'] = [];
    $GLOBALS['__wp_registered_settings'] = [];
    $GLOBALS['__wp_submenus'] = [];
    $GLOBALS['__wp_menus'] = [];
}

function _expect_wp_mock($function_name, $expected_count = 1) {
    $GLOBALS['__wp_mock_calls'][$function_name] = [
        'expected' => $expected_count,
        'actual' => 0,
    ];
}

function _assert_wp_mock($function_name) {
    if (!isset($GLOBALS['__wp_mock_calls'][$function_name])) {
        return;
    }
    $call = $GLOBALS['__wp_mock_calls'][$function_name];
    if ($call['expected'] === 0 && $call['actual'] > 0) {
        throw new \PHPUnit\Framework\ExpectationFailedException(
            "$function_name was not expected to be called but was called {$call['actual']} time(s)"
        );
    }
    if ($call['expected'] > 0 && $call['actual'] !== $call['expected']) {
        throw new \PHPUnit\Framework\ExpectationFailedException(
            "Method $function_name was expected to be called {$call['expected']} time(s), actually called {$call['actual']} time(s)"
        );
    }
}

function _track_wp_mock($function_name) {
    if (isset($GLOBALS['__wp_mock_calls'][$function_name])) {
        $GLOBALS['__wp_mock_calls'][$function_name]['actual']++;
    }
}

function _set_wp_override($function_name, callable $callback) {
    $GLOBALS['__wp_overrides'][$function_name] = $callback;
}

function _call_if_overridden($function_name, ...$args) {
    if (isset($GLOBALS['__wp_overrides'][$function_name])) {
        return ($GLOBALS['__wp_overrides'][$function_name])(...$args);
    }
    return null;
}

if (!class_exists('WP_Post')) {
    class WP_Post {
        public $ID = 0;
        public $post_type = 'post';
        public $post_status = 'publish';
        public $post_date = '';
        public $post_name = '';
        public $post_parent = 0;
    }
}
if (!class_exists('WP_Comment')) {
    class WP_Comment {
        public $comment_ID = 0;
        public $comment_approved = 0;
        public $comment_post_ID = 0;
    }
}
if (!class_exists('WP_Query')) {
    class WP_Query {
        private $data = [];
        public function get($key) { return $this->data[$key] ?? null; }
        public function set($key, $value) { $this->data[$key] = $value; }
    }
}
if (!class_exists('WP_Site')) { class WP_Site {} }
if (!class_exists('WP_List_Table')) { class WP_List_Table {} }
if (!class_exists('WP')) { class WP {} }

if (!function_exists('__')) { function __($text, $domain = 'default') { return $text; } }
if (!function_exists('esc_html')) { function esc_html($text) { return $text; } }
if (!function_exists('esc_html__')) { function esc_html__($text, $domain = 'default') { return $text; } }
if (!function_exists('esc_attr')) { function esc_attr($text) { return $text; } }
if (!function_exists('esc_url')) { function esc_url($url) { return $url; } }
if (!function_exists('esc_url_raw')) { function esc_url_raw($url) { return $url; } }
if (!function_exists('wp_kses_post_deep')) { function wp_kses_post_deep($data) { return $data; } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($str) { return $str; } }
if (!function_exists('wp_kses')) { function wp_kses($string, $allowed_html = [], $allowed_protocols = []) { return $string; } }
if (!function_exists('sanitize_key')) { function sanitize_key($key) { return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key)); } }
if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page, $args = []) {
        $GLOBALS['__wp_settings_sections'][] = compact('id', 'title', 'page');
    }
}
if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = []) {
        $GLOBALS['__wp_settings_fields'][] = compact('id', 'title', 'page', 'section') + ['args' => $args];
    }
}
if (!function_exists('register_setting')) {
    function register_setting($group, $name, $args = []) {
        $GLOBALS['__wp_registered_settings'][] = compact('group', 'name');
    }
}
if (!function_exists('add_option')) { function add_option($option, $value = '', $deprecated = '', $autoload = true) { return true; } }
if (!function_exists('wp_unslash')) { function wp_unslash($value) { return $value; } }
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = '') {
        if (is_object($args)) { $args = (array) $args; }
        if (is_array($defaults) && is_array($args)) { return array_merge($defaults, $args); }
        return $args;
    }
}
if (!function_exists('wp_json_encode')) { function wp_json_encode($data, $flags = 0, $depth = 512) { return json_encode($data); } }
if (!function_exists('trailingslashit')) { function trailingslashit($url) { return $url; } }
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$extra) {
        _track_wp_mock('apply_filters');
        $result = _call_if_overridden('apply_filters', $tag, $value, ...$extra);
        return $result ?? $value;
    }
}
if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        _track_wp_mock('do_action');
        _call_if_overridden('do_action', $tag, ...$args);
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        _track_wp_mock('add_action');
        return null;
    }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
        _track_wp_mock('add_filter');
        return null;
    }
}
if (!function_exists('do_action')) { function do_action($tag, ...$args) {} }
if (!function_exists('add_action')) { function add_action($tag, $callback, $priority = 10, $accepted_args = 1) { return null; } }
if (!function_exists('add_filter')) { function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) { return null; } }
if (!function_exists('remove_action')) { function remove_action($tag, $callback, $priority = 10) { return true; } }
if (!function_exists('register_activation_hook')) { function register_activation_hook($file, $callback) {} }
if (!function_exists('status_header')) { function status_header($code) { _track_wp_mock('status_header'); $r = _call_if_overridden('status_header', $code); return $r; } }
if (!function_exists('nocache_headers')) { function nocache_headers() { _track_wp_mock('nocache_headers'); $r = _call_if_overridden('nocache_headers'); return $r; } }
if (!function_exists('is_post_publicly_viewable')) { function is_post_publicly_viewable($post) { $r = _call_if_overridden('is_post_publicly_viewable', $post); return $r ?? true; } }
if (!function_exists('get_permalink')) { function get_permalink($post = 0) { return 'https://example.com/post-1/'; } }
if (!function_exists('get_home_url')) { function get_home_url($blog_id = null, $path = '', $scheme = null) { return 'https://example.com'; } }
if (!function_exists('get_bloginfo')) { function get_bloginfo($show = '', $filter = 'raw') { return 'https://example.com/feed/'; } }
if (!function_exists('admin_url')) { function admin_url($path = '', $scheme = 'admin') { return 'https://example.com/wp-admin/' . ltrim((string) $path, '/'); } }
if (!function_exists('get_admin_page_title')) { function get_admin_page_title($title = '') { return 'ReCrawler Log'; } }
if (!function_exists('get_self_link')) { function get_self_link() { return 'https://example.com/feed/rss2/'; } }
if (!function_exists('get_feed_link')) { function get_feed_link($feed = '', $blog_id = null) { return 'https://example.com/feed/rss2/'; } }
if (!function_exists('get_author_feed_link')) { function get_author_feed_link($author_id, $feed = '') { return 'https://example.com/author/admin/feed/'; } }
if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) { return ['body' => '', 'response' => ['code' => 200]]; }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) { return '{"ok":true}'; }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) { return 200; }
}
if (!function_exists('wp_remote_retrieve_response_message')) {
    function wp_remote_retrieve_response_message($response) { return 'OK'; }
}
if (!function_exists('is_admin')) { function is_admin() { $r = _call_if_overridden('is_admin'); return $r ?? true; } }
if (!function_exists('is_multisite')) { function is_multisite() { return false; } }
if (!function_exists('wp_doing_cron')) { function wp_doing_cron() { $r = _call_if_overridden('wp_doing_cron'); return $r ?? false; } }
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        _track_wp_mock('get_option');
        $result = _call_if_overridden('get_option', $option, $default);
        return $result ?? $default;
    }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        _track_wp_mock('update_option');
        $result = _call_if_overridden('update_option', $option, $value, $autoload);
        return $result ?? true;
    }
}
if (!function_exists('get_post_meta')) { function get_post_meta($post_id, $key, $single = false) { $r = _call_if_overridden('get_post_meta', $post_id, $key, $single); return $r ?? ($single ? '' : ''); } }
if (!function_exists('update_post_meta')) { function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') { return true; } }
if (!function_exists('get_comment_meta')) { function get_comment_meta($comment_id, $key, $single = false) { $r = _call_if_overridden('get_comment_meta', $comment_id, $key, $single); return $r ?? ''; } }
if (!function_exists('update_comment_meta')) { function update_comment_meta($comment_id, $meta_key, $meta_value, $prev_value = '') { return true; } }
if (!function_exists('get_term_meta')) { function get_term_meta($term_id, $key, $single = false) { $r = _call_if_overridden('get_term_meta', $term_id, $key, $single); return $r ?? ''; } }
if (!function_exists('update_term_meta')) { function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') { return true; } }
if (!function_exists('wp_is_post_revision')) { function wp_is_post_revision($post) { $r = _call_if_overridden('wp_is_post_revision', $post); return $r ?? false; } }
if (!function_exists('wp_is_post_autosave')) { function wp_is_post_autosave($post) { $r = _call_if_overridden('wp_is_post_autosave', $post); return $r ?? false; } }
if (!function_exists('header')) { function header($header, $replace = true, $http_response_code = null) {} }
if (!function_exists('get_post_type_object')) { function get_post_type_object($post_type) { return false; } }
if (!function_exists('get_post_type')) {
    function get_post_type($post = null) {
        if ($post instanceof WP_Post) return $post->post_type ?? 'post';
        return 'post';
    }
}
if (!function_exists('is_wp_error')) { function is_wp_error($thing) { return false; } }
if (!function_exists('get_the_title')) { function get_the_title($post = 0) { return 'Test Post'; } }
if (!function_exists('dbDelta')) { function dbDelta($queries, $execute = true) {} }
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = null, $icon = '', $position = null) {
        $GLOBALS['__wp_menus'][] = compact('menu_title', 'menu_slug');
        return 'toplevel_page_' . $menu_slug;
    }
}
if (!function_exists('remove_submenu_page')) {
    function remove_submenu_page($menu_slug, $submenu_slug) {
        $GLOBALS['__wp_submenus'] = array_values(array_filter(
            $GLOBALS['__wp_submenus'] ?? [],
            static function ($item) use ($submenu_slug) { return $item['menu_slug'] !== $submenu_slug; }
        ));
        return false;
    }
}
if (!function_exists('get_current_screen')) {
    function get_current_screen() {
        $r = _call_if_overridden('get_current_screen');
        return $r ?? (object) ['id' => 'toplevel_page_recrawler'];
    }
}
if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null, $position = null) {
        $GLOBALS['__wp_submenus'][] = compact('parent_slug', 'menu_title', 'menu_slug');
        return $menu_slug === 'recrawler-log' ? 'recrawler-log' : 'recrawler_page_' . $menu_slug;
    }
}
if (!function_exists('wp_generate_uuid4')) { function wp_generate_uuid4() { return '550e8400-e29b-41d4-a716-446655440000'; } }
if (!function_exists('get_post')) { function get_post($post = null, $output = OBJECT, $filter = 'raw') { return null; } }
if (!function_exists('_doing_it_wrong')) { function _doing_it_wrong($function, $message, $version) {} }
if (!function_exists('delete_option')) { function delete_option($option) { _track_wp_mock('delete_option'); $r = _call_if_overridden('delete_option', $option); return $r ?? true; } }
if (!function_exists('switch_to_blog')) { function switch_to_blog($blog_id) {} }
if (!function_exists('restore_current_blog')) { function restore_current_blog() {} }
if (!function_exists('get_sites')) { function get_sites($args = []) { return []; } }
if (!function_exists('wp_list_pluck')) { function wp_list_pluck($list, $field, $index_key = null) { return []; } }
if (!function_exists('is_serialized')) {
    function is_serialized($data, $strict = true) {
        return is_string($data) && preg_match('/^[aOs]:\d+:/', $data) === 1;
    }
}
if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($data) {
        return is_serialized($data) ? @unserialize($data) : $data;
    }
}
if (!function_exists('get_post_types')) { function get_post_types($args = [], $output = 'names', $operator = 'and') { $r = _call_if_overridden('get_post_types', $args, $output, $operator); return $r ?? []; } }
if (!function_exists('get_taxonomies')) { function get_taxonomies($args = [], $output = 'names', $operator = 'and') { $r = _call_if_overridden('get_taxonomies', $args, $output, $operator); return $r ?? []; } }
if (!function_exists('add_thickbox')) { function add_thickbox() {} }
if (!function_exists('get_post_type_archive_feed_link')) { function get_post_type_archive_feed_link($post_type, $feed = '') { return ''; } }
if (!function_exists('wp_get_post_tags')) { function wp_get_post_tags($post_id = 0, $args = []) { return []; } }
if (!function_exists('wp_get_post_categories')) { function wp_get_post_categories($post_id = 0, $args = []) { return []; } }
if (!function_exists('get_term_feed_link')) { function get_term_feed_link($term_id, $taxonomy = '', $feed = '') { return false; } }
if (!function_exists('get_object_taxonomies')) { function get_object_taxonomies($object, $output = 'names') { return []; } }
if (!function_exists('wp_get_post_terms')) { function wp_get_post_terms($post_id, $taxonomy, $args = []) { return []; } }
if (!function_exists('get_term_link')) { function get_term_link($term, $taxonomy = '') { return 'https://example.com/term/1/'; } }
if (!function_exists('wp_parse_url')) { function wp_parse_url($url, $component = -1) { $r = _call_if_overridden('wp_parse_url', $url, $component); return $r ?? 'example.com'; } }
if (!function_exists('get_home_url')) { function get_home_url($blog_id = null, $path = '', $scheme = null) { return 'https://example.com'; } }
if (!function_exists('wp_generate_uuid4')) { function wp_generate_uuid4() { $r = _call_if_overridden('wp_generate_uuid4'); return $r ?? '550e8400-e29b-41d4-a716-446655440000'; } }
if (!function_exists('as_enqueue_async_action')) { function as_enqueue_async_action($hook, $args = [], $group = '', $unique = false, $recurring = false) { $r = _call_if_overridden('as_enqueue_async_action', $hook, $args, $group, $unique, $recurring); return $r ?? 0; } }
if (!function_exists('as_schedule_single_action')) { function as_schedule_single_action($timestamp, $hook, $args = [], $group = '') { return 0; } }
if (!function_exists('as_schedule_recurring_action')) { function as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args = [], $group = '') { return 0; } }
if (!function_exists('as_schedule_cron_action')) { function as_schedule_cron_action($timestamp, $schedule, $hook, $args = [], $group = '') { return 0; } }
if (!function_exists('as_unschedule_action')) { function as_unschedule_action($hook, $args = [], $group = '') {} }
if (!function_exists('as_unschedule_all_actions')) { function as_unschedule_all_actions($hook, $args = [], $group = '') {} }
if (!function_exists('as_has_scheduled_actions')) { function as_has_scheduled_actions($hook, $args = [], $group = '') { return false; } }
if (!function_exists('as_next_scheduled_action')) { function as_next_scheduled_action($hook, $args = [], $group = '') { return false; } }
if (!function_exists('function_exists')) {
    // cannot override built-in
}

$GLOBALS['wpdb'] = new class {
    public $prefix = 'wp_';
    public function prepare($sql, ...$args) { return $sql; }
    public function query($sql) { return null; }
    public function insert($table, $data, $format = null) { return true; }
};

require_once __DIR__ . '/wp-functions.php';

