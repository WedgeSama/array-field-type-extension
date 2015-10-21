Array field type for Bolt
=========================

![Preview](preview.png)

This [Bolt](https://bolt.cm/) extension add an array field type for content type.

Requirements
------------

* bolt 2.*

Usage
-----

### Content type definition

```yaml
foos:
    name: Foos
    singular_name: Foo
    fields:
        ...
        bar:
            type: array
            children:
                foo:
                    label: The awesome FOO
                    type: html
                bar:
                    type: text
                foobar:
                    type: image
    ...
```

### In templates

```jinja
{# Add this line before using the record object. #}
{% set record = array_content_parser(record) %}

<h3>{{ record.bar.bar }}</h3>

<div>{{ record.bar.foo }}</div>

<a href="{{ record.bar.foobar|image }}" title="{{ record.bar.foobar.title }}">
    <img src="{{ record.bar.foobar|thumbnail(100,100) }}">
</a>
```

TODO
----
- Use cascade array (currently, only work with 1 deep).
- Make customizable template (add possibilities and improve doc).
- Find a way to parse content record without using `array_content_parser` in template (help welcome on this one).
- Translations?
- Make the list sortable.
- Multi selection for delete items.

License
-------

This library is release under [MIT license](LICENSE).
