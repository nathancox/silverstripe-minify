SilverStripe Minify CSS Module
=============

This module replaces part of the Requirements system to compress CSS files using http://code.google.com/p/minify/.
The idea and implementation are basically stolen from Tonyair (http://www.silverstripe.org/general-questions/show/14206)


Maintainer
-------------

Nathan Cox (<nathan@flyingmonkey.co.nz>)

Requirements
---------------

SilverStripe 3.1+
Check out the SS-3.0 branch for a version compatible with SS 3.0

Installation Instructions
-------------------------

1. Place the files in a directory called "minify" in the root of your SilverStripe installation
2. Visit yoursite.com/dev/build


Usage
-----

The module will automatically replace the Requirements backend with a custom subclass, so you don't need to do anything differently.

Just use combine_files to include your CSS as normal:

```php
<?php

$themeFolder = $this->ThemeDir();
     
$files = array(
	$themeFolder . '/css/layout.css',
	$themeFolder . '/css/typography.css',
	$themeFolder . '/css/form.css'
);

Requirements::combine_files("common.min.css", $files);

```

Your CSS files will automatically be minified when combining (like JavaScript is).

By default relative urls in the CSS files (eg background-image:url('../images/background.png');) will be rewritten so the combined CSS file can be kept in assets (eg to background-image:url('/themes/mytheme/images/background.png');) but you can turn this behaviour off by putting the following line in your _config.php:

```php
<?php

Minify_Requirements_Backend::$rewrite_uris = false;

```

Known Issues
------------

[Issue Tracker](https://github.com/nathancox/silverstripe-minify/issues)
