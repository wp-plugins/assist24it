<?php 
/*
	Plugin Name: EzLab Assist24.it
	Plugin URI: http://www.assist24.it/wordpress-plugin
	Description: Adds Assist24.it assistance chat room
	Author: Mauro Cordioli
	Author URI: http://www.ezlab.it/
	Version: 20150401.2
	License: GPL v2
	Usage: Visit the "app.Assist24.it" options page to enter your Assist24.it ID and done.
	Tags: Assist24, chat, help desk, tickets, assistenza online, call me back
*/

// NO EDITING REQUIRED - PLEASE SET PREFERENCES IN THE WP ADMIN!

if (!defined('ABSPATH')) die();

// i18n
function elapp_i18n_init() {
	load_plugin_textdomain('elapp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'elapp_i18n_init');

$elapp_version = '20150310.1';
$elapp_plugin  = __('EzLab Assist24.it', 'elapp');
$elapp_options = get_option('elapp_options');
$elapp_path    = plugin_basename(__FILE__); // 'el-assist24/el-assist24.php';
$elapp_homeurl = 'http://www.assist24.it/wordpress-plugin/';

// minimum version of WordPress
function elapp_require_wp_version() {
	global $wp_version, $elapp_path, $elapp_plugin;
	if (version_compare($wp_version, '3.7', '<')) {
		if (is_plugin_active($elapp_path)) {
			deactivate_plugins($elapp_path);
			$msg =  '<strong>' . $elapp_plugin . '</strong> ' . __('requires WordPress 3.7 or higher, and has been deactivated!', 'elapp') . '<br />';
			$msg .= __('Please return to the ', 'elapp') . '<a href="' . admin_url() . '">' . __('WordPress Admin area', 'elapp') . '</a> ' . __('to upgrade WordPress and try again.', 'elapp');
			wp_die($msg);
		}
	}
}
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('admin_init', 'elapp_require_wp_version');
}

// Assist24.it Room code (elapp.js)
// 
function assist24_room_code() { 
	$options    = get_option('elapp_options');
    $elapp_enable       = $options['elapp_enable'];
	$elapp_id       = $options['elapp_id'];
	$elapp_custom   = $options['elapp_custom'];
 
 	
	if ($elapp_enable) {
	   ?>
<!--ASSIST24 WP pluging -->
<script>
(function(){ //v15.01
    var t = document.createElement("script");
    t.type="text/javascript"; t.async=true; elroomdone=false;
    t.src="//res.assist24.it/store/widget/noima.js";
	t.onload = t.onreadystatechange = function(){
        if (!elroomdone && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) {
            elroomdone=true;
            elroom.Init({RoomUid:'<?php echo $elapp_id; ?>'});
            t.onload = t.onreadystatechange = null;
            }
            };
            var n=document.getElementsByTagName("script")[0];
            n.parentNode.insertBefore(t,n);
            })();
</script>		 
	<?php 
		if (!empty($elapp_custom)) echo $elapp_custom . "\n";
	}	
}

// include tracking code in header or footer
if ($elapp_options['elapp_location'] == 'header') {
	add_action('wp_head', 'assist24_room_code');
} else {
	add_action('wp_footer', 'assist24_room_code');
}

// display settings link on plugin page
add_filter ('plugin_action_links', 'elapp_plugin_action_links', 10, 2);
function elapp_plugin_action_links($links, $file) {
	global $elapp_path;
	if ($file == $elapp_path) {
		$elapp_links = '<a href="' . get_admin_url() . 'options-general.php?page=' . $elapp_path . '">' . __('Settings', 'elapp') .'</a>';
		array_unshift($links, $elapp_links);
	}
	return $links;
}

// rate plugin link
function add_elapp_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$links[] = '<a href="' . $rate_url . '" target="_blank" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
	}
	return $links;
}
add_filter('plugin_row_meta', 'add_elapp_links', 10, 2);

// delete plugin settings
function elapp_delete_plugin_options() {
	delete_option('elapp_options');
}
if ($elapp_options['default_options'] == 1) {
	register_uninstall_hook (__FILE__, 'elapp_delete_plugin_options');
}


function elapp_a24block_func( $atts ) {
	$atts = shortcode_atts( array(
		'action' => 'chat',
		'width' => '400px',
		'height' => '400px'

	), $atts, 'a24block' );

	return '<div data-a24-block="'.$atts['action'].'"  width="'.$atts['width'].'" height="'.$atts['height'].'"></div>';
 
}
add_shortcode( 'a24block', 'elapp_a24block_func' );


// define default settings
register_activation_hook (__FILE__, 'elapp_add_defaults');
function elapp_add_defaults() {
	$tmp = get_option('elapp_options');
	if(($tmp['default_options'] == '1') || (!is_array($tmp))) {
		$arr = array(
			'default_options' => 0,
			'elapp_id'          => 'RMO-NNNN-ABCDEFGHIJKLMNOP',
			'elapp_enable'      => 0,
			'elapp_location'    => 'footer',			 
			'elapp_custom'      => '',
			 
			 
		);
		update_option('elapp_options', $arr);
	}
}

// whitelist settings
add_action ('admin_init', 'elapp_init');
function elapp_init() {
	register_setting('elapp_plugin_options', 'elapp_options', 'elapp_validate_options');
}

// sanitize and validate input
function elapp_validate_options($input) {
	global $elapp_location;

	if (!isset($input['default_options'])) $input['default_options'] = null;
	$input['default_options'] = ($input['default_options'] == 1 ? 1 : 0);

	if (!isset($input['elapp_enable'])) $input['elapp_enable'] = null;
	$input['elapp_enable'] = ($input['elapp_enable'] == 1 ? 1 : 0);

	$input['elapp_id'] = wp_filter_nohtml_kses($input['elapp_id']);

	if (!isset($input['elapp_location'])) $input['elapp_location'] = null;
	if (!array_key_exists($input['elapp_location'], $elapp_location)) $input['elapp_location'] = null;

	 
		
	
	// dealing with kses
	global $allowedposttags;
	$allowed_atts = array(
		'align'=>array(), 'class'=>array(), 'id'=>array(), 'dir'=>array(), 'lang'=>array(), 'style'=>array(), 'label'=>array(), 'url'=>array(), 
		'xml:lang'=>array(), 'src'=>array(), 'alt'=>array(), 'name'=>array(), 'content'=>array(), 'http-equiv'=>array(), 'profile'=>array(), 
		'href'=>array(), 'property'=>array(), 'title'=>array(), 'rel'=>array(), 'type'=>array(), 'charset'=>array(), 'media'=>array(), 'rev'=>array(),
		);
	$allowedposttags['strong'] = $allowed_atts;
	$allowedposttags['script'] = $allowed_atts;
	$allowedposttags['style'] = $allowed_atts;
	$allowedposttags['small'] = $allowed_atts;
	$allowedposttags['span'] = $allowed_atts;
	$allowedposttags['meta'] = $allowed_atts;
	$allowedposttags['item'] = $allowed_atts;
	$allowedposttags['base'] = $allowed_atts;
	$allowedposttags['link'] = $allowed_atts;
	$allowedposttags['abbr'] = $allowed_atts;
	$allowedposttags['code'] = $allowed_atts;
	$allowedposttags['div'] = $allowed_atts;
	$allowedposttags['img'] = $allowed_atts;
	$allowedposttags['h1'] = $allowed_atts;
	$allowedposttags['h2'] = $allowed_atts;
	$allowedposttags['h3'] = $allowed_atts;
	$allowedposttags['h4'] = $allowed_atts;
	$allowedposttags['h5'] = $allowed_atts;
	$allowedposttags['ol'] = $allowed_atts;
	$allowedposttags['ul'] = $allowed_atts;
	$allowedposttags['li'] = $allowed_atts;
	$allowedposttags['em'] = $allowed_atts;
	$allowedposttags['p'] = $allowed_atts;
	$allowedposttags['a'] = $allowed_atts;

	$input['elapp_custom'] = wp_kses($input['elapp_custom'], $allowedposttags);
	 
	
	return $input;
}

// define dropdown options
$elapp_location = array(
	'header' => array(
		'value' => 'header',
		'label' => __('Include code in the document head (via wp_head)', 'elapp')
	),
	'footer' => array(
		'value' => 'footer',
		'label' => __('Include code in the document footer (via wp_footer)', 'elapp')
	),
);

// add the options page
add_action ('admin_menu', 'elapp_add_options_page');
function elapp_add_options_page() {
	global $elapp_plugin;
	add_options_page($elapp_plugin, 'A24 Plugin', 'manage_options', __FILE__, 'elapp_render_form');
}

// create the options page
function elapp_render_form() {
	global $elapp_plugin, $elapp_options, $elapp_path, $elapp_homeurl, $elapp_version, $elapp_location; ?>

	<style type="text/css">
		.mm-panel-overview { padding-left: 100px; background: url(<?php echo plugins_url(); ?>/el-assist24/a24-logo.png) no-repeat 15px 0; }

		#mm-plugin-options h2 small { font-size: 60%; }
		#mm-plugin-options h3 { cursor: pointer; }
		#mm-plugin-options h4, 
		#mm-plugin-options p { margin: 15px; line-height: 18px; }
		#mm-plugin-options p.mm-alt { margin: 15px 0; }
		#mm-plugin-options .mm-item-caption { font-size: 11px; }
		#mm-plugin-options ul { margin: 15px 15px 15px 40px; }
		#mm-plugin-options li { margin: 10px 0; list-style-type: disc; }
		#mm-plugin-options abbr { cursor: help; border-bottom: 1px dotted #dfdfdf; }

		.mm-table-wrap { margin: 15px; }
		.mm-table-wrap td { padding: 15px; vertical-align: middle; }
		.mm-table-wrap .mm-table {}
		.mm-table-wrap .widefat td { vertical-align: middle; }
		.mm-table-wrap .widefat th { width: 25%; vertical-align: middle; }
		.mm-code { background-color: #fafae0; color: #333; font-size: 14px; }
		.mm-radio-inputs { margin: 7px 0; }
		.mm-radio-inputs span { padding-left: 5px; }

		#setting-error-settings_updated { margin: 10px 0; }
		#setting-error-settings_updated p { margin: 5px; }
		#mm-plugin-options .button-primary { margin: 0 0 15px 15px; }

		#mm-panel-toggle { margin: 5px 0; }
		#mm-credit-info { margin-top: -5px; }
		#mm-iframe-wrap { width: 100%; height: 250px; overflow: hidden; }
		#mm-iframe-wrap iframe { width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0; }
	</style>

	<div id="mm-plugin-options" class="wrap">
		<?php screen_icon(); ?>

		<h2><?php echo $elapp_plugin; ?> <small><?php echo 'v' . $elapp_version; ?></small></h2>
		<div id="mm-panel-toggle"><a href="<?php get_admin_url() . 'options-general.php?page=' . $elapp_path; ?>"><?php _e('Toggle all panels', 'elapp'); ?></a></div>

		<form method="post" action="options.php">
			<?php $elapp_options = get_option('elapp_options'); settings_fields('elapp_plugin_options'); ?>

			<div class="metabox-holder">
				<div class="meta-box-sortables ui-sortable">
					<div id="mm-panel-overview" class="postbox">
						<h3><?php _e('Overview', 'elapp'); ?></h3>
						<div class="toggle">
							<div class="mm-panel-overview">
								<p>
									<strong><?php echo $elapp_plugin; ?></strong> <?php _e('(A24 Plugin) adds Assist24.it Help Desk your WordPress site.', 'elapp'); ?>
									<?php _e('Enter your Assist24.it ID, save your options, and done. Log into your Aapp.ssist24.it to find your ID.', 'elapp'); ?>
								</p>
								<ul>
									<li><?php _e('To enter your Assist24.it ID, visit', 'elapp'); ?> <a id="mm-panel-primary-link" href="#mm-panel-primary"><?php _e('A24 Plugin Options', 'elapp'); ?></a>.</li>
									<li><?php _e('To restore default settings, visit', 'elapp'); ?> <a id="mm-restore-settings-link" href="#mm-restore-settings"><?php _e('Restore Default Options', 'elapp'); ?></a>.</li>
									<li><?php _e('For more information check the <code>readme.txt</code> and', 'elapp'); ?> <a href="<?php echo $elapp_homeurl; ?>" target="_blank"><?php _e('A24 Plugin Homepage', 'elapp'); ?></a>.</li>
									<li><?php _e('If you like this plugin, please', 'elapp'); ?> 
										<a href="http://wordpress.org/support/view/plugin-reviews/<?php echo basename(dirname(__FILE__)); ?>?rate=5#postform" title="<?php _e('Click here to rate and review this plugin on WordPress.org', 'elapp'); ?>" target="_blank">
											<?php _e('rate it at the Plugin Directory', 'elapp'); ?>&nbsp;&raquo;
										</a>
									</li>
								</ul>
								<p><small><?php _e('N Learn more at the official', 'elapp'); ?> 
												<a href="http://www.assist24.it/wordpress-plugin" target="_blank">A24 Plugin Homepage</a>
								</small></p>
							</div>
						</div>
					</div>
					<div id="mm-panel-primary" class="postbox">
						<h3><?php _e('A24 Plugin Options', 'elapp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p><?php _e('Enter your Tracking Code and enable/disable the plugin.', 'elapp'); ?></p>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="elapp_options[elapp_id]"><?php _e('A24 property ID', 'elapp') ?></label></th>
										<td><input type="text" size="64" maxlength="64" name="elapp_options[elapp_id]" value="<?php echo $elapp_options['elapp_id']; ?>" />
										
										<div class="mm-item-caption">
										
												 
														<a href="http://app.assist24.it/room" target="_blank">Find you Room Id</a>
											</div>
										</td>
									
									
									</tr>
									<tr valign="top">
										<th scope="row"><label class="description" for="elapp_options[elapp_enable]"><?php _e('Enable Assist24.it Room', 'elapp') ?></label></th>
										<td>
											<input name="elapp_options[elapp_enable]" type="checkbox" value="1" <?php if (isset($elapp_options['elapp_enable'])) { checked('1', $elapp_options['elapp_enable']); } ?> /> 
											<?php _e('Include the A24 Room Code in your web pages?', 'elapp') ?>
										</td>
									</tr>
									 
									 
									 
									<tr>
										<th scope="row"><label class="description" for="elapp_options[elapp_location]"><?php _e('Code Location', 'elapp'); ?></label></th>
										<td>
											<?php if (!isset($checked)) $checked = '';
												foreach ($elapp_location as $elapp_loc) {
													$radio_setting = $elapp_options['elapp_location'];
													if ('' != $radio_setting) {
														if ($elapp_options['elapp_location'] == $elapp_loc['value']) {
															$checked = "checked=\"checked\"";
														} else {
															$checked = '';
														}
													} ?>
													<div class="mm-radio-inputs">
														<input type="radio" name="elapp_options[elapp_location]" value="<?php esc_attr_e($elapp_loc['value']); ?>" <?php echo $checked; ?> /> 
														<span><?php echo $elapp_loc['label']; ?></span>
													</div>
											<?php } ?>
											<div class="mm-item-caption">
												<?php _e('Tip: Assist24.it for WordPress recommends including the Room Code in the document footer.
														If in doubt, try the other option!.', 'elapp'); ?>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="elapp_options[elapp_custom]"><?php _e('Custom Code', 'elapp'); ?></label></th>
										<td>
											<textarea type="textarea" rows="3" cols="50" name="elapp_options[elapp_custom]"><?php if (isset($elapp_options['elapp_custom'])) echo esc_textarea($elapp_options['elapp_custom']); ?></textarea>
											<div class="mm-item-caption"><?php _e('Here you may specify any markup to be displayed in the &lt;head&gt; section. Leave blank to disable.', 'elapp'); ?></div>
										</td>
									</tr>
									 
								</table>
							</div>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'elapp'); ?>" />
						</div>
					</div>
					<div id="mm-restore-settings" class="postbox">
						<h3><?php _e('Restore Default Options', 'elapp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p>
								<input name="elapp_options[default_options]" type="checkbox" value="1" id="mm_restore_defaults" <?php if (isset($elapp_options['default_options'])) { checked('1', $elapp_options['default_options']); } ?> /> 
								<label class="description" for="elapp_options[default_options]"><?php _e('Restore default options upon plugin deactivation/reactivation.', 'elapp'); ?></label>
							</p>
							<p>
								<small>
									<?php _e('<strong>Tip:</strong> leave this option unchecked to remember your settings. Or, to go ahead and restore all default options, check the box, save your settings, and then deactivate/reactivate the plugin.', 'elapp'); ?>
								</small>
							</p>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'elapp'); ?>" />
						</div>
					</div>
					<div id="mm-panel-current" class="postbox">
						<h3><?php _e('Updates &amp; Info', 'elapp'); ?></h3>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<div id="mm-iframe-wrap">
								<iframe src="http://www.assist24.it/"></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="mm-credit-info">
				<a target="_blank" href="<?php echo $elapp_homeurl; ?>" title="<?php echo $elapp_plugin; ?> Homepage"><?php echo $elapp_plugin; ?></a> by 
				<a target="_blank" href="http://twitter.com/mcordioli" title="Mauro Cordioli on Twitter">Mauro Cordioli</a> @ 
				<a target="_blank" href="http://www.ezlab.it/" title="Futurible Development">EzLab</a>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			// toggle panels
			jQuery('.default-hidden').hide();
			jQuery('#mm-panel-toggle a').click(function(){
				jQuery('.toggle').slideToggle(300);
				return false;
			});
			jQuery('h3').click(function(){
				jQuery(this).next().slideToggle(300);
			});
			jQuery('#mm-panel-primary-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-panel-primary .toggle').slideToggle(300);
				return true;
			});
			jQuery('#mm-restore-settings-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-restore-settings .toggle').slideToggle(300);
				return true;
			});
			// prevent accidents
			if(!jQuery("#mm_restore_defaults").is(":checked")){
				jQuery('#mm_restore_defaults').click(function(event){
					var r = confirm("<?php _e('Are you sure you want to restore all default options? (this action cannot be undone)', 'elapp'); ?>");
					if (r == true){  
						jQuery("#mm_restore_defaults").attr('checked', true);
					} else {
						jQuery("#mm_restore_defaults").attr('checked', false);
					}
				});
			}
		});
	</script>

<?php }
