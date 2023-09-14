# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

<!--
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security
-->

## [3.1.0] - 2023-09-XX

### Added
- New button to explain ES queries. Props [@burhandodhy](https://github.com/burhandodhy), [@felipeelia](https://github.com/felipeelia), and [@brandwaffle](https://github.com/brandwaffle) via [#79](https://github.com/10up/ElasticPress/pull/79).
- New button to Reload and retrieve raw ES document. Props [@burhandodhy](https://github.com/burhandodhy), [@felipeelia](https://github.com/felipeelia), and [@brandwaffle](https://github.com/brandwaffle) via [#79](https://github.com/10up/ElasticPress/pull/79).
- Query types (and context when listing queries in the Query Log admin screen.) Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy) via [#82](https://github.com/10up/ElasticPress/pull/82).
- Log query by context, status, and fixed time. Props [@felipeelia](https://github.com/felipeelia) via [#83](https://github.com/10up/ElasticPress/pull/83) and [#86](https://github.com/10up/ElasticPress/pull/86).
- Official support to Query Monitor. Props [@felipeelia](https://github.com/felipeelia) via [#84](https://github.com/10up/ElasticPress/pull/84).

### Security
- Bumped `tough-cookie` from 4.1.2 to 4.1.3. Props [@dependabot](https://github.com/dependabot) via [#75](https://github.com/10up/debug-bar-elasticpress/pull/75).
- Bumped `word-wrap` from 1.2.3 to 1.2.4. Props [@dependabot](https://github.com/dependabot) via [#76](https://github.com/10up/debug-bar-elasticpress/pull/76).

## [3.0.0] - 2023-03-23

This release drops the support for older versions of ElasticPress and PHP.

### Added
- Instructions with error code for failed queries. Props [@MARQAS](https://github.com/MARQAS) and [@felipeelia](https://github.com/felipeelia) via [#58](https://github.com/10up/debug-bar-elasticpress/pull/58).
- Buttons to copy or download all requests info. Props [@MARQAS](https://github.com/MARQAS), [@felipeelia](https://github.com/felipeelia), and [@burhandodhy](https://github.com/burhandodhy) via [#63](https://github.com/10up/debug-bar-elasticpress/pull/63) and [#74](https://github.com/10up/ElasticPress/pull/74).
- Compatibility with the WordPress localization system. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia) via [#73](https://github.com/10up/debug-bar-elasticpress/pull/73).
- SECURITY.md file. Props [@felipeelia](https://github.com/felipeelia) via [#56](https://github.com/10up/debug-bar-elasticpress/pull/56).

### Changed
- Set minimum requirement for PHP to 7.0 and ElasticPress to 4.4.0. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia) via [#66](https://github.com/10up/debug-bar-elasticpress/pull/66).
- CSS and JS code lint by 10up toolkit. Props [@burhandodhy](https://github.com/burhandodhy) via [#64](https://github.com/10up/debug-bar-elasticpress/pull/64).

### Fixed
- Unnecessary `stripslashes()` call when outputting JSON objects. Props [@felipeelia](https://github.com/felipeelia), [@goldenapples](https://github.com/goldenapples), and [@mattonomics](https://github.com/mattonomics) via [#68](https://github.com/10up/debug-bar-elasticpress/pull/68).
- JS error on copy action. Props [@burhandodhy](https://github.com/burhandodhy) via [#72](https://github.com/10up/debug-bar-elasticpress/pull/72).

### Security
- Bumped `minimatch` from 3.0.4 to 3.1.2. Props [@dependabot](https://github.com/dependabot) via [#57](https://github.com/10up/debug-bar-elasticpress/pull/57).
- Bumped `json5` from 2.2.0 to 2.2.3. Props [@dependabot](https://github.com/dependabot) via [#60](https://github.com/10up/debug-bar-elasticpress/pull/60).
- Bumped `webpack` from 5.75.0 to 5.76.2. Props [@dependabot](https://github.com/dependabot) via [#67](https://github.com/10up/debug-bar-elasticpress/pull/67).

## [2.1.1] - 2022-08-04

### Security
- Fix XSS vulnerability. Props [@piotr-bajer](https://github.com/piotr-bajer) and [@felipeelia](https://github.com/felipeelia) via [#52](https://github.com/10up/debug-bar-elasticpress/pull/52).
- Bumped `path-parse` from 1.0.6 to 1.0.7. Props [@dependabot](https://github.com/dependabot) via [#49](https://github.com/10up/debug-bar-elasticpress/pull/49).
- Bumps `minimist` from 1.2.5 to 1.2.6. Props [@dependabot](https://github.com/dependabot) via [#51](https://github.com/10up/debug-bar-elasticpress/pull/51).
- Bumps `ansi-regex` from 5.0.0 to 5.0.1. Props [@dependabot](https://github.com/dependabot) via [#53](https://github.com/10up/debug-bar-elasticpress/pull/53).

## [2.1.0] - 2021-08-09

### Added
* ElasticPress and Elasticsearch versions. Props to [@oscarssanchez](https://github.com/oscarssanchez) and [@felipeelia](https://github.com/felipeelia) via [#43](https://github.com/10up/debug-bar-elasticpress/pull/43)
* Log of bulk_index requests. Props [@felipeelia](https://github.com/felipeelia) via [#44](https://github.com/10up/debug-bar-elasticpress/pull/44)
* Warning when ElasticPress is indexing. Props [@nathanielks](https://github.com/nathanielks) and [@felipeelia](https://github.com/felipeelia) via [#45](https://github.com/10up/debug-bar-elasticpress/pull/45)

### Changed
* Only load CSS and JS files for logged-in users. Props [@cbratschi](https://github.com/cbratschi) and [@felipeelia](https://github.com/felipeelia) via [#47](https://github.com/10up/debug-bar-elasticpress/pull/47)

## [2.0.0] - 2021-04-19

This release drops the support for older versions of WordPress Core, ElasticPress and Debug Bar.

* Code refactoring. Props [@felipeelia](https://github.com/felipeelia)
* Fixed Query Logs in EP Dashboard [@felipeelia](https://github.com/felipeelia)
* Fixed typo from "clsas" to "class" in the query output. Props [@Rahmon](https://github.com/Rahmon) 

## [1.4] - 2019-03-01
* Support ElasticPress 3.0+

## [1.3] - 2017-08-23
* Add query log

## [1.2] - 2017-03-15
* Show query errors (i.e. cURL timeout)
* Add ?explain to query if GET param is set

## [1.1.1] - 2016-12-13
* Only show query body if it exits

## [1.1] - 2016-07-25
* Improve formatting
* Show original query args (EP 2.1+)

## [1.0] - 2016-01-20
* Initial release

[Unreleased]: https://github.com/10up/debug-bar-elasticpress/compare/trunk...develop
[3.0.0]: https://github.com/10up/debug-bar-elasticpress/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/10up/debug-bar-elasticpress/compare/2.1.1...3.0.0
[2.1.1]: https://github.com/10up/debug-bar-elasticpress/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/10up/debug-bar-elasticpress/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/10up/debug-bar-elasticpress/compare/1.4...2.0.0
[1.4]: https://github.com/10up/debug-bar-elasticpress/compare/1.3...1.4
[1.3]: https://github.com/10up/debug-bar-elasticpress/compare/1.2...1.3
[1.2]: https://github.com/10up/debug-bar-elasticpress/compare/1.1.1...1.2
[1.1.1]: https://github.com/10up/debug-bar-elasticpress/compare/1.1...1.1.1
[1.1]: https://github.com/10up/debug-bar-elasticpress/compare/55102f1...1.1
[1.0]: https://github.com/10up/debug-bar-elasticpress/tree/55102f1b
