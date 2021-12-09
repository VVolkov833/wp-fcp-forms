<?php
/*
Modify the values before printing to inputs
*/

FCP_Forms::tz_set(); // set utc timezone for all the time operations, in case the server has a different settings

/*
FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
    'type' => 'notice',
    'meta_box' => true,
    'before' => '<pre>',
    'after' => '</pre>',
    'text' => "\n".
        print_r( $outdated, true )
    ."\n",
], 'before' );
//*/

$time = time(); // not used ha
$time_local = $time + ( $values['entity-timezone-bias'] ? $values['entity-timezone-bias'] : 0 ); // the saved one
$day = 60*60*24;
$prolong_gap = $day*30;

$tariffs = (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'options' );
$tariff_default = FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'value' );

$values['entity-tariff'] = $values['entity-tariff'] && $tariffs[ $values['entity-tariff'] ]
                         ? $values['entity-tariff']
                         : $tariff_default;

$tariff_paid = $values['entity-tariff'] !== $tariff_default;

$admin_am = current_user_can( 'administrator' );

$values['entity-tariff-till'] = $values['entity-tariff-till'] ? $values['entity-tariff-till'] : 0;
$till_limit = $values['entity-tariff-till'] - $time_local;
$tariff_till_view = date( get_option( 'date_format' ), $values['entity-tariff-till'] );

if ( $till_limit < 0 ) { // outdated
    $time_label = sprintf( __( 'Ended on %s', 'fcpfo' ), $tariff_till_view );
} elseif ( $till_limit < $day ) { // today
    $time_label = __( 'Ends today', 'fcpfo' );
} elseif ( $till_limit < $day*2 ) { // tomorrow
    $time_label = __( 'Tomorrow is the last day', 'fcpfo' );
} else {
    $time_label = $tariff_till_view;
}

if ( $values['entity-tariff-till'] === 0 ) {
    $time_label = __( 'Not set', 'fcpfo' );
}


// prolong variables

// the prolong is available to users 2 weeks before the current paid tariff ends
$prolong_available = $tariff_paid && $till_limit > 0 && ( $till_limit < $prolong_gap || $admin_am );


if ( $prolong_available ) {

    $time_label = $till_limit < $prolong_gap ? '<font color="#b32d2e">' . $time_label . '</font>' : '';
    
    $values['entity-tariff-next'] = $values['entity-tariff-next'] && $tariffs[ $values['entity-tariff-next'] ]
                                ? $values['entity-tariff-next']
                                : $tariff_default;

    $tariff_paid_next = $values['entity-tariff-next'] !== $tariff_default;
    $tariff_next_start_label = date( get_option( 'date_format' ), $values['entity-tariff-till'] + $day );
}


$billing_details_id = get_post_meta( $_GET['post'], 'entity-billing', true );
$billing_email = get_post_meta( $billing_details_id, 'billing-email', true );


// print field-by-field conditionally

// block the tariff if no billing method picked
if ( !$billing_details_id && !$admin_am ) {
    $this->s->fields = [];
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>To apply a different tariff, please select a billing details in the field above. Or fill in a new billing information <a href="/wp-admin/post-new.php?post_type=billing" target="_blank">here</a> first.</p>',
        'meta_box' => true,
    ]);
    return;
}


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
}
if ( $admin_am && !$tariff_paid ) { // just a notice
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
        'type' => 'notice',
        'text' => '<strong>The following fields will not be effecting a free tariff.</strong>',
        'meta_box' => true,
    ], 'after' );
}


// tariff requested date - used to change unpayed paid tariffs back to free, like in 2 weeks
if ( $values['entity-tariff-requested'] ) {
    $values['entity-tariff-requested'] = date( get_option( 'date_format' ), $values['entity-tariff-requested'] );
}
if ( !$values['entity-tariff-requested'] ) { // ++add reset conditions
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [], 'unset' );
}


// tariff due date
if ( $admin_am ) { // ++add reset conditions

    $values['entity-tariff-till'] = $values['entity-tariff-till'] && $values['entity-tariff-till'] > $time_local
        ? date( 'd.m.Y', $values['entity-tariff-till'] )
        : '';

    // date picker helping functions
    $one_year_from_now_plus_one_day = date( 'd.m.Y', strtotime( '+1 year', $time_local + $day ) );
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-till', [
        'type' => 'notice',
        'text' => '<a href="#" id="one-year-ahead" style="margin-top:-12px">Set 1 year from now</a><script>
            jQuery( \'#one-year-ahead\' ).click( function( e ) {
                e.preventDefault();
                jQuery( \'#entity-tariff-till_entity-tariff\' ).val( \'' . $one_year_from_now_plus_one_day . '\' );
            });
        </script>',
        'meta_box' => true,
    ], 'after' );

}
if ( !$admin_am && $tariff_paid && $values['entity-payment-status'] === 'payed' ) { // just styling
    $values['entity-tariff-till'] = $time_label;
}
if ( !$admin_am && $values['entity-payment-status'] !== 'payed' ) { // hide payed till date
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-till', [], 'unset' );
}


// timezones
if ( $admin_am ) {
    $tzs = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
    $tzs = array_combine( $tzs, $tzs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone', 'options', (object) $tzs );
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-timezone-bias', [], 'unset' ); //++invent invisible fields
}


// the payment status
if ( !$admin_am && $tariff_paid ) { // ++add reset conditions

    if ( $values['entity-payment-status'] === 'pending' ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields,
            'entity-payment-status',
            [
                'type' => 'notice',
                'text' => '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email ' . $billing_email . '. Contact our accountant, if you have problem with receiving the bill <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                'meta_box' => true,
            ],
            'override'
        );

    } elseif ( $values['entity-payment-status'] === 'billed' ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields,
            'entity-payment-status',
            [
                'type' => 'notice',
                'text' => '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The tariff will be activated when the payment is received. If not payed in 2 weeks, the initial free tariff will be restored.',
                'meta_box' => true,
            ],
            'override'
        );

    }

}


// prolong

if ( $prolong_available ) {

    // prolong tariff picker
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' ); // ++add reset conditions
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', $tariffs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'value', $tariff_default );

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    $pay_statuses = (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options', $pay_statuses );


    if ( $admin_am ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [
            'type' => 'notice',
            'text' => '<strong>The following fields are available to users '.( $prolong_gap / ($day) ).' days before a paid tariff ends.</strong>',
            'meta_box' => true,
        ], 'before' );
    }

    if ( !$admin_am && $tariff_paid_next ) {

        // allow changing tariff only from free to a paid one
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'roles_view', ['entity_delegate'] );

        if ( $values['entity-payment-status-next'] === 'pending' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email ' . $billing_email . '. Contact our accountant, if you have problem with receiving the bill <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                    'meta_box' => true,
                ],
                'override'
            );

        } elseif ( $values['entity-payment-status-next'] === 'billed' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                    'meta_box' => true,
                ],
                'override'
            );

        } elseif ( $values['entity-payment-status-next'] === 'payed' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em>Payment status - Payed</em>',
                    'meta_box' => true,
                ],
                'override'
            );

        }

    }

}



// helping labels
if ( $tariff_next_start_label ) {
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>The next tariff period will be activated <font color="#b32d2e" style="white-space:nowrap">on '.$tariff_next_start_label.'</font></p>',
        'meta_box' => true,
    ]);
}

array_push( $this->s->fields, (object) [
    'type' => 'notice',
    'text' => '<p>For more information check out our tariff prices and conditions <a href=\"/\" target=\"_blank\">here</a></p>',
    'meta_box' => true,
    'roles_view' => ['entity_delegate'],
]);


FCP_Forms::tz_reset();