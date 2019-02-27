<?php
/**
 * Class Google\WP_Feature_Policy\Admin\Screen
 *
 * @package   Google\WP_Feature_Policy
 * @copyright 2019 Google LLC
 * @license   GNU General Public License, version 2
 * @link      https://wordpress.org/plugins/feature-policy/
 */

namespace Google\WP_Feature_Policy\Admin;

use Google\WP_Feature_Policy\Policies;
use Google\WP_Feature_Policy\Policy;

/**
 * Class for the admin screen to control feature policies.
 *
 * @since 0.1.0
 */
class Screen {

	const PAGE_SLUG = 'feature_policies';

	/**
	 * Feature policies controller instance.
	 *
	 * @since 0.1.0
	 * @var Policies
	 */
	protected $policies;

	/**
	 * Constructor.
	 *
	 * Sets the feature policies controller instance.
	 *
	 * @since 0.1.0
	 *
	 * @param Policies $policies Feature policies controller instance.
	 */
	public function __construct( Policies $policies ) {
		$this->policies = $policies;
	}

	/**
	 * Registers the admin screen with WordPress.
	 *
	 * @since 0.1.0
	 */
	public function register() {
		// TODO: Consider creating `Policies_Option` class to handle this call, also to possibly add REST API support.
		add_action(
			'admin_init',
			function() {
				register_setting(
					self::PAGE_SLUG,
					Policies::OPTION_NAME,
					array(
						'type'              => 'object',
						'description'       => __( 'Enabled feature policies and their origins.', 'feature-policy' ),
						'sanitize_callback' => function( $value ) {
							// TODO: This is probably too basic.
							if ( ! is_array( $value ) ) {
								return array();
							}
							return $value;
						},
						'default'           => array(),
					)
				);
			}
		);

		add_action(
			'admin_menu',
			function() {
				$hook_suffix = add_options_page(
					__( 'Feature Policies', 'feature-policy' ),
					__( 'Feature Policies', 'feature-policy' ),
					'manage_options',
					self::PAGE_SLUG,
					array( $this, 'render' )
				);
				add_action(
					"load-{$hook_suffix}",
					function() {
						$this->add_settings_ui();
					}
				);
			}
		);

	}

	/**
	 * Renders the admin screen.
	 *
	 * @since 0.1.0
	 */
	public function render() {
		?>
		<style type="text/css">
			.external-link > .dashicons {
				font-size: 16px;
				text-decoration: none;
			}

			.external-link:hover > .dashicons,
			.external-link:focus > .dashicons {
				text-decoration: none;
			}
		</style>
		<div class="wrap">
			<h1><?php esc_html_e( 'Feature Policies', 'feature-policy' ); ?></h1>

			<p>
				<?php esc_html_e( 'Feature Policy grants you control over how certain browser APIs and web features act on your site.', 'feature-policy' ); ?>
				<?php
				printf(
					'<a class="external-link" href="%1$s" target="_blank">%2$s<span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
					esc_url( _x( 'https://developers.google.com/web/updates/2018/06/feature-policy', 'learn more link', 'feature-policy' ) ),
					esc_html__( 'Learn more about Feature Policy', 'feature-policy' ),
					/* translators: accessibility text */
					esc_html__( '(opens in a new tab)', 'feature-policy' )
				);
				?>
			</p>

			<form action="options.php" method="post" novalidate="novalidate">
				<?php settings_fields( self::PAGE_SLUG ); ?>
				<?php do_settings_sections( self::PAGE_SLUG ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Adds settings sections and fields.
	 *
	 * @since 0.1.0
	 */
	protected function add_settings_ui() {
		add_settings_section( 'default', '', null, self::PAGE_SLUG );

		$policies = $this->policies->get_all();
		$option   = $this->policies->get_option();

		foreach ( $policies as $policy ) {
			add_settings_field(
				$policy->name,
				$policy->title,
				function( $args ) use ( $policy, $option ) {
					$origin = isset( $option[ $policy->name ] ) ? $option[ $policy->name ][0] : $policy->default_origin;
					$this->render_field( $policy, $origin );
				},
				self::PAGE_SLUG,
				'default',
				array( 'label_for' => $policy->name )
			);
		}
	}

	/**
	 * Renders the UI field for determining the status of a feature policy.
	 *
	 * @since 0.1.0
	 *
	 * @param Policy $policy Feature policy definition.
	 * @param string $origin Origin set for the feature policy.
	 */
	protected function render_field( Policy $policy, $origin ) {
		$choices = array(
			Policy::ORIGIN_ANY  => __( 'Any', 'feature-policy' ),
			Policy::ORIGIN_SELF => __( 'Self', 'feature-policy' ),
			Policy::ORIGIN_NONE => __( 'None', 'feature-policy' ),
		);

		?>
		<select id="<?php echo esc_attr( $policy->name ); ?>" name="<?php echo esc_attr( Policies::OPTION_NAME . '[' . $policy->name . '][]' ); ?>">
			<?php
			foreach ( $choices as $value => $label ) {
				?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $origin, $value ); ?>>
					<?php echo esc_html( $label ); ?>
					<?php if ( $value === $policy->default_origin ) : ?>
						<?php esc_html_e( '(default)', 'feature-policy' ); ?>
					<?php endif; ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
	}
}
