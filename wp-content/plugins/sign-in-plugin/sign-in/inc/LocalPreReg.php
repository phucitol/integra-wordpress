<?php

class LocalPreReg
{
	public $registrants;

	public function get_participants( $post_id ) {
		$contacts = array();
		$event_data = array();

	    // Get children posts of type 'sign-in-prereg' that are children of this post
	    $args = array(
	        'post_type' => 'sign-in-prereg',
	        'post_parent' => $post_id,
	        'numberposts' => -1
	    );

	    $child_posts = get_posts( $args );

	    // Iterate over each child post
	    foreach ( $child_posts as $child_post ) {
	    	// $contact = array();

	        $prereg_post_meta = get_post_meta( $child_post->ID );

	        foreach ( SignIn::$scaffold as $key => $parameters ) {
	        	switch ( $parameters['element'] ) {

					case 'text' :
					case 'hidden' :
					case 'email' :
					case 'select' :
					case 'checkbox' :
					case 'radio' :
						$contact[$key] = ($prereg_post_meta[$key][0]) ?? "";
					default :
				}
	        }

	        $event_data[] = $prereg_post_meta; 
	        $contacts[] = $contact;
	    }

	    $this->registrants = $event_data;
	    
		return $contacts;
	}


}