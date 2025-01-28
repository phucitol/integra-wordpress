jQuery( document ).ready( function( $ ) {
    var inputTimer; // Timer variable to hold the timeout

    function getLocalTime() {
        const now = new Date();
        const localTime = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');
        // Set the hidden field value to the local time
        return localTime;
    }

    function validateForm( $form ) {
        var isValid = true;
        var rVal = true;

        // Remove existing error class
        $( '.required' ).removeClass( 'error' );

        // Check each required input and select
        $( '.required' ).each( function() {
            var $this = $( this );

            // Check if the field is empty or ( if checkbox ) not checked
            if ( !$this.prop( 'disabled' ) ) {
                if ( $this.is( 'input:checkbox' ) && !$this.is( ':checked' ) ) {
                    $( "span[aria-name='" + $this.attr( 'name' ) + "']" ).addClass( 'error' );
                    isValid = false;
                } else if ( $this.is( ':radio' ) ) {
                    if ( $( 'input[name="' + $this.attr( 'name' ) + '"]:checked' ).length == 0 ) {
                        $( "span[aria-name='" + $this.attr( 'name' ) + "']" ).addClass( 'error' );
                        isValid = false;
                    }
                } else if ( $this.is( 'input' ) || $this.is( 'select' ) ) {
                    if ( $this.val() === '' ) {
                        $this.addClass( 'error' );
                        isValid = false;
                    }
                }

                if ( !isValid ) {
                    rVal = false;
                    isValid = true;
                }
            }
        } );

        return rVal;
    }

    function validateRegistration( $form ) {
        var isValid = true;
        var rVal = true;

        // Check each required input and select
        $( '.required' ).each( function() {
            // Find the first form element inside the div
            var formElement = $(this).find("input, select, textarea").first();
            var isValid = true;

            if (formElement.is(":checkbox, :radio")) {
                $(this).find("input[name='" + formElement.attr("name") + "']").removeClass( 'error' );
                // For checkboxes and radio buttons, check if any of the group is checked
                if ($(this).find("input[name='" + formElement.attr("name") + "']:checked").length == 0) {
                    isValid = false;
                    $(this).find("input[name='" + formElement.attr("name") + "']").addClass( 'error' );
                }
            } else {
                formElement.removeClass( 'error' );
                // For other input types, check if the value is not empty
                if (!isSet(formElement.val())) {
                    isValid = false;
                    formElement.addClass( 'error' );
                }
            }

            if ( !isValid ) {
                isValid = true;
                rVal = false;
            }
        });

        return rVal;
    }

    window.clearRegistrationErrors = function() {
        // Check each required input and select
        $( "input, select, textarea" ).each( function() {
            $(this).removeClass( 'error' );
        });
    }

    function processingForm( $btn ) {
        // Check if the input is enabled
        if ( $btn.prop( 'disabled' ) ) {
            // If disabled, enable it, remove background image, and show text
            $btn.prop( 'disabled', false )
                .css( 'color', 'rgba( 255, 255, 255, 1 )' )
                .css( 'background-image', '' );
        } else {
            var bg = $btn.css( "background-color" );
            // If enabled, disable it, set loading background, and hide text
            $btn.prop( 'disabled', true )
                .css( 'color', 'rgba( 255, 255, 255, 0 )' )
                .css( 'background-image', 'url( ' + aasgnn_vars.loading_icon + ' )' )
                .css( 'background-position', 'center center' )
                .css( 'background-size', 'auto 100%' )
                .css( 'background-repeat', 'no-repeat' )
                .css( 'background-color', bg );
        }
    }

    window.isSet = function( x ) {
        if ( typeof x == 'undefined' || x == null || x === '' )
            return false;
        else 
            return true;
    }

    window.updateSignature = function() {
        var first = $( 'input[name="first_name"]' ).val();
        var last = $( 'input[name="last_name"]' ).val();
        var signature = "";

        if ( first !== '' || last !== '' ) {
            signature = first + (last ? " " + last : "");

            $( '#print_signature' ).html( signature );
        } else {
            $( '#print_signature' ).html( '__________________' );
        }
    }

    function showSuccessMessage() {
        $( '#message-overlay' ).css('display', 'flex').fadeIn(); // Show the overlay

        setTimeout( function() { // Hide the overlay after 2.5 seconds
            var guid = $( 'form.reg-form input[type="submit"]' ).attr( 'aria-guid' );

            $( '#message-overlay' ).fadeOut();
            window.location.href = aasgnn_vars.site_url + "/sign-in-event-start-registration/?event=" + guid;
        }, 2500 );
    }

    window.populateRegistrantInfo = function( registrant ) {
        $.each( registrant, function( index, value ) { 

            if ( isSet( value ) ) {
                var element = $( '#registrant-info [name="' + index + '"]' );


                if (element.length) { // Check if element exists
                    if (element.is('input[type="text"]') || element.is('input[type="hidden"]') || element.is('input[type="email"]')) {
                        if ( !isSet( element.val() ) || element.closest( "div" ).hasClass( "overwrite" ) ) {
                            element.val( value );
                        }
                    } else if (element.is('select')) {
                        if ( !isSet( element.val() ) || element.closest( "div" ).hasClass( "overwrite" ) ) {
                            element.val( value );
                        }
                    } else if (element.is(':checkbox')) {
                        element.each( function() {
                            if ( $(this).val() === value ) {
                                $(this).prop('checked', true);
                            }
                        } );
                    } else if (element.is(':radio')) {
                        //I need to loop through all of the radios with this name.
                        element.each( function() {
                            if ( $(this).val() === value ) {
                                $(this).prop('checked', true);
                            }
                        } );
                        
                    } else {
                        console.log('Element, '+index+' is not any of the element types.');
                    }
                } else {
                    console.log('Element not found: '+index);
                }
            }
        } );
    }

    $( document ).on( 'click','.event-row', ( event ) => {
        const sender = $( event.target ).parents( 'tr' );
        const link = $( sender ).attr( 'aria-url' );

        if ( link ) {
            window.location.href = link;
        }

    } );

    // Tabs
    $( document ).on( 'click', '.tabs .tab', ( event ) => {
        const button = $( event.target ).closest('.tab');
        const tab = button.attr( 'aria-tab' );

        if ( tab === 'planned-event' ) {
            $( 'button.tab > img.planned' ).attr( 'src', aasgnn_vars.planned_icon_white );
            $( 'button.tab > img.ad-hoc' ).attr( 'src', aasgnn_vars.ad_hoc_icon );
        } else if ( tab === 'ad-hoc-event' ) {
            $( 'button.tab > img.planned' ).attr( 'src', aasgnn_vars.planned_icon );
            $( 'button.tab > img.ad-hoc' ).attr( 'src', aasgnn_vars.ad_hoc_icon_white );
        }

        $( 'input[name="event_type"]' ).val( tab );

        $( 'div.event-dep' ).hide();
        $( 'input.event-dep' ).prop( 'disabled', true );

        $( 'div.' + tab ).show();
        $( 'input.' + tab ).prop( 'disabled', false );

        $( '.tab' ).removeClass( 'active' );
        button.addClass( 'active' );
    } );

    $( '#npi-lookup-text, #npi-lookup-full-text' ).bind( 'keypress', function( e ) {
       if( e.keyCode === 13 )
           return false;
    } );    

    $( '.share-sign-in-event' ).click( function( event ) {
        event.preventDefault();
        $( '#message-overlay' ).css( "display", "flex" )
                                     .hide()
                                     .fadeIn(); // Prevent the default form submission
    } );

    $( '.close-share-sign-in-event' ).click( function( event ) {
        event.preventDefault();
        $( '#message-overlay' ).fadeOut(); 
    } );
  
    $( '#submit-event' ).click( function( event ) {
        event.preventDefault(); // Prevent the default form submission

        var time_error = false;

        // Check if the start time is before the end time
        var startTime = new Date( $( 'input[name="start_time"]' ).val() );
        var endTime = new Date( $( 'input[name="end_time"]' ).val() );
        
        if ( startTime >= endTime ) {
            $( 'input[name="start_time"]' ).addClass( 'error');
            $( 'div#start-time-errors' ).show();
            time_error = true;
        } else {
            $( 'div#start-time-errors' ).hide();
            time_error = false;
        }

        if ( validateForm( $( this ) ) && !time_error ) {
            processingForm( $( this ) );

            $( 'input[name="local_time"]').val(getLocalTime);

            var formData = $( this ).closest( 'form' ).serialize();

            $.ajax( {
                url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                type: 'POST',
                data: formData, // Serialize the form data
                success: function( response ) {
                    // Handle the success case
                    window.location.href = aasgnn_vars.site_url+'/sign-in-event/'+response['data']['post_title'];
                    console.log( 'Success:', response );
                },
                error: function( xhr, status, error ) {
                    // Handle errors
                    console.error( 'Error:', error );
                },
                complete: function( xhr, status ) {
                    // Code to run regardless of success or error
                    console.log( 'Request completed with status:', status );
                }
            } );
        }
    } );
  
    $( '#submit-edit-event' ).click( function( event ) {
        event.preventDefault(); // Prevent the default form submission
        if ( validateForm( $( this ) ) ) {
            var startTime = new Date( $( 'input[name="start_time"]' ).val() );
            var endTime = new Date( $( 'input[name="end_time"]' ).val() );

            // Check if the start time is before the end time
            if ( startTime >= endTime ) {
                $( 'input[name="start_time"]' ).addClass( 'error' );
                return false; // Valid range 
            }
            processingForm( $( this ) );

            $( 'input[name="local_time"]').val(getLocalTime);

            var formData = $( this ).closest( 'form' ).serialize();

            $.ajax( {
                url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                type: 'POST',
                data: formData, // Serialize the form data
                success: function( response ) {
                    // Handle the success case
                    window.location.href = aasgnn_vars.site_url + '/sign-in-event/' + response['data']['post_title'];
                    console.log( 'Success:', response );
                },
                error: function( xhr, status, error ) {
                    // Handle errors
                    console.error( 'Error:', error );
                },
                complete: function( xhr, status ) {
                    // Code to run regardless of success or error
                    console.log( 'Request completed with status:', status );
                }
            } );
        }
    } );

    $( 'form.begin-check-in input[type="submit"], form.pause-check-in input[type="submit"]' ).click( function( event ) {
        event.preventDefault(); // Prevent the default form submission
        processingForm( $( this ) );

        var formData = $( this ).closest( 'form' ).serialize();

        $.ajax( {
            url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
            type: 'POST',
            data: formData, // Serialize the form data
            success: function( response ) {
                // Handle the success case
                location.reload( true );
                console.log( 'Success:', response );
            },
            error: function( xhr, status, error ) {
                // Handle errors
                console.error( 'Error:', error );
            },
            complete: function( xhr, status ) {
                // Code to run regardless of success or error
                console.log( 'Request completed with status:', status );
            }
        } );
    } );

    $( '#cancel-edit-event' ).click( function( event ) {
        event.preventDefault();
        processingForm( $( this ) );
        window.history.back();
    } );

    $( 'form#close-event input[type="submit"]' ).click( function( event ) {
        event.preventDefault(); // Prevent the default form submission

        if ( confirm( "Once the event is closed it cannot be reopened, and reports will be emailed. Proceed?" ) ) {
            processingForm( $( this ) );

            var formData = $( this ).closest( 'form' ).serialize(); // Serialize the form data

            $.ajax( {
                url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                type: 'POST',
                data: formData, 
                success: function( response ) {
                    // Handle the success case
                    console.log( 'Success:', response );
                    location.reload( true );
                },
                error: function( xhr, status, error ) {
                    // Handle errors
                    console.error( 'Error:', error );
                },
                complete: function( xhr, status ) {
                    // Code to run regardless of success or error
                    console.log( 'Request completed with status:', status );
                }
            } );
        } else {
            console.log( "Cancelled" );
        }
    } );

    $( '#look-up-npi-full' ).click( function( event ) {
        event.preventDefault();

        var fname = $( 'input[name="first_name"]' ).val();
        var lname = $( 'input[name="last_name"]' ).val();
        var s = "";

        if ( fname.length > 0 ) {
            s = fname;
        }

        if ( lname.length > 0 ) {
            s += " " + lname;
        }

        $( '#complete-registration input[name="search"]' ).val( s );
        $( '#complete-registration input[name="doing-npi-look-up"]' ).val( "true" );

        $( '#complete-registration' ).attr( 'action', $( '#complete-registration input[name="look-up-npi-action"]' ).val() );
        $( '#complete-registration' ).submit();
    } );

    $( '#npi-lookup-text, #npi-lookup-full-text' ).on( 'input', function() {
        var input = $( this );
        var s = $( this ).val();

        var form = $( this ).closest( 'form' );
        var container = form.find( '.autocomplete-container' );
        var tbody = container.find( 'table.attendee-suggestions > tbody' );
        var formData = form.serialize();
        
        tbody.empty();
        container.css( 'display', 'none' );

        if ( s.length < 2 ) {
            return;
        }

        clearTimeout( inputTimer );

        inputTimer = setTimeout( function() {
            $( input ).addClass( 'loading' );
            var currentLength = input.val().length;
            if ( currentLength === s.length ) {
                $.ajax( {
                    url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                    type: 'POST',
                    data: formData, // Serialize the form data
                    success: function( response ) {
                        console.log( 'Success:', response );
                        // Handle the success case
                        container.css( 'display', 'block' );

                        $.each( response.data.suggestions, function( index, item ) {
                            var row = $( '<tr><td>'+item['last_name']+', '+item['first_name']+'</td><td>'+item['npi_number']+'</td><td>'+item['work_address_1']+'<br />'+item['work_city']+', '+item['work_state_code']+'</td><td>'+item['primary_taxonomy']+'</td></tr>' );

                            tbody.append( row );
                        } );

                        $( 'table.attendee-suggestions > tbody > tr' ).click( function() {
                            var rowIndex = $( this ).index(); // Get the index of the clicked row
                            var arrayItem = response.data.suggestions[rowIndex]; // Get the corresponding array item

                            populateRegistrantInfo( response.data.suggestions[rowIndex] );
                            $( '#registrant-info' ).submit();
                        } );
                    },
                    error: function( xhr, status, error ) {
                        // Handle errors
                        console.error( 'Error:', error );

                    },
                    complete: function( xhr, status ) {
                        // Code to run regardless of success or error
                        console.log( 'Request completed with status:', status );
                        $( input ).removeClass( 'loading' );
                    }
                } );
            } else {
                console.log( 'Input length changed, initial length:', s.length, ', current length:', currentLength );
            }
        }, 250 );
    } );

    $( '#pre-reg-lookup-text' ).on( 'input', function() {
        $( '#view-all-pre-reg' ).show();

        var input = $( this );
        var s = $( this ).val();

        var form = $( this ).closest( 'form' );
        var formData = form.serialize();
        var container = form.find( '.autocomplete-container' );
        var tbody = container.find( 'table.attendee-suggestions > tbody' );
        
        tbody.empty();
        container.css( 'display', 'none' );

        if ( !isSet(aasgnn_vars.cvent_users) || aasgnn_vars.cvent_users.length < 1 ) {

            clearTimeout( inputTimer );

            inputTimer = setTimeout( function() {
                var currentLength = $( '#pre-reg-lookup-text' ).val().length;
                //console.log( "Input: " + s.length + " - Current: " + currentLength );
                if ( currentLength === s.length ) {
                    $( input ).addClass( 'loading' );
                    $.ajax( {
                        url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                        type: 'POST',
                        data: formData, // Serialize the form data
                        success: function( response ) {
                            console.log( 'Success:', response );
                            aasgnn_vars.cvent_users = response.data.suggestions;
                            populatePreRegSuggestions( form, s );
                        },
                        error: function( xhr, status, error ) {
                            // Handle errors
                            console.error( 'Error:', error );

                        },
                        complete: function( xhr, status ) {
                            // Code to run regardless of success or error
                            console.log( 'Request completed with status:', status );
                            $( input ).removeClass( 'loading' );

                        }
                    } );
                } else {
                    console.log( 'Input length changed, initial length:', s.length, ', current length:', currentLength );
                }
            }, 250 );
        } else {
            populatePreRegSuggestions( form, s );
        }
    } );
  
    $( '#submit-registration' ).click( function( event ) {
        if ( $( '#complete-registration input[name="doing-npi-look-up"]' ).val() == "false" ) {
            event.preventDefault(); // Prevent the default form submission
            if ( validateRegistration( $( this ) ) ) {
                processingForm( $( this ) );

                var formData = $( this ).closest( 'form' ).serialize();

                $.ajax( {
                    url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                    type: 'POST',
                    data: formData, // Serialize the form data
                    success: function( response ) {
                        // Handle the success case
                        showSuccessMessage();
                        console.log( 'Success:', response );
                    },
                    error: function( xhr, status, error ) {
                        // Handle errors
                        console.error( 'Error:', error );
                    },
                    complete: function( xhr, status ) {
                        // Code to run regardless of success or error
                        console.log( 'Request completed with status:', status );
                    }
                } );
            }
        }
    } );
  
    $( '#submit-pre-registration' ).click( function( event ) {
        event.preventDefault(); // Prevent the default form submission
        if ( validateRegistration( $( this ) ) ) {
            processingForm( $( this ) );

            var formData = $( this ).closest( 'form' ).serialize();

            $.ajax( {
                url: aasgnn_vars.ajax_url, // Use the ajax URL from wp_localize_script
                type: 'POST',
                data: formData, // Serialize the form data
                success: function( response ) {
                    // Handle the success case
                    showSuccessMessage();
                    console.log( 'Success:', response );
                },
                error: function( xhr, status, error ) {
                    // Handle errors
                    console.error( 'Error:', error );
                },
                complete: function( xhr, status ) {
                    // Code to run regardless of success or error
                    console.log( 'Request completed with status:', status );
                }
            } );
        }
    } );

    $( 'input[name="first_name"], input[name="last_name"]' ).on( 'input', updateSignature );

    if ( $( '#npi-lookup-full-form' ).length > 0 ) {
        $( '#npi-lookup-full-text' ).trigger( 'input' );
    }

    if ( $( 'form#complete-registration input[name="signature[]"' ).length > 0 ) {
        updateSignature();
    }

    if ( $( 'form#pre-registration input[name="signature[]"' ).length > 0 ) {
        updateSignature();
    }

    if ( $( 'form.edit-event' ).length > 0 ) {
        var event_type = $('input[name="event_type"').val();

        $('button[aria-tab="'+event_type+'"]').click();
    }

    if ( $( '#planned-lookup' ).length > 0 ) {
        $( '#pre-reg-lookup-text' ).trigger( 'input' );
    }

} );