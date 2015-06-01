boxalino landing page client for PHP
====================================

Overview
--------

This library allows you to integrate content returned by a boxalino landing page
in any PHP web application.

Requirements
------------

* PHP >= 5.3
* cURL library
* the site needs to be accessed over HTTP, so that cookies & HTTP headers can be
  forwarded correctly
* boxalino tracking needs to be integrated in the site for the reporting and
  real time personalization on the landing page to work

Usage
-----

To use the landing page client in your PHP project, take the following steps:

#1) Copy the contents of vendor into your PHP codebase
#2a) Use a PSR-4 compatible autoloader

https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md

#2b) or include the file where you need it:

require_once 'vendor/Boxalino/Landingpage/Proxy.php';

#3) To use the proxy:

$proxy = new \Boxalino\Landingpage\Proxy;
$content = $proxy->getContent('https://xyz.per-intelligence.com/example');
