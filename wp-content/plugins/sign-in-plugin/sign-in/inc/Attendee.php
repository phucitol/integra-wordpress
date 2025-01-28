<?php

class Attendee {

	public $metadata = array();

    public function __construct( $meta = array() ) {
    	$attendee_meta = array();

    	foreach ( SignIn::$scaffold as $key => $parameters ) {
        	switch ( $parameters['element'] ) {

				case 'text' :
				case 'hidden' :
				case 'email' :
				case 'select' :
				case 'checkbox' :
				case 'radio' :
					$attendee_meta[$key] = $meta[$key][0] ?? '';
				default :
			}
    	}

    	$attendee_meta = apply_filters( 'aasgnn_attendee_metadata', $attendee_meta );

    	$this->metadata = $attendee_meta;
    }
}