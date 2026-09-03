# Единая навигация админки 1.0.0 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Привести админку плагина к единому виду — шапка и полоса вкладок на всех страницах, вкладки на настоящих URL и в левом меню, настройки логирования секцией внутри «Общего», предупреждение о несохранённых данных.

**Architecture:** Навигация переезжает с якорей и `localStorage` на параметр `?tab=`, сервер рисует только активную вкладку. `WPOSA` учится настоящим секциям внутри вкладки и отдаёт шапку отдельным публичным методом, который переиспользует страница журнала. Настройки логирования переезжают в опцию `recrawler_general` разовой миграцией.

**Tech Stack:** PHP 8.2, WordPress Settings API, WP_List_Table, ActionScheduler, jQuery. Тесты: PHPUnit 9.5 (`tests/phpunit/`) и Testo (`tests/Unit/`).

**Spec:** `docs/superpowers/specs/2026-09-03-admin-tabs-unification-design.md`

## Global Constraints

- Минимальный PHP — **8.2** (поднимается в задаче 1). `Requires PHP` в `recrawler.php:10` и `readme.txt:8`, `require.php` и `config.platform.php` в `composer.json`.
- Версия релиза — **1.0.0**, ветка `1.0.0`. Проставляется в `recrawler.php:5`, `recrawler.php:25` (`RECRAWLER_VERSION`), `readme.txt:7` (`Stable tag`), плюс запись в changelog. Тег без префикса `v`.
- Слаг страницы журнала **не меняется** — `recrawler-log`. Её `screen` остаётся собственным, код screen options, пагинации и массового удаления не трогается.
- `'plural' => 'logs'` в `src/Views/Log_List_Table.php:40` — это имя CSS-класса `table.logs`, на который завязаны стили уровней (`assets/css/admin.css:348-362`). К опциям отношения не имеет, не переименовывать.
- Весь вывод `show_navigation()` проходит через `wp_kses( ..., self::ALLOWED_HTML )` (`src/Views/WPOSA.php:27-119`). Новые атрибуты в разметке навигации должны быть в этом белом списке, иначе молча вырежутся.
- Тексты интерфейса — через `__()` с доменом `recrawler`.
- Каждая задача заканчивается зелёными `composer test` и `composer test:testo`.

---

### Task 1: PHP 8.2 и раннер Testo

Testo требует PHP >= 8.2, а `config.platform.php` в `composer.json` фиксирует 8.1 и не даст его установить. Поднимаем планку и заводим второй раннер с общими WP-заглушками.

**Files:**
- Modify: `composer.json` (`require.php`, `config.platform.php`, `require-dev`, `scripts`)
- Modify: `recrawler.php:10` (`Requires PHP`)
- Modify: `readme.txt:8` (`Requires PHP`)
- Create: `testo.php`
- Create: `tests/stubs/wp.php`
- Modify: `tests/phpunit/bootstrap.php`
- Create: `tests/Unit/SmokeTest.php`
- Modify: `.github/workflows/deploy.yml`

**Interfaces:**
- Produces: `tests/stubs/wp.php` — файл с константами плагина и заглушками функций WordPress, подключаемый обоими раннерами. Функции-хелперы `_reset_wp_mocks()`, `_expect_wp_mock()`, `_assert_wp_mock()`, `_set_wp_override()`, `_call_if_overridden()` сохраняют текущие сигнатуры.
- Produces: скрипт `composer test:testo`.

- [ ] **Step 1: Поднять требования до PHP 8.2**

В `composer.json`:

```json
"require": {
    "php": ">=8.2",
```
```json
"config": {
    "platform": {
      "php": "8.2"
    },
```

В `recrawler.php` строка 10: ` * Requires PHP: 8.2`
В `readme.txt` строка 8: `Requires PHP: 8.2`

- [ ] **Step 2: Установить Testo**

```bash
composer require --dev testo/testo
```

Ожидается: пакет ставится без `--ignore-platform-req`. Если composer всё ещё ругается на платформу — значит шаг 1 применён не полностью.

- [ ] **Step 3: Проверить фактическое API установленной версии**

```bash
ls vendor/testo/testo/core/Attributes/ && sed -n '1,60p' vendor/testo/testo/core/Assert.php
```

Документация на сайте и README расходятся: README вешает `#[Test]` на класс, getting-started — на метод с `use Testo\Attributes\Test;`. Дальше по плану используется вариант из getting-started. **Если установленная версия ведёт себя иначе — правь примеры тестов во всех задачах под неё, это не повод менять структуру плана.**

- [ ] **Step 4: Вынести WP-заглушки в общий файл**

Создать `tests/stubs/wp.php`, перенеся в него содержимое `tests/phpunit/bootstrap.php` со строки с `define('RECRAWLER_VERSION', ...)` до конца блока заглушек (включая `$GLOBALS['wpdb']` и `require_once __DIR__ . '/wp-functions.php'`, поправив путь на `__DIR__ . '/../phpunit/wp-functions.php'`).

В начало файла добавить защиту от двойного подключения:

```php
<?php
if ( defined( 'RECRAWLER_VERSION' ) ) {
    return;
}
```

В `tests/phpunit/bootstrap.php` вместо перенесённого блока оставить:

```php
require_once __DIR__ . '/../stubs/wp.php';
```

- [ ] **Step 5: Прогнать PHPUnit — ничего не должно сломаться**

Run: `composer test`
Expected: PASS, 148 тестов.

- [ ] **Step 6: Конфиг Testo**

Создать `testo.php` в корне:

```php
<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

require_once __DIR__ . '/tests/stubs/wp.php';

return new ApplicationConfig(
    suites: [
        new SuiteConfig(
            name: 'Unit',
            location: ['tests/Unit'],
        ),
    ],
);
```

- [ ] **Step 7: Дымовой тест Testo**

Создать `tests/Unit/SmokeTest.php`:

```php
<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Utils;
use Testo\Assert;
use Testo\Attributes\Test;

final class SmokeTest
{
    #[Test]
    public function pluginSlugIsExposed(): void
    {
        Assert::same(Utils::get_plugin_slug(), 'recrawler');
    }
}
```

- [ ] **Step 8: Запустить Testo**

Run: `vendor/bin/testo`
Expected: PASS, 1 тест.

- [ ] **Step 9: Скрипты запуска**

В `composer.json` в `scripts`:

```json
"test:testo": "vendor/bin/testo",
```

Run: `composer test:testo`
Expected: PASS.

- [ ] **Step 10: CI**

В `.github/workflows/deploy.yml` в job `test` после шага `Run PHPUnit test suite` добавить:

```yaml
      - name: Run Testo test suite
        run: composer test:testo
```

Версия PHP в `setup-php` уже `8.2` во всех трёх джобах. Структура workflow: `test` → `build` (собирает `vendor-prefixed` и отдаёт артефактом) → `deploy` (забирает артефакт и публикует на wp.org).

- [ ] **Step 11: Коммит**

```bash
git add composer.json composer.lock recrawler.php readme.txt testo.php tests/ .github/workflows/deploy.yml
git commit -m "build: Поднять минимальный PHP до 8.2 и добавить раннер Testo"
```

---

### Task 2: Настоящие секции в WPOSA

`add_section()` пишет в `$sections_array`, который рендерер не читает (`src/Views/WPOSA.php:171,321,342` — записи есть, чтения нет). Оживляем.

**Files:**
- Modify: `src/Views/WPOSA.php` — `add_section()` (строка 336), `add_field()` (строка 372), `admin_init()` (строки 461-604)
- Modify: `tests/stubs/wp.php` — заглушки Settings API
- Create: `tests/phpunit/WposaSectionsTest.php`
- Create: `tests/Unit/WposaSectionsTest.php`

**Interfaces:**
- Produces: `WPOSA::add_section( array $section )` принимает `[ 'id' => string, 'tab' => string, 'title' => string, 'desc' => string|null ]`. `id` и `tab` префиксуются как в `add_tab()`.
- Produces: `WPOSA::add_field( string $section, array $field )` — новый необязательный ключ `$field['section']` задаёт **секцию отрисовки**; первый аргумент по-прежнему задаёт **опцию хранения**. Без ключа поведение прежнее.
- Produces: `WPOSA::get_sections_by_tab( string $tab_id ): array`.

- [ ] **Step 1: Заглушки Settings API**

В `tests/stubs/wp.php` рядом с существующими заглушками:

```php
$GLOBALS['__wp_settings_sections'] = [];
$GLOBALS['__wp_settings_fields']   = [];

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
if (!function_exists('add_option')) { function add_option($option, $value = '', $d = '', $autoload = true) { return true; } }
if (!function_exists('admin_url')) { function admin_url($path = '') { return 'https://example.com/wp-admin/' . $path; } }
```

В `_reset_wp_mocks()` добавить очистку трёх новых глобалей.

- [ ] **Step 2: Написать падающий PHPUnit-тест**

Создать `tests/phpunit/WposaSectionsTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaSectionsTest extends TestCase {

	private function make_wposa(): WPOSA {
		return new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_section_is_registered_on_its_tab_page() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_section( [ 'id' => 'logs', 'tab' => 'general', 'title' => 'Logs' ] );
		$wposa->admin_init();

		$sections = array_column( $GLOBALS['__wp_settings_sections'], 'page', 'id' );

		$this->assertArrayHasKey( 'recrawler_logs', $sections );
		$this->assertSame( 'recrawler_general', $sections['recrawler_logs'] );
	}

	public function test_field_renders_in_subsection_but_saves_to_tab_option() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_section( [ 'id' => 'logs', 'tab' => 'general', 'title' => 'Logs' ] );
		$wposa->add_field( 'general', [ 'id' => 'enable', 'type' => 'switcher', 'section' => 'logs' ] );
		$wposa->admin_init();

		$field = $GLOBALS['__wp_settings_fields'][0];

		$this->assertSame( 'recrawler_general', $field['page'] );
		$this->assertSame( 'recrawler_logs', $field['section'] );
		$this->assertSame( 'recrawler_general', $field['args']['section'] );
	}

	public function test_field_without_section_keeps_old_behaviour() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_field( 'general', [ 'id' => 'ping_delay', 'type' => 'number' ] );
		$wposa->admin_init();

		$field = $GLOBALS['__wp_settings_fields'][0];

		$this->assertSame( 'recrawler_general', $field['page'] );
		$this->assertSame( 'recrawler_general', $field['section'] );
	}
}
```

- [ ] **Step 3: Убедиться, что тест падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/WposaSectionsTest.php`
Expected: FAIL — секция `recrawler_logs` не зарегистрирована.

- [ ] **Step 4: Реализация в WPOSA**

`add_section()` — префиксовать `tab` и не терять его:

```php
	public function add_section( $section ) {
		if ( ! is_array( $section ) ) {
			return false;
		}

		$section['id'] = $this->get_prefix() . '_' . $section['id'];

		if ( isset( $section['tab'] ) ) {
			$section['tab'] = $this->get_prefix() . '_' . $section['tab'];
		}

		$this->sections_array[] = $section;

		return $this;
	}
```

Новый геттер рядом:

```php
	/**
	 * Get sections registered for a given tab.
	 *
	 * @param string $tab_id Prefixed tab id.
	 *
	 * @return array
	 */
	public function get_sections_by_tab( string $tab_id ): array {
		return array_values(
			array_filter(
				$this->sections_array,
				static function ( $section ) use ( $tab_id ) {
					return ( $section['tab'] ?? '' ) === $tab_id;
				}
			)
		);
	}
```

В `admin_init()` сразу после цикла регистрации вкладок — цикл секций:

```php
		foreach ( $this->sections_array as $section ) {
			if ( empty( $section['tab'] ) ) {
				continue;
			}

			$desc     = $section['desc'] ?? '';
			$callback = $desc
				? static function () use ( $desc ) {
					echo '<div class="inside wposa-section-description">' . wp_kses( $desc, self::ALLOWED_HTML ) . '</div>';
				}
				: null;

			add_settings_section( $section['id'], $section['title'], $callback, $section['tab'] );
		}
```

В цикле полей заменить вызов `add_settings_field()` (строка 596) на разведённые `page` и `section`:

```php
				$render_section = isset( $field['section'] )
					? $this->get_prefix() . '_' . $field['section']
					: $section;

				$page = $render_section;

				foreach ( $this->sections_array as $registered ) {
					if ( $registered['id'] === $render_section && ! empty( $registered['tab'] ) ) {
						$page = $registered['tab'];
						break;
					}
				}

				add_settings_field(
					$field_id,
					$name,
					array( $this, 'callback_' . $type ),
					$page,
					$render_section,
					$args
				);
```

`$args['section']` остаётся равным `$section` — от него зависит имя опции в `name` атрибуте (`label_for` на строке 529 и `callback_*`). Не трогать.

- [ ] **Step 5: Убедиться, что тесты проходят**

Run: `composer test`
Expected: PASS, 151 тест.

- [ ] **Step 6: Тот же контракт на Testo**

Создать `tests/Unit/WposaSectionsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Views\WPOSA;
use Testo\Assert;
use Testo\Attributes\Test;

final class WposaSectionsTest
{
    #[Test]
    public function sectionIsRegisteredOnItsTabPage(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_section(['id' => 'logs', 'tab' => 'general', 'title' => 'Logs']);
        $wposa->admin_init();

        $sections = array_column($GLOBALS['__wp_settings_sections'], 'page', 'id');

        Assert::same($sections['recrawler_logs'], 'recrawler_general');
    }

    #[Test]
    public function fieldRendersInSubsectionButSavesToTabOption(): void
    {
        _reset_wp_mocks();

        $wposa = new WPOSA('ReCrawler', '1.0.0', 'recrawler', 'recrawler');
        $wposa->add_tab(['id' => 'general', 'title' => 'General']);
        $wposa->add_section(['id' => 'logs', 'tab' => 'general', 'title' => 'Logs']);
        $wposa->add_field('general', ['id' => 'enable', 'type' => 'switcher', 'section' => 'logs']);
        $wposa->admin_init();

        $field = $GLOBALS['__wp_settings_fields'][0];

        Assert::same($field['page'], 'recrawler_general');
        Assert::same($field['section'], 'recrawler_logs');
    }
}
```

Run: `composer test:testo`
Expected: PASS.

- [ ] **Step 7: Коммит**

```bash
git add src/Views/WPOSA.php tests/
git commit -m "feat(wposa): Поддержка секций внутри вкладки"
```

---

### Task 3: Шапка отдельным методом и журнал под ней

**Files:**
- Modify: `src/Views/WPOSA.php` — `plugin_page()` (строки 1063-1101)
- Modify: `src/Main.php` — `render_log_page()` (строки 331-348)

**Interfaces:**
- Produces: `WPOSA::show_header(): void` — печатает блок `.wposa-header`.
- Consumes: `Main::$wposa` (`src/Main.php:96`), уже проинициализировано.

- [ ] **Step 1: Вынести шапку**

В `WPOSA` перед `plugin_page()` добавить метод, перенеся в него разметку со строк 1065-1077 дословно:

```php
	/**
	 * Show plugin header with logo, name and version.
	 */
	public function show_header() {
		?>
		<div class="wposa-header">
			<div class="wposa-header--left">
				<img class="wposa-logo" title="ReCrawler" src="<?php echo esc_url( Utils::get_plugin_asset_url( 'images/icons/logo.svg' ) ); ?>" width="80" alt="" />
			</div>
			<div class="wposa-header--center">
				<div class="wposa-heading"><?php echo esc_html( $this->plugin_name ); ?></div>
				<div class="wposa-version"><?php esc_html_e( 'Version' ); ?>: <?php echo esc_html( $this->plugin_version ); ?></div>
			</div>
			<div class="wposa-header--right">
				<p><?php esc_html_e( 'ReCrawler is a small WordPress Plugin for quickly notifying search engines whenever their website content is created, updated, or deleted.', 'recrawler' ); ?></p>
			</div>
		</div>
		<?php
	}
```

В `plugin_page()` на месте вырезанного блока — `<?php $this->show_header(); ?>`.

- [ ] **Step 2: Шапка и навигация на странице журнала**

`Main::render_log_page()`:

```php
	public function render_log_page() {
		?>
		<div class="wrap">
			<div class="wposa">
				<?php
				$this->wposa->show_header();
				$this->wposa->show_navigation();
				?>
			</div>
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . RECRAWLER_SLUG . '-log' ) ); ?>" method="post">
				<?php
				/**
				 * WP_List_table.
				 *
				 * @var WP_List_Table $table
				 */
				$table = $GLOBALS[ RECRAWLER_PREFIX . '_log' ];
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
```

Заголовок `<h2>` убирается — его роль берёт шапка. `action` формы проставляется явно: пустой работал по случайности и сломается, когда в URL появятся параметры.

- [ ] **Step 3: Прогнать тесты**

Run: `composer test && composer test:testo`
Expected: PASS.

- [ ] **Step 4: Проверить глазами**

Открыть `admin.php?page=recrawler-log`. Ожидается: шапка с логотипом и версией, под ней полоса вкладок, ниже таблица. Пагинация и «Настройки экрана» работают как раньше.

- [ ] **Step 5: Коммит**

```bash
git add src/Views/WPOSA.php src/Main.php
git commit -m "feat(admin): Общая шапка и навигация на странице журнала"
```

---

### Task 4: Навигация на `?tab=`

**Files:**
- Modify: `src/Views/WPOSA.php` — `show_navigation()` (строка 1113), `show_forms()` (строка 1145)
- Modify: `assets/js/admin.js` — строки 9-20 и 26-76
- Create: `tests/phpunit/WposaNavigationTest.php`
- Create: `tests/Unit/WposaNavigationTest.php`

**Interfaces:**
- Produces: `WPOSA::get_current_tab(): string` — префиксованный id активной вкладки; берётся из `$_GET['tab']`, при отсутствии или неизвестном значении — первая вкладка.

- [ ] **Step 1: Падающий тест на выбор активной вкладки**

Создать `tests/phpunit/WposaNavigationTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaNavigationTest extends TestCase {

	private function make_wposa(): WPOSA {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'logs', 'title' => 'Logs' ] );

		return $wposa;
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		unset( $_GET['tab'] );
	}

	public function tearDown(): void {
		unset( $_GET['tab'] );
		parent::tearDown();
	}

	public function test_defaults_to_first_tab() {
		$this->assertSame( 'recrawler_general', $this->make_wposa()->get_current_tab() );
	}

	public function test_reads_tab_from_query() {
		$_GET['tab'] = 'logs';
		$this->assertSame( 'recrawler_logs', $this->make_wposa()->get_current_tab() );
	}

	public function test_unknown_tab_falls_back_to_first() {
		$_GET['tab'] = 'nonexistent';
		$this->assertSame( 'recrawler_general', $this->make_wposa()->get_current_tab() );
	}
}
```

- [ ] **Step 2: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/WposaNavigationTest.php`
Expected: FAIL — метод `get_current_tab()` не существует.

- [ ] **Step 3: Реализация**

```php
	/**
	 * Get currently active tab id.
	 */
	public function get_current_tab(): string {
		$first = $this->tabs_array[0]['id'] ?? '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['tab'] ) ? $this->get_prefix() . '_' . sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		foreach ( $this->tabs_array as $tab ) {
			if ( $tab['id'] === $requested ) {
				return $requested;
			}
		}

		return $first;
	}
```

Заглушка `sanitize_key()` уже есть в `tests/stubs/wp.php`; если нет — добавить рядом с `sanitize_text_field()`.

- [ ] **Step 4: Ссылки в навигации**

В `show_navigation()` заменить строку с активной вкладкой:

```php
			} else {
				$url = admin_url( 'admin.php?page=' . $this->plugin_slug . '&tab=' . str_replace( $this->get_prefix() . '_', '', $tab['id'] ) );

				$html .= sprintf(
					'<a href="%1$s" class="nav-tab%4$s" id="%2$s-tab">%3$s</a>',
					esc_url( $url ),
					$tab['id'],
					$tab['title'],
					$tab['id'] === $current ? ' nav-tab-active' : ''
				);
			}
```

где `$current = $this->get_current_tab();` объявляется в начале метода.

Проверить, что `href` и `class` для `a` есть в `self::ALLOWED_HTML` (строки 27-119) — если нет, добавить.

- [ ] **Step 5: Рисовать только активную вкладку**

В `show_forms()` в начале цикла:

```php
		$current = $this->get_current_tab();
		?>
		<div class="metabox-holder">
			<?php foreach ( $this->tabs_array as $form ) : ?>
				<?php if ( $form['id'] !== $current ) : continue; endif; ?>
```

- [ ] **Step 6: Почистить JS**

В `assets/js/admin.js` удалить строки 26-76 целиком — блок `.wposa__group` / `ACTIVE_TAB` / обработчик клика по `.nav-tab-wrapper a`, вместе с константой `ACTIVE_TAB` на строке 2. Оставить только развёртывание `.collapsed` (строки 40-56), перенеся его выше.

Переход из вкладки помощи (строки 9-20) заменить на переход по ссылке:

```javascript
	$help.on(
		'click',
		function () {
			const $this = $(this);
			const tab = $('#recrawler_' + $this.data('tab') + '-tab');

			if ( tab.length && tab.attr('href') ) {
				window.location = tab.attr('href');
			}
		}
	);
```

- [ ] **Step 7: Тесты**

Run: `composer test && composer test:testo`
Expected: PASS.

- [ ] **Step 8: Testo-зеркало**

Создать `tests/Unit/WposaNavigationTest.php` с теми же тремя проверками через `Assert::same()`, по образцу Testo-теста из задачи 2.

Run: `composer test:testo`
Expected: PASS.

- [ ] **Step 9: Проверить глазами**

Открыть `admin.php?page=recrawler`, кликнуть по вкладкам. Ожидается: URL меняется на `&tab=...`, активная вкладка подсвечена, кнопка «Назад» возвращает на предыдущую, ссылка на вкладку открывается напрямую.

- [ ] **Step 10: Коммит**

```bash
git add src/Views/WPOSA.php assets/js/admin.js tests/
git commit -m "feat(admin): Вкладки на URL вместо localStorage"
```

---

### Task 5: Все вкладки в левом меню

**Files:**
- Modify: `src/Views/WPOSA.php` — `admin_menu()` (строки 1051-1061), `setup_hooks()`
- Modify: `tests/stubs/wp.php` — заглушка `add_submenu_page()`
- Modify: `tests/phpunit/WebmasterProvidersTest.php` (ожидания по числу вызовов, если сломаются)
- Create: `tests/phpunit/WposaMenuTest.php`

**Interfaces:**
- Consumes: `WPOSA::get_current_tab()` из задачи 4.
- Produces: `add_tab()` понимает ключ `'show_in_menu' => bool`, по умолчанию `true`. Вкладка с `false` рисуется в полосе вкладок, но пункта в левом меню не получает. Отключённая вкладка (`'disabled' => true`) в меню не попадает никогда, независимо от `show_in_menu`.

- [ ] **Step 1: Заглушка, записывающая вызовы**

В `tests/stubs/wp.php` заменить существующую заглушку `add_submenu_page()` (сейчас возвращает литерал `'recrawler-log'`) на пишущую:

```php
if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null, $position = null) {
        _track_wp_mock('add_submenu_page');
        $GLOBALS['__wp_submenus'][] = compact('parent_slug', 'menu_title', 'menu_slug');
        return $menu_slug === 'recrawler-log' ? 'recrawler-log' : 'recrawler_page_' . $menu_slug;
    }
}
```

Возврат для `recrawler-log` сохраняется прежним — на него завязан `MainTest`.

- [ ] **Step 2: Падающий тест**

Создать `tests/phpunit/WposaMenuTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaMenuTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		$GLOBALS['__wp_submenus'] = [];
	}

	public function test_every_tab_gets_submenu_item() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI' ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertContains( 'recrawler&tab=general', $slugs );
		$this->assertContains( 'recrawler&tab=ai', $slugs );
	}

	public function test_disabled_tab_has_no_submenu_item() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI', 'disabled' => true ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertNotContains( 'recrawler&tab=ai', $slugs );
	}

	public function test_tab_can_opt_out_of_menu() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'plugins', 'title' => 'Plugins', 'show_in_menu' => false ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertContains( 'recrawler&tab=general', $slugs );
		$this->assertNotContains( 'recrawler&tab=plugins', $slugs );
	}
}
```

- [ ] **Step 3: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/WposaMenuTest.php`
Expected: FAIL — подменю не регистрируются.

- [ ] **Step 4: Реализация**

Сначала — дефолт в `add_tab()` (строка 300), рядом с префиксованием id:

```php
	public function add_tab( array $tab ) {
		$tab['id'] = $this->get_prefix() . '_' . $tab['id'];

		// Tabs show up in the admin menu unless they opt out.
		$tab['show_in_menu'] = $tab['show_in_menu'] ?? true;

		$this->tabs_array[] = $tab;

		return $this;
	}
```

Затем в `admin_menu()` после `add_menu_page()`:

```php
		foreach ( $this->tabs_array as $tab ) {
			if ( ! empty( $tab['disabled'] ) || ! $tab['show_in_menu'] ) {
				continue;
			}

			$slug = str_replace( $this->get_prefix() . '_', '', $tab['id'] );

			add_submenu_page(
				$this->plugin_slug,
				$tab['title'],
				$tab['title'],
				'manage_options',
				$this->plugin_slug . '&tab=' . $slug,
				array( $this, 'plugin_page' )
			);
		}

		// WordPress добавляет копию родителя первым пунктом — убираем её,
		// первую вкладку мы зарегистрировали сами.
		remove_submenu_page( $this->plugin_slug, $this->plugin_slug );
```

Добавить заглушку `remove_submenu_page()` в `tests/stubs/wp.php`.

- [ ] **Step 5: Подсветка активного пункта**

WordPress сравнивает голый `page` и не поймёт слаг с параметром. В `setup_hooks()`:

```php
		add_filter( 'submenu_file', [ $this, 'highlight_current_submenu' ] );
```

```php
	/**
	 * Point WordPress at the submenu item matching the active tab.
	 *
	 * @param string|null $submenu_file Current submenu file.
	 *
	 * @return string|null
	 */
	public function highlight_current_submenu( $submenu_file ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || $screen->id !== 'toplevel_page_' . $this->plugin_slug ) {
			return $submenu_file;
		}

		$slug = str_replace( $this->get_prefix() . '_', '', $this->get_current_tab() );

		return $this->plugin_slug . '&tab=' . $slug;
	}
```

- [ ] **Step 6: Тесты**

Run: `composer test`
Expected: PASS. Если `WebmasterProvidersTest::test_setup_hooks_registers_actions` упадёт на числе `add_filter` — поправить ожидание, фильтр добавился намеренно.

- [ ] **Step 7: Проверить глазами**

Ожидается: в левом меню под ReCrawler девять пунктов плюс «Журнал»; при переходе подсвечивается нужный; отключённые вкладки в меню отсутствуют. `src/Views/Settings.php` при этом не правится ни в одной строке — дефолт `show_in_menu = true` покрывает все существующие вкладки.

- [ ] **Step 8: Коммит**

```bash
git add src/Views/WPOSA.php tests/
git commit -m "feat(admin): Все вкладки отдельными пунктами левого меню"
```

---

### Task 6: Настройки логирования секцией в «Общем» и миграция опции

**Files:**
- Modify: `src/Views/Settings.php` — блок вкладки Logs (строки 554-621)
- Modify: `src/Main.php:371`, `src/Cron.php:67,74`, `src/IndexNowAbstract.php:122`, `src/Views/Log_List_Table.php:215`
- Modify: `src/Migrations/Migrations.php`
- Create: `tests/phpunit/MigrateLogsOptionTest.php`
- Create: `tests/Unit/MigrateLogsOptionTest.php`

**Interfaces:**
- Consumes: `WPOSA::add_section()` из задачи 2.
- Produces: `Migrations::migrate_1_0_0(): bool` — сливает `recrawler_logs` в `recrawler_general` и удаляет старую опцию.

- [ ] **Step 1: Падающий тест миграции**

Создать `tests/phpunit/MigrateLogsOptionTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Migrations\Migrations;
use PHPUnit\Framework\TestCase;

class MigrateLogsOptionTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_merges_logs_into_general_keeping_old_values() {
		$saved = [];

		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			if ( 'recrawler_logs' === $option ) {
				return [ 'enable' => 'off', 'lifetime' => 30 ];
			}
			if ( 'recrawler_general' === $option ) {
				return [ 'ping_delay' => 5, 'enable' => 'on' ];
			}
			return $default;
		} );

		_set_wp_override( 'update_option', function ( $option, $value ) use ( &$saved ) {
			$saved[ $option ] = $value;
			return true;
		} );

		$this->assertTrue( ( new Migrations() )->migrate_1_0_0() );

		$this->assertSame( 'off', $saved['recrawler_general']['enable'] );
		$this->assertSame( 30, $saved['recrawler_general']['lifetime'] );
		$this->assertSame( 5, $saved['recrawler_general']['ping_delay'] );
	}

	public function test_returns_true_when_nothing_to_migrate() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return $default;
		} );

		$this->assertTrue( ( new Migrations() )->migrate_1_0_0() );
	}
}
```

- [ ] **Step 2: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/MigrateLogsOptionTest.php`
Expected: FAIL — метода нет.

- [ ] **Step 3: Реализация миграции**

В `Migrations` рядом с остальными `migrate_*`:

```php
	/**
	 * Merge the standalone logs option into the general one.
	 *
	 * @return bool
	 */
	public function migrate_1_0_0(): bool {
		$logs = get_option( 'recrawler_logs' );

		if ( ! is_array( $logs ) || ! $logs ) {
			return true;
		}

		$general = (array) get_option( 'recrawler_general', [] );

		// Old values win: they were set deliberately on the Logs tab.
		update_option( 'recrawler_general', array_merge( $general, $logs ) );
		delete_option( 'recrawler_logs' );

		return true;
	}
```

Имя метода даёт версию `1.0.0` через `get_upgrade_version()` (`src/Migrations/Migrations.php:126`) — миграция отработает ровно один раз и запишется в опцию `recrawler_versions`.

- [ ] **Step 4: Тест зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/MigrateLogsOptionTest.php`
Expected: PASS.

- [ ] **Step 5: Перевести поля в секцию**

В `src/Views/Settings.php` удалить `add_tab()` вкладки `logs` (строки 554-560) и заменить на секцию:

```php
		$this->wposa->add_section(
			[
				'id'    => 'logs',
				'tab'   => 'general',
				'title' => __( 'Logs', 'recrawler' ),
			]
		);
```

Во всех шести `add_field( 'logs', [...] )` этого блока первым аргументом поставить `'general'` и добавить в массив поля `'section' => 'logs'`. Пример для первого:

```php
		$this->wposa->add_field(
			'general',
			array(
				'id'      => 'enable',
				'section' => 'logs',
				'type'    => 'switcher',
				'name'    => __( 'Enable', 'recrawler' ),
				'default' => 'on',
			)
		);
```

**Проверено на 2026-09-03:** во вкладке «Общее» (строки 148-262) поля с id `enable` нет, конфликта ключей при слиянии опций не возникает. Если блок «Общего» с тех пор изменился — перепроверить командой `grep -n "'id' *=> *'enable'" src/Views/Settings.php` и при появлении конфликта переименовать логовое поле в `logs_enable` (тогда ключ переименовывается и в миграции, и в местах чтения).

- [ ] **Step 6: Переключить места чтения**

Пять вызовов, секция `'logs'` меняется на `'general'`:

- `src/Main.php:371` — `get_option( 'enable', 'general', 'on' )`
- `src/Cron.php:67` — `get_option( 'lifetime', 'general', 1 )`
- `src/Cron.php:74` — `get_option( 'cron_events', 'general', 'off' )`
- `src/IndexNowAbstract.php:122` — `get_option( 'key_logging', 'general', 'on' )`
- `src/Views/Log_List_Table.php:215` — `get_option( 'bulk_actions', 'general', 'off' )`

Не трогать `'plural' => 'logs'` в `Log_List_Table.php:40`.

- [ ] **Step 7: Прогнать всё**

Run: `composer test && composer test:testo`
Expected: PASS. `CronTest` и `MainTest` мокают `WPOSA::get_option()` — если они ожидают аргумент `'logs'`, поправить на `'general'`.

- [ ] **Step 8: Testo-зеркало миграции**

Создать `tests/Unit/MigrateLogsOptionTest.php` с проверкой слияния через `Assert::same()`.

Run: `composer test:testo`
Expected: PASS.

- [ ] **Step 9: Проверить на живой БД**

Поднять Local, на сайте с непустой `recrawler_logs` открыть админку. Ожидается: настройки логирования видны секцией внизу вкладки «Общее» с прежними значениями; в `wp_options` строки `recrawler_logs` больше нет, значения внутри `recrawler_general`.

- [ ] **Step 10: Коммит**

```bash
git add src/ tests/
git commit -m "feat(settings): Перенести настройки журнала в Общее секцией"
```

---

### Task 7: Предупреждение о несохранённых данных

**Files:**
- Modify: `assets/js/admin.js`

- [ ] **Step 1: Реализация**

В конец `jQuery( document ).ready()` в `assets/js/admin.js`:

```javascript
	// Warn before leaving a settings page with unsaved changes.
	const $settings_form = $( '.wposa__group form' );

	if ( $settings_form.length ) {
		const initial_state = $settings_form.serialize();
		let submitting = false;

		$settings_form.on( 'submit', function() {
			submitting = true;
		});

		$( window ).on( 'beforeunload', function( evt ) {
			if ( submitting || $settings_form.serialize() === initial_state ) {
				return;
			}

			evt.preventDefault();
			// Текст задаёт браузер, свой он игнорирует.
			evt.originalEvent.returnValue = '';
			return '';
		});
	}
```

- [ ] **Step 2: Проверить глазами**

Четыре сценария на `admin.php?page=recrawler`:
1. Изменить поле → клик по другой вкладке → браузер спрашивает подтверждение.
2. Изменить поле → клик по пункту левого меню → спрашивает.
3. Изменить поле → «Сохранить» → **не** спрашивает, страница сохраняется.
4. Ничего не менять → уйти → не спрашивает.

Отдельно: на `page=recrawler-log` формы настроек нет, предупреждение не должно появляться при пагинации.

- [ ] **Step 3: Коммит**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): Предупреждать о несохранённых настройках при уходе со страницы"
```

---

### Task 8: Релиз 1.0.0

**Files:**
- Modify: `recrawler.php:5`, `recrawler.php:25`
- Modify: `readme.txt:7`, changelog в `readme.txt`

- [ ] **Step 1: Версия**

`recrawler.php` строка 5: ` * Version: 1.0.0`
`recrawler.php` строка 25: `define( 'RECRAWLER_VERSION', '1.0.0' );`
`readme.txt` строка 7: `Stable tag: 1.0.0`

- [ ] **Step 2: Changelog**

В `readme.txt` перед записью `= 0.3.4 ... =`:

```
= 1.0.0 (дата релиза) =
* Единая шапка и навигация на всех страницах плагина, включая журнал
* Вкладки получили собственные адреса — на них можно ссылаться, работает кнопка «Назад»
* Все разделы продублированы отдельными пунктами в левом меню
* Настройки журнала перенесены в раздел «Общее» отдельной секцией
* Предупреждение о несохранённых настройках при уходе со страницы
* Минимальная версия PHP поднята до 8.2
* Опция recrawler_logs объединена с recrawler_general (миграция автоматическая)
```

- [ ] **Step 3: Финальный прогон**

Run: `composer test && composer test:testo && composer phpcs`
Expected: PASS.

- [ ] **Step 4: Коммит и PR**

```bash
git add recrawler.php readme.txt
git commit -m "chore(release): 1.0.0"
git push -u origin 1.0.0
gh pr create --base main --head 1.0.0 --title "1.0.0: Единая навигация админки"
```

Тег `1.0.0` ставить **после** мержа PR, из обновлённого `main`. Пушить явным рефспеком (`git push origin refs/tags/1.0.0`), иначе одноимённая ветка сделает `git push origin 1.0.0` неоднозначным.

---

## Self-Review

**Покрытие спеки:** раздел 1 (секции) → задача 2; раздел 2 (`?tab=`) → задача 4; раздел 3 (меню) → задача 5; раздел 4 (шапка и журнал) → задача 3; раздел 5 (перенос и миграция) → задача 6; раздел 6 (beforeunload) → задача 7. Проверка из спеки разложена по шагам «Проверить глазами». Задачи 1 и 8 добавлены сверх спеки: раннер Testo с подъёмом PHP и выпуск версии.

**Расхождение со спекой:** спека предлагала вешать миграцию на `Main::maybe_upgrade()`. План использует существующий класс `Migrations` — он уже ведёт учёт применённых версий в опции `recrawler_versions` и гарантирует однократный запуск, чего `maybe_upgrade()` не даёт.

**Проверенный риск:** совпадение ключа `enable` между секциями снято — во вкладке «Общее» такого поля нет (проверено 2026-09-03). Шаг 5 задачи 6 содержит команду для перепроверки, если блок изменится.
