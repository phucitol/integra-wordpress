jQuery( document ).ready( function( $ ) {

    window.populatePreRegSuggestions = function( form, s ) {
        var count = 0;
        var container = form.find( '.autocomplete-container' );
        var tbody = container.find( 'table.attendee-suggestions > tbody' );
        
        tbody.empty();
        container.css( 'display', 'none' );

        $.each( aasgnn_vars.cvent_users, function( index, value ) {
            var s_email = "";
            if ( isSet( value['email_address'] ) ) {
                s_email = value['email_address'].split( '@' )[0];
            }

            if ( s === "" || ( s_email.toLowerCase().indexOf( s.toLowerCase() ) >= 0 ) || 
            ( value['first_name'] !== null && value['first_name'].toLowerCase().indexOf( s.toLowerCase() ) >= 0 ) || 
            ( value['last_name'] !== null && value['last_name'].toLowerCase().indexOf( s.toLowerCase() ) >= 0 ) ) {
                count++
                var row = $( '<tr data-target="'+index+'"><td>'+value['first_name']+' '+value['last_name']+'</td><td>'+value['email_address']+'</td></tr>' );

                tbody.append( row );
            }
        } );

        container.find( 'table.attendee-suggestions > tbody > tr' ).click( function() {
            var target = $( this ).attr( 'data-target' ) // Get the index of the clicked row
            var arrayItem = aasgnn_vars.cvent_users[target]; // Get the corresponding array item

            populateRegistrantInfo( arrayItem );
            $( '#registrant-info' ).submit();
        } );

        if ( count > 0 )
            container.css( 'display', 'block' );
    }

} );