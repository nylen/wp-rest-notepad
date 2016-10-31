# wp-rest-notepad

This is a simple "secret notepad" powered by the WP REST API, using
server-to-server communication and private WordPress posts to store content.
Each note has a 10-character ID, and there is no index functionality.

Inspired by the once-glorious, now-defunct notepad.cc.

This is an example of a WP REST API application that can communicate with
either a self-hosted WordPress site or a WordPress.com site.

## Installation

Install PHP on your server and clone this repo.

Have a WordPress (self-hosted or WordPress.com) site handy that's running the
latest version of the WP REST API.

For self-hosed WordPress, WP 4.7 beta 2 and higher will definitely work; others
may or may not work.  You'll need to install the
[Application Passwords plugin](https://github.com/georgestephanis/application-passwords)
and generate a
[basic authentication header](https://en.wikipedia.org/wiki/Basic_access_authentication)
for the config file.

For WordPress.com, generate an authentication token following the
[auth documentation](https://developer.wordpress.com/docs/oauth2/).

Copy `sample-config.php` to `config.php` and fill in the values there for your
site.

The provided `.htaccess` file works with Apache `mod_rewrite` - nginx
configuration is up to you for the time being.

## Enhancements

I have intentionally kept this app very simple.  There are a few enhancements
for which I would definitely welcome PRs:

- [OAuth1](https://github.com/WP-API/OAuth1) support for self-hosted sites.
- Configuration instructions/files for nginx.

## License

GPL v3.  See the `LICENSE` file.
