<?php
/**
 * Real Estate main class
 *
 * @author    AyeCode Ltd
 * @package   Real_Estate_Directory
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Real_Estate_Directory class.
 */
final class Real_Estate_Directory {
	/**
	 * The single instance of the class.
	 *
	 * @since 2.0
	 */
	private static $instance = null;

	/**
	 * Save Search Notifications plugin main instance.
	 *
	 * @since 2.0
	 *
	 * @return Real_Estate_Directory instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Real_Estate_Directory ) ) {
			self::$instance = new Real_Estate_Directory;
			self::$instance->load_textdomain();

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'real_estate_directory_loaded' );
		}

		return self::$instance;
	}

	/**
	 * File includes.
	 *
	 * @return void
	 */
	public function includes(){
		// Blocks
		require_once( plugin_dir_path( REAL_ESTATE_DIRECTORY_PLUGIN_FILE ) . 'includes/blocks/class-geodir-widget-mortgage-calculator.php' );
		require_once( plugin_dir_path( REAL_ESTATE_DIRECTORY_PLUGIN_FILE ) . 'includes/blocks/class-geodir-widget-energy-rating.php' );
		require_once( plugin_dir_path( REAL_ESTATE_DIRECTORY_PLUGIN_FILE ) . 'includes/blocks/class-geodir-widget-walk-score.php' );
	}

	/**
	 * Handle actions and filters.
	 *
	 * @since 2.0
	 */
	private function init_hooks() {
		add_filter( 'geodir_get_widgets', array( $this, 'register_widgets' ), 41, 1 );
		add_filter( 'geodir_custom_fields_predefined', array( $this, 'add_predefined_fields' ), 10, 2 );
		add_filter( 'geodir_extra_custom_fields', array( $this, 'add_dummy_data_custom_fields' ), 10, 3 );
		add_filter( 'geodir_dummy_data_posts', array( $this, 'add_dummy_data_fields' ), 10, 2 );
	}

	/**
	 * Add dummy data fields to dummy posts.
	 *
	 * This method adds dummy data fields to the given dummy posts array based on the provided key.
	 * If the key is either 'property_sale' or 'property_rent', the following fields will be added to each dummy post:
	 * - energy_rating: An integer between 50 and 100.
	 * - virtual_tour: A URL to a Matterport virtual tour.
	 * - video: A URL to a YouTube video.
	 *
	 * @param array $dummy_posts The array of dummy posts.
	 * @param string $key The key used to determine whether to add the dummy fields.
	 *
	 * @return array The modified array of dummy posts.
	 */
	public function add_dummy_data_fields( $dummy_posts, $key ) {
		if ( ! empty( $dummy_posts ) && ( 'property_sale' === $key ||  'property_rent' === $key ) ) {
			foreach ( $dummy_posts as $i => $dummy_post ) {
				// add the energy rating (0-150) (EU one goes to 100 so lets keep it under that
				$dummy_post['energy_rating'] = rand(50, 100);

				// matterport
				$dummy_post['virtual_tour'] = 'https://my.matterport.com/show/?m=Zh14WDtkjdC&amp;play=1';

				// video
				$dummy_post['video'] = 'https://www.youtube.com/watch?v=-faXssUI7Lo';

				$dummy_posts[$i] = $dummy_post;
			}
		}
		return $dummy_posts;
	}

	/**
	 * Add dummy data custom fields.
	 *
	 * @param array $fields List of custom fields.
	 * @param string $post_type The post type.
	 * @param string $package_id The package ID.
	 *
	 * @return array List of custom fields with dummy data custom fields added.
	 */
	public function add_dummy_data_custom_fields( $fields, $post_type, $package_id ) {
		if ( ! empty( $_REQUEST['data_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = sanitize_key( $_REQUEST['data_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$package = $package_id == '' ? '' : array( $package_id );

			if ( 'property_sale' === $key ||  'property_rent' === $key ) {
				// virtual tour field
				$fields[] = array(
					'post_type'      => $post_type,
					'data_type'      => 'TEXT',
					'field_type'     => 'textarea',
					'admin_title'    => esc_attr__( 'Virtual Tour', 'real-estate-directory' ),
					'frontend_desc'  => esc_attr__( 'Add matterport.com or similar embed URL for 360 tours.', 'real-estate-directory' ),
					'frontend_title' => esc_attr__( 'Virtual Tour', 'real-estate-directory' ),
					'htmlvar_name'   => 'virtual_tour',
					'default_value'  => '',
					'is_active'      => '1',
					'option_values'  => '',
					'is_default'     => '0',
					'show_in'        => '[owntab]',
					'show_on_pkg'    => $package,
					'clabels'        => esc_attr__( 'Virtual Tour', 'real-estate-directory' ),
					'extra'       => array(
						'embed' => 1
					)
				);
			}
		}

		return $fields;
	}

	/**
	 * Add predefined fields for a specific post type.
	 *
	 * @param $custom_fields
	 *
	 * @return array The array of predefined fields.
	 */
	public function add_predefined_fields( $custom_fields ) {
		// virtual_tour
		$custom_fields['virtual_tour'] = array( // The key value should be unique and not contain any spaces.
			'field_type'  => 'textarea',
			'class'       => 'gd-virtual-tour',
			'icon'        => 'fas fa-globe',
			'name'        => esc_attr__( 'Virtual Tour', 'real-estate-directory' ),
			'description' => esc_attr__( 'Adds a matterport.com or similar 360 tour embed code input.', 'real-estate-directory' ),
			'defaults'    => array(
				'data_type'          => 'TEXT',
				'admin_title'        => 'Virtual Tour',
				'frontend_title'     => 'Virtual Tour',
				'frontend_desc'      => esc_attr__( 'Add matterport.com or similar embed code for 360 tours', 'real-estate-directory' ),
				'htmlvar_name'       => 'virtual_tour',
				'is_active'          => true,
				'for_admin_use'      => false,
				'default_value'      => '',
				'show_in'            => '[owntab]',
				'is_required'        => false,
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-globe',
				'css_class'          => '',
				'cat_sort'           => false,
				'cat_filter'         => false,
				'extra_fields'       => array(
					'embed' => 1
				)
			)
		);

		// energy_rating
		$custom_fields['energy_rating'] = array( // The key value should be unique and not contain any spaces.
			'field_type'  => 'text',
			'class'       => 'gd-energy-rating',
			'icon'        => 'fas fa-chart-bar',
			'name'        => esc_attr__( 'Property Energy Rating', 'real-estate-directory' ),
			'description' => esc_attr__( 'Adds a input for a property energy rating. Use the GD > Energy Rating Chart block for output.', 'real-estate-directory' ),
			'defaults'    => array(
				'data_type'          => 'INT',
				'admin_title'        => esc_attr__( 'Property Energy Rating', 'real-estate-directory' ),
				'frontend_title'     => esc_attr__( 'Property Energy Rating', 'real-estate-directory' ),
				'frontend_desc'      => esc_attr__( 'Enter the energy rating score', 'real-estate-directory' ),
				'htmlvar_name'       => 'energy_rating',
				'is_active'          => true,
				'for_admin_use'      => false,
				'default_value'      => '',
				'show_in'            => '[detail],[listing]',
				'is_required'        => false,
				'required_msg'       => '',
				'field_icon'         => 'fas fa-chart-bar',
				'css_class'          => '',
				'cat_sort'           => true,
				'cat_filter'         => true,
			)
		);

		return $custom_fields;
	}


	/**
	 * Loads the plugin language files
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$locale = determine_locale();

		$locale = apply_filters( 'plugin_locale', $locale, 'real-estate-directory' );

		unload_textdomain( 'real-estate-directory', true );
		load_textdomain( 'real-estate-directory', WP_LANG_DIR . '/real-estate-directory/real-estate-directory-' . $locale . '.mo' );
		load_plugin_textdomain( 'real-estate-directory', false, basename( dirname( REAL_ESTATE_DIRECTORY_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function geodirectory_notice() {
		if ( ! class_exists( 'GeoDirectory' ) ) {
			echo '<div class="error"><p>' .  esc_html__( 'GeoDirectory plugin is required for the Real Estate Directory plugin to work properly.', 'real-estate-directory' ) . '</p></div>';
		}
	}

	/**
	 * Show a warning to sites running PHP < 7.0
	 *
	 * @static
	 * @access private
	 * @since 2.0
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . esc_html__( 'Your version of PHP is below the minimum version of PHP required by Real Estate Directory plugin . Please contact your host and request that your version be upgraded to 7.0 or later.', 'real-estate-directory' ) . '</p></div>';
	}

	/**
	 * Register widgets.
	 *
	 * @since 2.0
	 *
	 * @param array $widgets List of GD widgets.
	 * @return array GD widgets.
	 */
	public function register_widgets( $widgets ) {
		if ( geodir_design_style() ) {
			$widgets[] = 'GeoDir_Widget_Mortgage_Calculator';
			$widgets[] = 'GeoDir_Widget_Energy_Rating';
			$widgets[] = 'GeoDir_Widget_Walk_Score';
		}

		return $widgets;
	}
}
