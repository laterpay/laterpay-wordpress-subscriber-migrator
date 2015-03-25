<?php

class LaterPay_Migrator_Form_Activation extends LaterPay_Form_Abstract
{
    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init() {
        $this->set_field(
            'action',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_migrator_activate',
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'timepasses',
            array(
                'validators' => array(
                    'is_array',
                    'array_check' => array(
                        'cmp' => array(
                            array(
                                'gt' => 0,
                            ),
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'assign_roles',
            array(
                'validators' => array(
                    'is_array',
                ),
            )
        );

        $this->set_field(
            'remove_roles',
            array(
                'validators' => array(
                    'is_array',
                ),
            )
        );

        $this->set_field(
            'sitenotice_message',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'sitenotice_button_text',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'sitenotice_bg_color',
            array(
                'validators' => array(
                    'is_string',
                    'match' => '/^$|#?([A-Fa-f0-9]){3}(([A-Fa-f0-9]){3})?/',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'sitenotice_text_color',
            array(
                'validators' => array(
                    'is_string',
                    'match' => '/^$|#?([A-Fa-f0-9]){3}(([A-Fa-f0-9]){3})?/',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'mailchimp_api_key',
            array(
                'validators' => array(
                    'is_string',
                    'match' => '/[a-z0-9-]+/'
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                )
            )
        );

        $this->set_field(
            'mailchimp_ssl_connection',
            array(
                'validators' => array(
                    'in_array' => array( 0, 1 ),
                ),
                'filters' => array(
                    'to_int',
                )
            )
        );

        $this->set_field(
            'mailchimp_campaign_before_expired',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                )
            )
        );

        $this->set_field(
            'mailchimp_campaign_after_expired',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                )
            )
        );
    }
}
