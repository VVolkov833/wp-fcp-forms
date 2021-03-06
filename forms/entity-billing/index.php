<?php

// meta select for 

if ( !class_exists( 'FCP_Add_Meta_Boxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$json = FCP_Forms::structure( $dir );
if ( $json === false ) { return; }


// get list of billing methods id-name of the entity author
$author_id = get_post_field( 'post_author', $_GET['post'] );

global $wpdb;
$options = $wpdb->get_results( '
    SELECT `ID`, `post_title`
    FROM `'.$wpdb->posts.'`
    WHERE `post_type` = "billing" AND `post_author` = "'.$author_id.'" AND `post_status` = "publish"
    ORDER BY `post_title` ASC
', ARRAY_A );

FCP_Forms::add_options( $json, 'entity-billing', $options, 'ID', 'post_title' );


new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'Rechnungsdaten',
    'post_types' => ['clinic', 'doctor'],
    'context' => 'side',
    'priority' => 'default'
] );
