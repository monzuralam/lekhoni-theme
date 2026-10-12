<?php

/**
 * ACF fields for author profiles (Users > Profile / Edit User).
 *
 * Shown on the storefront's author page (/author/<slug>) and the author box
 * under each post. Exposed to GraphQL as `user { authorProfile { … } }`.
 * Name, bio ("Biographical Info"), website and avatar (Gravatar) are the
 * normal WordPress profile fields.
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
                'key'        => 'group_lk_author_profile',
                'title'      => 'Author Profile',
                'fields'     => array(
                    lekhoni_acf_field('field_lk_author_job_title', 'job_title', 'Job Title', 'text', array('instructions' => 'Shown under the name, e.g. "ফ্রন্টএন্ড ডেভেলপার ও লেখক".', 'wrapper' => array('width' => '60'))),
                    lekhoni_acf_field('field_lk_author_experience', 'experience', 'Years of Experience', 'text', array('instructions' => 'e.g. "১০". Leave empty to hide this stat.', 'wrapper' => array('width' => '40'))),
                    lekhoni_acf_field('field_lk_author_facebook', 'facebook', 'Facebook URL', 'url', array('wrapper' => array('width' => '33'))),
                    lekhoni_acf_field('field_lk_author_x', 'x', 'X (Twitter) URL', 'url', array('graphql_field_name' => 'xUrl', 'wrapper' => array('width' => '33'))),
                    lekhoni_acf_field('field_lk_author_linkedin', 'linkedin', 'LinkedIn URL', 'url', array('wrapper' => array('width' => '34'))),
                ),
                'location'   => array(
                    array(
                        array(
                            'param'    => 'user_form',
                            'operator' => '==',
                            'value'    => 'all',
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
                'graphql_field_name'    => 'authorProfile',
                'map_graphql_types_from_location_rules' => 0,
                'graphql_types'         => array('User'),
            )
        );
    }
);
