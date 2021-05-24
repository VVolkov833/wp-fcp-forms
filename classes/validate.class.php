<?php

class FCP_Forms__Validate {

    private $s, $v, $p; // structure; $_POST + $_FILES
    public $result, $files_failed; // text warnings; [field name][] = failed file name

    public function __construct($s, $v, $f = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->v = $v + $f;
        
        // add prefix for meta boxes
        $this->p = is_admin() ? FCP_Forms::prefix( $s->options->form_name ) : '';

        $this->checkValues();
    }


    private function test_name($rule, $a) {
        return self::name( $rule, $a );
    }
    public static function name($rule, $a) {
        if ( !$a || $a && $rule == true && preg_match( '/^[a-zA-Z0-9-_]+$/', $a ) ) {
            return false;
        }
        return 'Must contain only letters, nubers or the following symbols: "-", "_"';
    }

    private function test_notEmpty($rule, $a) {
        if ( !$rule ) {
            return false;
        }
        
        $a = is_string( $a ) ? trim( $a ) : $a;
        if ( !empty( $a ) ) {
            return false;
        }
        return 'The field is empty';
    }
    
    private function test_regExp($rule, $a) {
        if ( !$a || $a && preg_match( '/'.$rule[0].'/', $a ) ) {
            return false;
        }
        return 'Doesn\'t fit the pattern '.$rule[1];
    }

    private function test_email($rule, $a) {
        if ( !$a || $a && $rule == true && filter_var( $a, FILTER_VALIDATE_EMAIL ) ) {
            return false;
        }
        return 'The email format is incorrect';
    }

    private function test_url($rule, $a) {
        if ( !$a || $a && $rule == true && filter_var( $a, FILTER_VALIDATE_URL ) ) {
            return false;
        }
        return 'Please, start the link with https:// or http://';
    }

    private function test_equals($rule, $a) {
        if ( !$a || $a && $a === $this->v[ $this->p . $rule ] ) {
            return false;
        }
        return 'The value has to match the previous field'; // ++ can add the title / placeholder here
    }
    
// -----____--____FILES VALIDATION____----____---____

    private function test_file_notEmpty($rule, $a) {
        return $this->test_notEmpty( $rule, $a['name'] );
    }

    private function test_file_maxSize($rule, $a) {
        if ( !$a['name'] ) {
            return false;
        }
        if ( is_numeric( $rule ) && $a['size'] < $rule ) {
            return false;
        }
        return 'The file <em>'.$a['name'].'</em> is too big. Max size is '.$rule;
    }
    
    private function test_file_extension($rule, $a) {
        if ( !$a['name'] ) {
            return false;
        }
        $ext = pathinfo( $a['name'], PATHINFO_EXTENSION );
        if ( is_array( $rule ) && in_array( $ext, $rule ) ) {
            return false;
        }
        return 'The file <em>'.$a['name'].'</em> extension doesn\'t fit the allowed list: ' . implode( ', ', $rule );
    }
    
    private function test_file_default($a) { // this one goes silently with current server settings
        if ( $a['error'] ) {
            return [ // taken from the php reference for uploading errors
                0 => 'There is no error, the file uploaded with success', // doesn't count anyways
                1 => 'The uploaded file '.$a['name'].' exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file '.$a['name'].' exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file '.$a['name'].' was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file '.$a['name'].' to disk.',
                8 => 'A PHP extension stopped the file '.$a['name'].' upload.',
            ][ $a['error'] ];
        }
        return false;
    }

// ---________---___--__________---

    private function checkValues() {

        foreach ( $this->s->fields as $f ) {

            if ( !$f->validate ) { continue; }

            foreach ( $f->validate as $mname => $rule ) {
                $method = 'test_' . ( $f->type == 'file' ? 'file_' : '' ) . $mname;
                $test = false;

                if ( !method_exists( $this, $method ) ) { continue; }

                $fname = $this->p . $f->name;
                
                // multiple files
                if ( $f->type == 'file' && $f->multiple ) {

                    $mflip = FCP_Forms__Files::flip_files( $this->v[ $fname ] );

                    foreach ( $mflip as $v ) {
                        if ( $this->addResult( $method, $f->name, $rule, $v ) ) {
                            $this->files_failed[ $f->name ][] = $v['name'];
                        }
                    }

                    continue;
                }

                // text data && single file
                if ( $this->addResult( $method, $f->name, $rule, $this->v[ $fname ] ) ){
                    if ( $f->type == 'file' ) {
                        $this->files_failed[ $f->name ][] = $this->v[ $fname ]['name'];
                    }
                }
            }
        }
    }
    
    private function addResult($method, $name, $rule, $a) {
        if ( $test = $this->{ $method }( $rule, $a ) ) {
            $this->result[$name][] = $test;
            return true;
        }
    }
    
    public function add_result($field, $value) { // --Y is it used??
        $this->result[$field][] = $value;
    }

}
