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

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * StorageListener
 *
 * @author Benjamin Georgeault <github@wedgesama.fr>
 */
class StorageListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            StorageEvents::PRE_SAVE => 'arrayToJson',
        );
    }

    /**
     * Encode array fields as json string just before save it in the storage.
     *
     * @param StorageEvent $event
     */
    public function arrayToJson(StorageEvent $event)
    {
        $content = $event->getContent();

        foreach ($content->contenttype['fields'] as $key => $options) {
            if ($options['type'] == 'array' && is_array($content->values[$key]) === true) {
                $content->values[$key] = json_encode(array_values($content->values[$key]));
            }
        }
    }
}
