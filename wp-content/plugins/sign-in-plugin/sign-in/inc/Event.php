<?php

class Event {
    public int $id;

    public $metadata = array();

    public function __construct( $id = 0 ) {
        $this->id = $id;

        $event_meta = array(
            "guid" => '',
            "event_type" => '',
            "start_time" => '',
            "end_time" => '',
            "event_name" => '',
            "event_description" => '',
            "cvent_id" => '',
            "rep_name" => '',
            "rep_email" => '',
            "concur_note" => ''
        );

        $event_meta = apply_filters( 'aasgnn_event_metadata', $attendee_meta );

        $this->metadata = $event_meta;
    }
}