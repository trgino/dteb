<?php

  /*
  Plugin Name: Disposable & Temporary Email Block
  Description: A simple tool for block disposable and temporary email address domains often used to register dummy users.
  Version: 0.1
  */
  
define('JEST_DTEB_PATH',plugin_dir_path( __FILE__ ));
define('JEST_DTEB_URL',plugin_dir_url( __FILE__ ));

function jest_dteb_check_email( $user_login, $user_email, $errors ){
	global $wpdb;
	$exp = explode('@',$user_email);
	if(isset($exp[1]) && !empty($exp[1]) && $wpdb->get_var( $wpdb->prepare( "select id from " . $wpdb->prefix . "dteb_list where domain = '%s'", $exp[1] ) )>0){
		$errors->add( 'invalid_email', __( '<strong>Hata</strong>: E-Posta adresi geçersiz.' ) );
	}
}
add_action( 'register_post', 'jest_dteb_check_email', 1, 3 );

function jest_dteb_update_list(){
	global $wpdb;
	$source = 'https://github.com/disposable/disposable-email-domains/raw/master/domains.json';
	$remote_source = wp_remote_get($source,array('sslverify' => false));
	if ( is_array( $remote_source ) && ! is_wp_error( $remote_source ) ) {
		$json = json_decode($remote_source['body'],true);
		if($json && !empty($json)){
			foreach($json as $j){
				if($wpdb->get_var("select id from " . $wpdb->prefix . "dteb_list where domain = '" . $j . "'")>0){
					//exists
				}else{
					$wpdb->insert($wpdb->prefix . 'dteb_list',array(
						'domain' => $j,
						'status' => 1,
					));
				}
			}
		}
	}
	if (! wp_next_scheduled ( 'jest_dteb_update_event' )) {
        wp_schedule_event( time(), 'daily', 'jest_dteb_update_event' );
    }
}
add_action( 'jest_dteb_update_event', 'jest_dteb_update_list');

function jest_dteb_database_table() {
    global $wpdb;
	
    $dteb_table = $wpdb->prefix . 'dteb_list';
	$charset_collate = $wpdb->get_charset_collate();
    if ( $wpdb->get_var("SHOW TABLES LIKE '{$dteb_table}'") != $dteb_table ) {

        $sql = "CREATE TABLE IF NOT EXISTS ". $dteb_table . " ( ";
        $sql .= "  id int(11) NOT NULL auto_increment, ";
        $sql .= "  domain varchar(255) NOT NULL, ";
        $sql .= "  status int(1) NOT NULL, ";
        $sql .= "  PRIMARY KEY  (id) "; 
        $sql .= ") " . $charset_collate . ";";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		if(dbDelta($sql)){
			jest_dteb_update_list();
		}
    }
}
register_activation_hook( __FILE__, 'jest_dteb_database_table' );