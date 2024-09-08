=== ReCrawler ===
Contributors: mihdan
Donate link: https://www.kobzarev.com/donate/
Tags: indexnow, yandex, bing, google, seo
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 0.1.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ReCrawler is a small WordPress Plugin for quickly notifying search engines whenever their website content is created, updated, or deleted.

== Description ==

ReCrawler is a small WordPress Plugin for quickly notifying search engines whenever their website content is created, updated, or deleted.

Improve your rankings by taking control of the crawling and indexing process, so search engines know what to focus on!

Once installed, it detects pages/terms creation/update/deletion in WordPress and automatically submits the URLs in the background via ReCrawler, Google API, Bing API, and Yandex API protocols.

It ensures that search engines invariably have the latest updates about your site.

### 🤖 What is ReCrawler? ###

ReCrawler is an easy way for websites owners to instantly inform search engines about latest content changes on their website. In its simplest form, ReCrawler is a simple ping so that search engines know that a URL and its content has been added, updated, or deleted, allowing search engines to quickly reflect this change in their search results.

Without ReCrawler, it can take days to weeks for search engines to discover that the content has changed, as search engines don’t crawl every URL often. With ReCrawler, search engines know immediately the "URLs that have changed, helping them prioritize crawl for these URLs and thereby limiting organic crawling to discover new content."

ReCrawler is offered under the terms of the Attribution-ShareAlike Creative Commons License and has support from Microsoft Bing, Yandex.

### ✅ Requirement for search engines ###

Search Engines adopting the ReCrawler protocol agree that submitted URLs will be automatically shared with all other participating Search Engines. To participate, search engines must have a noticeable presence in at least one market.

### ⛑️ Documentation and support ###
If you have some questions or suggestions, welcome to our [GitHub repository](https://github.com/mihdan/recrawler/issues).

### 💙 Love ReCrawler for WordPress? ###
If the plugin was useful, rate it with a [5 star rating](https://wordpress.org/support/plugin/recrawler/reviews/) and write a few nice words.

### 🏳️ Translations ###
[Help translate ReCrawler](https://translate.wordpress.org/projects/wp-plugins/recrawler/)

- 🇺🇸 English (en_US) - [Mikhail kobzarev](https://profiles.wordpress.org/mihdan)
- 🇷🇺 Русский (ru_RU) - [Mikhail kobzarev](https://profiles.wordpress.org/mihdan)
- [You could be next](https://translate.wordpress.org/projects/wp-plugins/recrawler/)...

Can you help with plugin translation? Please feel free to contribute!

### External services ###

This plugin uses external services, which are documented below with links to the service’s Privacy Policy. These services are integral to the functionality and features offered by the plugin. However, we acknowledge the importance of transparency regarding the use of such services.

1. Yandex Webmaster - [https://webmaster.yandex.ru](https://webmaster.yandex.ru)
2. Yandex IndexNow - [https://yandex.com/indexnow](https://yandex.com/indexnow)
3. Bing IndexNow - [https://www.bing.com/indexnow](https://www.bing.com/indexnow)
4. Bing Webmaster - [https://ssl.bing.com/webmaster/](https://ssl.bing.com/webmaster/)
5. Google Indexing API - [https://indexing.googleapis.com/](https://indexing.googleapis.com/)
6. Naver IndexNow - [https://searchadvisor.naver.com/indexnow](https://searchadvisor.naver.com/indexnow)
7. Seznam IndexNow - [https://search.seznam.cz/indexnow](https://search.seznam.cz/indexnow)
8. IndexNow - [https://api.indexnow.org](https://api.indexnow.org)
9. Google Developers Console - [https://console.developers.google.com/](https://console.developers.google.com/)
10. Yandex oauth - [https://oauth.yandex.ru/](https://oauth.yandex.ru/)

== Frequently Asked Questions ==

= What are the search engines' endpoint to submit URLs? =

Microsoft Bing - https://www.bing.com/indexnow?url=url-changed&key=your-key
Yandex - https://yandex.com/indexnow?url=url-changed&key=your-key
ReCrawler - https://api.indexnow.org/indexnow/?url=url-changed&key=your-key

Starting November 2021, ReCrawler-enabled search engines will share immediately all URLs submitted to all other ReCrawler-enabled search engines, so when you notify one, you will notify all search engines.

= I submitted a URL, what will happen next? =

If search engines like your URL, search engines will attempt crawling it to get the latest content quickly based on their crawl scheduling logic and crawl quota for your site.

= I submitted 10 thousand URLs today, what will happen next? =

If search engines like your URLs and have enough crawl quota for your site, search engines will attempt crawling some or all these URLs.

= I submitted a URL, but I don’t see the URL indexed? =

Using ReCrawler ensures that search engines are aware of your website changes. Using ReCrawler does not guarantee that web pages will be crawled or indexed by search engines. It may take time for the change to reflect in search engines.

= I just started using ReCrawler, should I publish URLs changed last year? =

No, you should publish only URLs changing (added, updated, or deleted) since the time you start to use ReCrawler.

= Does the URLs submitted count on my crawl quota? =

Yes, every crawl counts towards your crawl quota. By publishing them to ReCrawler, you notify search engines that you care about these URLs, search engines will generally prioritize crawling these URLs versus other URLs they know.

= Why do I not see all the URLs submitted indexed by search engines? =

Search engines can choose not to crawl and index URLs if they do not meet their selection criterion.

= Why is my URL indexed on one search engine but not the others? =

Search Engines can choose not to select specific URL if it does not meet its selection criterion.

= I have a small website that has few web pages. Should I use ReCrawler? =

Yes, if you want search engines to discover content as soon as it’s changed then you should use ReCrawler. You will not have to wait many hours or worse weeks to see your changes on search engines.

= Can I submit the same URL many times a day? =

Avoid submitting the same URL many times a day. If pages are edited often, then it is preferable to wait 10 minutes between edits before notifying search engines. If pages are updated constantly (examples: time in Waimea, Weather in Tokyo), it’s preferable to not use ReCrawler for every change.

= Can I submit 404 URLs through the API? =

Yes, you can submit dead links (http 404, http 410) pages to notify search engines about new dead links.

= Can I submit new redirects? =

Yes, you can submit URLs newly redirecting (example 301 redirect, 302 redirect, html with meta refresh tag, etc.) to notify search engines that the content has changed.

= Can I submit all URLs for my site? =

Use ReCrawler to submit only URLs having changed (added, updated, or deleted) recently, including all URLs if all URLs have been changed recently. Use sitemaps to inform search engines about all your URLs. Search engines will visit sitemaps every few days.

= I received a HTTP 429 Too Many Requests response from one Search Engine, what should I do? =

Such HTTP 429 Too Many Requests response status code indicates you are sending too many requests in a given amount of time, slow down or retry later.

= When do I need to change my key? =

Search engines will attempt crawling the {key}.txt file only once to verify ownership when they received a new key. Also, you don’t need to modify your key often.

= Can I use more than one key per host? =

Yes, if your websites use different Content Management Systems, each Content Management System can use its own key; publish different key files at the root of the host.

= Can I use one file key for the whole domain? =

No each host in your domain must have its own key. If your site has host-a.example.org and host-b.example.org, you need to have a key file for each host.

= Can I use for same key on two or more hosts? =

Yes, you can reuse the same key on two or more hosts, and two or more domains.

= I have a sitemap, do I need ReCrawler? =

Yes, when sitemaps are an easy way for webmasters to inform search engines about all pages on their sites that are available for crawling, sitemaps are visited by Search Engines infrequently. With ReCrawler, webmasters ''don't'' have to wait for search engines to discover and crawl sitemaps but can directly notify search engines of new content.

= What if I have another question about using ReCrawler? =
See the documentation available from each search engine for more details about ReCrawler.

== Changelog ==

= 0.1.5 (09.09.2024) =
* Fixed integration bug with "LuckyWP Table of Contents" plugin

= 0.1.4 (31.08.2024) =
* Fixed tab switching bug

= 0.1.3 (26.06.2024) =
* Updated logo of the application
* Fixed tab switching bug

= 0.1.2 (15.05.2024) =
* Added ability to sort by ReCrawler column in the list of Posts
* Added ability to migrate from IndexNow plugin in automatic mode
* Updated screenshots of the application
* Reduced the size of images in Google API documentation

= 0.1.1 (12.05.2024) =
* Added WordPress 6.5+ support

= 0.1.0 (21.02.2024) =
* Init plugin

== Installation ==

= From your WordPress dashboard =
1. Visit 'Plugins > Add New'
2. Search for 'ReCrawler'
3. Activate ReCrawler from your Plugins page.
4. [Optional] Configure plugin in 'ReCrawler'.

= From WordPress.org =
1. Download ReCrawler.
2. Upload the 'recrawler' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate ReCrawler from your Plugins page.
4. [Optional] Configure plugin in 'ReCrawler > Index Now'.
