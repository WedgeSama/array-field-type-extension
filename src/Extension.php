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

use Bolt\Application;
use Bolt\BaseExtension;
use Bolt\Content;
use Bolt\Helpers\Str;

/**
 * Extension
 *
 * @author Benjamin Georgeault <github@wedgesama.fr>
 */
class Extension extends BaseExtension
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->app['config']->getFields()->addField(new ArrayField());

        if ($this->app['config']->getWhichEnd() == 'backend') {
            $this->app['htmlsnippets'] = true;
            $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");
        }

        $this->addTwigFunction('array_prototype_context', 'getPrototypeContext');
        $this->addTwigFunction('array_children_contexts', 'getChildrenContexts');
        $this->addTwigFunction('array_content_parser', 'contentParser');

        $this->app['dispatcher']->addSubscriber(new StorageListener());
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->addCss('assets/array.min.css');
    }

    /**
     * Convert array fields values from json string to PHP array.
     *
     * @param Content $content
     * @return Content
     */
    public function contentParser(Content $content)
    {
        foreach ($content->contenttype['fields'] as $key => $options) {
            if ($options['type'] == 'array') {
                $values = json_decode($content->values[$key], true);
                $content->values[$key] = array_values($values);
            }
        }

        return $content;
    }

    /**
     * Get context for all children.
     *
     * @param array $context
     * @param string $key
     * @return array
     */
    public function getChildrenContexts(array $context, $key)
    {
        $contexts = array();
        $prototypeContext = $this->getPrototypeContext($context, $key);

        $parentValues = $context['content']->values;
        $values = array();
        if (array_key_exists($key, $parentValues)) {
            $values = json_decode($parentValues[$key], true);
            $values = array_values($values);
        }

        foreach ($values as $num => $vFields) {
            if (array_key_exists('children', $context['contenttype']['fields'][$key])) {
                $childContext = $prototypeContext;
                $childContext['content'] = clone $childContext['content'];

                foreach ($context['contenttype']['fields'][$key]['children'] as $field => $options) {
                    // Parse field values.
                    if (array_key_exists($field, $vFields)) {
                        $childContext['content']->values[$field] = $vFields[$field];
                    }


                    // Parse field name and id.
                    list ($realName, $realId) = $this->getRealNameAndId($key, $field, $num);
                    $childContext['contenttype']['fields'][$field]['real_name'] = $realName;
                    $childContext['contenttype']['fields'][$field]['real_id'] = $realId;
                }

                // Remove btn.
                list (, $realBtnId) = $this->getRealNameAndId($key, 'array_item_remove_btn', $num);
                $childContext['array_options']['btn_remove_id'] = $realBtnId;

                $contexts[] = $childContext;
            }
        }

        return $contexts;
    }

    /**
     * Create the prototype context based on the parent.
     *
     * @param array $context
     * @param string $key
     *
     * @return array
     */
    public function getPrototypeContext(array $context, $key)
    {
        list($fields, $groups) = $this->wrapParseFieldsAndGroups($context['contenttype']['fields'][$key]['children']);

        foreach ($fields as $childKey => &$value) {
            list ($realName, $realId) = $this->getRealNameAndId($key, $childKey);
            $value['real_name'] = $realName;
            $value['real_id'] = $realId;
        }

        // Do content.
        $content = new Content($this->app, '', array());

        foreach ($fields as $key => $options) {
            $content->setValue($key, $options['default']);
        }

        // Remove btn.
        list (, $realBtnId) = $this->getRealNameAndId($key, 'array_item_remove_btn');

        $childrenContext = array(
            'contenttype' => array(
                'fields' => $fields,
            ),
            'content' => $content,
            'allowed_status' => $context['allowed_status'],
            'contentowner' => $context['contentowner'],
            'fields' => $context['fields'],
            'fieldtemplates' => $context['fieldtemplates'],
            'can_upload' => $context['can_upload'],
            'groups' => $groups?:array('ungrouped' => array_keys($fields)),
            'has'            => array(
                'incoming_relations' => false,
                'relations'          => false,
                'tabs'               => false,
                'taxonomy'           => false,
                'templatefields'     => false,
            ),
            'array_options' => array(
                'btn_remove_id' => $realBtnId,
            ),
        );

        return $childrenContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "WSArrayFieldExtension";
    }

    /**
     * Get real name and id for form input.
     *
     * @param string $parentKey
     * @param string $childKey
     * @param string $name
     * @return array
     */
    protected function getRealNameAndId($parentKey, $childKey, $name = '__name__')
    {
        $stack = explode('[', str_replace(']', '', $childKey));
        $realName = $parentKey.'['.$name.']['.implode('][', $stack).']';
        $realId = str_replace(array('[', ']'), array('_', ''), $realName);

        return array($realName, $realId);
    }

    /**
     * Wrap parseFieldsAndGroups method.
     *
     * @param array $fields
     * @return array
     */
    protected function wrapParseFieldsAndGroups(array $fields)
    {
        return $this->parseFieldsAndGroups($fields, $this->app['config']->get('general'));
    }

    /**
     * Copy of the Bolt\Config::parseFieldsAndGroups method cause it is not a public one.
     * We need to use this method to parse child fields like a real one.
     *
     * @see \Bolt\Config::parseFieldsAndGroups
     */
    private function parseFieldsAndGroups(array $fields, array $generalConfig)
    {
        $acceptableFileTypes = $generalConfig['accept_file_types'];

        $currentGroup = 'ungrouped';
        $groups = array();
        $hasGroups = false;

        foreach ($fields as $key => $field) {
            unset($fields[$key]);
            $key = str_replace('-', '_', strtolower(Str::makeSafe($key, true)));

            // If field is a "file" type, make sure the 'extensions' are set, and it's an array.
            if ($field['type'] == 'file' || $field['type'] == 'filelist') {
                if (empty($field['extensions'])) {
                    $field['extensions'] = $acceptableFileTypes;
                }

                if (!is_array($field['extensions'])) {
                    $field['extensions'] = array($field['extensions']);
                }
            }

            // If field is an "image" type, make sure the 'extensions' are set, and it's an array.
            if ($field['type'] == 'image' || $field['type'] == 'imagelist') {
                if (empty($field['extensions'])) {
                    $field['extensions'] = array_intersect(
                        array('gif', 'jpg', 'jpeg', 'png'),
                        $acceptableFileTypes
                    );
                }

                if (!is_array($field['extensions'])) {
                    $field['extensions'] = array($field['extensions']);
                }
            }

            // If field is a "Select" type, make sure the array is a "hash" (as opposed to a "map")
            // For example: [ 'yes', 'no' ] => { 'yes': 'yes', 'no': 'no' }
            // The reason that we do this, is because if you set values to ['blue', 'green'], that is
            // what you'd expect to see in the database. Not '0' and '1', which is what would happen,
            // if we didn't "correct" it here.
            // @see used hack: http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
            if ($field['type'] == 'select' && isset($field['values']) && is_array($field['values']) &&
                array_values($field['values']) === $field['values']) {
                $field['values'] = array_combine($field['values'], $field['values']);
            }

            if (!empty($field['group'])) {
                $hasGroups = true;
            }

            // Make sure we have these keys and every field has a group set
            $field = array_replace(
                array(
                    'label'   => '',
                    'variant' => '',
                    'default' => '',
                    'pattern' => '',
                    'group'   => $currentGroup,
                ),
                $field
            );

            // Collect group data for rendering.
            // Make sure that once you started with group all following have that group, too.
            $currentGroup = $field['group'];
            $groups[$currentGroup] = 1;

            // Prefix class with "form-control"
            $field['class'] = 'form-control' . (isset($field['class']) ? ' ' . $field['class'] : '');

            $fields[$key] = $field;
        }

        // Make sure the 'uses' of the slug is an array.
        if (isset($fields['slug']) && isset($fields['slug']['uses']) &&
            !is_array($fields['slug']['uses'])
        ) {
            $fields['slug']['uses'] = array($fields['slug']['uses']);
        }

        return array($fields, $hasGroups ? array_keys($groups) : false);
    }
}
