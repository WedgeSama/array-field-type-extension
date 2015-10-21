<?php
/*
 * This file is part of the ws/array-field-type-extension package.
 *
 * (c) Benjamin Georgeault <github@wedgesama.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Bolt\Extension\WS\ArrayFieldExtension;

use Bolt\Field\FieldInterface;

/**
 * ArrayField
 *
 * @author Benjamin Georgeault <github@wedgesama.fr>
 */
class ArrayField implements FieldInterface
{
    public function getName()
    {
        return 'array';
    }

    public function getTemplate()
    {
        return '_array.twig';
    }

    public function getStorageType()
    {
        return 'array';
    }

    public function getStorageOptions()
    {
        return array(
            'children' => array(),
        );
    }
}
