<?php
/**
 * WordPress Installer
 *
 * @package WordPress
 * @subpackage Administration
 */

// Sanity check.
if ( false ) {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Error: PHP is not running</title>
</head>
<body>
	<h1 id="logo"><img alt="WordPress" src="images/wordpress-logo.png?ver=20120216" /></h1>
	<h2>Error: PHP is not running</h2>
	<p>WordPress requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
</body>
</html>
<?php
}

/**
 * We are installing WordPress.
 *
 * @since 1.5.1
 * @var bool
 */
define( 'WP_INSTALLING', true );

/** Load WordPress Bootstrap */
require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

/** Load WordPress Administration Upgrade API */
require_once( dirname( __FILE__ ) . '/includes/upgrade.php' );

/** Load wpdb */
require_once(dirname(dirname(__FILE__)) . '/wp-includes/wp-db.php');

$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 0;

/** Plugins to activate upon installation */
$default_plugins = array(
		'backwpup/backwpup.php',
		'better-wp-security/better-wp-security.php',
		'disable-pointers/disable-pointers.php',
		'wordpress-firewall-2/wordpress-firewall-2.php',
		'wordpress-importer/wordpress-importer.php'
		);

/**
 * {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID.
 */
function wp_install_defaults($user_id) {
	global $wpdb, $wp_rewrite, $current_site, $table_prefix;

	// Default category
	$cat_name = __('News');
	/* translators: Default category slug */
	$cat_slug = sanitize_title(_x('News', 'Default category slug'));

	if ( global_terms_enabled() ) {
		$cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
		if ( $cat_id == null ) {
			$wpdb->insert( $wpdb->sitecategories, array('cat_ID' => 0, 'cat_name' => $cat_name, 'category_nicename' => $cat_slug, 'last_updated' => current_time('mysql', true)) );
			$cat_id = $wpdb->insert_id;
		}
		update_option('default_category', $cat_id);
	} else {
		$cat_id = 1;
	}

	$wpdb->insert( $wpdb->terms, array('term_id' => $cat_id, 'name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0) );
	$wpdb->insert( $wpdb->term_taxonomy, array('term_id' => $cat_id, 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 1));
	$cat_tt_id = $wpdb->insert_id;

	// Default link category
	$cat_name = __('Blogroll');
	/* translators: Default link category slug */
	$cat_slug = sanitize_title(_x('Blogroll', 'Default link category slug'));

	if ( global_terms_enabled() ) {
		$blogroll_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
		if ( $blogroll_id == null ) {
			$wpdb->insert( $wpdb->sitecategories, array('cat_ID' => 0, 'cat_name' => $cat_name, 'category_nicename' => $cat_slug, 'last_updated' => current_time('mysql', true)) );
			$blogroll_id = $wpdb->insert_id;
		}
		update_option('default_link_category', $blogroll_id);
	} else {
		$blogroll_id = 2;
	}

	$wpdb->insert( $wpdb->terms, array('term_id' => $blogroll_id, 'name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0) );
	$wpdb->insert( $wpdb->term_taxonomy, array('term_id' => $blogroll_id, 'taxonomy' => 'link_category', 'description' => '', 'parent' => 0, 'count' => 7));
	$blogroll_tt_id = $wpdb->insert_id;

	if ( ! is_multisite() )
		update_user_meta( $user_id, 'show_welcome_panel', 0 );
	elseif ( ! is_super_admin( $user_id ) && ! metadata_exists( 'user', $user_id, 'show_welcome_panel' ) )
	update_user_meta( $user_id, 'show_welcome_panel', 0 );

	if ( is_multisite() ) {
		// Flush rules to pick up the new page.
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();

		$user = new WP_User($user_id);
		$wpdb->update( $wpdb->options, array('option_value' => $user->user_email), array('option_name' => 'admin_email') );

		// Remove all perms except for the login user.
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix.'user_level') );
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix.'capabilities') );

		// Delete any caps that snuck into the previously active blog. (Hardcoded to blog 1 for now.) TODO: Get previous_blog_id.
		if ( !is_super_admin( $user_id ) && $user_id != 1 )
			$wpdb->delete( $wpdb->usermeta, array( 'user_id' => $user_id , 'meta_key' => $wpdb->base_prefix.'1_capabilities' ) );
	}
}

/**
 * {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @since 2.1.0
 *
 * @param string $blog_title Blog title.
 * @param string $blog_url Blog url.
 * @param int $user_id User ID.
 * @param string $password User's Password.
 */
function wp_new_blog_notification($blog_title, $blog_url, $user_id, $password) {
	// do nothing, I don't want install notifications!
}

/**
 * Activate Default Plugins
 * 
 * After installation, this function activates the default plugins we want, saving us some time
 * 
 * @param string $plugin Plugin, e.g akismet/akismet.php
 * 
 * @author Philip John
 */
function run_activate_plugin( $plugin ) {
	$current = get_option( 'active_plugins' );
	$plugin = plugin_basename( trim( $plugin ) );
		
	if ( !in_array( $plugin, $current ) ) {
		$current[] = $plugin;
		sort( $current );
		do_action( 'activate_plugin', trim( $plugin ) );
		update_option( 'active_plugins', $current );
		do_action( 'activate_' . trim( $plugin ) );
		do_action( 'activated_plugin', trim( $plugin) );
	}
		
	return null;
}


/**
 * Display install header.
 *
 * @since 2.5.0
 * @package WordPress
 * @subpackage Installer
 */
function display_header() {
	header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'WordPress &rsaquo; Installation' ); ?></title>
	<?php wp_admin_css( 'install', true ); ?>
</head>
<body<?php if ( is_rtl() ) echo ' class="rtl"'; ?>>
<h1 id="logo"><img alt="WordPress" src="images/wordpress-logo.png?ver=20120216" /></h1>

<?php
} // end display_header()

/**
 * Display installer setup form.
 *
 * @since 2.8.0
 * @package WordPress
 * @subpackage Installer
 */
function display_setup_form( $error = null ) {
	global $wpdb;
	$user_table = ( $wpdb->get_var("SHOW TABLES LIKE '$wpdb->users'") != null );

	// Ensure that Blogs appear in search engines by default
	$blog_public = 1;
	if ( ! empty( $_POST ) )
		$blog_public = isset( $_POST['blog_public'] );

	$weblog_title = isset( $_POST['weblog_title'] ) ? trim( stripslashes( $_POST['weblog_title'] ) ) : '';
	$user_name = isset($_POST['user_name']) ? trim( stripslashes( $_POST['user_name'] ) ) : 'admin';
	$admin_password = isset($_POST['admin_password']) ? trim( stripslashes( $_POST['admin_password'] ) ) : '';
	$admin_email  = isset( $_POST['admin_email']  ) ? trim( stripslashes( $_POST['admin_email'] ) ) : '';

	if ( ! is_null( $error ) ) {
?>
<p class="message"><?php printf( __( '<strong>ERROR</strong>: %s' ), $error ); ?></p>
<?php } ?>
<form id="setup" method="post" action="install.php?step=2">
	<table class="form-table">
		<tr>
			<th scope="row"><label for="weblog_title"><?php _e( 'Site Title' ); ?></label></th>
			<td><input name="weblog_title" type="text" id="weblog_title" size="25" value="<?php echo esc_attr( $weblog_title ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="user_name"><?php _e('Username'); ?></label></th>
			<td>
			<?php
			if ( $user_table ) {
				_e('User(s) already exists.');
			} else {
				?><input name="user_name" type="text" id="user_login" size="25" value="<?php echo esc_attr( sanitize_user( $user_name, true ) ); ?>" />
				<p><?php _e( 'Usernames can have only alphanumeric characters, spaces, underscores, hyphens, periods and the @ symbol.' ); ?></p>
			<?php
			} ?>
			</td>
		</tr>
		<?php if ( ! $user_table ) : ?>
		<tr>
			<th scope="row">
				<label for="admin_password"><?php _e('Password, twice'); ?></label>
				<p><?php _e('A password will be automatically generated for you if you leave this blank.'); ?></p>
			</th>
			<td>
				<input name="admin_password" type="password" id="pass1" size="25" value="" />
				<p><input name="admin_password2" type="password" id="pass2" size="25" value="" /></p>
				<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
				<p><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th scope="row"><label for="admin_email"><?php _e( 'Your E-mail' ); ?></label></th>
			<td><input name="admin_email" type="text" id="admin_email" size="25" value="<?php echo esc_attr( $admin_email ); ?>" />
			<p><?php _e( 'Double-check your email address before continuing.' ); ?></p></td>
		</tr>
		<tr>
			<th scope="row"><label for="blog_public"><?php _e( 'Privacy' ); ?></label></th>
			<td colspan="2"><label><input type="checkbox" name="blog_public" value="1" <?php checked( $blog_public ); ?> /> <?php _e( 'Allow search engines to index this site.' ); ?></label></td>
		</tr>
	</table>
	<p class="step"><input type="submit" name="Submit" value="<?php esc_attr_e( 'Install WordPress' ); ?>" class="button" /></p>
</form>
<?php
} // end display_setup_form()

// Let's check to make sure WP isn't already installed.
if ( is_blog_installed() ) {
	display_header();
	die( '<h1>' . __( 'Already Installed' ) . '</h1><p>' . __( 'You appear to have already installed WordPress. To reinstall please clear your old database tables first.' ) . '</p><p class="step"><a href="../wp-login.php" class="button">' . __('Log In') . '</a></p></body></html>' );
}

$php_version    = phpversion();
$mysql_version  = $wpdb->db_version();
$php_compat     = version_compare( $php_version, $required_php_version, '>=' );
$mysql_compat   = version_compare( $mysql_version, $required_mysql_version, '>=' ) || file_exists( WP_CONTENT_DIR . '/db.php' );

if ( !$mysql_compat && !$php_compat )
	$compat = sprintf( __( 'You cannot install because <a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.' ), $wp_version, $required_php_version, $required_mysql_version, $php_version, $mysql_version );
elseif ( !$php_compat )
	$compat = sprintf( __( 'You cannot install because <a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires PHP version %2$s or higher. You are running version %3$s.' ), $wp_version, $required_php_version, $php_version );
elseif ( !$mysql_compat )
	$compat = sprintf( __( 'You cannot install because <a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires MySQL version %2$s or higher. You are running version %3$s.' ), $wp_version, $required_mysql_version, $mysql_version );

if ( !$mysql_compat || !$php_compat ) {
	display_header();
	die( '<h1>' . __( 'Insufficient Requirements' ) . '</h1><p>' . $compat . '</p></body></html>' );
}

if ( ! is_string( $wpdb->base_prefix ) || '' === $wpdb->base_prefix ) {
	display_header();
	die( '<h1>' . __( 'Configuration Error' ) . '</h1><p>' . __( 'Your <code>wp-config.php</code> file has an empty database table prefix, which is not supported.' ) . '</p></body></html>' );
}

switch($step) {
	case 0: // Step 1
	case 1: // Step 1, direct link.
	  display_header();
?>
<h1><?php _ex( 'Welcome', 'Howdy' ); ?></h1>
<p><?php printf( __( 'Welcome to the famous five minute WordPress installation process! You may want to browse the <a href="%s">ReadMe documentation</a> at your leisure. Otherwise, just fill in the information below and you&#8217;ll be on your way to using the most extendable and powerful personal publishing platform in the world.' ), '../readme.html' ); ?></p>

<h1><?php _e( 'Information needed' ); ?></h1>
<p><?php _e( 'Please provide the following information. Don&#8217;t worry, you can always change these settings later.' ); ?></p>

<?php
		display_setup_form();
		break;
	case 2:
		if ( ! empty( $wpdb->error ) )
			wp_die( $wpdb->error->get_error_message() );

		display_header();
		// Fill in the data we gathered
		$weblog_title = isset( $_POST['weblog_title'] ) ? trim( stripslashes( $_POST['weblog_title'] ) ) : '';
		$user_name = isset($_POST['user_name']) ? trim( stripslashes( $_POST['user_name'] ) ) : 'admin';
		$admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
		$admin_password_check = isset($_POST['admin_password2']) ? $_POST['admin_password2'] : '';
		$admin_email  = isset( $_POST['admin_email']  ) ?trim( stripslashes( $_POST['admin_email'] ) ) : '';
		$public       = isset( $_POST['blog_public']  ) ? (int) $_POST['blog_public'] : 0;
		// check e-mail address
		$error = false;
		if ( empty( $user_name ) ) {
			// TODO: poka-yoke
			display_setup_form( __('you must provide a valid username.') );
			$error = true;
		} elseif ( $user_name != sanitize_user( $user_name, true ) ) {
			display_setup_form( __('the username you provided has invalid characters.') );
			$error = true;
		} elseif ( $admin_password != $admin_password_check ) {
			// TODO: poka-yoke
			display_setup_form( __( 'your passwords do not match. Please try again' ) );
			$error = true;
		} else if ( empty( $admin_email ) ) {
			// TODO: poka-yoke
			display_setup_form( __( 'you must provide an e-mail address.' ) );
			$error = true;
		} elseif ( ! is_email( $admin_email ) ) {
			// TODO: poka-yoke
			display_setup_form( __( 'that isn&#8217;t a valid e-mail address. E-mail addresses look like: <code>username@example.com</code>' ) );
			$error = true;
		}

		if ( $error === false ) {
			$wpdb->show_errors();
			$result = wp_install($weblog_title, $user_name, $admin_email, $public, '', $admin_password);
			extract( $result, EXTR_SKIP );
			
?>

<h1><?php _e( 'Success!' ); ?></h1>

<p><?php _e( 'WordPress has been installed. Were you expecting more steps? Sorry to disappoint.' ); ?></p>

<table class="form-table install-success">
	<tr>
		<th><?php _e( 'Username' ); ?></th>
		<td><?php echo esc_html( sanitize_user( $user_name, true ) ); ?></td>
	</tr>
	<tr>
		<th><?php _e( 'Password' ); ?></th>
		<td><?php
		if ( ! empty( $password ) && empty($admin_password_check) )
			echo '<code>'. esc_html($password) .'</code><br />';
		echo "<p>$password_message</p>"; ?>
		</td>
	</tr>
</table>

<p class="step"><a href="../wp-login.php" class="button"><?php _e( 'Log In' ); ?></a></p>

<?php
		}
		// Now activate our default plugins
		foreach ($default_plugins as $plugin){
			run_activate_plugin($plugin);
		}
	break;
}
?>
<script type="text/javascript">var t = document.getElementById('weblog_title'); if (t){ t.focus(); }</script>
<?php wp_print_scripts( 'user-profile' ); ?>
</body>
</html>
