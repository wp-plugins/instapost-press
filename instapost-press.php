<?php
/*  
Plugin Name: InstaPost Press
Plugin URI: http://www.polevaultweb.com/instapost-press/  
Description: Plugin for automatic posting of Instagram images into a WordPress blog.
Author: Polevaultweb 
Version: 1.0 
Author URI: http://www.polevaultweb.com 


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

define('IPP_VERSION', '1.0');


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
			//register the listener function
			add_action( 'pre_get_posts', get_class()  . '::auto_post_images');	
			
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
		
		/* Check Instagram details and reply data for feed section */ 
		public static function admin_feed(){
			
			$insusername = get_option('ipp_insusername');
			$inspassword = get_option('ipp_inspassword');
			
			if ($insusername.$inspassword != '') :
				
				//Instagram settings filled out, make authorise
				$auth_msg = self::auth_instagram();
				
				if ( $auth_msg  == 'success') :
					
					//return feed
					$images = self::feed_data(null);
					self::return_admin_feed($images);
				
				else :
					
					//return issue
					echo '<div class="error"><p><strong>'.$auth_msg.'</strong></p></div>';
			
				endif;
				
			else :
				
				//Username and password not properly filled out
				echo '<div class="updated"><p><strong>Please enter your Instagram Username and Password</strong></p></div>';
			
			endif;
			
		}
		
		/* Authorise Instagram settings to get auth token */
		public static function auth_instagram() {
		
		
			$insusername = get_option('ipp_insusername');
			$inspassword = get_option('ipp_inspassword');
	
			$response = wp_remote_post("https://api.instagram.com/oauth/access_token", array(
					'body' => array(
					'username' => $insusername,
					'password' => $inspassword,
					'grant_type' => 'password',
					'client_id' => self::$clientid,
					'client_secret' => self::$cliendsecret
									)
							)
						);
			
			if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
			
				$auth = json_decode($response['body']);
				
				if(isset($auth->access_token)):
					$access_token = $auth->access_token;
					update_option('ipp_accesstoken', $access_token);
					return 'success';
				else:
					return 'Instagram: '.$auth->error_message;
				endif;
			else:
				
				$auth = json_decode($response['body']);
				return 'Instagram: '.$auth->error_message;
			
			endif;
		
		}
		
		/* Instagram Admin feed array */
		public static function feed_data($manuallstid) {
		
			$access_token = get_option('ipp_accesstoken');
		
			$images = array();
			
			if ($manuallstid == null) :
				
				//do not add minId parameter
				$min_id = '';
				
			else :
			
				//add min_id param
				$min_id = "&min_id=".$manuallstid;
				
			endif;
			
			
			if($access_token != null):
			$response = wp_remote_get("https://api.instagram.com/v1/users/self/feed?access_token=".$access_token.$min_id);
						
			if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
				$data = json_decode($response['body']);
				if($data->meta->code == 200):
					
							
					foreach($data->data as $item):
										
						$images[] = array(
							"id" => $item->id,
							"title" => (isset($item->caption->text)?filter_var($item->caption->text, FILTER_SANITIZE_STRING):""),
							"image_small" => $item->images->thumbnail->url,
							"image_middle" => $item->images->low_resolution->url,
							"image_large" => $item->images->standard_resolution->url
						);				
		
					endforeach;
						
					return $images;
					
						
					endif;
				endif;
			endif;
	
		}
		
		/* Return HTML for admin feed */
		public static function return_admin_feed($images) {
		
		
			//get count of array of images
			$count = sizeof($images);
			
			//loop through array to get image data
			for ($i = 0; $i < $count; $i++) {
					
				echo '<div class="image_left"><img src="'.$images[$i]["image_small"].'" width="150px" height="150px" alt="'.$images[$i]["title"].'" /> </div>';
				echo '<div class="image_right"><b>'.$images[$i]["title"].'</b></br>';
				echo $images[$i]["id"].'</div>';
			
			}
			echo '<div class="clear"></div>';
		
		}
		
		/* Return last image ID count of feed */
		public static function return_last_id($images) {
		
			return $images[0]["id"];
		
		}
		
		/* Return last image ID count of feed */
		public static function check_get_last_id() {
		
			//Instagram settings filled out, make authorise
			$auth_msg = self::auth_instagram();
				
			if ( $auth_msg  == 'success') :
				
				//get feed of images
				$images = self::feed_data(null);
			
				$oldid = get_option('ipp_manuallstid');
				if ($oldid == '') :
					
					//return last id of feed
					return self::return_last_id($images);
				
				else :
					
					//return saved id
					return $oldid;
					
				endif;	
			
			else :
				
				//return blank id
				return '';
		
			endif;
		
		}
		
		/* Main function to post Instagram images */
		public static function auto_post_images() {
		
			//get current last id
			$manuallstid = get_option('ipp_manuallstid');
			
			//get images array
			$images = self::feed_data($manuallstid);
			
			//get count of array of images
			$count = sizeof($images);
			
			//set counter
			$last_id = 0;
			
			//loop through array to get image data
			for ($i = 0; $i < $count; $i++) {
					
				//get image variables
				$title = $images[$i]["title"];
				$image = $images[$i]["image_large"];
				
				$last_id = $images[$i]["id"];
							
				//post new images to wordpress
				self::blog_post($title,$image);
			
			}
			
			if ($last_id != 0)
			{
				//update last id field in database with last id of image added
				update_option('ipp_manuallstid', self::return_last_id($images));
			}
					
		}
		
		/* Posting to WordPress */
		public static function blog_post($post_title, $post_image) {

			$postcats = get_option('ipp_postcats');
			$postauthor = get_option('ipp_postauthor');
			$imagesize = get_option('ipp_imagesize');
			$imageclass = get_option('ipp_imageclass');
	
			if ($imageclass != "")
			{
				$imageclass = 'class="'.$imageclass.'"';
			}
			
			// Create post object
		  	$my_post = array(
		     'post_title' => $post_title,
		     'post_content' => '<a href="'.$post_image.'" title="'.$post_title.'"><img src="'.$post_image.'" '.$imageclass.' alt="'.$post_title.'" width="'.$imagesize.'" height="'.$imagesize.'" /></a>',
		     'post_status' => 'publish',
		     'post_author' => $postauthor,
		     'post_category' => array($postcats)
		 	 );
		
			// Insert the post into the database
		  	wp_insert_post( $my_post );
			
		}	

		/* Plugin Settings page and settings data */
		public static function settings_page() {
								
			if($_POST['ipp_hidden'] == 'Y') {
				//Form data sent
			
				$insusername = $_POST['ipp_insusername'];
				update_option('ipp_insusername', $insusername);
				
				$inspassword = $_POST['ipp_inspassword'];
				update_option('ipp_inspassword', $inspassword);
				
				$manuallstid  = $_POST['ipp_manuallstid'];
				update_option('ipp_manuallstid', $manuallstid);
				
				$postcats  = $_POST['ipp_postcats'];
				update_option('ipp_postcats', $postcats);
				
				$postauthor  = $_POST['ipp_postauthor'];
				update_option('ipp_postauthor', $postauthor);
				
				$imagesize  = $_POST['ipp_imagesize'];
				update_option('ipp_imagesize', $imagesize);
				
				$imageclass  = $_POST['ipp_imageclass'];
				update_option('ipp_imageclass', $imageclass);
				//last id logic		
				$manuallstid = self::check_get_last_id();
												
				?>
				<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
				<?php
			} else {
				//Normal page display
				$insusername = get_option('ipp_insusername');
				$inspassword = get_option('ipp_inspassword');
				$postcats = get_option('ipp_postcats');
				$postauthor = get_option('ipp_postauthor');
				$imagesize = get_option('ipp_imagesize');
				$imageclass = get_option('ipp_imageclass');
				//last id logic		
				$manuallstid = self::check_get_last_id();										
				}
				?>
				
				<!-- BEGIN Wrap -->
				<div class="wrap">
				
					<?php echo "<h2>" . __( 'InstaPost Press Settings', 'ipp_trdom' ) . "</h2>"; ?>
					
								
					<!-- BEGIN ipp_content_left -->
					<div id="ipp_content_left" class="postbox-container">
						
						<!-- BEGIN metabox-holder -->
						<div class="metabox-holder">
						
							<!-- BEGIN meta-box-sortables ui-sortable -->
							<div class="meta-box-sortables ui-sortable">
							
				
								<form name="ipp_form" method="post" autocomplete="off" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
								<input type="hidden" name="ipp_hidden" value="Y">
						
								<!-- BEGIN instagram -->
								<div id="instagram" class="postbox">
								
									<div class="handlediv" title="Click to toggle">
										<br>
									</div>
									
									<?php echo "<h3 class='hndle'><span>" . __( 'Instagram Settings', 'ipp_trdom' ) . "</span></h3>"; ?>
									
										<!-- BEGIN inside -->
										<div class="inside">
							
											<p><label class="textinput">Username</label><input type="text" name="ipp_insusername" value="<?php echo $insusername; ?>" ></p>
											<p><label class="textinput">Password</label><input type="password" name="ipp_inspassword" value="<?php echo $inspassword; ?>"></p>
											
											<p>All Images after this ID will get auto posted. Amend to retrospectively post images from your feed.</p>
											<p><label class="textinput">Last Image ID</label><input type="text" name="ipp_manuallstid" value="<?php echo $manuallstid; ?>" ></p>
										
										<!-- END inside -->
										</div>
								
								<!-- END instagram -->
								</div>
						
								<!-- BEGIN wordpress -->
								<div id="wordpress" class="postbox">
								
									<div class="handlediv" title="Click to toggle">
										<br>
									</div>
						
									<?php echo "<h3 class='hndle'><span>" . __( 'WordPress Post Settings', 'ipp_trdom' ) . "</span></h3>"; ?>
									
									<!-- BEGIN inside -->
									<div class="inside">
									
										<p>Default settings for the posts created by the plugin</p>
						
										<p><label class="textinput">Image Size</label><input type="text" name="ipp_imagesize" value="<?php echo $imagesize; ?>" ></p>
										
										<p><label class="textinput">Image Class</label><input type="text" name="ipp_imageclass" value="<?php echo $imageclass; ?>" ></p>
						
										<p><label class="textinput">Post Category</label>
						
										 <?php $args = array(
										
										
										'selected'                => $postcats,
										'include_selected'        => true,
										'name'                    => 'ipp_postcats'
										);
										
										
										 
										  wp_dropdown_categories( $args ); ?> 
										</p>
										<p><label class="textinput">Post Author</label>
										<?php $args = array(
										
										
										'selected'                => $postauthor,
										'include_selected'        => true,
										'name'                    => 'ipp_postauthor'
										
										); 
										 wp_dropdown_users( $args ); ?> </p>
						 			
						 			<!-- END inside -->
						 			</div>
						
								<!-- END wordpress -->
								</div>
								
														
								<p class="submit">
								<input type="submit" class="button-primary" name="Submit" value="<?php _e('Update Options', 'ipp_trdom' ) ?>" />
							
								</p>
								</form>
								
								<!-- BEGIN credit -->
								<div id="credit" class="postbox">
								
									<div class="handlediv" title="Click to toggle">
										<br>
									</div>
						
									<?php echo "<h3 class='hndle'><span>" . __( 'Links', 'ipp_trdom' ) . "</span></h3>"; ?>
									
									<!-- BEGIN inside -->
									<div class="inside">
									
										<p>We hope you enjoy the plugin. Any issues please contact us.</p>
						
										<p></label><a href="http://www.polevaultweb.com/instapost-press/">Plugin Site</a></p>
						
										<p><a href="mailto:info@polevaultweb.com">Contact</a></p>
										
										<p><a href="http://twitter.com/#!/polevaultweb">Follow on Twitter</a></p>
						
										<a id="logo" href="http://www.polevaultweb.com/" title="Plugin by Polevaultweb" target="_blank"><img src="<?php echo plugins_url('',__FILE__); ?>/images/polevaultweb_logo.png" alt="polevaultweb logo" width="100" height="26" /></a>
																 			
						 			<!-- END inside -->
						 			</div>
						
								<!-- END credit -->
								</div>
							
							<!-- END meta-box-sortables ui-sortable -->
							</div>		
					
						<!-- END metabox-holder -->
						</div>
				
					<!-- END ipp_content_left -->
					</div>
					
					<!-- BEGIN ipp_content_right -->
					<div id="ipp_content_right" class="postbox-container">
					
						<!-- BEGIN metabox-holder -->
						<div class="metabox-holder">	
						
							<!-- BEGIN meta-box-sortables ui-sortable -->
							<div class="meta-box-sortables ui-sortable">
							
								<!-- BEGIN images -->
								<div id="images" class="postbox">
								
									<div class="handlediv" title="Click to toggle">
									<br>
									</div>
										
									<h3 class='hndle'><span>Instagram Feed</span></h3>
										
										<!-- BEGIN inside -->
										<div class="inside">
										<?php 
										
										//reply admin feed data
										self::admin_feed();
										?>
										
										<!-- END inside -->
										</div>
										
								<!-- END images -->	
								</div>
								
							<!-- END meta-box-sortables ui-sortable -->
							</div>	
						
						<!-- END metabox-holder -->
						</div>
						
					<!-- END ipp_content_right -->	
					</div>
				
				
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