<?php

/**
 * Shared helpers for the ACF field groups defined in PHP.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Icon choices matching the storefront's SVG sprite (app/components/Icons.tsx).
 *
 * @return array<string, string>
 */
function lekhoni_icon_choices()
{
    return array(
        'book'         => 'Book',
        'zap'          => 'Zap',
        'heart'        => 'Heart',
        'star'         => 'Star',
        'check'        => 'Check',
        'shield-check' => 'Shield Check',
        'truck'        => 'Truck',
        'gift'         => 'Gift',
        'tag'          => 'Tag',
        'clock'        => 'Clock',
        'headset'      => 'Headset',
        'chat'         => 'Chat',
        'mail'         => 'Mail',
        'phone'        => 'Phone',
        'map-pin'      => 'Map Pin',
        'user'         => 'User',
        'cart'         => 'Cart',
        'eye'          => 'Eye',
        'lock'         => 'Lock',
        'quote'        => 'Quote',
        'calendar'     => 'Calendar',
        'grid'         => 'Grid',
        'rotate-ccw'   => 'Rotate CCW',
    );
}

/**
 * Shorthand for an ACF sub field shown in GraphQL.
 *
 * @param string $key        Unique field key.
 * @param string $name       Field name (also used for the GraphQL name, camelCased).
 * @param string $label      Admin label.
 * @param string $type       ACF field type.
 * @param array  $extra      Extra settings.
 * @return array
 */
function lekhoni_acf_field($key, $name, $label, $type, $extra = array())
{
    return array_merge(
        array(
            'key'                => $key,
            'label'              => $label,
            'name'               => $name,
            'type'               => $type,
            'show_in_graphql'    => 1,
            'graphql_field_name' => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name)))),
        ),
        $extra
    );
}
