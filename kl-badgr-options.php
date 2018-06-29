<?php
/*
KL Badgr
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

// create custom plugin settings menu
add_action('admin_menu', 'klbadgr_plugin_create_menu');

function klbadgr_plugin_create_menu() {
    //create options page
    add_options_page('KL Badgr', 'KL Badgr', 'manage_options', __FILE__, 'klbadgr_plugin_settings_page' , __FILE__ );

    //call register settings function
    add_action( 'admin_init', 'register_klbadgr_plugin_settings' );	
}

function register_klbadgr_plugin_settings() {
    //register our settings
    register_setting( 'klbadgr-plugin-settings-group', 'klbadgr_token' );	    
    register_setting( 'klbadgr-plugin-settings-group', 'klbadgr_credentials' );	    
    register_setting( 'klbadgr-plugin-settings-group', 'klbadgr_badges' );	    
    register_setting( 'klbadgr-plugin-settings-group', 'klbadgr_admin_roles' );
}

function klbadgr_plugin_settings_page() {
?>
    <div class="wrap">
    <h1>KL Badgr</h1>

    <form method="post" action="options.php">
    <?php settings_fields( 'klbadgr-plugin-settings-group' ); ?>
    <?php do_settings_sections( 'klbadgr-plugin-settings-group' ); ?>
    <table class="form-table">
        
        <tr valign="top">
        <th scope="row">Badgr Token</th>
        <td>
        	<input type="text" name="klbadgr_token" value="<?php echo esc_attr( get_option('klbadgr_token') ); ?>" size="40" />
        	<p><small>Badgr API Token for authorisation.</small></p>
        </td>
        </tr>        
        
        <tr valign="top">
        <th scope="row">Badgr credentials</th>
        <td>
        	<input type="text" name="klbadgr_credentials" value="<?php echo esc_attr( get_option('klbadgr_credentials') ); ?>" size="60" />
        	<p><small>Badgr credential string e.g. username={username}&password={password} to use if Token not set.</small></p>
        </td>
        </tr>        
        
        <tr valign="top">
        <th scope="row">Badge ids</th>
        <td>
        	<input type="text" name="klbadgr_badges" value="<?php echo esc_attr( get_option('klbadgr_badges') ); ?>" size="90%"  />
        	<p><small>Badge entity_ids (comma-delimited). [Hard-coded instead of using API].</small></p>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Badge URLs</th>
        <td>
        	<textarea style="width: 100%; height: 10em;" name="klbadgr_urls"><?php echo esc_attr( get_option('klbadgr_urls') ); ?></textarea>
        	<p><small>Badge/URL mappings for criteria in JSON format.<br/>Example:<br/>{
"ZaVRytPWT72xw3zNRz9xJg": "https://educate.london/test-badge/",
"FQLl1REwQ7qiOSzUKYg0rA": "https://educate.london/test-badge-2/"
}<br/> [Hard-coded instead of using API].</small></p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row">Admin roles</th>
        <td>
        	<input type="text" name="klbadgr_admin_roles" value="<?php echo esc_attr( get_option('klbadgr_admin_roles') ); ?>" size="90%"  />
        	<p><small>Roles that have admin-level permissions, e.g. to see a full listing of awards. Comma-delimited.</small></p>
        </td>
        </tr>

                            
    </table>
    
    <?php submit_button(); ?>
    </form>

</div>
<?php } 
