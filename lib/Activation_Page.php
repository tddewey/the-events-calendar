<?php

/**
 * Shows a welcome or update message after the plugin is installed/updated
 */
class Tribe__Events__Activation_Page {
	/** @var self */
	private static $instance = NULL;

	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'maybe_redirect' ), 10, 0 );
		add_action( 'admin_menu', array( $this, 'register_page' ), 100, 0 ); // come in after the default page is registered
	}

	public function maybe_redirect() {
		if ( !empty($_POST) ) {
			return; // don't interrupt anything the user's trying to do
		}
		if ( !is_admin() || defined('DOING_AJAX') ) {
			return;
		}
		if ( defined('IFRAME_REQUEST') && IFRAME_REQUEST ) {
			return; // probably the plugin update/install iframe
		}
		if ( isset($_GET['tec-welcome-message']) || isset($_GET['tec-update-message']) ) {
			return; // no infinite redirects
		}
		if ( $this->showed_update_message_for_current_version() ) {
			return;
		}

		// the redirect might be intercepted by another plugin, but
		// we'll go ahead and mark it as viewed right now, just in case
		// we end up in a redirect loop
		// see #31088
		$this->log_display_of_message_page();

		if ( $this->is_new_install() ) {
			$this->redirect_to_welcome_page();
		} else {
			$this->redirect_to_update_page();
		}
	}

	/**
	 * Have we shown the welcome/update message for the current version?
	 *
	 * @return bool
	 */
	protected function showed_update_message_for_current_version() {
		$tec = Tribe__Events__Events::instance();
		$message_version_displayed = $tec->getOption('last-update-message');
		if ( empty($message_version_displayed) ) {
			return FALSE;
		}
		if ( version_compare( $message_version_displayed, Tribe__Events__Events::VERSION, '<' ) ) {
			return FALSE;
		}
		return TRUE;
	}

	protected function log_display_of_message_page() {
		$tec = Tribe__Events__Events::instance();
		$tec->setOption('last-update-message', Tribe__Events__Events::VERSION);
	}

	/**
	 * The previous_ecp_versions option will be empty or set to 0
	 * if the current version is the first version to be installed.
	 *
	 * @return bool
	 * @see Tribe__Events__Events::maybeSetTECVersion()
	 */
	protected function is_new_install() {
		$tec = Tribe__Events__Events::instance();
		$previous_versions = $tec->getOption('previous_ecp_versions');
		return empty($previous_versions) || ( end($previous_versions) == '0' );
	}

	protected function redirect_to_welcome_page() {
		$url = $this->get_message_page_url( 'tec-welcome-message' );
		wp_safe_redirect($url);
		exit();
	}

	protected function redirect_to_update_page() {
		$url = $this->get_message_page_url( 'tec-update-message' );
		wp_safe_redirect($url);
		exit();
	}

	protected function get_message_page_url( $slug ) {
		$settings = Tribe__Events__Settings::instance();
		// get the base settings page url
		$url  = apply_filters(
			'tribe_settings_url', add_query_arg(
				array(
					'post_type' => Tribe__Events__Events::POSTTYPE,
					'page'      => $settings->adminSlug
				), admin_url( 'edit.php' )
			)
		);
		$url = add_query_arg( $slug, 1, $url );
		return $url;
	}

	public function register_page() {
		// tribe_events_page_tribe-events-calendar
		if ( isset($_GET['tec-welcome-message']) ) {
			$this->disable_default_settings_page();
			add_action( 'tribe_events_page_tribe-events-calendar', array( $this, 'display_welcome_page' ) );
		} elseif ( isset( $_GET['tec-update-message'] ) ) {
			$this->disable_default_settings_page();
			add_action( 'tribe_events_page_tribe-events-calendar', array( $this, 'display_update_page' ) );
		}
	}

	protected function disable_default_settings_page() {
		remove_action( 'tribe_events_page_tribe-events-calendar', array( Tribe__Events__Settings::instance(), 'generatePage' ) );
	}

	public function display_welcome_page() {
		do_action( 'tribe_settings_top' );
		echo '<div class="tribe_settings tribe_welcome_page wrap">';
		echo '<h2>';
		echo $this->welcome_page_title();
		echo '</h2>';
		echo $this->welcome_page_content();
		echo '</div>';
		do_action( 'tribe_settings_bottom' );
		$this->log_display_of_message_page();
	}

	protected function welcome_page_title() {
		return __('Welcome to The Events Calendar', 'tribe-events-calendar');
	}

	protected function welcome_page_content() {
		return $this->load_template('admin-welcome-message');
	}

	public function display_update_page() {
		do_action( 'tribe_settings_top' );
		echo '<div class="tribe_settings tribe_update_page wrap">';
		echo '<h2>';
		echo $this->update_page_title();
		echo '</h2>';
		echo $this->update_page_content();
		echo '</div>';
		do_action( 'tribe_settings_bottom' );
		$this->log_display_of_message_page();
	}

	protected function update_page_title() {
		return __('Thanks for Updating The Events Calendar', 'tribe-events-calendar');
	}

	protected function update_page_content() {
		return $this->load_template('admin-update-message');
	}

	protected function load_template( $name ) {
		ob_start();
		include(trailingslashit(Tribe__Events__Events::instance()->pluginPath).'admin-views/'.$name.'.php');
		return ob_get_clean();
	}

	/**
	 * Initialize the global instance of the class.
	 */
	public static function init() {
		self::instance()->add_hooks();
	}

	/**
	 * @return self
	 */
	public static function instance() {
		if ( empty(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


} 