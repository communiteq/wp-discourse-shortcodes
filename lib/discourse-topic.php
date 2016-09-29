<?php

namespace WPDiscourseShortcodes\DiscourseTopic;

class DiscourseTopic {
	protected $utilities;
	protected $options;

	public function __construct( $utilities ) {
		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = $this->utilities->get_options();
	}
}