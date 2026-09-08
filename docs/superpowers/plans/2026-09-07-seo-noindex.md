# Не пинговать записи, закрытые от индексации — план реализации

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Не отправлять на переобход записи, которым SEO-плагин выставил `noindex`, и ничего не пинговать при `blog_public = 0`.

**Architecture:** Интерфейс `SeoPluginInterface` и пять детекторов-адаптеров в `src/Seo/`, поверх них агрегатор `Indexability` с единственным публичным методом. Агрегатор вызывается из двух точек `Hooks` — `post_updated()` и `comment_updated()`. Каждый вызов чужого API обёрнут в `try/catch ( \Throwable )` и при ошибке трактуется как «не смог определить», то есть пингуем.

**Tech Stack:** PHP 8.2, WordPress 6.4+, PHPUnit 10.5, Testo, собственный DI-контейнер с автоварингом.

**Spec:** `docs/superpowers/specs/2026-09-07-seo-noindex-design.md`

## Global Constraints

- PHP 8.2, `declare(strict_types=1)` не добавлять в новые файлы `src/Seo/` — в `src/Providers/*` его нет, следуем окружению
- Отступы табами, UTF-8, LF; snake_case для методов и переменных, PascalCase для классов
- Text domain — `recrawler`, префикс констант — `RECRAWLER_`
- WPCS: короткие массивы `[]`, Yoda-условия отключены, короткий тернарник разрешён
- Namespace новых классов — `Mihdan\ReCrawler\Seo`, автозагрузка PSR-4 из `src/`
- Каждый тест дублируется в оба раннера: `tests/phpunit/` (PHPUnit, `class XTest extends TestCase`, методы `test_*`) и `tests/Unit/` (Testo, `#[Test] final class X`, методы camelCase, `Testo\Assert`)
- Порядок опроса детекторов фиксирован: `Yoast`, `RankMath`, `Aioseo`, `SeoPress`, `TheSeoFramework`
- `readme.txt` changelog **не трогать** — его заполняет владелец проекта. `Stable tag` менять можно
- Команды проверки: `composer test`, `composer test:testo`, `composer psalm` (baseline — 134 ошибки, больше быть не должно)

---

## Task 0: Механизм заглушек чужих API

**Files:**
- Modify: `tests/phpunit/bootstrap.php`

**Interfaces:**
- Produces: `_set_seo_stub( string $key, $value ): void`, `_get_seo_stub( string $key, $default = null )` — управление поведением заглушек SEO-плагинов из тестов. Ключи вводят задачи 1–5.

- [ ] **Step 1: Добавить хранилище заглушек в bootstrap**

В `tests/phpunit/bootstrap.php` после строки `$GLOBALS['__wp_overrides'] = [];` добавить:

```php
$GLOBALS['__seo_stubs'] = [];

function _set_seo_stub( $key, $value ) {
    $GLOBALS['__seo_stubs'][ $key ] = $value;
}

function _get_seo_stub( $key, $default = null ) {
    return array_key_exists( $key, $GLOBALS['__seo_stubs'] ) ? $GLOBALS['__seo_stubs'][ $key ] : $default;
}
```

- [ ] **Step 2: Сбрасывать заглушки между тестами**

В функции `_reset_wp_mocks()` добавить строку рядом с `$GLOBALS['__wp_overrides'] = [];`:

```php
    $GLOBALS['__seo_stubs'] = [];
```

- [ ] **Step 3: Проверить, что существующие тесты не сломались**

Run: `composer test && composer test:testo`
Expected: PASS — 174 PHPUnit и 21 Testo, как до правки.

- [ ] **Step 4: Commit**

```bash
git add tests/phpunit/bootstrap.php
git commit -m "test: Добавить управляемые заглушки для API SEO-плагинов"
```

---

## Task 1: Интерфейс и детектор Yoast

**Files:**
- Create: `src/Seo/SeoPluginInterface.php`
- Create: `src/Seo/Yoast.php`
- Modify: `tests/phpunit/bootstrap.php` (заглушка `YoastSEO()`)
- Test: `tests/phpunit/Seo/YoastTest.php`, `tests/Unit/SeoYoastTest.php`

**Interfaces:**
- Consumes: `_set_seo_stub()` из Task 0
- Produces: `Mihdan\ReCrawler\Seo\SeoPluginInterface` с методами `get_slug(): string`, `is_active(): bool`, `is_post_noindex( int $post_id ): ?bool`; класс `Mihdan\ReCrawler\Seo\Yoast` реализует его, слаг `yoast`. Ключ заглушки — `yoast_robots`.

- [ ] **Step 1: Создать интерфейс**

`src/Seo/SeoPluginInterface.php`:

```php
<?php
/**
 * SEO plugin detector interface.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

interface SeoPluginInterface {
	/**
	 * Slug used to report which plugin closed the post.
	 */
	public function get_slug(): string;

	/**
	 * Whether the plugin is installed and active.
	 */
	public function is_active(): bool;

	/**
	 * Whether the post is closed from indexing.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool|null True when noindex, false when indexable, null when undetermined.
	 */
	public function is_post_noindex( int $post_id ): ?bool;
}
```

- [ ] **Step 2: Добавить заглушку YoastSEO() в bootstrap**

В `tests/phpunit/bootstrap.php` рядом с остальными заглушками:

```php
if ( ! function_exists( 'YoastSEO' ) ) {
    function YoastSEO() {
        return new class {
            public $meta;
            public function __construct() {
                $this->meta = new class {
                    public function for_post( $post_id ) {
                        $robots = _get_seo_stub( 'yoast_robots' );
                        if ( 'throw' === $robots ) {
                            throw new \RuntimeException( 'Yoast exploded' );
                        }
                        if ( null === $robots ) {
                            return null;
                        }
                        return (object) [ 'robots' => $robots ];
                    }
                };
            }
        };
    }
}
```

- [ ] **Step 3: Написать падающий тест (PHPUnit)**

`tests/phpunit/Seo/YoastTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\Yoast;
use PHPUnit\Framework\TestCase;

class YoastTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'yoast', ( new Yoast() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'yoast_robots', [ 'index' => 'noindex', 'follow' => 'follow' ] );
		$this->assertTrue( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'yoast_robots', [ 'index' => 'index', 'follow' => 'follow' ] );
		$this->assertFalse( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_missing_presentation_is_undetermined() {
		_set_seo_stub( 'yoast_robots', null );
		$this->assertNull( ( new Yoast() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'yoast_robots', 'throw' );
		$this->assertNull( ( new Yoast() )->is_post_noindex( 42 ) );
	}
}
```

- [ ] **Step 4: Убедиться, что тест падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/YoastTest.php`
Expected: FAIL — `Class "Mihdan\ReCrawler\Seo\Yoast" not found`

- [ ] **Step 5: Реализовать детектор**

`src/Seo/Yoast.php`:

```php
<?php
/**
 * Yoast SEO noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class Yoast implements SeoPluginInterface {

	public function get_slug(): string {
		return 'yoast';
	}

	public function is_active(): bool {
		return function_exists( 'YoastSEO' );
	}

	/**
	 * Read the robots array off the Surfaces API (Yoast 14+). It accounts for
	 * both the post meta and the post type wide noindex from Yoast settings.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$presentation = YoastSEO()->meta->for_post( $post_id );

			if ( ! is_object( $presentation ) || ! isset( $presentation->robots['index'] ) ) {
				return null;
			}

			return 'noindex' === $presentation->robots['index'];
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
```

- [ ] **Step 6: Убедиться, что тест зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/YoastTest.php`
Expected: PASS — 5 тестов

- [ ] **Step 7: Продублировать тест в Testo**

`tests/Unit/SeoYoastTest.php`:

```php
<?php

declare(strict_types=1);

namespace Mihdan\ReCrawler\Tests\Unit;

use Mihdan\ReCrawler\Seo\Yoast;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SeoYoastTest
{
    public function detectsNoindex(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', ['index' => 'noindex']);

        Assert::true((new Yoast())->is_post_noindex(42));
    }

    public function detectsIndexablePost(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', ['index' => 'index']);

        Assert::false((new Yoast())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        _set_seo_stub('yoast_robots', 'throw');

        Assert::null((new Yoast())->is_post_noindex(42));
    }
}
```

- [ ] **Step 8: Прогнать оба раннера**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add src/Seo/SeoPluginInterface.php src/Seo/Yoast.php tests/phpunit/bootstrap.php tests/phpunit/Seo/YoastTest.php tests/Unit/SeoYoastTest.php
git commit -m "feat(seo): Определять noindex от Yoast SEO"
```

---

## Task 2: Детектор Rank Math

**Files:**
- Create: `src/Seo/RankMath.php`
- Modify: `tests/phpunit/bootstrap.php` (заглушка `RankMath\Post`)
- Test: `tests/phpunit/Seo/RankMathTest.php`, `tests/Unit/SeoRankMathTest.php`

**Interfaces:**
- Consumes: `SeoPluginInterface` из Task 1, `_set_seo_stub()` из Task 0
- Produces: `Mihdan\ReCrawler\Seo\RankMath`, слаг `rank-math`. Ключ заглушки — `rankmath_robots`.

- [ ] **Step 1: Добавить заглушку класса в bootstrap**

```php
if ( ! class_exists( 'RankMath\Post' ) ) {
    eval( 'namespace RankMath; class Post {
        public static function get_meta( $key, $post_id = 0, $default_value = "" ) {
            $robots = \_get_seo_stub( "rankmath_robots" );
            if ( "throw" === $robots ) {
                throw new \RuntimeException( "Rank Math exploded" );
            }
            return null === $robots ? $default_value : $robots;
        }
    }' );
}
```

`eval` здесь оправдан: класс должен лежать в чужом namespace `RankMath`, а один файл bootstrap не может объявить два namespace. Альтернатива — отдельный файл-фикстура; если исполнитель предпочитает её, положить в `tests/phpunit/stubs/rank-math.php` и подключить из bootstrap через `require_once`.

- [ ] **Step 2: Написать падающий тест**

`tests/phpunit/Seo/RankMathTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\RankMath;
use PHPUnit\Framework\TestCase;

class RankMathTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'rank-math', ( new RankMath() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'rankmath_robots', [ 'noindex', 'nofollow' ] );
		$this->assertTrue( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'rankmath_robots', [ 'index', 'follow' ] );
		$this->assertFalse( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_empty_meta_is_undetermined() {
		_set_seo_stub( 'rankmath_robots', [] );
		$this->assertNull( ( new RankMath() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'rankmath_robots', 'throw' );
		$this->assertNull( ( new RankMath() )->is_post_noindex( 42 ) );
	}
}
```

- [ ] **Step 3: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/RankMathTest.php`
Expected: FAIL — класс не найден

- [ ] **Step 4: Реализовать детектор**

`src/Seo/RankMath.php`:

```php
<?php
/**
 * Rank Math noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use RankMath\Post as RankMathPost;
use Throwable;

class RankMath implements SeoPluginInterface {

	public function get_slug(): string {
		return 'rank-math';
	}

	public function is_active(): bool {
		return class_exists( RankMathPost::class );
	}

	/**
	 * Rank Math keeps robots directives as an array in post meta. An empty
	 * array means the post defers to the global settings, which this detector
	 * does not read — hence undetermined rather than indexable.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$robots = RankMathPost::get_meta( 'robots', $post_id );

			if ( ! is_array( $robots ) || [] === $robots ) {
				return null;
			}

			return in_array( 'noindex', $robots, true );
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
```

- [ ] **Step 5: Убедиться, что зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/RankMathTest.php`
Expected: PASS — 5 тестов

- [ ] **Step 6: Продублировать в Testo**

`tests/Unit/SeoRankMathTest.php`:

```php
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
}
```

- [ ] **Step 7: Прогнать оба раннера**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add src/Seo/RankMath.php tests/phpunit/bootstrap.php tests/phpunit/Seo/RankMathTest.php tests/Unit/SeoRankMathTest.php
git commit -m "feat(seo): Определять noindex от Rank Math"
```

---

## Task 3: Детектор SEOPress

**Files:**
- Create: `src/Seo/SeoPress.php`
- Test: `tests/phpunit/Seo/SeoPressTest.php`, `tests/Unit/SeoSeoPressTest.php`

**Interfaces:**
- Consumes: `SeoPluginInterface` из Task 1
- Produces: `Mihdan\ReCrawler\Seo\SeoPress`, слаг `seopress`. Заглушек не требует: читает `get_post_meta`, уже застабленный в bootstrap через `_set_wp_override`.

- [ ] **Step 1: Написать падающий тест**

`tests/phpunit/Seo/SeoPressTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\SeoPress;
use PHPUnit\Framework\TestCase;

class SeoPressTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'seopress', ( new SeoPress() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_wp_override( 'get_post_meta', function ( $post_id, $key, $single = false ) {
			return '_seopress_robots_index' === $key ? 'yes' : '';
		} );

		$this->assertTrue( ( new SeoPress() )->is_post_noindex( 42 ) );
	}

	public function test_empty_meta_is_undetermined() {
		_set_wp_override( 'get_post_meta', function () {
			return '';
		} );

		$this->assertNull( ( new SeoPress() )->is_post_noindex( 42 ) );
	}
}
```

Пустая мета — именно `null`, а не `false`: SEOPress пишет `yes` только при явном noindex, а отсутствие значения означает наследование глобальных настроек, которые этот детектор не читает.

- [ ] **Step 2: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/SeoPressTest.php`
Expected: FAIL — класс не найден

- [ ] **Step 3: Реализовать детектор**

`src/Seo/SeoPress.php`:

```php
<?php
/**
 * SEOPress noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class SeoPress implements SeoPluginInterface {

	public function get_slug(): string {
		return 'seopress';
	}

	public function is_active(): bool {
		return defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * SEOPress stores `yes` only when noindex is set explicitly on the post.
	 * An empty value means the post inherits the plugin's global settings,
	 * which have no public API — so it stays undetermined.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$value = get_post_meta( $post_id, '_seopress_robots_index', true );

			return 'yes' === $value ? true : null;
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
```

- [ ] **Step 4: Убедиться, что зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/SeoPressTest.php`
Expected: PASS — 3 теста

- [ ] **Step 5: Продублировать в Testo**

`tests/Unit/SeoSeoPressTest.php`:

```php
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
```

- [ ] **Step 6: Прогнать оба раннера**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add src/Seo/SeoPress.php tests/phpunit/Seo/SeoPressTest.php tests/Unit/SeoSeoPressTest.php
git commit -m "feat(seo): Определять noindex от SEOPress"
```

---

## Task 4: Детектор All in One SEO

**Files:**
- Create: `src/Seo/Aioseo.php`
- Modify: `tests/phpunit/bootstrap.php` (заглушка `aioseo()`)
- Test: `tests/phpunit/Seo/AioseoTest.php`, `tests/Unit/SeoAioseoTest.php`

**Interfaces:**
- Consumes: `SeoPluginInterface` из Task 1, `_set_seo_stub()` из Task 0
- Produces: `Mihdan\ReCrawler\Seo\Aioseo`, слаг `aioseo`. Ключи заглушки — `aioseo_meta` (объект или `'throw'`) и `aioseo_post_type_noindex` (bool).

Логика повторяет приватный `AIOSEO\Plugin\Common\Standalone\DetailsColumn::isNoindexed()`: при `robots_default = false` решает мета записи, иначе — настройка типа записи. Читаем через публичный `aioseo()`, в таблицу `aioseo_posts` не заглядываем.

- [ ] **Step 1: Добавить заглушку aioseo() в bootstrap**

```php
if ( ! function_exists( 'aioseo' ) ) {
    function aioseo() {
        return new class {
            public $meta;
            public $dynamicOptions;
            public function __construct() {
                $this->meta = new class {
                    public $metaData;
                    public function __construct() {
                        $this->metaData = new class {
                            public function getMetaData( $post ) {
                                $meta = _get_seo_stub( 'aioseo_meta' );
                                if ( 'throw' === $meta ) {
                                    throw new \RuntimeException( 'AIOSEO exploded' );
                                }
                                return $meta;
                            }
                        };
                    }
                };
                $this->dynamicOptions = new class {
                    public function noConflict( $flag = false ) {
                        return $this;
                    }
                    public function __get( $name ) {
                        return $this;
                    }
                    public function has( $key, $flag = true ) {
                        return null !== _get_seo_stub( 'aioseo_post_type_noindex' );
                    }
                    public function __call( $name, $args ) {
                        return (bool) _get_seo_stub( 'aioseo_post_type_noindex', false );
                    }
                };
            }
        };
    }
}
```

Заглушка `dynamicOptions` намеренно грубая: возвращает себя на любой обход свойств, а конечное значение отдаёт через `__call`. Детектор обязан спрашивать `noindex` вызовом метода, а не чтением свойства — так тестовая заглушка и реальный `Options`-объект AIOSEO ведут себя одинаково.

- [ ] **Step 2: Написать падающий тест**

`tests/phpunit/Seo/AioseoTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\Aioseo;
use PHPUnit\Framework\TestCase;

class AioseoTest extends TestCase {

	private function make_post( int $id = 42, string $type = 'post' ): \WP_Post {
		$post            = new \WP_Post();
		$post->ID        = $id;
		$post->post_type = $type;

		return $post;
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		_set_wp_override( 'get_post', function () {
			return $this->make_post();
		} );
	}

	public function test_slug() {
		$this->assertSame( 'aioseo', ( new Aioseo() )->get_slug() );
	}

	public function test_post_level_noindex_wins() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => false, 'robots_noindex' => true ] );
		_set_seo_stub( 'aioseo_post_type_noindex', false );

		$this->assertTrue( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_post_level_index_wins() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => false, 'robots_noindex' => false ] );
		_set_seo_stub( 'aioseo_post_type_noindex', true );

		$this->assertFalse( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_falls_back_to_post_type_setting() {
		_set_seo_stub( 'aioseo_meta', (object) [ 'robots_default' => true, 'robots_noindex' => false ] );
		_set_seo_stub( 'aioseo_post_type_noindex', true );

		$this->assertTrue( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_missing_meta_is_undetermined() {
		_set_seo_stub( 'aioseo_meta', null );

		$this->assertNull( ( new Aioseo() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'aioseo_meta', 'throw' );

		$this->assertNull( ( new Aioseo() )->is_post_noindex( 42 ) );
	}
}
```

- [ ] **Step 3: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/AioseoTest.php`
Expected: FAIL — класс не найден

- [ ] **Step 4: Реализовать детектор**

`src/Seo/Aioseo.php`:

```php
<?php
/**
 * All in One SEO noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Throwable;

class Aioseo implements SeoPluginInterface {

	public function get_slug(): string {
		return 'aioseo';
	}

	public function is_active(): bool {
		return function_exists( 'aioseo' );
	}

	/**
	 * Mirrors AIOSEO's own resolution order: the post's own robots meta wins
	 * unless it defers to defaults, in which case the post type setting decides.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$post = get_post( $post_id );

			if ( ! $post ) {
				return null;
			}

			$meta = aioseo()->meta->metaData->getMetaData( $post );

			if ( ! is_object( $meta ) ) {
				return null;
			}

			if ( empty( $meta->robots_default ) ) {
				return (bool) ( $meta->robots_noindex ?? false );
			}

			return $this->is_post_type_noindex( $post->post_type );
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * A post type can be registered without an `advanced` group, so every hop
	 * is guarded the same way AIOSEO guards it in Meta\Robots::globalValues().
	 */
	private function is_post_type_noindex( string $post_type ): ?bool {
		$options = aioseo()->dynamicOptions->noConflict( true )->searchAppearance;

		if ( ! $options->has( 'postTypes', false ) ) {
			return null;
		}

		$options = $options->postTypes;

		if ( ! $options->has( $post_type, false ) ) {
			return null;
		}

		$options = $options->{$post_type};

		if ( ! $options->has( 'advanced', false ) ) {
			return null;
		}

		$options = $options->advanced;

		if ( ! $options->has( 'robotsMeta', false ) ) {
			return null;
		}

		return (bool) $options->robotsMeta->noindex();
	}
}
```

- [ ] **Step 5: Убедиться, что зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/AioseoTest.php`
Expected: PASS — 6 тестов

- [ ] **Step 6: Продублировать в Testo**

`tests/Unit/SeoAioseoTest.php`:

```php
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

    public function fallsBackToPostTypeSetting(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', (object) ['robots_default' => true, 'robots_noindex' => false]);
        _set_seo_stub('aioseo_post_type_noindex', true);

        Assert::true((new Aioseo())->is_post_noindex(42));
    }

    public function treatsThrownErrorAsUndetermined(): void
    {
        _reset_wp_mocks();
        $this->stubPost();
        _set_seo_stub('aioseo_meta', 'throw');

        Assert::null((new Aioseo())->is_post_noindex(42));
    }
}
```

- [ ] **Step 7: Прогнать оба раннера**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add src/Seo/Aioseo.php tests/phpunit/bootstrap.php tests/phpunit/Seo/AioseoTest.php tests/Unit/SeoAioseoTest.php
git commit -m "feat(seo): Определять noindex от All in One SEO"
```

---

## Task 5: Детектор The SEO Framework

**Files:**
- Create: `src/Seo/TheSeoFramework.php`
- Modify: `tests/phpunit/bootstrap.php` (заглушка `The_SEO_Framework\Meta\Robots`)
- Test: `tests/phpunit/Seo/TheSeoFrameworkTest.php`, `tests/Unit/SeoTheSeoFrameworkTest.php`

**Interfaces:**
- Consumes: `SeoPluginInterface` из Task 1, `_set_seo_stub()` из Task 0
- Produces: `Mihdan\ReCrawler\Seo\TheSeoFramework`, слаг `the-seo-framework`. Ключ заглушки — `tsf_meta` (массив, `'throw'` или `null`).

- [ ] **Step 1: Добавить заглушку в bootstrap**

```php
if ( ! class_exists( 'The_SEO_Framework\Meta\Robots' ) ) {
    eval( 'namespace The_SEO_Framework\Meta; class Robots {
        public static function get_generated_meta( $args = null, $get = null, $options = 0 ) {
            $meta = \_get_seo_stub( "tsf_meta" );
            if ( "throw" === $meta ) {
                throw new \RuntimeException( "TSF exploded" );
            }
            return null === $meta ? [] : $meta;
        }
    }' );
}
```

- [ ] **Step 2: Написать падающий тест**

`tests/phpunit/Seo/TheSeoFrameworkTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Seo\TheSeoFramework;
use PHPUnit\Framework\TestCase;

class TheSeoFrameworkTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_slug() {
		$this->assertSame( 'the-seo-framework', ( new TheSeoFramework() )->get_slug() );
	}

	public function test_noindex_detected() {
		_set_seo_stub( 'tsf_meta', [ 'noindex' => 'noindex' ] );
		$this->assertTrue( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_indexable_post() {
		_set_seo_stub( 'tsf_meta', [ 'noindex' => '' ] );
		$this->assertFalse( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_missing_key_is_undetermined() {
		_set_seo_stub( 'tsf_meta', [] );
		$this->assertNull( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}

	public function test_thrown_error_is_undetermined() {
		_set_seo_stub( 'tsf_meta', 'throw' );
		$this->assertNull( ( new TheSeoFramework() )->is_post_noindex( 42 ) );
	}
}
```

- [ ] **Step 3: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/TheSeoFrameworkTest.php`
Expected: FAIL — класс не найден

- [ ] **Step 4: Реализовать детектор**

`src/Seo/TheSeoFramework.php`:

```php
<?php
/**
 * The SEO Framework noindex detector.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use The_SEO_Framework\Meta\Robots as TsfRobots;
use Throwable;

class TheSeoFramework implements SeoPluginInterface {

	public function get_slug(): string {
		return 'the-seo-framework';
	}

	public function is_active(): bool {
		return class_exists( TsfRobots::class );
	}

	/**
	 * TSF converts a truthy directive into its own name, so the `noindex` key
	 * holds either the string `noindex` or an empty value.
	 */
	public function is_post_noindex( int $post_id ): ?bool {
		try {
			$meta = TsfRobots::get_generated_meta( [ 'id' => $post_id ], [ 'noindex' ] );

			if ( ! is_array( $meta ) || ! array_key_exists( 'noindex', $meta ) ) {
				return null;
			}

			return 'noindex' === $meta['noindex'];
		} catch ( Throwable $e ) {
			return null;
		}
	}
}
```

- [ ] **Step 5: Убедиться, что зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/TheSeoFrameworkTest.php`
Expected: PASS — 5 тестов

- [ ] **Step 6: Продублировать в Testo**

`tests/Unit/SeoTheSeoFrameworkTest.php`:

```php
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
}
```

- [ ] **Step 7: Прогнать оба раннера**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add src/Seo/TheSeoFramework.php tests/phpunit/bootstrap.php tests/phpunit/Seo/TheSeoFrameworkTest.php tests/Unit/SeoTheSeoFrameworkTest.php
git commit -m "feat(seo): Определять noindex от The SEO Framework"
```

---

## Task 6: Агрегатор Indexability

**Files:**
- Create: `src/Seo/Indexability.php`
- Test: `tests/phpunit/Seo/IndexabilityTest.php`, `tests/Unit/SeoIndexabilityTest.php`

**Interfaces:**
- Consumes: `SeoPluginInterface` и все пять детекторов из задач 1–5, `Mihdan\ReCrawler\Logger\Logger`
- Produces: `Mihdan\ReCrawler\Seo\Indexability` с конструктором `__construct( Logger $logger, ?array $detectors = null )` и методом `is_post_indexable( int $post_id ): bool`; фильтр `recrawler/is_post_indexable` с аргументами `( bool $indexable, int $post_id )`

- [ ] **Step 1: Написать падающий тест**

`tests/phpunit/Seo/IndexabilityTest.php`:

```php
<?php

namespace Mihdan\ReCrawler\Tests\Seo;

use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Seo\Indexability;
use Mihdan\ReCrawler\Seo\SeoPluginInterface;
use PHPUnit\Framework\TestCase;

class IndexabilityTest extends TestCase {

	private $logger;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		$this->logger = $this->createMock( Logger::class );
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 1 : $default;
		} );
	}

	/**
	 * @param bool|null $answer What is_post_noindex() returns.
	 */
	private function detector( string $slug, bool $active, $answer, bool $throws = false ): SeoPluginInterface {
		return new class( $slug, $active, $answer, $throws ) implements SeoPluginInterface {
			private $slug;
			private $active;
			private $answer;
			private $throws;
			public $asked = false;

			public function __construct( $slug, $active, $answer, $throws ) {
				$this->slug   = $slug;
				$this->active = $active;
				$this->answer = $answer;
				$this->throws = $throws;
			}

			public function get_slug(): string {
				return $this->slug;
			}

			public function is_active(): bool {
				return $this->active;
			}

			public function is_post_noindex( int $post_id ): ?bool {
				$this->asked = true;

				if ( $this->throws ) {
					throw new \RuntimeException( 'detector exploded' );
				}

				return $this->answer;
			}
		};
	}

	public function test_closed_site_is_not_indexable_and_detectors_are_not_asked() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 0 : $default;
		} );

		$detector = $this->detector( 'yoast', true, false );

		$this->assertFalse( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $detector->asked );
	}

	public function test_first_definite_answer_stops_the_walk() {
		$first  = $this->detector( 'yoast', true, true );
		$second = $this->detector( 'rank-math', true, false );

		$this->assertFalse( ( new Indexability( $this->logger, [ $first, $second ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $second->asked );
	}

	public function test_undetermined_detector_defers_to_the_next_one() {
		$first  = $this->detector( 'yoast', true, null );
		$second = $this->detector( 'rank-math', true, true );

		$this->assertFalse( ( new Indexability( $this->logger, [ $first, $second ] ) )->is_post_indexable( 42 ) );
		$this->assertTrue( $second->asked );
	}

	public function test_inactive_detector_is_skipped() {
		$inactive = $this->detector( 'yoast', false, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $inactive ] ) )->is_post_indexable( 42 ) );
		$this->assertFalse( $inactive->asked );
	}

	public function test_empty_list_fails_open() {
		$this->assertTrue( ( new Indexability( $this->logger, [] ) )->is_post_indexable( 42 ) );
	}

	public function test_throwing_detector_fails_open() {
		$detector = $this->detector( 'yoast', true, null, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
	}

	public function test_filter_can_override_noindex() {
		_set_wp_override( 'apply_filters', function ( $tag, $value, ...$args ) {
			return 'recrawler/is_post_indexable' === $tag ? true : $value;
		} );

		$detector = $this->detector( 'yoast', true, true );

		$this->assertTrue( ( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 ) );
	}

	public function test_filter_can_override_closed_site() {
		_set_wp_override( 'get_option', function ( $option, $default = false ) {
			return 'blog_public' === $option ? 0 : $default;
		} );
		_set_wp_override( 'apply_filters', function ( $tag, $value, ...$args ) {
			return 'recrawler/is_post_indexable' === $tag ? true : $value;
		} );

		$this->assertTrue( ( new Indexability( $this->logger, [] ) )->is_post_indexable( 42 ) );
	}

	public function test_skip_is_logged_with_the_reason() {
		$this->logger->expects( $this->once() )
			->method( 'debug' )
			->with( $this->stringContains( 'yoast' ), $this->anything() );

		$detector = $this->detector( 'yoast', true, true );

		( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 );
	}

	public function test_indexable_post_is_not_logged() {
		$this->logger->expects( $this->never() )->method( 'debug' );

		$detector = $this->detector( 'yoast', true, false );

		( new Indexability( $this->logger, [ $detector ] ) )->is_post_indexable( 42 );
	}
}
```

- [ ] **Step 2: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/IndexabilityTest.php`
Expected: FAIL — `Class "Mihdan\ReCrawler\Seo\Indexability" not found`

- [ ] **Step 3: Реализовать агрегатор**

`src/Seo/Indexability.php`:

```php
<?php
/**
 * Decides whether a post may be submitted for recrawl.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler\Seo;

use Mihdan\ReCrawler\Logger\Logger;
use Throwable;

class Indexability {

	/**
	 * Logger instance.
	 *
	 * @var Logger $logger
	 */
	private Logger $logger;

	/**
	 * Detectors in the order they are asked.
	 *
	 * @var SeoPluginInterface[] $detectors
	 */
	private array $detectors;

	/**
	 * Constructor.
	 *
	 * @param Logger                    $logger    Logger instance.
	 * @param SeoPluginInterface[]|null $detectors Detectors to ask. Null means the default set.
	 */
	public function __construct( Logger $logger, ?array $detectors = null ) {
		$this->logger    = $logger;
		$this->detectors = $detectors ?? [
			new Yoast(),
			new RankMath(),
			new Aioseo(),
			new SeoPress(),
			new TheSeoFramework(),
		];
	}

	/**
	 * Whether the post may be submitted for recrawl.
	 *
	 * Fails open: when nothing can tell, the post is treated as indexable —
	 * a broken third-party API must not stop the plugin from doing its job.
	 *
	 * @param int $post_id Post ID.
	 */
	public function is_post_indexable( int $post_id ): bool {
		$indexable = true;
		$reason    = '';

		if ( ! get_option( 'blog_public' ) ) {
			$indexable = false;
			$reason    = 'blog_public';
		} else {
			foreach ( $this->detectors as $detector ) {
				if ( ! $detector->is_active() ) {
					continue;
				}

				try {
					$noindex = $detector->is_post_noindex( $post_id );
				} catch ( Throwable $e ) {
					continue;
				}

				if ( null === $noindex ) {
					continue;
				}

				if ( $noindex ) {
					$indexable = false;
					$reason    = $detector->get_slug();
				}

				break;
			}
		}

		if ( ! $indexable ) {
			$this->logger->debug(
				sprintf( 'Post %d skipped: closed from indexing (%s)', $post_id, $reason ),
				[
					'search_engine' => 'site',
					'status_code'   => 0,
				]
			);
		}

		return (bool) apply_filters( 'recrawler/is_post_indexable', $indexable, $post_id );
	}
}
```

- [ ] **Step 4: Убедиться, что зелёный**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/Seo/IndexabilityTest.php`
Expected: PASS — 10 тестов

- [ ] **Step 5: Продублировать ключевые случаи в Testo**

`tests/Unit/SeoIndexabilityTest.php`:

```php
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
}
```

Testo-версия использует настоящий `Logger`: его `log()` пишет через `$GLOBALS['wpdb']`, а заглушка `wpdb` в bootstrap принимает `insert()` без побочных эффектов.

- [ ] **Step 6: Прогнать оба раннера и psalm**

Run: `composer test && composer test:testo && composer psalm`
Expected: PASS; psalm — не больше 134 ошибок

- [ ] **Step 7: Commit**

```bash
git add src/Seo/Indexability.php tests/phpunit/Seo/IndexabilityTest.php tests/Unit/SeoIndexabilityTest.php
git commit -m "feat(seo): Свести детекторы noindex в Indexability"
```

---

## Task 7: Подключить проверку к хукам

**Files:**
- Modify: `src/Hooks.php` — конструктор (строки 37–40), `post_updated()` (после проверки на строке 112), `comment_updated()` (перед `do_action` на строке 182)
- Test: `tests/phpunit/HooksTest.php` (шесть вызовов `new Hooks($this->wposa)` требуют второго аргумента)

**Interfaces:**
- Consumes: `Indexability::is_post_indexable( int $post_id ): bool` из Task 6
- Produces: `Hooks::__construct( WPOSA $wposa, Indexability $indexability )`

- [ ] **Step 1: Написать падающие тесты**

Добавить в `tests/phpunit/HooksTest.php`:

```php
	private function indexability( bool $indexable ) {
		$mock = $this->createMock( \Mihdan\ReCrawler\Seo\Indexability::class );
		$mock->method( 'is_post_indexable' )->willReturn( $indexable );

		return $mock;
	}

	public function test_post_updated_skips_noindex_post() {
		$this->setup_defaults();
		$this->wposa->method( 'get_option' )->willReturn( [ 'post' ] );

		$hooks           = new Hooks( $this->wposa, $this->indexability( false ) );
		$do_action_calls = [];
		_set_wp_override( 'do_action', function ( $tag, ...$args ) use ( &$do_action_calls ) {
			$do_action_calls[] = $tag;
		} );

		$hooks->post_updated( 'publish', 'draft', $this->make_post() );

		$this->assertEmpty( $do_action_calls );
	}

	public function test_comment_updated_skips_noindex_post() {
		$this->setup_defaults();

		$hooks           = new Hooks( $this->wposa, $this->indexability( false ) );
		$do_action_calls = [];
		_set_wp_override( 'do_action', function ( $tag, ...$args ) use ( &$do_action_calls ) {
			$do_action_calls[] = $tag;
		} );

		$hooks->comment_updated( 'approved', 'hold', $this->make_comment( 1, 1, 7 ) );

		$this->assertEmpty( $do_action_calls );
	}
```

- [ ] **Step 2: Убедиться, что падает**

Run: `vendor/bin/phpunit --no-coverage tests/phpunit/HooksTest.php`
Expected: FAIL — `Too few arguments to function Hooks::__construct()`

- [ ] **Step 3: Добавить зависимость в конструктор**

В `src/Hooks.php` добавить импорт `use Mihdan\ReCrawler\Seo\Indexability;`, свойство и параметр:

```php
	/**
	 * Indexability checker.
	 *
	 * @var Indexability $indexability
	 */
	private Indexability $indexability;

	/**
	 * Constructor.
	 *
	 * @param WPOSA        $wposa        WPOSA instance.
	 * @param Indexability $indexability Indexability checker.
	 */
	public function __construct( WPOSA $wposa, Indexability $indexability ) {
		$this->wposa        = $wposa;
		$this->indexability = $indexability;
		$this->ping_delay   = (int) $this->wposa->get_option( 'ping_delay', 'general', 60 );
	}
```

- [ ] **Step 4: Вставить проверку в post_updated()**

Сразу после блока `is_post_publicly_viewable()`:

```php
		if ( ! $this->indexability->is_post_indexable( $post->ID ) ) {
			return;
		}
```

- [ ] **Step 5: Вставить проверку в comment_updated()**

Перед `do_action( 'recrawler/comment_updated', ... )`:

```php
		if ( ! $this->indexability->is_post_indexable( (int) $comment->comment_post_ID ) ) {
			return;
		}
```

- [ ] **Step 6: Обновить существующие вызовы в HooksTest**

Заменить все шесть `new Hooks($this->wposa)` на `new Hooks($this->wposa, $this->indexability(true))`.

Run: `grep -n 'new Hooks(' tests/phpunit/HooksTest.php`
Expected: ни одного вызова с одним аргументом

- [ ] **Step 7: Убедиться, что всё зелёное**

Run: `composer test && composer test:testo`
Expected: PASS

- [ ] **Step 8: Проверить, что тесты ловят регрессию**

Временно закомментировать обе вставленные проверки и прогнать `vendor/bin/phpunit --no-coverage tests/phpunit/HooksTest.php`.
Expected: FAIL на `test_post_updated_skips_noindex_post` и `test_comment_updated_skips_noindex_post` — «Failed asserting that array is empty», а не на отсутствии заглушек. Затем вернуть проверки.

- [ ] **Step 9: Commit**

```bash
git add src/Hooks.php tests/phpunit/HooksTest.php
git commit -m "feat(seo): Не пинговать записи, закрытые от индексации

Closes: #12"
```

---

## Task 8: Версия 1.1.0

**Files:**
- Modify: `recrawler.php:5` (заголовок), `recrawler.php:25` (`RECRAWLER_VERSION`)
- Modify: `readme.txt:7` (`Stable tag`)

**Interfaces:**
- Consumes: ничего
- Produces: ничего

- [ ] **Step 1: Поднять версию**

```bash
sed -i '' "s/^ \* Version: 1\.0\.2$/ * Version: 1.1.0/" recrawler.php
sed -i '' "s/define( 'RECRAWLER_VERSION', '1\.0\.2' );/define( 'RECRAWLER_VERSION', '1.1.0' );/" recrawler.php
sed -i '' "s/^Stable tag: 1\.0\.2$/Stable tag: 1.1.0/" readme.txt
```

- [ ] **Step 2: Проверить, что версия проставлена в трёх местах**

Run: `grep -E "^ \* Version:|RECRAWLER_VERSION" recrawler.php && grep "^Stable tag:" readme.txt`
Expected: везде `1.1.0`

- [ ] **Step 3: Финальная проверка**

Run: `composer test && composer test:testo && composer psalm`
Expected: PASS; psalm — не больше 134 ошибок

- [ ] **Step 4: Commit**

```bash
git add recrawler.php readme.txt
git commit -m "chore(release): 1.1.0"
```

**Changelog в `readme.txt` не заполнять** — записи релизов пишет владелец проекта. Сообщить ему, что в 1.1.0 вошло: записи с `noindex` от Yoast, Rank Math, All in One SEO, SEOPress и The SEO Framework больше не отправляются на переобход; при выключенной галке «Видимость для поисковых систем» пинги не уходят вовсе; поведение переопределяется фильтром `recrawler/is_post_indexable`.

---

## Self-Review

**Покрытие спеки:** интерфейс и пять детекторов — задачи 1–5; `Indexability` с порядком проверок, fail open и фильтром — задача 6; `blog_public` — задача 6, шаг 3; логирование `debug` с причиной — задача 6; две точки вызова в `Hooks` — задача 7; проверка честности тестов — задача 7, шаг 8; SemVer-минор — задача 8. Термы в спеке объявлены вне объёма, задач под них нет намеренно.

**Согласованность типов:** `is_post_noindex(): ?bool` во всех пяти детекторах и в стабах тестов; `is_post_indexable( int ): bool` в задачах 6 и 7; `get_slug(): string` используется в логировании задачи 6; конструктор `Indexability( Logger, ?array )` совпадает во всех тестах.

**Известный риск:** в задаче 4 заглушка `dynamicOptions` отдаёт итоговое значение через `__call`, поэтому детектор обязан спрашивать `robotsMeta->noindex()` вызовом метода. Если при живой проверке с настоящим AIOSEO окажется, что там свойство, а не метод, правится и детектор, и заглушка одновременно.
