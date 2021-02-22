A redirection/proxy service for hiding news links from Facebook so that they
can be posted in Australia.

## Requirements

* PHP 7.2+
* Composer
* Memcached
* The memcached PHP extension

## Installation

Get the code. I used Composer just for its autoloader, I didn't actually use
any dependencies. Install the autoloader with:

composer install --no-dev 

Copy config.sample.php to config.php.

Choose a path for the redirector and the proxy script. It's probably best to
avoid using something too obvious like "news_link_redirection", to reduce the
risk of Facebook blocking it. In the following examples I have used "r" and
"x".

HTTPS should always be used.

Set up your webserver with appropriate aliases, e.g.

```
	DocumentRoot /srv/ogproxy/public_html
	Alias /x /srv/ogproxy/public_html/proxy.php
	Alias /r /srv/ogproxy/public_html/redir.php
```

Modify `proxy-url` and `redir-url` accordingly in config.php.

## Configuration

Enter a list of allowed hosts in `allowed-hosts` in config.php. The domain
whitelist is necessary to prevent abuse which would potentially lead to the
host being blocked.

The domain names should include the main website, and also the domain used in
the og:image meta tags.

## Using

Navigate to index.php. Enter a URL. You get back a URL which can be posted to
Facebook.
