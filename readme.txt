=== ElasticPress Debugging Add-On ===
Contributors: tlovett1, 10up
Tags: debug, debug bar, elasticpress, elasticsearch
Requires at least: 4.6
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 3.1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends the Query Monitor and Debug Bar plugins for ElasticPress queries.

== Description ==

Adds an [ElasticPress](https://wordpress.org/plugins/elasticpress) panel to [Debug Bar](https://wordpress.org/plugins/debug-bar/) and/or [Query Monitor](https://wordpress.org/plugins/query-monitor/) plugins. Allows you to examine every ElasticPress query running on any given request.

= Requirements: =

* [ElasticPress 4.4.0+](https://wordpress.org/plugins/elasticpress)
* [Debug Bar 1.0+](https://wordpress.org/plugins/debug-bar/)
* PHP 7.0+

== Installation ==
1. Install [ElasticPress](https://wordpress.org/plugins/elasticpress).
2. Install [Debug Bar](https://wordpress.org/plugins/debug-bar/).
3. Install the plugin in WordPress.

== Changelog ==

= 3.1.0 - 2023-09-20 =

__Added:__

* New button to explain ES queries. Props [@burhandodhy](https://github.com/burhandodhy), [@felipeelia](https://github.com/felipeelia), [@MARQAS](https://github.com/MARQAS), and [@brandwaffle](https://github.com/brandwaffle).
* New button to Reload and retrieve raw ES document. Props [@burhandodhy](https://github.com/burhandodhy), [@felipeelia](https://github.com/felipeelia), and [@brandwaffle](https://github.com/brandwaffle).
* Query types (and context when listing queries in the Query Log admin screen.) Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy).
* Log query by context, status, and fixed time. Props [@felipeelia](https://github.com/felipeelia).
* Official support to Query Monitor. Props [@felipeelia](https://github.com/felipeelia).

__Security:__

* Bumped `tough-cookie` from 4.1.2 to 4.1.3. Props [@dependabot](https://github.com/dependabot).
* Bumped `word-wrap` from 1.2.3 to 1.2.4. Props [@dependabot](https://github.com/dependabot).

= 3.0.0 - 2023-03-23 =

This release drops the support for older versions of ElasticPress and PHP.

__Added:__

* Instructions with error code for failed queries. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia).
* Buttons to copy or download all requests info. Props [@MARQAS](https://github.com/MARQAS), [@felipeelia](https://github.com/felipeelia), and [@burhandodhy](https://github.com/burhandodhy).
* Compatibility with the WordPress localization system. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia).
* SECURITY.md file. Props [@felipeelia](https://github.com/felipeelia).

__Changed:__

* Set minimum requirement for PHP to 7.0 and ElasticPress to 4.4.0. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia).
* CSS and JS code lint by 10up toolkit. Props [@burhandodhy](https://github.com/burhandodhy).

__Fixed:__

* Unnecessary `stripslashes()` call when outputting JSON objects. Props [@felipeelia](https://github.com/felipeelia), [@goldenapples](https://github.com/goldenapples), and [@mattonomics](https://github.com/mattonomics).
* JS error on copy action. Props [@burhandodhy](https://github.com/burhandodhy).

__Security:__

* Bumped `minimatch` from 3.0.4 to 3.1.2. Props [@dependabot](https://github.com/dependabot).
* Bumped `json5` from 2.2.0 to 2.2.3. Props [@dependabot](https://github.com/dependabot).
* Bumped `webpack` from 5.75.0 to 5.76.2. Props [@dependabot](https://github.com/dependabot).


= 2.1.1 - 2022-08-04 =

__Security:__

* Fix XSS vulnerability. Props [@piotr-bajer](https://github.com/piotr-bajer) and [@felipeelia](https://github.com/felipeelia).
* Bumped `path-parse` from 1.0.6 to 1.0.7. Props [@dependabot](https://github.com/dependabot).
* Bumps `minimist` from 1.2.5 to 1.2.6. Props [@dependabot](https://github.com/dependabot).
* Bumps `ansi-regex` from 5.0.0 to 5.0.1. Props [@dependabot](https://github.com/dependabot).


= 2.1.0 =

__Added:__

* ElasticPress and Elasticsearch versions. Props to [@oscarssanchez](https://github.com/oscarssanchez) and [@felipeelia](https://github.com/felipeelia).
* Log of bulk_index requests. Props [@felipeelia](https://github.com/felipeelia).
* Warning when ElasticPress is indexing. Props [@nathanielks](https://github.com/nathanielks) and [@felipeelia](https://github.com/felipeelia).

__Changed:__

* Only load CSS and JS files for logged-in users. Props [@cbratschi](https://github.com/cbratschi) and [@felipeelia](https://github.com/felipeelia).

= 2.0.0 =
This release drops the support for older versions of WordPress Core, ElasticPress and Debug Bar.

* Code refactoring. Props [@felipeelia](https://github.com/felipeelia)
* Fixed Query Logs in EP Dashboard [@felipeelia](https://github.com/felipeelia)
* Fixed typo from "clsas" to "class" in the query output. Props [@Rahmon](https://github.com/Rahmon)

= 1.4 =
* Support ElasticPress 3.0+

= 1.3 =
* Add query log

= 1.2 =
* Show query errors (i.e. cURL timeout)
* Add ?explain to query if GET param is set

= 1.1.1 =
* Only show query body if it exits

= 1.1 =
* Improve formatting
* Show original query args (EP 2.1+)

= 1.0 =
* Initial release
