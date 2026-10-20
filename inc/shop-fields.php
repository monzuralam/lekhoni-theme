<?php

/**
 * Extra fields on shop terms, used by the e-commerce and cosmetics homes:
 *
 * - Icon on product categories (Products → Categories): the department menu
 *   and grid. GraphQL: `productCategory { icon }`.
 * - Swatch Colour on attribute terms (Products → Attributes → Configure
 *   terms), e.g. lipstick shades. GraphQL: `swatchColor` on every term
 *   (null unless set).
 *
 * The ACF groups are admin-only; the GraphQL fields read the term meta.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Registers the "Category Icon" and "Swatch" field groups.
 *
 * @return void
 */
function lekhoni_register_shop_term_fields()
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'             => 'group_lk_category_icon',
            'title'           => 'Category Icon',
            'fields'          => array(
                array(
                    'key'           => 'field_lk_category_icon',
                    'label'         => 'Icon',
                    'name'          => 'lekhoni_icon',
                    'type'          => 'select',
                    'instructions'  => 'Shown in the "Home – E-commerce" department menu and grid.',
                    'choices'       => lekhoni_shop_icon_choices(),
                    'allow_null'    => 1,
                    'return_format' => 'value',
                ),
            ),
            'location'        => array(
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => 'product_cat',
                    ),
                ),
            ),
            'show_in_graphql' => 0,
        )
    );

    // One location rule per product attribute (pa_color, pa_shade, …)
    $attributes = function_exists('wc_get_attribute_taxonomy_names') ? wc_get_attribute_taxonomy_names() : array();
    if (! $attributes) {
        return;
    }
    acf_add_local_field_group(
        array(
            'key'             => 'group_lk_swatch',
            'title'           => 'Swatch',
            'fields'          => array(
                array(
                    'key'            => 'field_lk_swatch_color',
                    'label'          => 'Swatch Colour',
                    'name'           => 'lekhoni_swatch',
                    'type'           => 'color_picker',
                    'instructions'   => 'Optional. Products show a colour dot for this value (e.g. a lipstick shade) instead of the name.',
                    'enable_opacity' => 0,
                    'return_format'  => 'string',
                ),
            ),
            'location'        => array_map(
                function ($taxonomy) {
                    return array(
                        array(
                            'param'    => 'taxonomy',
                            'operator' => '==',
                            'value'    => $taxonomy,
                        ),
                    );
                },
                $attributes
            ),
            'show_in_graphql' => 0,
        )
    );
}
add_action('acf/include_fields', 'lekhoni_register_shop_term_fields');

/**
 * GraphQL `productCategory { icon }` and `swatchColor` on terms.
 *
 * @return void
 */
function lekhoni_register_shop_term_graphql()
{
    register_graphql_field(
        'ProductCategory',
        'icon',
        array(
            'type'        => 'String',
            'description' => 'Icon name from the storefront sprite (Products → Categories → Icon).',
            'resolve'     => function ($term) {
                $icon = get_term_meta($term->databaseId, 'lekhoni_icon', true);
                return is_string($icon) && '' !== $icon ? $icon : null;
            },
        )
    );

    register_graphql_field(
        'TermNode',
        'swatchColor',
        array(
            'type'        => 'String',
            'description' => 'Swatch colour (hex) of a product attribute term, if set.',
            'resolve'     => function ($term) {
                $color = get_term_meta($term->databaseId, 'lekhoni_swatch', true);
                return is_string($color) && preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : null;
            },
        )
    );
}
add_action('graphql_register_types', 'lekhoni_register_shop_term_graphql');
