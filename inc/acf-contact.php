<?php

/**
 * ACF fields for the Contact page (page template "Contact").
 *
 * Exposed to GraphQL as `page { contactPage { … } }`. The recipient email is
 * deliberately not exposed; inc/contact.php reads it on the server.
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
                'key'        => 'group_lk_contact_page',
                'title'      => 'Contact Page',
                'fields'     => array(
                    lekhoni_acf_field('field_lk_contact_subtitle', 'subtitle', 'Subtitle', 'textarea', array('instructions' => 'Shown under the page title.', 'rows' => 2, 'new_lines' => '')),
                    // Info cards
                    lekhoni_acf_field(
                        'field_lk_contact_cards',
                        'info_cards',
                        'Info Cards',
                        'repeater',
                        array(
                            'instructions' => 'Email, phone, address, hours. Link examples: mailto:hello@example.com, tel:+8801712345678, https://maps.google.com/...',
                            'layout'       => 'block',
                            'button_label' => 'Add Card',
                            'sub_fields'   => array(
                                lekhoni_acf_field('field_lk_contact_card_icon', 'icon', 'Icon', 'select', array('choices' => lekhoni_icon_choices(), 'default_value' => 'mail', 'return_format' => 'value', 'wrapper' => array('width' => '20'), 'parent_repeater' => 'field_lk_contact_cards')),
                                lekhoni_acf_field('field_lk_contact_card_label', 'label', 'Label', 'text', array('required' => 1, 'wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_contact_cards')),
                                lekhoni_acf_field('field_lk_contact_card_value', 'value', 'Value', 'text', array('required' => 1, 'wrapper' => array('width' => '30'), 'parent_repeater' => 'field_lk_contact_cards')),
                                lekhoni_acf_field('field_lk_contact_card_link', 'link', 'Link (optional)', 'text', array('wrapper' => array('width' => '25'), 'parent_repeater' => 'field_lk_contact_cards')),
                            ),
                        )
                    ),
                    // Form
                    lekhoni_acf_field(
                        'field_lk_contact_form',
                        'form',
                        'Contact Form',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_contact_form_title', 'title', 'Title', 'text', array('placeholder' => 'আমাদের লিখুন', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_contact_form_note', 'note', 'Note', 'text', array('placeholder' => 'তারকা চিহ্ন ছাড়া সব ঘর পূরণ করা আবশ্যক।', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_contact_form_subjects', 'subjects', 'Subject Options', 'textarea', array('instructions' => 'One per line. Leave empty to hide the subject field.', 'rows' => 4, 'new_lines' => '')),
                                lekhoni_acf_field('field_lk_contact_form_recipient', 'recipient', 'Send Messages To', 'email', array('instructions' => 'Leave empty to use the site admin email. Not shown on the site.', 'show_in_graphql' => 0, 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_contact_form_success', 'success_message', 'Success Message', 'text', array('placeholder' => 'বার্তা পাঠানো হয়েছে। শিগগিরই উত্তর দেব।', 'wrapper' => array('width' => '50'))),
                            ),
                        )
                    ),
                    // FAQ
                    lekhoni_acf_field(
                        'field_lk_contact_faq',
                        'faq',
                        'FAQ',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_contact_faq_title', 'title', 'Section Title', 'text', array('placeholder' => 'সাধারণ জিজ্ঞাসা')),
                                lekhoni_acf_field(
                                    'field_lk_contact_faq_items',
                                    'items',
                                    'Questions',
                                    'repeater',
                                    array(
                                        'layout'       => 'block',
                                        'button_label' => 'Add Question',
                                        'sub_fields'   => array(
                                            lekhoni_acf_field('field_lk_contact_faq_question', 'question', 'Question', 'text', array('required' => 1, 'parent_repeater' => 'field_lk_contact_faq_items')),
                                            lekhoni_acf_field('field_lk_contact_faq_answer', 'answer', 'Answer', 'textarea', array('required' => 1, 'rows' => 3, 'new_lines' => '', 'parent_repeater' => 'field_lk_contact_faq_items')),
                                        ),
                                    )
                                ),
                            ),
                        )
                    ),
                    // Map
                    lekhoni_acf_field(
                        'field_lk_contact_map',
                        'map',
                        'Map',
                        'group',
                        array(
                            'layout'     => 'block',
                            'sub_fields' => array(
                                lekhoni_acf_field('field_lk_contact_map_embed', 'embed_url', 'Google Maps Embed URL', 'url', array('instructions' => 'Google Maps > Share > Embed a map, then copy only the src URL (https://www.google.com/maps/embed?pb=...). Leave empty to show a placeholder.')),
                                lekhoni_acf_field('field_lk_contact_map_label', 'label', 'Location Label', 'text', array('placeholder' => 'ধানমন্ডি, ঢাকা', 'wrapper' => array('width' => '50'))),
                                lekhoni_acf_field('field_lk_contact_map_note', 'note', 'Placeholder Note', 'text', array('instructions' => 'Shown under the label when there is no map.', 'wrapper' => array('width' => '50'))),
                            ),
                        )
                    ),
                ),
                'location'   => array(
                    array(
                        array(
                            'param'    => 'page_template',
                            'operator' => '==',
                            'value'    => 'templates/template-contact.php',
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
                'graphql_field_name'    => 'contactPage',
                // Shown on the "Contact" template in the admin, but exposed on every Page in GraphQL
                'map_graphql_types_from_location_rules' => 0,
                'graphql_types'         => array('Page'),
            )
        );
    }
);
