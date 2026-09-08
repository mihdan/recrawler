# Не пинговать записи, закрытые от индексации

Дизайн для [issue #12](https://github.com/mihdan/recrawler/issues/12). Целевая версия — 1.1.0.

## Задача

Плагин отправляет на переобход записи, которым SEO-плагин выставил `noindex`. Единственная релевантная проверка в `Hooks::post_updated()` — `is_post_publicly_viewable( $post )`, а она смотрит только на статус записи и публичность типа записи и мета-роботов не видит.

Попутно обнаружено: плагин не смотрит и `blog_public` — общесайтовую галку «Попросить поисковые системы не индексировать сайт». На закрытом от индексации сайте пинги уходят независимо от SEO-плагина.

Обе дырки закрываются одной проверкой в одной точке, поэтому идут одним изменением.

## Что в объёме

Записи и комментарии. Комментарии пингуют ту же запись через `comment_post_ID` — двумя разными хуками: `Hooks::comment_updated()` (`transition_comment_status`, смена статуса существующего комментария) и `Hooks::comment_inserted()` (`wp_insert_comment`, срабатывает и на автоодобренный новый комментарий). Без проверки в обоих `noindex`-запись утекала бы на переобход при каждом новом или переодобренном комментарии.

Термы (`Hooks::term_updated()`) — вне объёма для проверки *noindex*: у каждого SEO-плагина для таксономий отдельный API, это ровно вдвое больше детекторов и тестов. Отдельной задачей. Но общесайтовую `blog_public` термы всё же учитывают: это не per-term проверка, а `get_option('blog_public')` через `Indexability::is_site_public()` — тот же метод, которым закрывается запись из спеки строкой выше.

Переключателя в настройках нет. Отправлять на переобход то, что владелец сайта сам закрыл от индексации, смысла не имеет; для нестандартных случаев есть фильтр.

## Компоненты

### `src/Seo/SeoPluginInterface.php`

```php
public function get_slug(): string;
public function is_active(): bool;
public function is_post_noindex( int $post_id ): ?bool;
```

`is_post_noindex()` возвращает `null`, когда определить не удалось. `get_slug()` нужен для записи причины в журнал.

### Детекторы в `src/Seo/`

Все API проверены по исходникам плагинов, а не по документации.

| Класс | Признак активности | Проверка |
|---|---|---|
| `Yoast` | `function_exists( 'YoastSEO' )` | `YoastSEO()->meta->for_post( $post_id )->robots['index'] === 'noindex'` (Surfaces API, Yoast 14+; учитывает и мету записи, и глобальный `noindex` типа записи) |
| `RankMath` | `class_exists( RankMath\Post::class )` | `RankMath\Post::get_meta( 'robots', $post_id )` → массив, `in_array( 'noindex', …, true )` |
| `Aioseo` | `function_exists( 'aioseo' )` | `aioseo()->meta->metaData->getMetaData( $post )`: при `! robots_default` → `(bool) robots_noindex`, иначе `noindex` типа записи через `aioseo()->dynamicOptions->noConflict( true )->searchAppearance->postTypes` |
| `SeoPress` | `defined( 'SEOPRESS_VERSION' )` | `get_post_meta( $post_id, '_seopress_robots_index', true ) === 'yes'` |
| `TheSeoFramework` | `class_exists( The_SEO_Framework\Meta\Robots::class )` | `Robots::get_generated_meta( [ 'id' => $post_id ], [ 'noindex' ] )` |

Логика AIOSEO воспроизводит их собственный приватный `DetailsColumn::isNoindexed()`; читаем через публичный `aioseo()`, без запросов в таблицу `aioseo_posts` напрямую. Сигнатуры сверены с исходниками конкретных версий: Yoast SEO — Surfaces API, документация developer.yoast.com; Rank Math — `includes/class-post.php` 1.0.278-beta; SEOPress — `inc/functions/options-advanced-admin.php` 10.2; The SEO Framework — `inc/classes/meta/robots.class.php` 5.1.4; All in One SEO — `app/Common/Traits/Options.php` (`has()`, `all()`) и `app/Common/Meta/Robots.php`, версия 5.0.1.1.

AIOSEO покрыт не полностью. Их резолюция при непустом флаге `default` у типа записи падает на глобальные robots meta, а тип записи, исключённый из результатов поиска (`show`), считается noindex независимо от robots meta. Обе ветки идут через три уровня магических методов приватного `Options`-объекта, поэтому детектор их не воспроизводит: при флаге `default` он возвращает `null` — «не смог определить» — и срабатывает fail open. Следствие: записи, закрытые **глобальной** настройкой типа записи в AIOSEO, продолжат отправляться на переобход. Явный `noindex` на самой записи — главный сценарий issue #12 — покрыт полностью, до типа записи дело не доходит.

SEOPress видит только мету записи — глобальный `noindex` типа записи он в этой проверке не учитывает. Это осознанное ограничение: публичного API у плагина нет, а его настройки типов записи лежат в структуре, на которую опираться небезопасно.

### `src/Seo/Indexability.php`

Единственный публичный вход:

```php
public function __construct( Logger $logger, ?array $detectors = null );
public function is_post_indexable( int $post_id ): bool;
```

`?array $detectors = null` даёт тестируемость: контейнер для builtin-типов подставляет значение по умолчанию (`Container.php:207`), поэтому в проде приходит `null` и класс собирает пятерых сам, а тест передаёт стабы или `[]`.

## Поток

1. `get_option( 'blog_public' )` пустой → `false`. Сайт закрыт целиком, детекторы не опрашиваются.
2. Детекторы по списку: перебор останавливается на первом активном, вернувшем не-`null`.
3. `apply_filters( 'recrawler/is_post_indexable', $indexable, $post_id )` применяется к итогу последним — включая случай `blog_public`, чтобы у фильтра не было исключений в контракте.

Имя фильтра следует стилю существующих: `recrawler/post_types`, `recrawler/taxonomies`, `recrawler/host`.

### Fail open

Ни один детектор не активен, либо все вернули `null` → `true`, пингуем. Каждый вызов чужого API обёрнут в `try/catch ( \Throwable )`, исключение превращается в `null`: Yoast Surfaces API умеет бросать на записях без индексируемого объекта, и падать из-за чужого кода в момент сохранения записи плагин не должен.

Если активны два SEO-плагина, решает первый в списке, вернувший определённый ответ. Порядок фиксирован по распространённости плагинов: `Yoast`, `RankMath`, `Aioseo`, `SeoPress`, `TheSeoFramework`.

### Логирование

Только при отрицательном решении, уровень `debug` (`Logger` наследует PSR `AbstractLogger`, стиль `.level--debug` в `admin.css` уже есть):

```php
$this->logger->debug(
	sprintf( 'Post %d skipped: closed from indexing (%s)', $post_id, $reason ),
	[ 'search_engine' => 'site', 'status_code' => 0 ]
);
```

`$reason` — `blog_public` либо слаг сработавшего детектора, чтобы из журнала было видно, кто закрыл запись.

## Точки вызова

`src/Hooks.php`, четыре штуки:

- `post_updated()` — непосредственно перед `do_action( 'recrawler/post_added' | 'recrawler/post_updated' )`, после проверок `post_types`, Bulk Edit и задержки. Не сразу после `is_post_publicly_viewable()`: раньше в этом месте детекторы и `debug`-лог срабатывали бы на каждом сохранении любой публичной записи, включая типы, не отслеживаемые настройками, и записи внутри `ping_delay`
- `comment_updated()` — перед `do_action( 'recrawler/comment_updated' )`, по `$comment->comment_post_ID`
- `comment_inserted()` — перед тем же `do_action( 'recrawler/comment_updated' )`, тем же способом; хук отдельный (`wp_insert_comment`), а не альтернативный путь того же события
- `term_updated()` — вызывает не `is_post_indexable()`, а `Indexability::is_site_public(): bool` (без применения фильтра `recrawler/is_post_indexable` — его контракт `( bool $indexable, int $post_id )` term-у не подходит)

`Indexability` приезжает в `Hooks` через конструктор, автоваринг контейнера разрешает его как `WPOSA` и `Logger`.

## Тесты

Оба раннера: PHPUnit-версии на моках, Testo-версии на настоящих объектах и `_set_wp_override`, по образцу `YandexWebmasterSchedulePingTest`.

`IndexabilityTest` — вся логика на стабах `SeoPluginInterface`, без чужих плагинов:

- `blog_public = 0` → `false`, детекторы не опрашиваются
- первый активный вернул `false` → `false`, второй не спрашивается
- первый вернул `null` → опрашивается второй
- все `null` или список пуст → `true`
- детектор бросил `\Throwable` → трактуется как `null`, пингуем
- фильтр перебивает и `false`, и `blog_public`
- при отрицательном решении в логгер уходит `debug` с причиной; при положительном — ничего

Тесты детекторов — по одному на плагин. Чужие API подделываются: `YoastSEO()` и `aioseo()` — функциями в `tests/phpunit/bootstrap.php`, `RankMath\Post` и `The_SEO_Framework\Meta\Robots` — классами-заглушками; поведение управляется через существующий `_set_wp_override`. `SeoPress` обходится уже застабленным `get_post_meta`.

Заглушки делают все пять детекторов «активными» во всех тестах — именно поэтому `IndexabilityTest` работает на инъекции, а не на реальном списке.

`HooksTest` — `post_updated()` и `comment_updated()` не бросают свои `do_action`, когда `Indexability` вернул `false`.

Набор прогоняется на коде без проверки и должен падать по сути, а не на отсутствии заглушек.

## Совместимость

Изменение поведения по умолчанию: записи с `noindex` перестают отправляться на переобход. Публичные хуки, имена опций и сигнатуры не меняются, поэтому по SemVer это минор — 1.1.0.
