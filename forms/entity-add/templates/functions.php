<?php

function fct_print_video() {
    $url = fct_print_meta( 'entity-video', true );

    if ( !$url ) { return; }

    // direct video
    $video_formats = ['mp4', 'webm', 'wmv', 'mov', 'avi', 'ogg'];
    $format = strtolower( substr( $url, strrpos( $url, '.' ) + 1 ) );
    if ( in_array( $format , $video_formats ) ) {

        ?>
        <div class="fct-video">
            <video width="600" controls>
                <source src="<?php echo $url ?>" type="video/<?php echo $format ?>">
                Your browser does not support HTML video.
            </video>
        </div>
        <?php

        return;
    }
    
    // youtube
	if ( preg_match(
        '/^(?:https?\:\/\/(?:www\.)?youtu(?:.?)+[=\/]{1}([\w-]{11})(?:.?))$/i', $url, $match
    ) ) {
        ?>
        <div class="fct-video">
            <iframe src="https://www.youtube.com/embed/<?php echo $match[1] ?>?feature=oembed&autoplay=0" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" width="600" height="312" class="youtube"></iframe>
        </div>
        <?php
    }

	return $filtered_data;

}

function fct_print_gmap() {
    
    $lat = fct_print_meta( 'entity-geo-lat', true );
    $long = fct_print_meta( 'entity-geo-long', true );
    $addr = fct_print_meta( 'entity-address', true );
    $zoom = fct_print_meta( 'entity-zoom', true );

    ?>
    <div class="fct-gmap-view"
        <?php echo $lat ? 'data-lat="'.$lat.'"' : '' ?>
        <?php echo $long ? 'data-long="'.$long.'"' : '' ?>
        <?php echo $zoom ? 'data-zoom="'.$zoom.'"' : '' ?>
        <?php echo $addr ? 'data-addr="'.$addr.'"' : '' ?>
        <?php echo 'data-title="'.get_the_title().'"' ?>
    ></div>
    <?php
}

function fct_print_contact_buttons($meta, $name) {
    $button = fct_print_meta( $meta, true );
    if ( !$button ) { return; }
    
    $commercial = !fct_free_account( fct_print_meta( 'entity-tariff', true ) );

    if ( strpos( $meta, 'phone' ) !== false ) { $prefix = 'tel:'; }
    if ( strpos( $meta, 'mail' ) !== false ) { $prefix = 'mailto:'; }

    ?>
        <div class="wp-block-button is-style-outline">
            <a class="wp-block-button__link has-text-color" href="<?php echo $prefix ?><?php echo $button ?>" style="color:var(--h-color)" rel="noopener<?php echo $commercial ? '' : ' nofollow noreferrer' ?>">
                <strong><?php echo $name ?></strong>
            </a>
        </div>
    <?php
}

function fct_entity_print_schedule($toggle_in = false) {

    $fields = [
        'entity-mo' => __( 'Monday' ), // -open, -close
        'entity-tu' => __( 'Tuesday' ),
        'entity-we' => __( 'Wednesday' ),
        'entity-th' => __( 'Thursday' ),
        'entity-fr' => __( 'Friday' ),
        'entity-sa' => __( 'Saturday' ),
        'entity-su' => __( 'Sunday' )
    ];

    $values = [];
    foreach ( $fields as $k => $v ) {
        $open = fct_print_meta( $k . '-open', true );
        $close = fct_print_meta( $k . '-close', true );

        if ( !empty( $open ) ) {
            foreach ( $open as $l => $w ) {
                if ( !$close[ $l ] ) {
                    continue;
                }
                $values[ $k ][] = $open[ $l ] . ' - ' . $close[ $l ]; // format
            }
            if ( !empty( $values[ $k ] ) ) { continue; }
        }
        
        $values[ $k ][] = '<small>' . __( 'Closed' ) . '</small>';

    }
    
    if ( empty( $values ) ) { return; }
    
    ?>
    <div class="wp-block-button is-style-outline fct-button-select fct-open-next<?php echo $toggle_in ? ' fct-active' : '' ?>">
        <a class="wp-block-button__link has-text-color" href="#" style="color:var(--h-color)">
            <strong>Öffnungszeiten</strong>
        </a>
    </div>
    <dl class="fct-schedule-list">
    <?php
    
    foreach ( $values as $k => $v ) {
        ?>
        <dt>
            <?php echo $fields[ $k ] ?>
        </dt>
        <dd>
            <?php echo implode( '<br/>', $v ) ?>
        </dd>
        <?php
    }
    
    ?>
    </dl>
    <?php
}

function fct_entity_print_gallery() {

    $gallery = fct_print_meta( 'gallery-images', true );
    if ( empty( $gallery ) ) { return; }

?>
    <h2 class="with-line">Gallerie</h2>

    <div id="entity-gallery">
        <?php foreach ( $gallery as $v ) { ?>
            <figure class="wp-block-image">
                <a href="<?php echo fct1_image_src( 'entity/' . get_the_ID() . '/gallery/' . $v )[0] ?>">
                    <?php fct1_image_print( 'entity/' . get_the_ID() . '/gallery/' . $v, [554,554] ) ?>
                </a>
            </figure>
        <?php } ?>
    </div>
<?
}


function fct_print_meta($name, $return = false, $before = '', $after = '') { // ++ add reset for lists ++ move to common ++ maybe wrap in class
    static $a = null;
    if ( !$name ) { return ''; }
    if ( $a === null ) {
        $a = get_post_meta( get_the_ID(), '' );
    }

    if ( is_serialized( $a[ $name ][0] ) ) {
        $result = unserialize( $a[ $name ][0] );
    } else {
        $result = trim( $a[ $name ][0] ) ? $before . $a[ $name ][0] . $after : '';
    }

    if ( $return ) {
        return $result;
    }
    echo $result;
}


function fct_entity_content_filter($content, $tariff = '') {

    if ( !$content ) { return; }

    $filter = function ($text, $commercial = false) {

        $result = preg_replace_callback(
            '/<a\s+[^>]+>/i',
            function( $matches ) use ( $commercial ) {

                $matches[0] = preg_replace_callback(
                    '/\s*(\w+)="([^"]+)"/i',
                    function( $matches ) use ( $commercial ) {
                        if ( $matches[1] != 'href' ) { return; }
                        
                        return  ' ' . $matches[1] . '="' . $matches[2] . '"' .
                                ( $commercial ? ' rel="noopener"' : ' rel="nofollow noopener noreferrer"' ) .
                                ' target="_blank"';
                    },
                    $matches[0]
                );

                return $matches[0];

            },
            $text
        );
        
        return $result;
    };

    return $filter( $content, !fct_free_account( $tariff ) );
}

function fct_free_account($tariff) {
    if ( !$tariff || $tariff == 'kostenloser_eintrag' ) {
        return true;
    }
    return false;
}
