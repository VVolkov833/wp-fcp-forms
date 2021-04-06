<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w, $t; // structure - json; $_FILES; warnings; dir for temporary files
    public $files; // [ single file array + post field name ]

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        $this->t = wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir;
        $this->proceed();

    }

    public function for_hiddens() {
        $result = [];
        foreach ( $this->files as $v ) {
            $result[ $v['field'] ][] = $v['name'];
        }
        foreach ( $result as &$v ) {
            $v = implode( ', ', $v );
        }
        return $result;
    }
    
    private function proceed() { // ++brush it up later

        // filter by structure (field, multiple)
        $fields = [];
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) {
                continue;
            }
            $fields[ $v->name ] = $v->multiple ? 1 : 0;
        }

        $f = $this->f;
        foreach ( $f as $k => $v ) {
            if ( !isset( $fields[$k] ) ) { // field doesn't exist in structure
                unset( $f[$k] );
                continue;
            }

            if ( isset( $v[0] ) && !$fields[$k] ) { // field is not multiple in structure
                $f[$k] = $v[0];
                continue;
            }
            if ( isset( $v['name'] ) && $fields[$k] ) { // field is multiple in structure
                unset( $f[$k] );
                $f[$k] = [ 0 => $v ];
            }
        }

        // flatten
        $fl = [];
        foreach ( $f as $k => $v ) {
            if ( isset( $v['name'] ) ) {
                $fl[] = $v + ['field' => $k];
                continue;
            }
            foreach ( $v as $w ) {
                $fl[] = $w + ['field' => $k];
            }
        }
        $f = $fl;
        unset( $fl );

        // uploading error
        foreach ( $f as $k => $v ) {
            if ( $v['error'] ) {
                unset( $f[$k] );
            }
        }

        // filter by warnings & error
        foreach ( $f as $k => $v ) {
            if ( !$this->w[ $v['field'] ] ) { // no warnings for the field
                continue;
            }
            if ( !in_array( $v['name'], $this->w[ $v['field'] ] ) ) { // no warnings for the file by name
                continue;
            }
            unset( $f[$k] );
        }

        $this->files = array_values( $f );

    }

    public static function rm_dir($dir) { /* from https://www.php.net/manual/ru/function.rmdir.php */
        if ( !is_dir( $dir ) ) {
            return;
        }
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        foreach ( $files as $file ) {
            $rm = $dir . '/' . $file;
            if ( is_dir( $rm ) ) {
                self::rm_dir( $rm );
                continue;
            }
            unlink( $rm );
        }
        return rmdir($dir);
    }

    public static function rm($a) {
        if ( is_file( $a ) ) {
            unlink( $a );
            return;
        }
        if ( is_dir( $a ) ) {
            self::rm_dir( $a );
        }
    }
    
    public static function flip_files($mfile = []) { /* flip the array of uploading files from [name][0] to [0][name] */
        if ( !is_array( $mfile['name'] ) ) {
            return $mfile;
        }
        $mflip = [];
        for ( $i = 0, $j = count( $mfile['name'] ); $i < $j; $i++ ) {
            foreach ( $mfile as $k => $v ) {
                $mflip[$i][$k] = $mfile[$k][$i];
            }
        }
        return $mflip;
    }
    
    public static function ext($name) {
        if ( !$name ) {
            return;
        }
        return pathinfo( $name, PATHINFO_EXTENSION );
    }

}
