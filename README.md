# Debug Bar ElasticPress

A WordPress plugin that adds a [Debug Bar](https://wordpress.org/plugins/debug-bar/) panel to examine ElasticPress queries. This plugin also includes the ability to generate a debugging file to aid our support staff in troubleshooting your issue.

## Requirements

* ElasticPress 1.8+
* Debug Bar 0.8.2+
* PHP 5.4+

## Usage

After installing and activating, click the `Debug` button in the admin toolbar. Within the Debug Bar Panel, click the ElasticPress panel.

To utilize the troubleshooting feature, navigate to `Tools->EP Troubleshooting`.
Next, click the button titled "Download Debugging Information" to download the debugging file.

## Frequently Asked Questions

**What does the troubleshooting feature of this plugin do?**

This plugin will generate a JSON file outlining details about your WordPress, ElasticPress, Elasticsearch, and (if used) WooCommerce versions and setup.

**What do I do with the JSON file?**

If you are encountering an issue with ElasticPress, you can create a [GitHub issue](https://github.com/10up/ElasticPress/issues) for the ElasticPress team to review and attach the file to the issue.

**ElasticPress cannot connect to Elasticsearch, can you help?**

This plugin will not allow us to troubleshoot connection issues between ElasticPress and Elasticsearch. However, there is some great documentation on the [Elastic website](https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html) for you to review.

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/10up/debug-bar-elasticpress/issues?state=open).

## License

Debug Bar ElasticPress is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.