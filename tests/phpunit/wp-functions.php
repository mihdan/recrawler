<?php

namespace Mihdan\ReCrawler;

function current_time($type = 'timestamp', $gmt = false) {
    $r = \_call_if_overridden('current_time', $type, $gmt);
    return $r ?? 1000;
}

namespace Mihdan\ReCrawler\Logger;

function current_time($type = 'timestamp', $gmt = false) {
    $r = \_call_if_overridden('current_time', $type, $gmt);
    return $r ?? 1000;
}
function get_option($option, $default = false) {
    \_track_wp_mock('get_option');
    $r = \_call_if_overridden('get_option', $option, $default);
    return $r ?? $default;
}
function update_option($option, $value, $autoload = null) {
    \_track_wp_mock('update_option');
    $r = \_call_if_overridden('update_option', $option, $value, $autoload);
    return $r ?? true;
}
function get_post_meta($post_id, $key, $single = false) {
    $r = \_call_if_overridden('get_post_meta', $post_id, $key, $single);
    return $r ?? ($single ? '' : '');
}
function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
    return true;
}
function get_comment_meta($comment_id, $key, $single = false) {
    $r = \_call_if_overridden('get_comment_meta', $comment_id, $key, $single);
    return $r ?? '';
}
function update_comment_meta($comment_id, $meta_key, $meta_value, $prev_value = '') {
    return true;
}
function get_term_meta($term_id, $key, $single = false) {
    $r = \_call_if_overridden('get_term_meta', $term_id, $key, $single);
    return $r ?? '';
}
function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
    return true;
}
function do_action($tag, ...$args) {
    \_track_wp_mock('do_action');
    \_call_if_overridden('do_action', $tag, ...$args);
}
function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    \_track_wp_mock('add_action');
    return null;
}
function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    \_track_wp_mock('add_filter');
    return null;
}
function apply_filters($tag, $value, ...$extra) {
    \_track_wp_mock('apply_filters');
    $r = \_call_if_overridden('apply_filters', $tag, $value, ...$extra);
    return $r ?? $value;
}
function wp_is_post_revision($post) {
    $r = \_call_if_overridden('wp_is_post_revision', $post);
    return $r ?? false;
}
function wp_is_post_autosave($post) {
    $r = \_call_if_overridden('wp_is_post_autosave', $post);
    return $r ?? false;
}
function is_post_publicly_viewable($post) {
    $r = \_call_if_overridden('is_post_publicly_viewable', $post);
    return $r ?? true;
}
function get_home_url($blog_id = null, $path = '', $scheme = null) {
    return 'https://example.com';
}
function get_permalink($post = 0) {
    return 'https://example.com/post-1/';
}
function wp_generate_uuid4() {
    $r = \_call_if_overridden('wp_generate_uuid4');
    return $r ?? '550e8400-e29b-41d4-a716-446655440000';
}
function wp_parse_url($url, $component = -1) {
    return 'example.com';
}
function status_header($code) {
    \_track_wp_mock('status_header');
}
function nocache_headers() {
    \_track_wp_mock('nocache_headers');
}
function wp_doing_cron() {
    $r = \_call_if_overridden('wp_doing_cron');
    return $r ?? false;
}
function is_admin() {
    $r = \_call_if_overridden('is_admin');
    return $r ?? true;
}
function get_post_type_object($post_type) {
    return false;
}
function get_bloginfo($show = '', $filter = 'raw') {
    return 'https://example.com/feed/';
}
function get_feed_link($feed = '', $blog_id = null) {
    return 'https://example.com/feed/rss2/';
}
function get_term_feed_link($term_id, $taxonomy = '', $feed = '') {
    return false;
}
function wp_get_post_tags($post_id = 0, $args = []) {
    return [];
}
function wp_get_post_categories($post_id = 0, $args = []) {
    return [];
}
function get_object_taxonomies($object, $output = 'names') {
    return [];
}
function wp_get_post_terms($post_id, $taxonomy, $args = []) {
    return [];
}
function get_author_feed_link($author_id, $feed = '') {
    return 'https://example.com/author/admin/feed/';
}
function get_post_type_archive_feed_link($post_type, $feed = '') {
    return '';
}
function function_exists($function) {
    return \function_exists($function);
}
function delete_option($option) {
    return true;
}
function is_multisite() {
    return false;
}
function get_sites($args = []) {
    return [];
}
function switch_to_blog($blog_id) {}
function restore_current_blog() {}
function dbDelta($queries, $execute = true) {}
function register_activation_hook($file, $callback) {}
function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback, $position = null) {
    return 'recrawler-log';
}
function wp_kses_post_deep($data) {
    return $data;
}
function wp_parse_args($args, $defaults = '') {
    if (is_object($args)) { $args = (array) $args; }
    if (is_array($defaults) && is_array($args)) { return array_merge($defaults, $args); }
    return $args;
}
function wp_remote_post($url, $args = []) {
    return ['body' => '', 'response' => ['code' => 200]];
}
function wp_remote_retrieve_response_code($response) {
    return 200;
}
function wp_remote_retrieve_response_message($response) {
    return 'OK';
}
function wp_remote_retrieve_body($response) {
    return '{"ok":true}';
}
function esc_url($url) {
    return $url;
}
function esc_url_raw($url) {
    return $url;
}
function esc_html($text) {
    return $text;
}
function get_self_link() {
    return 'https://example.com/feed/rss2/';
}
function is_wp_error($thing) {
    return false;
}
function get_post($post = null, $output = OBJECT, $filter = 'raw') {
    return null;
}

