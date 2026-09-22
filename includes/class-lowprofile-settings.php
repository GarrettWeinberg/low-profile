<?php
/**
 * Settings: the option, its defaults and sanitizer, and the Settings screen.
 *
 * @package LowProfile
 */

defined( 'ABSPATH' ) || exit;

/**
 * One option holds every toggle. Defaults are "on" for anything that never
 * has a legitimate reason to be visible, and "off" for the one guard that
 * changes what visitors can reach (author archives).
 */
final class LowProfile_Settings {

	/**
	 * The English default for the login message. Kept untranslated here so
	 * the schema can be read at plugin load, before translations may be
	 * loaded; the guard translates it at output time when it is unchanged.
	 */
	const DEFAULT_LOGIN_MESSAGE = 'The username or password you entered is incorrect.';

	/**
	 * Types and defaults only — no translatable strings, so this is safe to
	 * call while the plugin boots. Order here is display order.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function schema() {
		return array(
			'version_markers'       => array(
				'section' => 'software',
				'type'    => 'checkbox',
				'default' => true,
			),
			'emoji'                 => array(
				'section' => 'software',
				'type'    => 'checkbox',
				'default' => true,
			),
			'powered_by'            => array(
				'section' => 'software',
				'type'    => 'checkbox',
				'default' => true,
			),
			'discovery_links'       => array(
				'section' => 'discovery',
				'type'    => 'checkbox',
				'default' => true,
			),
			'xmlrpc'                => array(
				'section' => 'remote',
				'type'    => 'checkbox',
				'default' => true,
			),
			'pingbacks'             => array(
				'section' => 'remote',
				'type'    => 'checkbox',
				'default' => true,
			),
			'application_passwords' => array(
				'section' => 'remote',
				'type'    => 'checkbox',
				'default' => true,
			),
			'rest_users'            => array(
				'section' => 'users',
				'type'    => 'checkbox',
				'default' => true,
			),
			'author_archives'       => array(
				'section' => 'users',
				'type'    => 'checkbox',
				'default' => false,
			),
			'sitemap_users'         => array(
				'section' => 'users',
				'type'    => 'checkbox',
				'default' => true,
			),
			'oembed_author'         => array(
				'section' => 'users',
				'type'    => 'checkbox',
				'default' => true,
			),
			'login_errors'          => array(
				'section' => 'login',
				'type'    => 'checkbox',
				'default' => true,
			),
			'login_message'         => array(
				'section' => 'login',
				'type'    => 'text',
				'default' => self::DEFAULT_LOGIN_MESSAGE,
			),
			'file_edit'             => array(
				'section' => 'files',
				'type'    => 'checkbox',
				'default' => true,
			),
			'noindex'               => array(
				'section' => 'indexing',
				'type'    => 'checkbox',
				'default' => true,
			),
			'noindex_hosts'         => array(
				'section' => 'indexing',
				'type'    => 'textarea',
				'default' => ".wpenginepowered.com\n.wpengine.com\n.kinsta.cloud\n.pantheonsite.io\n.flywheelsites.com\n.wpcomstaging.com\n.cloudwaysapps.com\n.ddev.site\n.test\n.local\nlocalhost",
			),
		);
	}

	/**
	 * The schema plus labels and descriptions for the screen. Only called
	 * from admin hooks, after `init`, so translations are available.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields() {
		$fields = self::schema();
		$labels = array(
			'version_markers'       => array(
				'label' => __( 'Hide the WordPress version', 'low-profile' ),
				'desc'  => __( 'Removes the generator tag from the head and feeds, and replaces ?ver= on core scripts and styles with an opaque token that still changes on every update. Plugin and theme assets keep their own version strings.', 'low-profile' ),
			),
			'emoji'                 => array(
				'label' => __( 'Remove the emoji script', 'low-profile' ),
				'desc'  => __( 'The inline emoji settings block is one of the most recognisable WordPress fingerprints, and modern browsers render emoji natively.', 'low-profile' ),
			),
			'powered_by'            => array(
				'label' => __( 'Remove software headers', 'low-profile' ),
				'desc'  => __( 'Drops the X-Powered-By and X-Pingback HTTP headers when PHP or WordPress sets them.', 'low-profile' ),
			),
			'discovery_links'       => array(
				'label' => __( 'Remove discovery links and headers', 'low-profile' ),
				'desc'  => __( 'The REST API root link and Link: header, the shortlink tag and header, oEmbed discovery, the RSD and Windows Live Writer links, and the per-category feed links. The REST API itself stays available — it is simply not advertised.', 'low-profile' ),
			),
			'xmlrpc'                => array(
				'label' => __( 'Disable XML-RPC', 'low-profile' ),
				'desc'  => __( 'Requests to xmlrpc.php get a 403. Turn this off if you publish through the mobile apps or Jetpack.', 'low-profile' ),
			),
			'pingbacks'             => array(
				'label' => __( 'Disable pingbacks', 'low-profile' ),
				'desc'  => __( 'Removes the pingback methods and answers "closed" for every post, whatever its stored ping status.', 'low-profile' ),
			),
			'application_passwords' => array(
				'label' => __( 'Disable application passwords', 'low-profile' ),
				'desc'  => __( 'Application passwords let REST clients log in with basic auth, which is a brute-force surface most sites never use. Turn this off if a headless front end or an integration authenticates this way.', 'low-profile' ),
			),
			'rest_users'            => array(
				'label' => __( 'Hide the REST users list from visitors', 'low-profile' ),
				'desc'  => __( '/wp-json/wp/v2/users lists every account and its login slug to anyone. Logged-in users keep the endpoint, so the editor still works.', 'low-profile' ),
			),
			'author_archives'       => array(
				'label' => __( 'Redirect author archives to the home page', 'low-profile' ),
				'desc'  => __( 'Author archive URLs contain the login name. Leave this off if your site has real author pages.', 'low-profile' ),
			),
			'sitemap_users'         => array(
				'label' => __( 'Leave users out of the core sitemap', 'low-profile' ),
				'desc'  => __( 'Removes wp-sitemap-users-1.xml from the sitemap index.', 'low-profile' ),
			),
			'oembed_author'         => array(
				'label' => __( 'Strip the author from oEmbed responses', 'low-profile' ),
				'desc'  => __( 'Embedding a post elsewhere would otherwise hand over the author name and archive URL.', 'low-profile' ),
			),
			'login_errors'          => array(
				'label' => __( 'Use one login error message', 'low-profile' ),
				'desc'  => __( 'By default the login form says whether it was the username or the password that was wrong, and the lost-password form says outright when no account matches. Both confirm which usernames exist. This applies to every login form, including a theme\'s own.', 'low-profile' ),
			),
			'login_message'         => array(
				'label' => __( 'Login error message', 'low-profile' ),
				'desc'  => '',
			),
			'file_edit'             => array(
				'label' => __( 'Disable the theme and plugin file editors', 'low-profile' ),
				'desc'  => __( 'Removes the editor capability through WordPress\'s own checks, so the switch holds even where the host\'s configuration sets DISALLOW_FILE_EDIT to false, as WP Engine\'s does. Code changes belong in version control, not in wp-admin.', 'low-profile' ),
			),
			'noindex'               => array(
				'label' => __( 'Never index non-production hosts', 'low-profile' ),
				'desc'  => __( 'When the site address ends with one of the suffixes below: noindex, nofollow on every page and response, a disallow-all robots.txt, and no sitemap. Deciding by hostname means a database copied from production to staging, or back, cannot carry the wrong indexing state with it.', 'low-profile' ),
			),
			'noindex_hosts'         => array(
				'label' => __( 'Non-production host suffixes', 'low-profile' ),
				'desc'  => __( 'One per line. A host matches when it ends with the suffix.', 'low-profile' ),
			),
		);

		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = array_merge( $field, $labels[ $key ] );
		}

		return $fields;
	}

	/**
	 * Section titles, in display order.
	 *
	 * @return array<string, string>
	 */
	public static function sections() {
		return array(
			'software'  => __( 'Software and version', 'low-profile' ),
			'discovery' => __( 'Discovery', 'low-profile' ),
			'remote'    => __( 'Remote publishing', 'low-profile' ),
			'users'     => __( 'User enumeration', 'low-profile' ),
			'login'     => __( 'Login form', 'low-profile' ),
			'files'     => __( 'File editing', 'low-profile' ),
			'indexing'  => __( 'Search engine indexing', 'low-profile' ),
		);
	}

	/**
	 * Every field's default value.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		$defaults = array();
		foreach ( self::schema() as $key => $field ) {
			$defaults[ $key ] = $field['default'];
		}

		return $defaults;
	}

	/**
	 * The saved settings, with defaults filled in for anything unset.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$saved = get_option( LOWPROFILE_OPTION, array() );

		return self::sanitize( is_array( $saved ) ? $saved : array(), true );
	}

	/**
	 * Sanitize a settings array. With $fill, missing keys take their defaults
	 * (reading); without it, missing checkboxes mean "unchecked" (saving).
	 *
	 * @param mixed $input Raw settings.
	 * @param bool  $fill  Fill missing keys from defaults.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input, $fill = false ) {
		$input  = is_array( $input ) ? $input : array();
		$fields = self::schema();
		$output = array();

		foreach ( $fields as $key => $field ) {
			// A non-scalar value (an array posted as field[]=x) is treated as absent.
			$present = array_key_exists( $key, $input ) && is_scalar( $input[ $key ] );

			switch ( $field['type'] ) {
				case 'checkbox':
					// wp_validate_boolean() reads "false" and "0" as false, so
					// WP-CLI `option patch` behaves the way people expect.
					$output[ $key ] = $present ? wp_validate_boolean( $input[ $key ] ) : ( $fill ? (bool) $field['default'] : false );
					break;
				case 'textarea':
					$output[ $key ] = $present ? sanitize_textarea_field( (string) $input[ $key ] ) : ( $fill ? $field['default'] : '' );
					break;
				default:
					$output[ $key ] = $present ? sanitize_text_field( (string) $input[ $key ] ) : ( $fill ? $field['default'] : '' );
			}
		}

		if ( '' === trim( (string) $output['login_message'] ) ) {
			$output['login_message'] = $fields['login_message']['default'];
		}

		return $output;
	}

	/**
	 * Write the defaults on activation so the screen shows real values and
	 * the option is autoloaded rather than looked up on every request. On a
	 * network activation, every site gets its row.
	 *
	 * @param bool $network_wide Whether the plugin is being network-activated.
	 */
	public static function activate( $network_wide = false ) {
		if ( $network_wide && is_multisite() ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( $site_id );
				self::write_defaults();
				restore_current_blog();
			}

			return;
		}

		self::write_defaults();
	}

	/**
	 * Add the option with defaults if the current site has none.
	 */
	private static function write_defaults() {
		if ( false === get_option( LOWPROFILE_OPTION, false ) ) {
			add_option( LOWPROFILE_OPTION, self::defaults() );
		}
	}

	/**
	 * Settings API wiring and the screen.
	 */
	public static function register_admin() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LOWPROFILE_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Register the option with the Settings API.
	 */
	public static function register_setting() {
		register_setting(
			'lowprofile',
			LOWPROFILE_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Settings → Low Profile.
	 */
	public static function add_page() {
		add_options_page(
			__( 'Low Profile', 'low-profile' ),
			__( 'Low Profile', 'low-profile' ),
			'manage_options',
			'low-profile',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'options-general.php?page=low-profile' ) ) . '">' . esc_html__( 'Settings', 'low-profile' ) . '</a>'
		);

		return $links;
	}

	/**
	 * The screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get();
		$fields   = self::fields();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Low Profile', 'low-profile' ); ?></h1>
			<p class="description" style="max-width: 60em;">
				<?php esc_html_e( 'Everything here is on by default except the author-archive redirect. Turn a guard off only when something on the site genuinely needs what it hides.', 'low-profile' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'lowprofile' ); ?>

				<?php foreach ( self::sections() as $section_key => $section_title ) : ?>
					<h2 class="title"><?php echo esc_html( $section_title ); ?></h2>
					<table class="form-table" role="presentation">
						<?php foreach ( $fields as $key => $field ) : ?>
							<?php
							if ( $field['section'] !== $section_key ) {
								continue;
							}
							$id   = 'lowprofile-' . $key;
							$name = LOWPROFILE_OPTION . '[' . $key . ']';
							?>
							<tr>
								<th scope="row">
									<?php if ( 'checkbox' === $field['type'] ) : ?>
										<?php echo esc_html( $field['label'] ); ?>
									<?php else : ?>
										<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
									<?php endif; ?>
								</th>
								<td>
									<?php if ( 'checkbox' === $field['type'] ) : ?>
										<label for="<?php echo esc_attr( $id ); ?>">
											<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
											<?php esc_html_e( 'Enabled', 'low-profile' ); ?>
										</label>
									<?php elseif ( 'textarea' === $field['type'] ) : ?>
										<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="6" cols="40" class="code"><?php echo esc_textarea( (string) $settings[ $key ] ); ?></textarea>
									<?php else : ?>
										<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>" class="regular-text" />
									<?php endif; ?>
									<?php if ( '' !== $field['desc'] ) : ?>
										<p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
									<?php endif; ?>
									<?php if ( 'file_edit' === $key && defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT && empty( $settings[ $key ] ) ) : ?>
										<p class="description"><?php esc_html_e( 'Already set by the site configuration, so the editors are disabled whatever this switch says.', 'low-profile' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>

			<h2 class="title"><?php esc_html_e( 'What this plugin does not do', 'low-profile' ); ?></h2>
			<p class="description" style="max-width: 60em;">
				<?php esc_html_e( 'It does not rate-limit logins, add a firewall, or set HTTP security headers such as Strict-Transport-Security and X-Frame-Options. Those belong to your host, CDN or web server, where they work whether or not WordPress renders the page.', 'low-profile' ); ?>
			</p>
		</div>
		<?php
	}
}
