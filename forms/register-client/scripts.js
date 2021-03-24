(function($) {

    var a = setInterval( function() {
        if ( document.readyState !== "complete" && document.readyState !== "interactive" ) {
            return;
        }

        if ( typeof window.jQuery === 'undefined' ) {
            return;
        }

        window.clearInterval( a );

//      ++!!if hidden field is not empty - fill in the lable with the image

        var s = { // selectors
            "file" : ".fcp-form-register-client input[type=file]",
            "select" : ".fcp-form-register-client select",
            "empty" : "fcp-form-empty"
        };
        
        // change the content of file lable
        $( s.file ).on( 'change', function() {
            var $self = $( this ),
                $label = $self.next( 'label' );

            empty_file( $self );
            
            if ( $self[0].files.length === 0 ) {
                $label.html( 'Datei Auswählen' );
                return;
            }
            if ( $self[0].files.length === 1 ) {
                $label.html( $self[0].files[0]['name'] );
                return;
            }
            $label.html( $self[0].files.length + ' Files Chosen' );
        });
        
        // change the style of empty select
        $( s.select ).on( 'change', function() {
            empty_select( $( this ) );
        });
        
        // placeholder replacement on init
        $( s.file ).each( function() {
            empty_file( $( this ) );
        });
        $( s.select ).each( function() {
            empty_select( $( this ) );
        });
        
        function empty_file($self) {
            if ( $self[0].files.length === 0 ) {
                $self.addClass( s.empty );
                return;
            }
            $self.removeClass( s.empty );
        }
        function empty_select() {
            if ( $self.children( 'option:selected' ).val() === '' ) {
                $self.addClass( s.empty );
                return;
            }
            $self.removeClass( s.empty );
        }

    });

})(jQuery);
