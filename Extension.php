<?php
/*
 * This file is part of the ws/array-field-type-extension package.
 *
 * (c) Benjamin Georgeault <github@wedgesama.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * "Virtual" Extension.php file. It is use only for local extension. It add a PSR-4 autoloader for the extension.
 *
 * @see http://www.php-fig.org/psr/psr-4/examples/
 */
spl_autoload_register(function ($class) {
    $prefix = 'Bolt\\Extension\\WS\\ArrayFieldExtension\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
