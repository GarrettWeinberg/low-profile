<?php
/**
 * The guards. Each one is a handful of hooks gated by a setting.
 *
 * @package LowProfile
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers hooks for every enabled guard. Nothing here changes what a
 * logged-in user can do in wp-admin; a few guards (emoji, asset versions,
 * the X-Powered-By header) apply there as well as on the front end.
 */
final class LowProfile_Guards {

	/**
	 * Non-production host suffixes, parsed from the setting.
	 *
	 * @var string[]
	 */
	private static $noindex_hosts = array();

	/**
	 * Whether the site's host matched a non-production suffix. Null until
	 * first asked.
	 *
	 * @var bool|null
	 */
	private static $is_non_production = null;

	/**
	 * Login message, from the setting.
	 *
	 * @var string
	 */
	private static $login_message = '';

	/**
	 * Hook everything that is switched on.
	 *
	 * @param array<string, mixed> $s Settings.
	 */
	public static function boot( array $s ) {
		if ( ! empty( $s['version_markers'] ) ) {
			self::version_markers();
		}
		if ( ! empty( $s['emoji'] ) ) {
			self::emoji();
		}
		if ( ! empty( $s['powered_by'] ) ) {
			self::powered_by();
		}
		if ( ! empty( $s['discovery_links'] ) ) {
			self::discovery_links();
		}
		if ( ! empty( $s['xmlrpc'] ) ) {
			self::xmlrpc();
		}
		if ( ! empty( $s['pingbacks'] ) ) {
			self::pingbacks();
		}
		if ( ! empty( $s['application_passwords'] ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
		if ( ! empty( $s['rest_users'] ) ) {
			add_filter( 'rest_endpoints', array( __CLASS__, 'hide_rest_users' ) );
		}
		if ( ! empty( $s['author_archives'] ) ) {
			// Priority 0: core's redirect_canonical() runs at 10 and would
			// first 301 ?author=N to /author/<login>/, leaking the login.
			add_action( 'template_redirect', array( __CLASS__, 'redirect_author_archives' ), 0 );
		}
		if ( ! empty( $s['sitemap_users'] ) ) {
			add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'drop_users_sitemap' ), 10, 2 );
		}
		if ( ! empty( $s['oembed_author'] ) ) {
			add_filter( 'oembed_response_data', array( __CLASS__, 'strip_oembed_author' ) );
		}
		if ( ! empty( $s['login_errors'] ) ) {
			self::$login_message = (string) $s['login_message'];
			self::login_errors();
		}
		if ( ! empty( $s['file_edit'] ) ) {
			self::file_edit();
		}
		if ( ! empty( $s['noindex'] ) ) {
			self::$noindex_hosts = array_values( array_filter( array_map( 'trim', explode( "\n", strtolower( (string) $s['noindex_hosts'] ) ) ) ) );
			self::noindex();
		}
	}

	// --- Software and version -----------------------------------------------

	/**
	 * Generator tags and the core ?ver= query string.
	 */
	private static function version_markers() {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'style_loader_src', array( __CLASS__, 'mask_core_version' ), 15 );
		add_filter( 'script_loader_src', array( __CLASS__, 'mask_core_version' ), 15 );
		// wp-login.php concatenates its assets into load-scripts.php?…&ver=<core
		// version>, which never passes through the src filters. Turning
		// concatenation off there sends each asset through them instead.
		add_action( 'login_init', array( __CLASS__, 'disable_login_concat' ) );
	}

	/**
	 * Replace ?ver=<core version> with an opaque token that still changes on
	 * every core update, so caches are busted without naming the version.
	 * Assets with their own version string are left alone.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public static function mask_core_version( $src ) {
		if ( ! is_string( $src ) || false === strpos( $src, 'ver=' ) ) {
			return $src;
		}

		$query = wp_parse_url( $src, PHP_URL_QUERY );
		parse_str( is_string( $query ) ? $query : '', $args );

		if ( isset( $args['ver'] ) && get_bloginfo( 'version' ) === $args['ver'] ) {
			return add_query_arg( 'ver', self::version_token(), $src );
		}

		return $src;
	}

	/**
	 * Eight hex characters derived from the core version and the site's
	 * salt: unique per site and per version, not reversible to either.
	 *
	 * @return string
	 */
	private static function version_token() {
		static $token = null;

		if ( null === $token ) {
			$token = substr( md5( get_bloginfo( 'version' ) . wp_salt() ), 0, 8 );
		}

		return $token;
	}

	/**
	 * Keep wp-login.php from bundling its scripts and styles. Core reads the
	 * CONCATENATE_SCRIPTS constant when it decides, and this runs on
	 * `login_init`, so it only ever applies to the login screen's request.
	 */
	public static function disable_login_concat() {
		if ( ! defined( 'CONCATENATE_SCRIPTS' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- core's own switch (script_concat_settings()), not a new constant; the only way to keep load-scripts.php?ver=<core version> off the login page.
			define( 'CONCATENATE_SCRIPTS', false );
		}
	}

	/**
	 * The emoji detection script and its styles.
	 */
	private static function emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'drop_tinymce_emoji' ) );
	}

	/**
	 * Keep the classic editor from loading its emoji plugin.
	 *
	 * @param mixed $plugins TinyMCE plugin list.
	 * @return mixed
	 */
	public static function drop_tinymce_emoji( $plugins ) {
		return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpemoji' ) ) ) : $plugins;
	}

	/**
	 * X-Powered-By and X-Pingback.
	 */
	private static function powered_by() {
		add_filter( 'wp_headers', array( __CLASS__, 'drop_pingback_header' ) );
		// PHP's own X-Powered-By is already set when the plugin loads, on
		// every entry point (wp-login.php, REST, cron, XML-RPC), so drop it
		// now; the two hooks catch a header a framework adds later.
		self::drop_powered_by_header();
		add_action( 'send_headers', array( __CLASS__, 'drop_powered_by_header' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'drop_powered_by_header' ), 1 );
	}

	/**
	 * Drop X-Pingback from the headers WordPress sends.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	public static function drop_pingback_header( $headers ) {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	/**
	 * Drop X-Powered-By when PHP (expose_php) or a framework set it and the
	 * headers have not gone out yet.
	 */
	public static function drop_powered_by_header() {
		if ( ! headers_sent() ) {
			header_remove( 'X-Powered-By' );
		}
	}

	// --- Discovery ----------------------------------------------------------

	/**
	 * REST, shortlink, oEmbed, RSD, WLW and extra feed links — tags and headers.
	 */
	private static function discovery_links() {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	// --- Remote publishing ----------------------------------------------------

	/**
	 * Refuse XML-RPC outright. `xmlrpc_enabled` only gates the authenticated
	 * methods; system.listMethods and pingback.ping would still answer.
	 */
	private static function xmlrpc() {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		add_action( 'init', array( __CLASS__, 'refuse_xmlrpc' ) );
	}

	/**
	 * 403 for any XML-RPC request.
	 */
	public static function refuse_xmlrpc() {
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			exit( 'XML-RPC is disabled.' );
		}
	}

	/**
	 * Pingbacks off: the methods go, and pings_open() answers false at
	 * runtime whatever a post's stored ping status says.
	 */
	private static function pingbacks() {
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'xmlrpc_methods', array( __CLASS__, 'drop_pingback_methods' ), 20 );
	}

	/**
	 * Remove the pingback methods when XML-RPC is otherwise allowed.
	 *
	 * @param array<string, mixed> $methods XML-RPC methods.
	 * @return array<string, mixed>
	 */
	public static function drop_pingback_methods( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	// --- User enumeration ---------------------------------------------------

	/**
	 * Only logged-in users get /wp/v2/users (the editor's author picker).
	 *
	 * @param array<string, mixed> $endpoints Registered routes.
	 * @return array<string, mixed>
	 */
	public static function hide_rest_users( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Author archives go home.
	 */
	public static function redirect_author_archives() {
		if ( is_author() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * No users sitemap.
	 *
	 * @param mixed  $provider Sitemap provider.
	 * @param string $name     Provider name.
	 * @return mixed
	 */
	public static function drop_users_sitemap( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * No author in oEmbed data.
	 *
	 * @param array<string, mixed> $data oEmbed response.
	 * @return array<string, mixed>
	 */
	public static function strip_oembed_author( $data ) {
		unset( $data['author_name'], $data['author_url'] );

		return $data;
	}

	// --- Login ----------------------------------------------------------------

	/**
	 * Normalise the "which half was wrong" errors at the source. Hooking
	 * `authenticate` rather than `login_errors` reaches wp-login.php, themes'
	 * front-end login forms and every other caller of wp_signon(), and leaves
	 * password-reset, registration and other plugins' messages untouched.
	 */
	private static function login_errors() {
		add_filter( 'authenticate', array( __CLASS__, 'normalise_login_error' ), 100 );
		add_action( 'lostpassword_post', array( __CLASS__, 'hide_lost_password_result' ), 10, 2 );
	}

	/**
	 * Replace the distinguishing credential errors with one message.
	 *
	 * @param WP_User|WP_Error|null $user Authentication result so far.
	 * @return WP_User|WP_Error|null
	 */
	public static function normalise_login_error( $user ) {
		if ( ! is_wp_error( $user ) ) {
			return $user;
		}

		$revealing = array( 'invalid_username', 'incorrect_password', 'invalid_email', 'invalidcombo' );

		if ( array_intersect( $revealing, $user->get_error_codes() ) ) {
			return new WP_Error( 'lowprofile_invalid_credentials', self::login_message() );
		}

		return $user;
	}

	/**
	 * The lost-password form says outright when no account matches. Send an
	 * unknown username to the same "check your email" screen a known one
	 * gets, so both outcomes look identical.
	 *
	 * @param WP_Error           $errors    Validation errors so far.
	 * @param WP_User|false|null $user_data The matched user, or false.
	 */
	public static function hide_lost_password_result( $errors, $user_data = null ) {
		if ( $user_data || ( $errors instanceof WP_Error && $errors->has_errors() ) ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', wp_login_url() ) );
		exit;
	}

	/**
	 * The configured message, escaped. The stored default is English and is
	 * translated here unless the site changed it.
	 *
	 * @return string
	 */
	private static function login_message() {
		$message = self::$login_message;

		if ( LowProfile_Settings::DEFAULT_LOGIN_MESSAGE === $message ) {
			$message = __( 'The username or password you entered is incorrect.', 'low-profile' );
		}

		return esc_html( $message );
	}

	// --- File editing -----------------------------------------------------------

	/**
	 * Disable the theme and plugin editors. DISALLOW_FILE_EDIT is set when
	 * nothing else has, but a constant cannot be redefined and some hosts
	 * hard-code it to false in the wp-config.php they generate (WP Engine
	 * does), so the guard does not depend on it. Core maps edit_themes,
	 * edit_plugins and edit_files to do_not_allow whenever
	 * wp_is_file_mod_allowed( 'capability_edit_themes' ) answers false,
	 * whatever the constant says. Answering false there removes the editor
	 * screens, their menu entries and the "Edit" links on the Plugins and
	 * Themes screens. Other contexts of that filter (updates, language
	 * packs) are left alone.
	 */
	private static function file_edit() {
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
		add_filter( 'file_mod_allowed', array( __CLASS__, 'disallow_editor_capability' ), 10, 2 );
	}

	/**
	 * Refuse the editor capability; leave every other file_mod_allowed
	 * context as it was.
	 *
	 * @param bool   $allowed Whether file modification is allowed.
	 * @param string $context The usage context core is asking about.
	 * @return bool
	 */
	public static function disallow_editor_capability( $allowed, $context ) {
		return 'capability_edit_themes' === $context ? false : $allowed;
	}

	// --- Indexing ---------------------------------------------------------------

	/**
	 * Everything that keeps a non-production host out of search engines:
	 * the robots meta, an X-Robots-Tag header for non-HTML responses, a
	 * disallow-all robots.txt and no sitemap.
	 */
	private static function noindex() {
		add_filter( 'wp_robots', array( __CLASS__, 'noindex_robots_meta' ), 20 );
		add_filter( 'wp_headers', array( __CLASS__, 'noindex_header' ) );
		add_filter( 'robots_txt', array( __CLASS__, 'noindex_robots_txt' ), 20 );
		add_filter( 'wp_sitemaps_enabled', array( __CLASS__, 'noindex_sitemaps' ), 20 );
	}

	/**
	 * Does the site address end with one of the configured suffixes? Each
	 * suffix is matched on a label boundary, so `.test` matches
	 * `example.test` and `localhost` matches `localhost` or `x.localhost`,
	 * never `mylocalhost`.
	 *
	 * @return bool
	 */
	public static function is_non_production() {
		if ( null !== self::$is_non_production ) {
			return self::$is_non_production;
		}

		self::$is_non_production = false;
		$host                    = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		if ( '' === $host ) {
			return false;
		}

		foreach ( self::$noindex_hosts as $suffix ) {
			$bare = ltrim( $suffix, '.' );
			if ( '' === $bare ) {
				continue;
			}
			$dotted = '.' . $bare;
			if ( $host === $bare || ( strlen( $host ) > strlen( $dotted ) && substr( $host, -strlen( $dotted ) ) === $dotted ) ) {
				self::$is_non_production = true;
				break;
			}
		}

		return self::$is_non_production;
	}

	/**
	 * Robots meta: noindex, nofollow.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 * @return array<string, mixed>
	 */
	public static function noindex_robots_meta( $robots ) {
		if ( self::is_non_production() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview'] );
		}

		return $robots;
	}

	/**
	 * X-Robots-Tag on every WordPress response, HTML or not.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	public static function noindex_header( $headers ) {
		if ( self::is_non_production() ) {
			$headers['X-Robots-Tag'] = 'noindex, nofollow';
		}

		return $headers;
	}

	/**
	 * Disallow everything in robots.txt.
	 *
	 * @param string $output robots.txt body.
	 * @return string
	 */
	public static function noindex_robots_txt( $output ) {
		return self::is_non_production() ? "User-agent: *\nDisallow: /\n" : $output;
	}

	/**
	 * No sitemap to invite crawling.
	 *
	 * @param bool $enabled Whether sitemaps are enabled.
	 * @return bool
	 */
	public static function noindex_sitemaps( $enabled ) {
		return self::is_non_production() ? false : $enabled;
	}
}
