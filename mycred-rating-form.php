<?php
/**
 * Plugin Name: myCred for Rating Form
 * Plugin URI: http://mycred.me
 * Description: Allows you to reward users points for ratings.
 * Version: 1.3.5
 * Tags: mycred, points, rate
 * Author: mycred
 * Author URI: http://www.mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.8
 * Tested up to: WP 6.6.1
 * Text Domain: mycred_ratingform
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
if ( ! class_exists( 'myCRED_RatingForm' ) ) :
	final class myCRED_RatingForm {

		// Plugin Version
		public $version             = '1.3.5';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.2
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.2
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.2
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.2
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.2
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.2
		 */
		public function __construct() {

			$this->slug        = 'mycred-rating-form';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_ratingform';
			$this->plugin_name = 'myCRED for Rating Form';

			$this->define_constants();

			add_filter( 'mycred_setup_hooks',    array( $this, 'register_hook' ) );
			add_action( 'mycred_init',           array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks',    'mycred_rating_form_load_hook' );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.2
		 */
		public function define_constants() {

			$this->define( 'MYCRED_RATINGFORM_SLUG',  $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.2
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.2
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Register Hook
		 * @since 1.0
		 * @version 1.2
		 */
		public function register_hook( $installed ) {

			$installed['rating-form'] = array(
				'title'       => __( 'Rating Form', 'mycred_ratingform' ),
				'description' => __( 'Awards %_plural% to users who rate posts', 'mycred_ratingform' ),
				'callback'    => array( 'myCRED_Hook_Rating_Form' )
			);

			return $installed;

		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.2
		 */
		public function add_badge_support( $references ) {

			$references['rate_post'] = __( 'Rating (Rating Form)', 'mycred_ratingform' );

			return $references;

		}

	}
endif;

function mycred_ratingform_plugin() {
	return myCRED_RatingForm::instance();
}
mycred_ratingform_plugin();

/**
 * Rating Form Hook
 * @since 1.0
 * @version 1.2.1
 */
if ( ! function_exists( 'mycred_rating_form_load_hook' ) ) :
	function mycred_rating_form_load_hook() {

		if ( class_exists( 'myCRED_Hook_Rating_Form' ) ) return;

		class myCRED_Hook_Rating_Form extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'rating-form',
					'defaults' => array(
						'creds'    => 1,
						'log'      => '%plural% for rating',
						'limit'    => '0/x'
					)
				), $hook_prefs, $type );

			}

			/**
			 * Run
			 * This class method is fired of by myCRED when it's time to load all hooks.
			 * It should be used to "hook" into the plugin we want to add support for or the
			 * appropriate WordPress instances. Anything that must be loaded for this hook to work
			 * needs to be called here.
			 * @since 1.0
			 * @version 1.2
			 */
			public function run() {

				if ( $this->prefs['creds'] != 0 )
					add_action( 'rating_form_new_rating', array( $this, 'new_rating' ), 10, 3 );

			}

			/**
			 * New Rating
			 * @since 1.0
			 * @version 1.2.1
			 */
			public function new_rating( $rate_id, $post_id, $user_id ) {

				$data = array( 'ref_type' => 'post', 'rating' => $rate_id );

				// Award rating
				if ( $this->prefs['creds'] != 0 ) {

					// Can not award guests
					if ( ! is_user_logged_in() ) return;

					// Check for exclusion
					if ( $this->core->exclude_user( $user_id ) ) return;

					// Make sure this is unique event
					if ( $this->core->has_entry( 'rate_post', $post_id, $user_id, $data, $this->mycred_type ) ) return;

					// Execute
					if ( ! $this->over_hook_limit( '', 'rate_post', $user_id ) )
						$this->core->add_creds(
							'rate_post',
							$user_id,
							$this->prefs['creds'],
							$this->prefs['log'],
							$post_id,
							$data,
							$this->mycred_type
						);

				}

			}

			/**
			 * Preferences
			 * If the hook has settings, it has to be added in using this class method.
			 * @since 1.0
			 * @version 1.2.1
			 */
			public function preferences() {

				$prefs = $this->prefs;

?>
<div class="hook-instance">
	<label class="subheader"><?php esc_html_e( 'New Rating', 'mycred_ratingform' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" size="8" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'mycred_ratingform' ); ?></label>
				<?php 
				echo wp_kses( $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ),
						array(
							'div' => array(
								'class' => array()
							),
							'input' => array(
								'type' => array(),
								'size' => array(),
								'class' => array(),
								'name' => array(),
								'id' => array(),
								'value' => array()
							),
							'select' => array(
								'name' => array(),
								'id' => array(),
								'class' => array()
							),
							'option' => array(
								'value' => array(),
								'selected' => array()
							)
						)
					); 
					?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader" for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred_ratingform' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'post' ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php

			}

			/**
			 * Sanitise Preferences
			 * While myCRED does some basic sanitization of the data you submit in the settings,
			 * we do need to handle our hook limits since 1.6. If your settings contain a checkbox (or multiple)
			 * then you should also use this method to handle the submission making sure the checkbox values are
			 * taken care of.
			 * @since 1.0
			 * @version 1.2
			 */
			function sanitise_preferences( $data ) {

				if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['limit'] = $limit . '/' . $data['limit_by'];
					unset( $data['limit_by'] );
				}

				return $data;

			}

		}

	}
endif;
