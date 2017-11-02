<?php
/*
Plugin Name: iCG Creator
Plugin URI: http://www.iconcept.com.ph
Description: Creates REST API for menu, pages and other settings for Ionic Development.
Version: 1.0
Author: Mark Angelo Urian
Author URI: mailto:urian.markangelo@gmail.com
*/

if (!defined('ABSPATH')) {
	die();
}

if ( ! function_exists( 'masurian_admin_assets' ) ) {


	function masurian_admin_assets()
	{
		$checked = '';
		if ( get_option('masurian_option_name')['post_type_enable'] == 'checked' ){
			$checked = 'checked';		
		} else {
			$checked = 'unchecked';
		}


		wp_register_style('masurian', plugin_dir_url(__FILE__). 'assets/css/masurian.css');
		wp_enqueue_style('masurian');

		wp_register_script('masurian', plugin_dir_url(__FILE__). 'assets/js/masurian.js', array('jquery'), null, true);
		wp_localize_script( 'masurian', 'masurian_check', $checked );
		wp_enqueue_script( 'masurian' );


	}

	add_action('admin_enqueue_scripts', 'masurian_admin_assets');

}

// Create Mobile Menu
if ( ! function_exists( 'add_my_menu_for_mobile' ) ) {
	function add_my_menu_for_mobile(){
		register_nav_menus( array(
			'mobile_menu'   => __( 'Mobile Menu', 'masurian' ),
		) );
	}
	add_action( 'after_setup_theme', 'add_my_menu_for_mobile', 100 );
}

//Mobile Menu Lists -  Return Data
if ( ! function_exists( 'get_mobile_menu' ) ) {


	function get_mobile_menu($data){
		$locations = get_nav_menu_locations();
		$menu = wp_get_nav_menu_object( $locations[ 'mobile_menu' ] );
		$menu_items = wp_get_nav_menu_items($menu->term_id);

		return $menu_items;
	}


}

//Homepage Contents - Return Data
if ( ! function_exists('get_homepage_content') ){
	function get_homepage_content($data){
		if ( get_option('masurian_option_name')['homepage_id'] ){
			return get_post(get_option('masurian_option_name')['homepage_id']);
		}
	}
}


//News and Events Archive - Return Data
if ( ! function_exists('get_notifs_archive') ){
	function get_notifs_archive($data){
		$args = array(
			'post_type'			=> array('icg-news'),
			'posts_per_page'	=> -1,
		);

		if ( isset($_GET['page']) ){
			$args['paged'] = intval($_GET['page']);
		}

		$querys = new WP_Query( $args );

		$notifs_array = array();
		$counter = 0;
		if( $querys->have_posts() ){
			while( $querys->have_posts() ){
				$querys->the_post();
				$notifs_array[$counter]['ID'] = get_the_id();
				$notifs_array[$counter]['post_title'] = get_the_title();
				$notifs_array[$counter]['post_content'] = get_the_content(); 
				$notifs_array[$counter]['guid'] = get_permalink();
				
				$single_thumb = wp_prepare_attachment_for_js( get_post_thumbnail_id(get_the_ID()) ); 
				if ( !$single_thumb ) {
					$single_thumb = 'none';
				}else{
					unset( $single_thumb['authorName'] );
				}
				$notifs_array[$counter]['post_image'] = $single_thumb;
				// $notifs_array[$counter]['data']	= get_post(get_the_id());
				$counter++;
			}
		}

		return $notifs_array;
	}
}


//News and Events Single - Return Data
if ( !function_exists('get_notifs_single') ){
	function get_notifs_single($data){
		$post_id = $data['post_id'];

		if ( get_post_type($post_id) == 'icg-news' ){
			return get_post($post_id);
		} else {
			return new  WP_Error('rest_no_route', __( "No route was found matching the URL and request method" ), array('status' => 404));
		}

	}
}

// Create Post type for News and Events
if ( get_option('masurian_option_name')['post_type_enable'] == 'checked' ){
	if ( ! function_exists( 'masurian_custom_post_type' ) ) {
		function masurian_custom_post_type() {
			if ( get_option('masurian_option_name')['post_type_label'] && get_option('masurian_option_name')['post_type_enable'] == 'checked' ){
				$post_type_label = get_option('masurian_option_name')['post_type_label'];
			} else {
				$post_type_label = 'News and Events';
			}
			$labels = array(
				'name' 					=> $post_type_label,
				'singular_name'			=> $post_type_label,
				'menu_name' 			=> $post_type_label,
				'add_new' 				=> 'Add New',
				'add_new_item' 			=> 'Add New Post',
				'new_item' 				=> 'New Post',
				'edit_item' 			=> 'Edit Post',
				'view_item' 			=> 'View Post',
				'all_items' 			=> $post_type_label,
				'search_items' 			=> 'Search Post',
				// 'parent_item_colon' 	=> 'Parent Post Types:',
				'not_found' 			=> 'Nothing found.',
				'not_found_in_trash'	=> 'Nothing found in Trash.',
			);
			
			register_post_type(
				'icg-news', 
				array(
					'labels' 				=> $labels,
					'public' 				=> true,
					'publicly_queryable' 	=> true,
					'show_ui' 				=> true,
					'query_var' 			=> true,
					'show_in_menu'			=> true,
					'show_in_nav_menus'		=> true,
					'show_in_admin_bar'   	=> false,
					'can_export'			=> true,
					'exclude_from_search' 	=> false,
					'has_archive' 			=> true,
					'hierarchical'			=> false,
					'capability_type' 		=> 'post',
					'menu_icon'   			=> 'dashicons-media-document',
					'supports' 				=> array('title', 'editor', 'excerpt', 'thumbnail'),
				)
			);
		}
		add_action('init', 'masurian_custom_post_type', 100);
	}
}

// Create all custom REST API
if ( function_exists('rest_api_init') ){

	add_action( 'rest_api_init', function () {
		register_rest_route( 'icg/v2' , '/mobilemenu/', array(
			'methods' => 'GET',
			'callback' =>  'get_mobile_menu'
		) );

		register_rest_route( 'icg/v2' , '/homepagecontent/', array(
			'methods' => 'GET',
			'callback' =>  'get_homepage_content'
		) );

		register_rest_route( 'icg/v2' , '/notifs/', array(
			'methods' => 'GET',
			'callback' =>  'get_notifs_archive'
		) );

		register_rest_route( 'icg/v2' , '/notifs/(?P<post_id>[0-9]+)', array(
			'methods' => 'GET',
			'callback' =>  'get_notifs_single'
		) );

	} , 100 );


	
}

// Set up Settings Page
class MasurianSettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	* Add options page
	*/
    public function add_plugin_page()
	{
	// This page will be under "Settings"
		add_options_page(
			'Settings Admin', 
			'Ionic Settings', 
			'manage_options', 
			'masurian-setting-admin', 
			array( $this, 'create_admin_page' )
		);
	}

	/**
	* Options page callback
	*/
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option( 'masurian_option_name' );
		?>
		<div class="wrap masurian">
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'masurian_option_group' );
				do_settings_sections( 'masurian-setting-admin' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	* Register and add settings
	*/
	public function page_init()
	{        
		register_setting(
			'masurian_option_group', // Option group
			'masurian_option_name', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'Ionic Settings', // Title
			array( $this, 'print_section_info' ), // Callback
			'masurian-setting-admin' // Page
		);    

		add_settings_field(
			'existing_url',  // ID
			'Main Website URL<span style="color: red;">*</span>',  // Title
			array( $this, 'existing_url_callback' ),  // Callback
			'masurian-setting-admin',  // Page
			'setting_section_id' // Section
		); 

		add_settings_field(
			'homepage_id',  // ID
			'Select Homepage<span style="color: red;">*</span>',  // Title
			array( $this, 'homepage_callback' ),  // Callback
			'masurian-setting-admin',  // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'post_type_enable',  // ID
			'News and Events Archive',  // Title
			array( $this, 'post_type_callback' ),  // Callback
			'masurian-setting-admin',  // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'post_type_label',  // ID
			'News and Events Label',  // Title
			array( $this, 'post_type_label_callback' ),  // Callback
			'masurian-setting-admin',  // Page
			'setting_section_id' // Section
		);
	}

	/**
	* Sanitize each setting field as needed
	*
	* @param array $input Contains all settings fields as array keys
	*/
	public function sanitize( $input )
	{
		$new_input = array();

		if( isset( $input['existing_url'] ) )
			$new_input['existing_url'] = esc_url_raw( $input['existing_url'] );

		if( isset( $input['homepage_id'] ) )
			$new_input['homepage_id'] = absint( $input['homepage_id'] );

		if( isset( $input['post_type_enable'] ) )
			$new_input['post_type_enable'] = $input['post_type_enable'];

		if( isset( $input['post_type_label'] ) )
			$new_input['post_type_label'] = sanitize_text_field ( $input['post_type_label'] );

		return $new_input;
	}

	/** 
	* Print the Section text
	*/
	public function print_section_info()
	{
		print '<span style="color: red;">*</span>Please fill the required fields:';
	}

	public function existing_url_callback(){
		printf(
			'<input type="url" name="masurian_option_name[existing_url]" value="%s" class="regular-text" required />',
			isset( $this->options['existing_url'] ) ? esc_attr( $this->options['existing_url']) : ''
		);
	}

	public function homepage_callback()
	{
		$page_args = array(
			'sort_order' => 'asc',
			'sort_column' => 'post_title',
			'hierarchical' => 1,
			'exclude' => '',
			'include' => '',
			'meta_key' => '',
			'meta_value' => '',
			'authors' => '',
			'child_of' => 0,
			'parent' => -1,
			'exclude_tree' => '',
			'number' => '',
			'offset' => 0,
			'post_type' => 'page',
			'post_status' => 'publish'
		); 
		$all_pages = get_pages($page_args); 
		if ( $all_pages ){
			if ( isset( $this->options['homepage_id'] ) ){
				$current_id = $this->options['homepage_id']; 
			} else {
				$current_id = 0; 
			}
			echo '<select name="masurian_option_name[homepage_id]" class="regular-text" required>';
				foreach ( $all_pages as $all_page ){
					if ( isset( $this->options['homepage_id'] ) ){
						if ( $current_id == $all_page->ID ){
							echo '<option value="'.$all_page->ID.'" selected>'.$all_page->post_title.'</option>';
						} else {
							echo '<option value="'.$all_page->ID.'">'.$all_page->post_title.'</option>';
						}
					} else {
						echo '<option value="'.$all_page->ID.'">'.$all_page->post_title.'</option>';
					}
				}
			echo '</select>';
		} else {
			echo 'Sorry, no posts matched your criteria!';
		}
	}

	public function post_type_callback()
	{
		$checked = '';
		if ( $this->options['post_type_enable'] == 'checked' ){
			$checked = 'checked';		
		} else {
			$checked = '';
		}

		echo '<input type="checkbox" name="masurian_option_name[post_type_enable]" id="post-type-1" value="checked" '.$checked.'><label for="post-type-1">Enable News Archive</label>';
	}

	public function post_type_label_callback()
	{
		echo '<input type="text" name="masurian_option_name[post_type_label]" placeholder="News and Events" data-post="news-1" class="regular-text" value="'.$this->options['post_type_label'].'">';
		echo '<h5 data-post="news-1" class="masurian-bold-weight" style="margin: 1em 4px;"><em>Default Name</em>: News and Events</h5>';

	}
}

if( is_admin() ){
	$my_settings_page = new MasurianSettingsPage();
}

//Force Redirect to main website
if ( ! function_exists('masurian_pre_get_posts_redirect') ){
	add_action( 'pre_get_posts', 'masurian_pre_get_posts_redirect', 200 );

	function masurian_pre_get_posts_redirect($query) {
			if ( $query->is_main_query() && !is_admin() && ( $query->is_home() || $query->is_feed() || $query->is_author() || $query->is_tag()|| $query->is_category() || $query->is_page() || $query->is_tax() || $query->is_singular() || $query->is_single()|| $query->is_post_type_archive()) ) :
				wp_redirect(get_option('masurian_option_name')['existing_url']);
				exit;
			endif;
			
			return $query;
	}
}
