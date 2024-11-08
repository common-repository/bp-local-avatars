<?php


class PP_Local_Avatars {

	private $upload_dir;
	private $group_upload_dir;

	static $instance = false;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {

		$this->upload_dir 		= bp_core_avatar_upload_path() . '/avatars';
		$this->group_upload_dir = bp_core_avatar_upload_path() . '/group-avatars';

		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		add_action( 'user_register', array( $this, 'register' ) );

		add_filter( 'bp_core_fetch_avatar_no_grav', array( $this, 'no_grav' ) );

	}

	function login( $user_login, $user ) {
		$this->create( $user->ID );
	}

	function register( $user_id ) {
		$this->create( $user_id );
	}

	// Creates an identicon if no local avatar exists
	public function create( $user_id ) {
		global $wpdb;

		// Bail if an avatar already exists for this user.
		if ( $this->has_avatar( $user_id ) )
			return;

		wp_mkdir_p( $this->upload_dir . '/' . $user_id );

		$user_email = $wpdb->get_var( "SELECT user_email FROM $wpdb->users WHERE ID = $user_id" );

		// thumbnail
		$dim = BP_AVATAR_THUMB_WIDTH;
		$url = $this->gravatar_url( $user_email, $dim, 'identicon', 'g' );

		//var_dump( $url );

		$path = $this->upload_dir . '/' . $user_id . '/' . $user_id . '-bpthumb.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

		// full size
		$dim = BP_AVATAR_FULL_WIDTH;
		$url = $this->gravatar_url( $user_email, $dim, 'identicon', 'g' );

		$path = $this->upload_dir . '/' . $user_id . '/' . $user_id . '-bpfull.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

	}

	// Creates a Group identicon if no Group avatar exists
	public function group_create( $group_id ) {

		// Bail if an avatar already exists for this group.
		if ( $this->group_has_avatar( $group_id ) )
			return;

		wp_mkdir_p( $this->group_upload_dir . '/' . $group_id );

		$fake_email = uniqid('', true) . '@gmail.com';

		// thumbnail
		$dim = BP_AVATAR_THUMB_WIDTH;
		$url = $this->gravatar_url( $fake_email, $dim, 'identicon', 'g' );

		$path = $this->group_upload_dir . '/' . $group_id . '/' . $group_id . '-bpthumb.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

		// full size
		$dim = BP_AVATAR_FULL_WIDTH;
		$url = $this->gravatar_url( $fake_email, $dim, 'identicon', 'g' );

		$path = $this->group_upload_dir . '/' . $group_id . '/' . $group_id . '-bpfull.jpg';
		copy($url, $path);  //NOTE:  requires allow_url_fopen set to true

	}


	/**
	 * Generate a Gravatar URL for a specified email address
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @return String containing a URL
	 * @source http://gravatar.com/site/implement/
	 */

	private function gravatar_url( $email, $s = 50, $d = 'identicon', $r = 'g' ) {

	    $url = 'https://www.gravatar.com/avatar/';
	    $url .= md5( strtolower( trim( $email ) ) );
	    //$url .= ".jpg?s=$s&d=$d&r=$r";  // old structure
	    $url .= ".?s=$s&d=$d&r=$r";

		write_log( $url );

		return $url;

	}

	// Checks if a given user has local avatar dir
	private function has_avatar( $user_id ) {

		$dir_path = $this->upload_dir . '/' . $user_id;

		//write_log( '' );
		//write_log( $user_id  );
		//write_log( $dir_path );

		if ( ! file_exists( $dir_path ) ) {
			return false;
		} else {
			$empty_dir = $this->checkFolderIsEmptyOrNot( $dir_path );

			if ( ! $empty_dir )
				return true;
			else
				return false;

		}
	}

	// Checks if a given Group has  avatar dir
	private function group_has_avatar( $group_id ) {

		$dir_path = $this->group_upload_dir . '/' . $group_id;

		if ( ! file_exists( $dir_path ) ) {
			return false;
		} else {
			$empty_dir = $this->checkFolderIsEmptyOrNot( $dir_path );

			if ( ! $empty_dir )
				return true;
			else
				return false;

		}
	}

	private function checkFolderIsEmptyOrNot ( $folderName ){
		$files = array ();
		if ( $handle = opendir ( $folderName ) ) {
			while ( false !== ( $file = readdir ( $handle ) ) ) {
				if ( $file != "." && $file != ".." ) {
					$files [] = $file;
				}
			}
			closedir ( $handle );
		}
		return ( count ( $files ) > 0 ) ?  false : true;
	}

	// Disables Gravatar.
	function no_grav() {
		return true;
	}

}

