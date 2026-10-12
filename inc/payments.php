<?php

/**
 * Payment details for the storefront that WooGraphQL doesn't expose.
 *
 * GraphQL field `bankTransfer { instructions accounts { … } }`: the "Direct
 * bank transfer" settings (WooCommerce → Settings → Payments → Direct bank
 * transfer). Null while that method is disabled. The account details are
 * what WooCommerce itself shows every customer after ordering, so the field
 * is public.
 *
 * @package WordPress
 * @subpackage Lekhoni
 * @since Lekhoni 1.0
 */

/**
 * Returns the bank transfer settings, or null when the method is disabled.
 *
 * @return array|null
 */
function lekhoni_bank_transfer()
{
    $settings = get_option('woocommerce_bacs_settings', array());

    if (! is_array($settings) || 'yes' !== ($settings['enabled'] ?? 'no')) {
        return null;
    }

    $accounts = array();

    foreach ((array) get_option('woocommerce_bacs_accounts', array()) as $account) {
        $account = array_map('trim', array_map('strval', (array) $account));
        $row     = array(
            'accountName'   => $account['account_name'] ?? '',
            'bankName'      => $account['bank_name'] ?? '',
            'accountNumber' => $account['account_number'] ?? '',
            'sortCode'      => $account['sort_code'] ?? '',
            'iban'          => $account['iban'] ?? '',
            'bic'           => $account['bic'] ?? '',
        );

        // Skip the empty rows the settings table can leave behind
        if (array_filter($row)) {
            $accounts[] = array_map(
                static function ($value) {
                    return '' === $value ? null : $value;
                },
                $row
            );
        }
    }

    return array(
        'instructions' => trim((string) ($settings['instructions'] ?? '')) ?: null,
        'accounts'     => $accounts,
    );
}

/**
 * Registers the `bankTransfer` GraphQL field.
 *
 * @return void
 */
function lekhoni_payments_register_types()
{
    register_graphql_object_type(
        'LekhoniBankAccount',
        array(
            'description' => 'A bank account from the Direct bank transfer settings.',
            'fields'      => array(
                'accountName'   => array('type' => 'String'),
                'bankName'      => array('type' => 'String'),
                'accountNumber' => array('type' => 'String'),
                'sortCode'      => array('type' => 'String'),
                'iban'          => array('type' => 'String'),
                'bic'           => array('type' => 'String'),
            ),
        )
    );

    register_graphql_object_type(
        'LekhoniBankTransfer',
        array(
            'description' => 'Direct bank transfer settings shown to customers.',
            'fields'      => array(
                'instructions' => array(
                    'type'        => 'String',
                    'description' => 'The "Instructions" text (shown after ordering).',
                ),
                'accounts'     => array(
                    'type' => array('list_of' => 'LekhoniBankAccount'),
                ),
            ),
        )
    );

    register_graphql_field(
        'RootQuery',
        'bankTransfer',
        array(
            'type'        => 'LekhoniBankTransfer',
            'description' => 'Direct bank transfer details; null while the method is disabled.',
            'resolve'     => 'lekhoni_bank_transfer',
        )
    );
}
add_action('graphql_register_types', 'lekhoni_payments_register_types');
