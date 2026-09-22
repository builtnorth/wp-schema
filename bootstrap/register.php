<?php
/**
 * Registers this copy of the package with the shared version coordinator.
 *
 * Runs eagerly via composer's `files` autoload, so every plugin that vendors
 * this package registers its own copy with no action on the plugin author's
 * part. The coordinator then resolves classes from the newest registered copy
 * rather than whichever plugin happened to load first.
 *
 * @package BuiltNorth\WPSchema
 */

declare(strict_types=1);

// A consumer could vendor this package without the coordinator (a stale
// composer.lock, a hand-assembled vendor tree). Falling back to plain Composer
// resolution is correct there — worse version selection, never a fatal.
if (class_exists('Novalis\\PackageLoader\\Registry', false)) {
	\Novalis\PackageLoader\Registry::instance()->register([
		'package' => 'builtnorth/wp-schema',
		'version' => (string) require dirname(__DIR__) . '/version.php',
		'root'    => dirname(__DIR__),
		'psr4'    => ['BuiltNorth\\WPSchema\\' => 'inc/'],
		// Pure library — instantiated by its consumer, nothing to start.
		'boot'    => null,
	]);
}
