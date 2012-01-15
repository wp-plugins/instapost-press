<?php
/*  
Plugin Name: InstaPost Press
Plugin URI: http://www.polevaultweb.com/instapost-press/  
Description: Plugin for automatic posting of Instagram images into a WordPress blog.
Author: polevaultweb 
Version: 1.2
Author URI: http://www.polevaultweb.com/


Copyright 2012  polevaultweb  (email : info@polevaultweb.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


if (!class_exists("InstaPost_Press")) {

	class InstaPost_Press {
	
	
		public static $clientid = '9079d268044542b1b262211338005b26';
		public static $cliendsecret = '6bf4eb8924dc4249b9537fc623852202';
		
		
		/* Plugin loading method */
		public static function load_instapost_press() {
			
			//settings menu
			add_action('admin_menu',get_class()  . '::register_settings_menu' );
			//settings link
			add_filter('plugin_action_links', get_class()  . '::register_settings_link', 10, 2 );
			//styles and scripts
			add_action('admin_print_styles', get_class()  . '::register_styles');
				
			
		}
		
		/* Add menu item for plugin to Settings Menu */
		public static function register_settings_menu() {  
   		  			
   			add_options_page( 'InstaPost Press', 'InstaPost Press', 1, 'instapostpress', get_class() . '::settings_page' );
	  				
		}

		/* Add settings link to Plugin page */
		public static function register_settings_link($links, $file) {  
   		  		
			static $this_plugin;
				if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
				 
				if ($file == $this_plugin){
				$settings_link = '<a href="options-general.php?page=instapostpress">' . __('Settings', 'instapostpress') . '</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;
			
	  				
		}
		
		/* Register custom stylesheets and script files */
		public static function register_styles() {
		 
			//register styles
			wp_register_style( 'ipp_style', plugins_url('css/style.css', __FILE__) );
	  		
	  		//enqueue styles	
	  		wp_enqueue_style('ipp_style' );
	  		wp_enqueue_style('dashboard');
			//enqueue scripts
			wp_enqueue_script('dashboard');
		
		}
		
		
		/* Plugin Settings page and settings data */
		public static function settings_page() {
								
			?>
		
				
				<!-- BEGIN Wrap -->
				<div class="wrap">
				
					<?php echo "<h2>" . __( 'InstaPost Press', 'ipp_trdom' ) . "</h2>"; ?>
					
					<div class="updated">
					<p>
					The InstaPost Press plugin has been superseded by <a href="http://wordpress.org/extend/plugins/instagrate-to-wordpress/">Instagrate to WordPress.</a>

Please deactivate, delete and install <a href="http://wordpress.org/extend/plugins/instagrate-to-wordpress/">Instagrate to WordPress.</a>
					</p></div>
					
									
				
				<!-- END wrap -->
				</div>
				
				<?php
			
		}
		
	}
 	
}	

if (class_exists("InstaPost_Press")) {

	// Load
	InstaPost_Press::load_instapost_press();
}

?>