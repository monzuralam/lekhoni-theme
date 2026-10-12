<?php

/**
 * ACF fields for the About page (page template "About").
 *
 * Exposed to GraphQL as `page { aboutPage { … } }`. The mission text comes
 * from the page's normal editor content (`page { content }`).
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

add_action(
    'acf/include_fields',
    function () {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(
            array(
                'key'        => 'group_lk_about_page',
                'title'      => 'About Page',
                'fields'     => array(
                    // Hero
                    lekhoni_acf_field(
                        'field_lk_about_hero',
                        'hero',
                        'Hero',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_about_hero_eyebrow', 'eyebrow', 'Eyebrow', 'text', array('placeholder' => 'আমাদের গল্প', 'wrapper' => array('width' => '30'))),
                                lekhoni_acf_field('field_lk_about_hero_title', 'title', 'Title', 'text', array('instructions' => 'Leave empty to use the page title.', 'wrapper' => array('width' => '35'))),
                                lekhoni_acf_field('field_lk_about_hero_highlight', 'highlight', 'Highlighted Title Part', 'text', array('instructions' => 'Shown after the title in the accent colour.', 'wrapper' => array('width' => '35'))),
                                lekhoni_acf_field('field_lk_about_hero_description', 'description', 'Description', 'textarea', array('rows' => 3, 'new_lines' => '')),
                                lekhoni_acf_field('field_lk_about_hero_primary', 'primary_button', 'Primary Button', 'link', array('return_format' => 'array', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_about_hero_secondary', 'secondary_button', 'Secondary Button', 'link', array('return_format' => 'array', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_about_hero_image', 'image', 'Image', 'image', array('instructions' => 'About 16:9. Leave empty to use the featured image.', 'return_format' => 'array', 'preview_size' => 'medium')),
                            ),
                        )
                    ),
                    // Values
                    lekhoni_acf_field(
                        'field_lk_about_values',
                        'values_section',
                        'Values',
                        'group',
                        array(
                            'graphql_field_name' => 'valuesSection',
                            'layout'             => 'block',
                            'sub_fields'         => array(
                                lekhoni_acf_field('field_lk_about_values_title', 'title', 'Section Title', 'text', array('placeholder' => 'যা আমরা মেনে চলি')),
                                lekhoni_acf_field(
                                    'field_lk_about_values_items',
                                    'items',
                                    'Values',
                                    'repeater',
                                    array(
                                        'layout'       => 'block',
                                        'button_label' => 'Add Value',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_about_values_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_icon_choices(), 'default_value' => 'book', 'return_format' => 'value', 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_about_values_items')),
                                            lekhoni_acf_field('field_lk_about_values_item_title', 'title', 'Title', 'text', array('required' => 1, 'wrapper' => array('width' => '75'), 'parent_repeater' => 'field_lk_about_values_items')),
                                            lekhoni_acf_field('field_lk_about_values_item_text', 'description', 'Description', 'textarea', array('rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_about_values_items')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),
                    // Stats
                    lekhoni_acf_field(
                        'field_lk_about_stats',
                        'stats',
                        'Stats',
                        'repeater',
                        array(
                            'instructions' => 'Numbers row, e.g. "১২০+" / "প্রকাশিত লেখা".',
                            'layout'       => 'table',
                            'button_label' => 'Add Stat',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_about_stat_value', 'value', 'Value', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_about_stats')),
                                lekhoni_acf_field('field_lk_about_stat_label', 'label', 'Label', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_about_stats')),
                            ),
                        )
                    ),
                    // Team
                    lekhoni_acf_field(
                        'field_lk_about_team',
                        'team_section',
                        'Team',
                        'group',
                        array(
                            'graphql_field_name' => 'teamSection',
                            'layout'             => 'block',
                            'sub_fields'         => array(
                                lekhoni_acf_field('field_lk_about_team_title', 'title', 'Section Title', 'text', array('placeholder' => 'যাঁরা লেখেন')),
                                lekhoni_acf_field(
                                    'field_lk_about_team_members',
                                    'members',
                                    'Members',
                                    'repeater',
                                    array(
                                        'layout'       => 'block',
                                        'button_label' => 'Add Member',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_about_member_photo', 'photo', 'Photo', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_about_team_members')),
                                            lekhoni_acf_field('field_lk_about_member_name', 'name', 'Name', 'text', array('required' => 1, 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_about_team_members')),
                                            lekhoni_acf_field('field_lk_about_member_role', 'role', 'Role', 'text', array('wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_about_team_members')),
                                            lekhoni_acf_field('field_lk_about_member_link', 'link', 'Profile Link', 'url', array('wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_about_team_members')),
                                            lekhoni_acf_field('field_lk_about_member_bio', 'bio', 'Bio', 'textarea', array('rows' => 2, 'new_lines' => '', 'parent_repeater' => 'field_lk_about_team_members')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),
                    // Contact call to action
                    lekhoni_acf_field(
                        'field_lk_about_cta',
                        'cta',
                        'Contact CTA',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_about_cta_title', 'title', 'Title', 'text', array('instructions' => 'Leave empty to hide this section.')),
                                lekhoni_acf_field('field_lk_about_cta_text', 'text', 'Text', 'textarea', array('rows' => 2, 'new_lines' => '')),
                                lekhoni_acf_field('field_lk_about_cta_button', 'button', 'Button', 'link', array('return_format' => 'array')),
                            ),
                        )
                    ),
                ),
                'location'   => array(
                    array(
                        array(
                            'param'    => 'page_template',
                            'operator' => '==',
                            'value'    => 'templates/template-about.php',
                        ),
                    ),
                ),
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'active'                => true,
                'show_in_rest'          => 0,
                'show_in_graphql'       => 1,
                'graphql_field_name'    => 'aboutPage',
                // Shown on the "About" template in the admin, but exposed on every Page in GraphQL
                'map_graphql_types_from_location_rules' => 0,
                'graphql_types'         => array('Page'),
            )
        );
    }
);
