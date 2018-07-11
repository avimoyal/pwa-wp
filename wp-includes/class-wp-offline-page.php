<?php
/**
 * WP_Offline_Page class.
 *
 * @package PWA
 */

/**
 * WP_Offline_Page class.
 * Much of this is taken from wp-admin/privacy.php.
 */
class WP_Offline_Page {

	/**
	 * The option group for the offline page option.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'reading';

	/**
	 * The option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wp_offline_page';

	/**
	 * The ID of the settings section.
	 *
	 * @var string
	 */
	const SETTING_ID = 'pwa_offline_page';

	/**
	 * Inits the class.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_init', array( $this, 'settings_field' ) );
	}

	/**
	 * Registers the offline page setting.
	 */
	public function register_setting() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_callback' ),
			)
		);
	}

	/**
	 * Sanitize callback for the setting.
	 * Mainly taken from wp-admin/privacy.php.
	 *
	 * @todo: Ensure this is stored with a custom post status.
	 * @param string $raw_setting The setting before sanitizing it.
	 * @return string|null The sanitized setting, or null if it's invalid.
	 */
	public function sanitize_callback( $raw_setting ) {
		$sanitized_post_id = sanitize_text_field( $raw_setting );
		$offline_page      = get_post( $sanitized_post_id );
		if ( ! $offline_page instanceof WP_Post ) {
			add_settings_error(
				self::OPTION_NAME,
				self::OPTION_NAME,
				__( 'The current offline page does not exist. Please select or create one.', 'pwa' )
			);
		} elseif ( 'trash' === $offline_page->post_status ) {
			add_settings_error(
				self::OPTION_NAME,
				self::OPTION_NAME,
				__( 'The current offline page is in the trash. Please select or create one.', 'pwa' )
			);
		} else {
			return $sanitized_post_id;
		}
	}

	/**
	 * Adds a settings field to the 'Reading Settings' page.
	 */
	public function settings_field() {
		add_settings_field(
			self::SETTING_ID,
			__( 'Progressive Web App Offline Page', 'pwa' ),
			array( $this, 'settings_callback' ),
			self::OPTION_GROUP
		);
	}

	/**
	 * Outputs the settings section.
	 * Mainly taken from wp-admin/privacy.php.
	 */
	public function settings_callback() {
		if ( $this->has_pages() ) :
			?>
			<label for="<?php echo esc_attr( self::OPTION_NAME ); ?>">
				<?php esc_html_e( 'Select an existing page:', 'pwa' ); ?>
			</label>
			<?php
			wp_dropdown_pages(
				array(
					'name'              => esc_html( self::OPTION_NAME ),
					/* Translators: %1$s: A long dash */
					'show_option_none'  => sprintf( esc_html__( '%1$s Select %1$s', 'pwa' ), '&mdash;' ),
					'option_none_value' => '0',
					'selected'          => intval( get_option( self::OPTION_NAME ) ),
					'post_status'       => array( 'draft', 'publish' ),
				)
			);
		endif;
		?>
		<p>
			<span>
				<?php
				// @todo: Add the href value, to allow creating a new page.
				if ( $this->has_pages() ) {
					printf(
						// Translators: %s: A link to create a new page.
						esc_html__( 'or %s a new one.', 'pwa' ),
						sprintf( '<a href="%s">%s</a>', '#', esc_html__( 'create', 'pwa' ) )
					);
				} else {
					esc_html_e( 'There are no pages.', 'pwa' );
				}
				?>
			</span>
		</p>
		<?php
	}

	/**
	 * Whether the there are pages to display in the <select>
	 * Mainly taken from wp-admin/privacy.php
	 *
	 * @todo: Handle a case where there are no pages.
	 * @return boolean
	 */
	public function has_pages() {
		$query = new WP_Query( array(
			'post_type'      => 'page',
			'posts_per_page' => 1,
			'post_status'    => array(
				'publish',
				'draft',
			),
		) );
		return $query->found_posts > 0;
	}
}