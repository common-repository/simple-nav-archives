<?php
/*
Plugin Name: Simple Nav Archives
Plugin URI: http://www.philipwalton.com
Description: Allows you to group your archives by month and years with several customization options.
Version: 2.1.3
Author: Philip Walton
Author URI: http://wordpress.org/extend/plugins/profile/philipwalton
*/

add_action('widgets_init', create_function('', 'return register_widget("SimpleNavArchives");'));

class SimpleNavArchives extends WP_Widget {

	/** constructor */
	function SimpleNavArchives() {
		$this->WP_Widget( false, 'Simple Nav Archives', array( 'classname' => 'SimpleNavArchives', 'description' => 'Group your archives by month and year'));
	}

	/** @see WP_Widget::widget */
   function widget($args, $instance) {
		
		global $wpdb;
			
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <?php
							echo simple_nav_archives();
						
						?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
	function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
			return $instance;
		}

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

} // class FooWidget


$sna_options = array();

$sna_option_defaults = array(
	'show_year_count' => 'outside',
	'show_month_count' => 'outside',
	'show_all_years' => 'yes',
	'year_list_count' => '1',
	'expand_all_years' => 'one',
	'year_expand_count' => '1',
	'month_list_count' => '12',
	'year_order' => 'desc',
	'month_order' => 'desc',
	'year_pre_wrapper' => '<ul>',
	'year_pre_code1' => '<li>',
	'year_pre_code2' => '',
	'year_post_code1' => '',
	'year_post_code2' => '</li>',
	'year_post_wrapper' => '</ul>',
	'month_pre_wrapper' => '<ul>',
	'month_pre_code1' => '<li>&raquo;&nbsp;',
	'month_pre_code2' => '',
	'month_post_code1' => '',
	'month_post_code2' => '</li>',
	'month_post_wrapper' => '</ul>'
);

function simple_nav_archives() {
		
	global $wpdb, $sna_option_defaults, $blog_id;
	
	if (get_option('sna_options') ) {
	
		$sna_options = get_option('sna_options');
		
		if (count($sna_options) != 21) { // if not using the current version, then upgrade the options
			$sna_upgraded = true;
			$sna_options = array(
				'show_year_count' => ($sna_options["show_year_count"] == "1") ? "inside" : "no",
				'show_month_count' => ($sna_options["show_month_count"] == "1") ? "inside" : "no",
				'show_all_years' => ($sna_options['num_years'] == "all") ? "yes" : "one",
				'year_list_count' => $sna_options['num_years'],
				'expand_all_years' => ($sna_options['recent_months_only'] == "1") ? "one" : "yes",
				'year_expand_count' => "1",
				'month_list_count' => $sna_options["num_months"],
				'year_order' => ($sna_options['desc_order'] == "1") ? "desc" : "asc",
				'month_order' => ($sna_options['desc_order'] == "1") ? "desc" : "asc",
				'year_pre_wrapper' => "",
				'year_pre_code1' => $sna_options["year_pre_code1"],
				'year_pre_code2' => $sna_options["year_pre_code2"],
				'year_post_code1' => $sna_options["year_post_code1"],
				'year_post_code2' => $sna_options["year_post_code2"],
				'year_post_wrapper' => "",
				'month_pre_wrapper' => $sna_options["month_pre_wrapper"],
				'month_pre_code1' => $sna_options["month_pre_code1"],
				'month_pre_code2' => $sna_options["month_pre_code2"],
				'month_post_code1' => $sna_options["month_post_code1"],
				'month_post_code2' => $sna_options["month_post_code2"],
				'month_post_wrapper' => $sna_options["month_post_wrapper"]
			);
			delete_option('sna_options');
			add_option('sna_options', $sna_options, '', 'no');
		}
		$sna_options = stripslashes_array($sna_options);
	} else {
		$sna_options = $sna_option_defaults;
		add_option('sna_options', $sna_options, '', 'no');
		$sna_options = stripslashes_array($sna_options);
	}

	$yearLimit = ($sna_options['show_all_years'] == "yes") ? "" : " LIMIT " . $sna_options['year_list_count'];
	
	// Set the month limit based on the options
	if ($sna_options['expand_all_years'] == "one") {
		$monthLimit = " LIMIT " . $sna_options['month_list_count'];
	}
	
	$year_query = "SELECT DISTINCT YEAR(post_date) AS post_year, count(ID) as post_count FROM " . $wpdb->get_blog_prefix($blog_id) . "posts WHERE post_status = 'publish' AND post_type = 'post' GROUP BY YEAR(post_date) ORDER BY post_date DESC " . $yearLimit;
	$year_results = $wpdb->get_results($year_query);
	
	// if only somes years are to be listed, store those years in an array
	if ($sna_options['expand_all_years'] == "some") {
		$display_years = array();
		for ($i = 0; $i < (int) $sna_options['year_expand_count']; $i++) {
			$display_years[] = $year_results[$i]->post_year;
		}
	}
	
	$curYear = $year_results[0]->post_year;
	
	// if years are to be listed in ascending order
	if ($sna_options['year_order'] == "asc") {
		$year_results = array_reverse($year_results);
	}
	
	if ($year_results) {
		echo $sna_options['year_pre_wrapper'];
			foreach($year_results as $year_result) {
				
				// If and where to display the year post count
				if ($sna_options['show_year_count'] == "inside") {
					$year_count = ' (' . $year_result->post_count . ')';
					echo $sna_options['year_pre_code1'] . '<a href=' . get_year_link($year_result->post_year) . '>' . $sna_options['year_pre_code2'] . $year_result->post_year . $year_count . $sna_options['year_post_code1'] . '</a>';	
	
				} else if ($sna_options['show_year_count'] == "outside") {
					$year_count = ' (' . $year_result->post_count . ')';
					echo $sna_options['year_pre_code1'] . '<a href=' . get_year_link($year_result->post_year) . '>' . $sna_options['year_pre_code2'] . $year_result->post_year . $sna_options['year_post_code1'] . '</a>' . $year_count;
				} else {
					echo $sna_options['year_pre_code1'] . '<a href=' . get_year_link($year_result->post_year) . '>' . $sna_options['year_pre_code2'] . $year_result->post_year . $sna_options['year_post_code1'] . '</a>';
				}
				
				
				// decides whether to show the moths in this year or not
				if ($sna_options['expand_all_years'] == "yes") {
					$showMonths = true;
				} else if ($sna_options['expand_all_years'] == "no") {
					$showManths = false;
				} else if ($sna_options['expand_all_years'] == "some") {
					$showMonths = in_array($year_result->post_year, $display_years);
				} else if ($sna_options['expand_all_years'] == "one") {
					$showMonths = $year_result->post_year == $curYear;
				} else {
					$showMonths = false;
				}
							
				if ($showMonths) {
	
					$month_query = "SELECT MONTH( post_date ) AS post_month, count( ID ) AS post_count FROM " . $wpdb->get_blog_prefix($blog_id) . "posts WHERE post_status = 'publish' AND post_type = 'post' AND YEAR( post_date ) = " . $year_result->post_year . " GROUP BY MONTH( post_date ) ORDER BY post_date DESC" . $monthLimit;
					$month_results = $wpdb->get_results($month_query);
						
					// if months are to be listed in ascending order
					if ($sna_options['month_order'] == "asc") {
						$month_results = array_reverse($month_results);
					}

					if (count($month_results) > 0) {
					
						echo $sna_options['month_pre_wrapper'];
											
						foreach($month_results as $month_result) {				
							if ($sna_options['show_month_count'] == "inside") {
								$month_count = ' (' . $month_result->post_count . ')';
								echo $sna_options['month_pre_code1'] . '<a href=' . get_month_link($year_result->post_year, $month_result->post_month) . '>' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $month_count . $sna_options['month_post_code1'] . '</a>' . $sna_options['month_post_code2'];
							} else if ($sna_options['show_month_count'] == "outside") {
								$month_count = ' (' . $month_result->post_count . ')';
								echo $sna_options['month_pre_code1'] . '<a href=' . get_month_link($year_result->post_year, $month_result->post_month) . '>' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $sna_options['month_post_code1'] . '</a>' . $month_count . $sna_options['month_post_code2'];
							} else {
								echo $sna_options['month_pre_code1'] . '<a href=' . get_month_link($year_result->post_year, $month_result->post_month) . '>' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $sna_options['month_post_code1'] . '</a>' . $sna_options['month_post_code2'];
							}
						}
						echo $sna_options['month_post_wrapper'];
					}
				}
				echo $sna_options['year_post_code2'];
			}
		echo $sna_options['year_post_wrapper'];
	}
}

function sna_markup() {

	global $wpdb, $sna_options, $blog_id;

	$yearLimit = ($sna_options['show_all_years'] == "yes") ? "" : " LIMIT " . $sna_options['year_list_count'];
	
	// Set the month limit based on the options
	if ($sna_options['expand_all_years'] == "one") {
		$monthLimit = " LIMIT " . $sna_options['month_list_count'];
	}
	
	$year_query = "SELECT DISTINCT YEAR(post_date) AS post_year, count(ID) as post_count FROM " . $wpdb->get_blog_prefix($blog_id) . "posts WHERE post_status = 'publish' AND post_type = 'post' GROUP BY YEAR(post_date) ORDER BY post_date DESC " . $yearLimit;
	$year_results = $wpdb->get_results($year_query);
	
	// if only somes years are to be listed, store those years in an array
	if ($sna_options['expand_all_years'] == "some") {
		$display_years = array();
		for ($i = 0; $i < (int) $sna_options['year_expand_count']; $i++) {
			$display_years[] = $year_results[$i]->post_year;
		}
	}
	
	if ($year_results) {
	
		$curYear = $year_results[0]->post_year;
	
		// if years are to be listed in ascending order
		if ($sna_options['year_order'] == "asc") {
			$year_results = array_reverse($year_results);
		}
	
		if ($year_results) {
		
			$indent_count = 0;
			$indent_size = 2;
		
			if ($sna_options['year_pre_wrapper'] != "") {
				echo '<span style="padding-left: ' . ($indent_count * $indent_size) . 'em">' . ($sna_options['year_pre_wrapper']) . '</span>';
				$indent_count++;
			}
				foreach($year_results as $year_result) {
				
					// If and where to display the year post count
					echo '<span style="padding-left: ' . ($indent_count * $indent_size) . 'em">';
						if ($sna_options['show_year_count'] == "inside") {
							$year_count = ' (' . $year_result->post_count . ')';
							echo ($sna_options['year_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['year_pre_code2'] . $year_result->post_year . $year_count . $sna_options['year_post_code1'] . '&lt;/a&gt;');	
		
						} else if ($sna_options['show_year_count'] == "outside") {
							$year_count = ' (' . $year_result->post_count . ')';
							echo ($sna_options['year_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['year_pre_code2'] . $year_result->post_year . $sna_options['year_post_code1'] . '&lt;/a&gt;' . $year_count);
						} else {
							echo ($sna_options['year_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['year_pre_code2'] . $year_result->post_year . $sna_options['year_post_code1'] . '&lt;/a&gt;');
						}
					echo '</span>';
				
					// decides whether to show the moths in this year or not
					if ($sna_options['expand_all_years'] == "yes") {
						$showMonths = true;
					} else if ($sna_options['expand_all_years'] == "no") {
						$showManths = false;
					} else if ($sna_options['expand_all_years'] == "some") {
						$showMonths = in_array($year_result->post_year, $display_years);
					} else if ($sna_options['expand_all_years'] == "one") {
						$showMonths = $year_result->post_year == $curYear;
					} else {
						$showMonths = false;
					}				
	
					if ($showMonths) {
					
						$month_query = "SELECT MONTH( post_date ) AS post_month, count( ID ) AS post_count FROM wp_posts WHERE post_status = 'publish' AND post_type = 'post' AND YEAR( post_date ) = " . $year_result->post_year . " GROUP BY MONTH( post_date ) ORDER BY post_date DESC" . $monthLimit;
						$month_results = $wpdb->get_results($month_query);
						
						// if months are to be listed in ascending order
						if ($sna_options['month_order'] == "asc") {
							$month_results = array_reverse($month_results);
						}
					
						if (count($month_results) > 0) {
						
							if ($sna_options['month_pre_wrapper'] != "") {
								$indent_count++;
								echo '<span style="padding-left: ' . ($indent_count  * $indent_size) . 'em">' . ($sna_options['month_pre_wrapper']) . '</span>';
							}
									
							foreach($month_results as $month_result) {				
							
								echo '<span style="padding-left: ' . (($indent_count + 1) * $indent_size) . 'em">';
									if ($sna_options['show_month_count'] == "inside") {
										$month_count = ' (' . $month_result->post_count . ')';
										echo ($sna_options['month_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $month_count . $sna_options['month_post_code1'] . '&lt;/a&gt;' . $sna_options['month_post_code2']);
									} else if ($sna_options['show_month_count'] == "outside") {
										$month_count = ' (' . $month_result->post_count . ')';
										echo ($sna_options['month_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $sna_options['month_post_code1'] . '&lt;/a&gt;' . $month_count . $sna_options['month_post_code2']);
									} else {
										echo ($sna_options['month_pre_code1'] . '&lt;a href="..."&gt;' . $sna_options['month_pre_code2'] . date("F", mktime(0, 0, 0, $month_result->post_month, 1)) . $sna_options['month_post_code1'] . '&lt;/a&gt;' . $sna_options['month_post_code2']);
									}
								echo '</span>';
							}
							echo '<span style="padding-left: ' . ($indent_count  * $indent_size) . 'em">' . ($sna_options['month_post_wrapper']) . '</span>';
							if ($sna_options['month_pre_wrapper'] != "") {
								$indent_count--;	
							}
						}
					}
					echo '<span style="padding-left: ' . ($indent_count  * $indent_size) . 'em">' . ($sna_options['year_post_code2']) . '</span>';			
				}
			if ($sna_options['year_pre_wrapper'] != "") {
				$indent_count--;	
			}
			echo '<span style="padding-left: ' . ($indent_count * $indent_size) . 'em">' . ($sna_options['year_post_wrapper']) . '</span>';
		}
	} // end if ($year_results)
}

add_action('admin_menu', 'sna_menu');
add_action('network_admin_menu', 'sna_menu');

function stripslashes_array($a) {
	foreach ($a as $key => $ai) {
		$new_a[$key] = stripslashes($ai);
	}
	return $new_a;
}

function htmlentities_array($a) {
	foreach ($a as $key => $ai) {
		$new_a[$key] = htmlentities($ai, ENT_QUOTES);
	}
	return $new_a;
}

function sna_menu() {
	$sna_plugin_file = add_options_page('Simple Nav Archives', 'Simple Nav Archives', 'manage_options', __FILE__, 'sna_options');
	//add_action( 'admin_enqueue_scripts', 'sna_admin_enqueue_scripts' );
	
	add_thickbox();

	add_action( 'admin_print_styles', 'sna_admin_print_style');
	add_action( 'admin_notices', 'sna_admin_notices');
	add_action( 'network_admin_notices', 'sna_admin_notices');	
}

function sna_admin_notices() {
		
	global $status, $page, $s;

	$install_PW_Archives_link = esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=pw-archives&TB_iframe=true&width=600&height=550' ) );	
	
	$plugin_file = 'simple-nav-archives/simple_nav_archives.php';
	

	if ( is_multisite() ) {
		$deactivate_sna_link = wp_nonce_url('plugins.php?action=deactivate&amp;networkwide=1&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . $plugin_file);
	} else {
		$deactivate_sna_link = wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . $plugin_file);
	} // end if $screen->is_network
	$deactivate_sna_link = esc_url($deactivate_sna_link);
	
	
	// Wait $time_interval seconds before showing the alert again
	$time_interval = 60*60*24*3; // 3 days
		
	$sna_ignore = get_option('sna_ignore');
	if ($sna_ignore) {
		
		// if the sna_ignore option status is 'ignore' return without showing a warning
		if ($sna_ignore['status'] == 'ignore') {
			return;
		}
		
		if ($sna_ignore['status'] == 'remind') {
			
			// if the timestamp is less than one minute ago, return without showing a warning
			if (time() - $sna_ignore['timestamp'] < $time_interval) {
				return;
			} 
			// otherwise, remove the sna_ignore option from the database
			else {
				delete_option('sna_ignore');
			}
		}
	}

	if (isset($_POST['sna_ignore'])) {
		$ignore = $_POST['sna_ignore'];
		
		if ($ignore == "remind") {
			// echo "Remind me later";
			update_option('sna_ignore', array('status'=>'remind', 'timestamp'=>time()));
			return;
		}
		
		if ($ignore == "ignore") {
			update_option('sna_ignore', array('status'=>'ignore'));
			return;
		}
	}

	// if PW_Archives is not yet installed (this includes when you're on the page that installs PW_Archives)
	if ( file_exists(WP_PLUGIN_DIR . '/pw-archives/PW_Archives.php') ) {
	?>
		<div class="error action-links">
			<p><strong>WARNING:</strong> Simple Nav Archives is still active. Please <a href="<?php echo $deactivate_sna_link; ?>">deactivate it</a> and replace all occurrences with <a class="thickbox" title="More Information About PW_Archives" href="<?php echo $install_PW_Archives_link; ?>">PW_Archives</a> as soon as possible.</p>
		</div>
	<?php
	} else {
		// if you're on the page installing PW_Archives, show no warning
		if (isset($_GET['action']) && $_GET['action']=='install-plugin' && isset($_GET['plugin']) && $_GET['plugin']=='pw-archives') {
			return;
		}
	?>
		<div class="error action-links" style="padding-bottom:10px;">
			<p><span style="border-bottom:1px dotted #C33; color:#C33; display:block; font-weight:bold; font-size:14px; padding-bottom:5px;">WARNING:</stron></p>			
			<p>You're have an active plugin called "Simple Nav Archives" that is no longer being supported by its developer and may not work with future versions of WordPress. A replacement by the same developer is available called <a class="thickbox" title="More Information About PW_Archives" href="<?php echo $install_PW_Archives_link; ?>">PW_Archives</a>. It's faster, more efficient, and implements the latest WordPress security features. We strongly recommended that you <a href="<?php echo $deactivate_sna_link; ?>">deactivate Simple Nav Archives</a> and <a class="thickbox" title="More Information About PW_Archives" href="<?php echo $install_PW_Archives_link; ?>">install PW_Archives</a> as soon as possible.</p>
			<p style="padding-bottom:5px;">If you have any questions about switching, feel free to email <a href="mailto:philip@philipwalton.com?subject=Simple%20Nav%20Archives%20Upgrade">philip@philipwalton.com</a> for help.</p>
			<a style="color:#fff; padding:4px 11px" class="thickbox button-primary" title="More Information About PW_Archives" href="<?php echo $install_PW_Archives_link; ?>">Install PW_Archives</a>
			<form style="display:inline;" method="post" name="sna_options" target="_self">
				<input type="hidden" name="sna_ignore" value="remind">
				<input class="button" type="submit" name="ignore" value="Remind me later" />				
			</form>
			<form style="display:inline;" method="post" name="sna_options" target="_self">
				<input type="hidden" name="sna_ignore" value="ignore">
				<input class="button" type="submit" name="ignore" value="No thanks, I'll take my chances" />
			</form>
		</div>
	<?php
	}
}

function sna_admin_print_style() {
	$myStyleUrl = WP_PLUGIN_URL . '/simple-nav-archives/style.css';
	$myStyleFile = WP_PLUGIN_DIR . '/simple-nav-archives/style.css';
	if ( file_exists($myStyleFile) ) {
		wp_register_style('myStyleSheets', $myStyleUrl);
		wp_enqueue_style( 'myStyleSheets');
	}
}

function sna_options() {

	global $sna_options, $sna_option_defaults;
	
	if(isset($_POST['submit']) || isset($_POST['preview'])) {
		
		$sna_options = array(
			'show_year_count' => $_POST["show_year_count"],
			'show_month_count' => $_POST["show_month_count"],
			'show_all_years' => $_POST['show_all_years'],
			'year_list_count' => $_POST['year_list_count'],
			'expand_all_years' => $_POST["expand_all_years"],
			'year_expand_count' => $_POST["year_expand_count"],
			'month_list_count' => $_POST["month_list_count"],
			'year_order' => $_POST["year_order"],
			'month_order' => $_POST["month_order"],
			'year_pre_wrapper' => $_POST["year_pre_wrapper"],
			'year_pre_code1' => $_POST["year_pre_code1"],
			'year_pre_code2' => $_POST["year_pre_code2"],
			'year_post_code1' => $_POST["year_post_code1"],
			'year_post_code2' => $_POST["year_post_code2"],
			'year_post_wrapper' => $_POST["year_post_wrapper"],
			'month_pre_wrapper' => $_POST["month_pre_wrapper"],
			'month_pre_code1' => $_POST["month_pre_code1"],
			'month_pre_code2' => $_POST["month_pre_code2"],
			'month_post_code1' => $_POST["month_post_code1"],
			'month_post_code2' => $_POST["month_post_code2"],
			'month_post_wrapper' => $_POST["month_post_wrapper"]
		);
	} ?>
	
	<?php
	
	if (isset($_POST['submit'])) {
		update_option('sna_options', $sna_options);
		$sna_options = htmlentities_array(stripslashes_array($sna_options));	?>

		<div class="updated">
			<p><strong>
				<?php _e('Options Saved', 'mt_trans_domain' ); ?>
				</strong></p>
		</div>
	<?php  } else if (isset($_POST['preview'])) {
		
		$sna_options = htmlentities_array(stripslashes_array($sna_options)); ?>
		
		<div class="updated">
			<p><strong>
				<?php echo 'Markup preview refreshed: click the "Update" button to save changes'; ?>
				</strong></p>
		</div>
	<?php  } else if (isset($_POST['restore'])) {
		
		$sna_options = $sna_option_defaults;
		$sna_options = htmlentities_array(stripslashes_array($sna_options));	?>

		<div class="updated">
			<p><strong>
				<?php echo 'Defaults restored: click the "Update" button to save changes'; ?>
				</strong></p>
		</div>
	<?php  } else {
	
		if (get_option('sna_options') ) {
			$sna_options = get_option('sna_options');
		
			if (count($sna_options) != 21) { // if not using the current version, then upgrade the options
				$sna_upgraded = true;
				$sna_options = array(
					'show_year_count' => ($sna_options["show_year_count"] == "1") ? "inside" : "no",
					'show_month_count' => ($sna_options["show_month_count"] == "1") ? "inside" : "no",
					'show_all_years' => ($sna_options['num_years'] == "all") ? "yes" : "one",
					'year_list_count' => $sna_options['num_years'],
					'expand_all_years' => ($sna_options['recent_months_only'] == "1") ? "one" : "yes",
					'year_expand_count' => "1",
					'month_list_count' => $sna_options["num_months"],
					'year_order' => ($sna_options['desc_order'] == "1") ? "desc" : "asc",
					'month_order' => ($sna_options['desc_order'] == "1") ? "desc" : "asc",
					'year_pre_wrapper' => "",
					'year_pre_code1' => $sna_options["year_pre_code1"],
					'year_pre_code2' => $sna_options["year_pre_code2"],
					'year_post_code1' => $sna_options["year_post_code1"],
					'year_post_code2' => $sna_options["year_post_code2"],
					'year_post_wrapper' => "",
					'month_pre_wrapper' => $sna_options["month_pre_wrapper"],
					'month_pre_code1' => $sna_options["month_pre_code1"],
					'month_pre_code2' => $sna_options["month_pre_code2"],
					'month_post_code1' => $sna_options["month_post_code1"],
					'month_post_code2' => $sna_options["month_post_code2"],
					'month_post_wrapper' => $sna_options["month_post_wrapper"]
				);
				delete_option('sna_options');
				add_option('sna_options', $sna_options, '', 'no');
			}
			$sna_options = htmlentities_array(stripslashes_array($sna_options));
		} else {
			$sna_options = $sna_option_defaults;
			update_option('sna_options', $sna_options);
			$sna_options = htmlentities_array(stripslashes_array($sna_options));
		}
	} ?>
	
		
<div class="wrap">
	<form method="post" name="options" target="_self">
	
		<h2>Simple Nav Archives</h2>

		<div class="sna">
			
			<?php if (get_option('sna_ignore')) { 
				$install_PW_Archives_link = esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=pw-archives&TB_iframe=true&width=600&height=550' ) );	
			?>
				<div class="error action-links">
					<p><strong>WARNING:</strong> Simple Nav Archives is no longer being supported by its developer. A newer version has been redeveloped under the name <a class="thickbox" title="More Information About PW_Archives" href="<?php echo $install_PW_Archives_link; ?>">PW_Archives</a>. In short, continue using Simple Nav Archives at your own risk.</p>
				</div>
			<?php } // endif ?>
			
			<fieldset>
			<legend>Basic Options</legend>
			<ol>
				
				<li>
					<fieldset>
					<legend><strong>1)</strong> List all years that contain posts?</legend>
					<label>
	
					<input type="radio" name="show_all_years" value="yes" <?php if ($sna_options['show_all_years'] == "yes") { echo 'checked="checked"'; } ?> />
					Yes</label>
					<label>
					<input type="radio" name="show_all_years" value="no" <?php if ($sna_options['show_all_years'] == "no") { echo 'checked="checked"'; } ?> />
					No, only display the
					<select name="year_list_count">
						<?php
							for ($i = 1; $i <= 10; $i++) { 
								$selected = ($i == $sna_options['year_list_count']) ? 'selected="selected"' : "";
								echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
						 	}
						?>
					</select>
					most recent year(s).</label>
					</fieldset>
				</li>
				<li>
					<fieldset>
					<legend><strong>2)</strong> Expand years to list the months that contain posts?</legend>
					<label>
					<input type="radio" name="expand_all_years" value="yes" <?php if ($sna_options['expand_all_years'] == "yes") { echo 'checked="checked"'; } ?> />
					Yes, expand all years</label>
					<label>
					<input type="radio" name="expand_all_years" value="no" <?php if ($sna_options['expand_all_years'] == "no") { echo 'checked="checked"'; } ?> />
					No, expand no years (i.e. do not list any months)</label>
					<label>
					<input type="radio" name="expand_all_years" value="some" <?php if ($sna_options['expand_all_years'] == "some") { echo 'checked="checked"'; } ?> />
					Expand only the
					<select name="year_expand_count">
						<?php
							for ($i = 1; $i <= 10; $i++) { 
								$selected = ($i == $sna_options['year_expand_count']) ? 'selected="selected"' : "";
								echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
						 	}
						?>
					</select>
					most recent year(s)</label>
					<label>
					<input type="radio" name="expand_all_years" value="one" <?php if ($sna_options['expand_all_years'] == "one") { echo 'checked="checked"'; } ?> />
					Expand only the most recent year and list only the most recent
					<select name="month_list_count">
						<?php
							for ($i = 1; $i <= 12; $i++) { 
								$selected = ($i == $sna_options['month_list_count']) ? 'selected="selected"' : "";
								echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
						 	}
						?>
					</select>
					month(s). <span class="note"></span></label>
					</fieldset>
				</li>
				<li>
					<fieldset>
					<legend><strong>3)</strong> Display post count for each listed year?</legend>
					<label>
					<input type="radio" name="show_year_count" value="outside" <?php if ($sna_options['show_year_count'] == "outside") { echo 'checked="checked"'; } ?> />
					Yes (outside the anchor tag)</label>
					<label>
					<input type="radio" name="show_year_count" value="inside" <?php if ($sna_options['show_year_count'] == "inside") { echo 'checked="checked"'; } ?> />
					Yes (inside the anchor tag)</label>
					<label>
					<input type="radio" name="show_year_count" value="no" <?php if ($sna_options['show_year_count'] == "no") { echo 'checked="checked"'; } ?> />
					No</label>
					</fieldset>
				</li>
				<li>
					<fieldset>
					<legend><strong>4)</strong> Display post count for each listed month?</legend>
					<label>
					<input type="radio" name="show_month_count" value="outside" <?php if ($sna_options['show_month_count'] == "outside") { echo 'checked="checked"'; } ?> />
					Yes (outside the anchor tag)</label>
					<label>
					<input type="radio" name="show_month_count" value="inside" <?php if ($sna_options['show_month_count'] == "inside") { echo 'checked="checked"'; } ?> />
					Yes (inside the anchor tag)</label>
					<label>
					<input type="radio" name="show_month_count" value="no" <?php if ($sna_options['show_month_count'] == "no") { echo 'checked="checked"'; } ?> />
					No</label>
					</fieldset>
				</li>
				<li>
					<fieldset>
					<legend><strong>5)</strong> Lists years in the follow order:</legend>
					<label>
					<input type="radio" name="year_order" value="desc" <?php if ($sna_options['year_order'] == "desc") { echo 'checked="checked"'; } ?> />
					Descending</label>
					<label>
					<input type="radio" name="year_order" value="asc" <?php if ($sna_options['year_order'] == "asc") { echo 'checked="checked"'; } ?> />
					Ascending</label>
					</fieldset>
				</li>
				<li>
					<fieldset>
					<legend><strong>6)</strong> Lists months in the follow order:</legend>
					<label>
					<input type="radio" name="month_order" value="desc" <?php if ($sna_options['month_order'] == "desc") { echo 'checked="checked"'; } ?> />
					Descending</label>
					<label>
					<input type="radio" name="month_order" value="asc" <?php if ($sna_options['month_order'] == "asc") { echo 'checked="checked"'; } ?> />
					Ascending</label>
					</fieldset>
				</li>
			</ol>
			</fieldset>
			<fieldset>
			<legend>Advanced HTML Options for Year Items</legend>
			<ol>
				<li>
					<label>Start Wrapper</label>
					<input name="year_pre_wrapper" value="<?php echo $sna_options['year_pre_wrapper']; ?>" type="text" />
					(inserted before all year anchors tags)</li>
				<li>
					<label>Outter Pre Code</label>
					<input name="year_pre_code1" value="<?php echo $sna_options['year_pre_code1']; ?>" type="text" />
					(inserted outside the anchor tag before each year)</li>
				<li>
					<label>Inner Pre Code</label>
					<input name="year_pre_code2" value="<?php echo $sna_options['year_pre_code2']; ?>" type="text" />
					(inserted within the anchor tag before each year)</li>
				<li>
					<label>Inner Post Code</label>
					<input name="year_post_code1" value="<?php echo $sna_options['year_post_code1']; ?>" type="text" />
					(inserted within the anchor tag after each year)</li>
				<li>
					<label>Outter Post Code</label>
					<input name="year_post_code2" value="<?php echo $sna_options['year_post_code2']; ?>" type="text" />
					(inserted outside the anchor tag after each year)</li>
				<li>
					<label>End Wrapper</label>
					<input name="year_post_wrapper" value="<?php echo $sna_options['year_post_wrapper']; ?>" type="text" />
					(inserted after all year anchor tags)</li>
			</ol>
			</fieldset>
			<fieldset>
			<legend>Advanced HTML Options for Months Items</legend>
			<ol>
				<li>
					<label>Start Wrapper</label>
					<input name="month_pre_wrapper" value="<?php echo $sna_options['month_pre_wrapper']; ?>" type="text" />
					(inserted before all month anchors tags)</li>
				<li>
					<label>Outter Pre Code</label>
					<input name="month_pre_code1" value="<?php echo $sna_options['month_pre_code1']; ?>" type="text" />
					(inserted outside the anchor tag before each month)</li>
				<li>
					<label>Inner Pre Code</label>
					<input name="month_pre_code2" value="<?php echo $sna_options['month_pre_code2']; ?>" type="text" />
					(inserted within the anchor tag before each month)</li>
				<li>
					<label>Inner Post Code</label>
					<input name="month_post_code1" value="<?php echo $sna_options['month_post_code1']; ?>" type="text" />
					(inserted within the anchor tag after each month)</li>
				<li>
					<label>Outter Post Code</label>
					<input name="month_post_code2" value="<?php echo $sna_options['month_post_code2']; ?>" type="text" />
					(inserted outside the anchor tag after each month)</li>
				<li>
					<label>End Wrapper</label>
					<input name="month_post_wrapper" value="<?php echo $sna_options['month_post_wrapper']; ?>" type="text" />
					(inserted after all month anchor tags)</li>
			</ol>
			</fieldset>
			
			<p class="submit">
				<input type="submit" name="submit" value="Update" class="button-primary" />
				<input type="submit" name="preview" value="Preview Markup" />
				<input type="submit" name="restore" value="Restore Defaults" />
			</p>
			
			<fieldset>
			<legend>Markup Preview</legend>
			<code>
				<?php sna_markup(); ?>
			</code>
			</fieldset>
			
			<p class="submit">
				<input type="submit" name="submit" value="Update" class="button-primary" />
				<input type="submit" name="preview" value="Preview Markup" />
				<input type="submit" name="restore" value="Restore Defaults" />
			</p>
			
		</div>
		
	</form>
</div>
<?php
}