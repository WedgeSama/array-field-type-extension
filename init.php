<?php
/*
 * This file is part of the ws/array-field-type-extension package.
 *
 * (c) Benjamin Georgeault <github@wedgesama.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Bolt\Extension\WS\ArrayFieldExtension\Extension;

$app['extensions']->register(new Extension($app));
