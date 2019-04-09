<?php

class Schedule_Tags {
	/**
  * Construct for the class
  *
  * @param empty
  * @return mixed
  *
  */
  protected $option_name = 'wpts_options';
  protected $data = array(
  	'tags_scheduler_posts' => array('post'),
  );

	public function __construct() {
		$options = get_option($this->option_name);
		//Add setting link for the admin settings
    add_filter( "plugin_action_links_".WPTS_BASE, array( $this, 'wpts_settings_link' ) );

    // Listen for the activate event
    register_activation_hook( WPTS_FILE, array( $this, 'activate' ) );

		add_action('add_meta_boxes', array($this, 'wpts_add_meta_box'));
		add_action('admin_enqueue_scripts', array($this, 'wpts_admin_script'));
		add_action('save_post', array($this, 'wpts_save_data'));

		add_action('admin_menu', array($this, 'wpts_admin_menu'));
		add_action('admin_init', array($this, 'wpts_plugin_settings') );

		add_action('init', array( $this, 'wpts_schedule_cron') );
		add_action('wpts_tags_cron', array($this, 'wpts_tags_cron_event') );
		register_deactivation_hook( __FILE__, array( $this, 'wpts_deactivate_event' ) );
		
    // Deactivation plugin
    register_deactivation_hook( WPTS_FILE, array( $this, 'deactivate' ) );
	}

	//Plugin settings link
  public function wpts_settings_link($links) {
    $settings_link = '<a href="'.admin_url('options-general.php?page=wpts-options').'">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }

  /**
  * Activate our settings
  *
  * @param empty
  * @return bool
  *
  */
  public function activate() {
    update_option( $this->option_name, $this->data );
  }

  /**
  * Deactivate input
  *
  * @param empty
  * @return bool
  *
  */
  public function deactivate() {
    delete_option( $this->option_name );
  }

	/**
  * Adds meta box for the Schedule Tags
  *
  * @param empty
  * @return mixed
  *
  */
	public function wpts_add_meta_box() {
		$options = get_option($this->option_name);
		$selected_posts = $options['tags_scheduler_posts'];

		$screens = array();

		if( is_array($selected_posts) && !empty($selected_posts) ) {
			$screens = $selected_posts;
		}

  	foreach ($screens as $screen) {
    	add_meta_box(
      	'wpts_box_id',
      	esc_html__('Schedule Tag','tags_scheduler'),
      	array($this, 'wpts_meta_box_html'),
      	$screen,
				'normal'
    	);
  	}
	}

	public function wpts_prepare_tags_array($post_id) {
		$expiry_tags_data = get_post_meta($post_id,'schedule_tag');
		$prepared_arr = array();
		if( is_array($expiry_tags_data) ) {
			foreach( $expiry_tags_data as $key => $data ) {
				foreach( $data as $d => $val ) {
					array_push($prepared_arr, $data[$d]['tag_name']);
				}
			}
		}
		return $prepared_arr;
	}

	/**
  * Renders the html layou for the metabox contents
  *
  * @param empty
  * @return html
  *
  */
	public function wpts_meta_box_html() {
		$post_id = get_the_ID();
		$expiry_tags_data = get_post_meta($post_id,'schedule_tag');
		$post_tags = get_the_tags();
		$prepared_tags = $this->wpts_prepare_tags_array($post_id);
		?>
		<select name="wpts_field" id="wpts_field" class="postbox">
			<option value=""><?php echo __('Select Tag','tags_scheduler'); ?></option>
			<?php
			if ( $post_tags ) :
				$expiredTags = [];

				if( is_array($post_tags) ) {
        	foreach( $post_tags as $tag ) {
          	if( in_array($tag->name, $prepared_tags) ) {
            	echo "<option value='{$tag->name}' disabled='disabled'>{$tag->name}</option>";
          	}
          	else {
            	echo "<option value='{$tag->name}'>{$tag->name}</option>";
          	}
        	}
      	}
			endif;
			?>
		</select>
		<div class="tags-scheduler-data">
			<table id="tags-scheduler-table">
				<thead>
					<th><?php echo __('Tag Name', 'tags_scheduler'); ?></th>
					<th><?php echo __('Start Date', 'tags_scheduler'); ?></th>
					<th><?php echo __('End Date', 'tags_scheduler'); ?></th>
				</thead>
				<tbody>
				<?php
				if( is_array($expiry_tags_data) && !empty($expiry_tags_data) ) {
					foreach ($expiry_tags_data as $key =>  $expiry_tags) {
            sort($expiry_tags);
						foreach( $expiry_tags as $k => $data ) {
							$tag_name = isset($expiry_tags[$k]['tag_name']) ? $expiry_tags[$k]['tag_name'] : '';
							$start_date = isset($expiry_tags[$k]['startDate']) ? $expiry_tags[$k]['startDate'] : '';
							$end_date = isset($expiry_tags[$k]['endDate']) ? $expiry_tags[$k]['endDate'] : '';
							?>
							<tr>
								<td><?php echo ucfirst($tag_name); ?> <input type="hidden" name="tags_scheduler[<?php echo $k; ?>][tag_name]" value="<?php echo $tag_name; ?>"></td>
								<td><input type="text" name="tags_scheduler[<?php echo $k; ?>][startDate]" class="startDate" value="<?php echo $start_date ?>"></td>
								<td><input type="text" name="tags_scheduler[<?php echo $k; ?>][endDate]" class="endDate" value="<?php echo $end_date;  ?>"></td>
								<td><input type="button" data-name="<?php echo $tag_name; ?>" class="button tags-scheduler-remove-row" value="Remove"></td>
							</tr>
							<?php
						}
					}
				}
				?>
				</tbody>
			</table>
		</div>
		<?php
	}


	/**
  * Add necessary js and css for our plugin
  *
  * @param empty
  * @return mixed
  *
  */
	public function wpts_admin_script() {
		$url = plugin_dir_url( __FILE__ ).'/assets/js/tag-scheduler.js';

  	//Add js file
  	wp_enqueue_script( 'jquery-ui-datepicker' );
  	wp_register_script('wpst-main', $url, array('jquery', 'jquery-ui-datepicker'), '1.0.0', true);
  	wp_enqueue_script('wpst-main');

  	//Add css file
  	wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    wp_enqueue_style('jquery-ui');
	}

	/**
  * Saves the tags data into the post meta
  *
  * @param post_id
  * @return bool
  *
  */
	public function wpts_save_data($post_id) {
		global $post;
		if($post_id) {
			$tags_arr = array();
			if( isset($_POST['tags_scheduler']) ) {
				foreach( $_POST['tags_scheduler'] as $key => $data ) {
					if( '' !== $_POST['tags_scheduler'][$key]['tag_name']
						&& '' !== $_POST['tags_scheduler'][$key]['startDate']
						&& '' !== $_POST['tags_scheduler'][$key]['endDate'] ) {
						$tags_arr[$key]['tag_name'] = isset($_POST['tags_scheduler'][$key]['tag_name']) ? sanitize_text_field($_POST['tags_scheduler'][$key]['tag_name']) : '';
						$tags_arr[$key]['startDate'] = isset($_POST['tags_scheduler'][$key]['startDate']) ? sanitize_text_field($_POST['tags_scheduler'][$key]['startDate']) : '';
						$tags_arr[$key]['endDate'] = isset($_POST['tags_scheduler'][$key]['endDate']) ? sanitize_text_field($_POST['tags_scheduler'][$key]['endDate']) : '';
					}
				}
			}
			update_post_meta( $post_id, 'schedule_tag', $tags_arr );
			$this->wpts_post_tag_scheduler();
		}
	}

	/**
  * Makes the scheduled jobs for the tags
  *
  * @param empty
  * @return mixed
  *
  */
	public function wpts_post_tag_scheduler() {
		global $wpdb;
  	$scheduledTags = $wpdb->get_results("SELECT post_id,meta_value FROM wp_postmeta WHERE meta_key = 'schedule_tag' ");
  	$current_date = strtotime('now');


  	if( is_array($scheduledTags) && !empty($scheduledTags) ) {
  		foreach( $scheduledTags as $scheduledTag ) {
  			$id_post = $scheduledTag->post_id;
  			$m_value = unserialize($scheduledTag->meta_value);
  			sort($m_value);

				if( is_array($m_value) && !empty($m_value) && isset($m_value)) {
	  			for( $i = 0; $i < sizeof($m_value); $i++ ){
	  				$tag_name = $m_value[$i]['tag_name'];
	  				$tag_start_date = $m_value[$i]['startDate'];
	  				$tag_end_date = $m_value[$i]['endDate'];

	        	if( strtotime($tag_end_date) < $current_date ){
	          	wp_remove_object_terms( $id_post, $tag_name,'post_tag');
	        	}

	        	if( $current_date <= strtotime($tag_end_date)){
	          	wp_set_object_terms( $id_post, $tag_name,'post_tag',true);
	        	}

	        	if( strtotime($tag_start_date) > $current_date ){
	          	wp_remove_object_terms( $id_post, $tag_name,'post_tag');
	        	}
	  			}
				}
  		}
  	}
	}

	/**
  * Check for scheduled event. Creates the event
  *
  * @param empty
  * @return mixed
  *
  */
	public function wpts_schedule_cron() {
		if( !wp_next_scheduled('wpts_tags_cron') ) {
    	$customer_time = '00:00';
      wp_schedule_event(strtotime( date( 'Y-m-d' ) .' '. $customer_time ), 'daily', 'wpts_tags_cron');
    }
	}

	/**
  * Cron job function
  *
  * @param empty
  * @return mixed
  *
  */
	public function wpts_tags_cron_event() {
  	$this->wpts_post_tag_scheduler();
	}

	/**
  * Clear scheduled cron job when plugin is removed
  *
  * @param empty
  * @return bool
  *
  */
	public function wpts_deactivate_event() {
  	wp_clear_scheduled_hook('wpts_tags_cron' );
    wp_die();
  }

  /**
  * Add options page
  *
  * @param empty
  * @return mixed
  *
  */
  public function wpts_admin_menu() {
  	add_options_page(__('Schedule Tags','tags_scheduler'), __('Schedule Tags','tags-scheduler'), 'manage_options', 'wpts-options', array($this, 'wpts_admin_options') );
  }


  /**
  * Admin settings to show posts
  *
  * @param empty
  * @return mixed
  *
  */
  public function wpts_admin_options() {
  	if ( !current_user_can( 'manage_options' ) )  :
  		wp_die( __( 'You do not have sufficient permissions to access this page.', 'tags_scheduler' ) );
  	endif;

  	$options = get_option($this->option_name);

  	$args = array(
    	'public'   => true,
      '_builtin' => false,
    );

    $output = 'names';
		$operator = 'and';

  	$types[] = 'post';
    $post_types = get_post_types( $args, $output, $operator );
    if( is_array($post_types) ) {
    	foreach( $post_types as $key => $type ) {
    		array_push($types, $type);
    	}
    }

  	?>
  	<div class="wrap">
  		<h1><?php echo __('Schedule Tags','tags_scheduler'); ?></h1>
  		<form method="post" action="options.php">
  			<?php settings_fields('wpts_options'); ?>
  			<table class="form-table">
  				<tr valign="top">
          	<th scope="row">
            	<?php echo __('Select posts for which the tag scheduler should work  
','tags_scheduler'); ?>
          	</th>
						<td>
            	<?php
            		if( is_array($types) ) :
            	?>
            	<ul>
            	<?php
    						foreach( $types as $key => $post_type ) :
    					?>
    						<li>
    							<label for="<?php echo $post_type; ?>">
    								<?php if (is_array($options['tags_scheduler_posts']) && in_array($post_type, $options['tags_scheduler_posts']) ) : ?>
    									<input type="checkbox" checked id="<?php echo $post_type; ?>" value="<?php echo $post_type; ?>" name="tags_scheduler_posts[]">
    								<?php else : ?>
    									<input type="checkbox"  id="<?php echo $post_type; ?>" value="<?php echo $post_type; ?>" name="tags_scheduler_posts[]">
    								<?php endif; ?>
    								<?php echo ucfirst($post_type); ?>
    							</label>
    						</li>

    					<?php
    						endforeach;
    					?>
    					</ul>
    					<?php
    						endif;
            	?>
          	</td>
					</tr>
  			</table>
  			<?php submit_button(); ?>
  		</form>
  	</div>
  	<?php
  }


	/**
  * Register Our Settings
  *
  * @param empty
  * @return array
  *
  */
  public function wpts_plugin_settings() {
  	register_setting('wpts_options', $this->option_name, array($this, 'validate'));
  }


  /**
  * Validate input
  *
  * @param empty
  * @return array
  *
  */
  public function validate( $input ) {
  	$valid = array();
  	$output_array = array();
  	
    if( isset($_POST['tags_scheduler_posts']) ) {
      foreach( $_POST['tags_scheduler_posts'] as $key => $post_arr ) {
        array_push( $output_array, sanitize_text_field($post_arr) );
      }
    }

    $valid['tags_scheduler_posts'] = $output_array;
  	return $valid;
  }

}
