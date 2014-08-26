<?php 
  
    /*
    Plugin Name: WordPres Persistent Login
    Plugin URI: 
    Description: Keep users logged into your website forever, unless they explicitly log out. Requires ACF.
    Author: B9 Media Ltd
    Version: 1.0.0
    Author URI: http://b9media.co.uk
    */
    
    
    /*  
    Copyright 2014 B9 Media Ltd  (email : info@b9media.co.uk)

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
    
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	 
	
	// check if ACF is active   
    if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) ) :
     
     	// register ACF field to store the users login key
		if(function_exists("register_field_group")) :
			register_field_group(array (
			'id' => 'acf_persistent-login',
			'title' => 'Persistent Login',
			'fields' => array (
				array (
					'key' => 'field_53f476cb9c562',
					'label' => 'Login Key',
					'name' => 'login_key',
					'type' => 'text',
					'instructions' => 'Login Key for Persistent Login Plugin to control users sessions. Please do not change.',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'ef_user',
						'operator' => '==',
						'value' => 'all',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'no_box',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
			));
		endif;
		
		
		
		// if the user isn't logged in, check for a valid cookie
	    function pl_login_check() {
	    
		    if( !is_user_logged_in() ) :
					
				// check if user has cookies
				if ( isset($_COOKIE['pl_i']) && isset($_COOKIE['pl_k']) ) :
					
					// query that checkes if the cookie key === db key
					$args = array(
						'meta_key' => 'login_key',
						'meta_value' => $_COOKIE['pl_k'],
						'meta_compare' => '=',
						'fields' => 'ID',
						'include' => $_COOKIE['pl_i']
					);
					$user_query = new WP_User_Query($args);
					
					// if there is a result
					if( $user_query->results[0] != '' || $user_query->results[0] != NULL ) :
														
						// get user id and login name
						$user_id = $user_query->results[0];
						$user_login = get_user_by( 'id', $user_id );
						
						// log the user in
						wp_set_current_user( $user_id, $user_login->user_login );
						wp_set_auth_cookie( $user_id );
						
						do_action( 'wp_login', $user_login->user_login );
					
					endif;
					
				endif; // end if cookies
							
			endif;
		}
		add_action('wp', 'pl_login_check');
		
		
		
		// remove the users cookie when they click logout
		function pl_remove_user_cookie() {
	
			unset($_COOKIE['pl_i']);
	        unset($_COOKIE['pl_k']);
	        setcookie('pl_i', null, -1, '/');
	        setcookie('pl_k', null, -1, '/');
		
		}
		add_action('wp_logout', 'pl_remove_user_cookie');
	    
	
		
		// when a user is logged in, reset their cookie
		function pl_set_user_cookie($user_login) {
					
				// if they have cookies, delete them
				if ( isset($_COOKIE['pl_i']) && isset($_COOKIE['pl_k']) ) :
		            unset($_COOKIE['pl_i']);
		            unset($_COOKIE['pl_k']);
				endif;
				
				$user_id = get_user_by( 'login', $user_login );
				
				// generate new key for user
				$salt = wp_generate_password(20); // 20 character "random" string
				$key = sha1($salt . uniqid(time(), true));
							
				// set new cookies
				setcookie("pl_i", $user_id->ID, time()+31536000);  /* expire in 1 year */
				
				setcookie("pl_k", $key, time()+31536000);  /* expire in 1 year */
				
				// update the db
				update_field('field_53f476cb9c562', $key, 'user_'.$user_id->ID.'');
		
		}
		add_action('wp_login', 'pl_set_user_cookie', 10, 1);



	else : 



		// if ACF isn't installed, give notice.
		function my_admin_notice() {
		    ?>
		    <div class="error">
		        <p>Please install/activate the Advanced Custom Fields plugin to allow WP Persistent Login to run.</p>
		    </div>
		    <?php
		}
		add_action( 'admin_notices', 'my_admin_notice' );


			
	endif;
	     
?>