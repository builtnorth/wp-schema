<?php
/**
 * Registers this copy of the package with the shared version coordinator.
 *
 * Runs eagerly via composer's `files` autoload, so every plugin that vendors
 * this package registers its own copy with no action on the plugin author's
 * part. The coordinator then resolves classes from the newest registered copy
 * rather than whichever plugin happened to load first.
 *
 * Lives outside the psr-4 root on purpose: a registration file reachable
 * through its own package's namespace prefix would make the coordinator
 * resolve a class name back onto this file.
 *
 * @package BuiltNorth\WPSchema
 */

declare(strict_types=1);

use Novalis\PackageLoader\Registry;

// A consumer could vendor this package without the coordinator (a stale
// composer.lock, a hand-assembled vendor tree). Falling back to plain Composer
// resolution is correct there — worse version selection, never a fatal.
// Autoloading stays off: the coordinator declares Registry inline from its own
// files entry, so if it is not already declared no autoloader can produce it.
if (class_exists(Registry::class, false)) {
	Registry::instance()->register([
		'package' => 'builtnorth/wp-schema',
		'version' => (string) require dirname(__DIR__) . '/version.php',
		'root'    => dirname(__DIR__),
		'psr4'    => ['BuiltNorth\\WPSchema\\' => 'inc/'],
	]);
}
