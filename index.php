<?php
/*
	Plugin Name: Student Details
   	Plugin URI: https:devnote.com
   	description: Student Details Plugin
   	Version: 1.1
   	Author: devnote
   	Author URI: https://devnote.in
   	License: GPL2
*/

global $wpdb;
global $wpstud_db_version;
$wpstud_db_version = '1.2';

function custom_student_plugin_create(){
	add_menu_page('Student Details', 'Student Details', 'manage_options', 'student-details','std_display_table_data');
	add_submenu_page('student-details', 'Add Student', 'Add Student', 'read', 'add-student', 'add_student_form');
	add_submenu_page('', '', '', 'read', 'edit-student', 'add_student_form');
}

add_action('admin_menu', 'custom_student_plugin_create');

/* Custom Css And Js File Add */
function student_stylesheet_js_custom(){
	wp_enqueue_style('student-style', plugins_url('/css/style.css',__FILE__));
	wp_enqueue_style('student-jquery-ui', plugins_url('/css/jquery-ui.css',__FILE__));

	wp_enqueue_script('student-custom', plugins_url('/js/student-custom.js',__FILE__));
	wp_enqueue_script('jBox-all-js', plugins_url('/js/jquery-ui.js',__FILE__));
	wp_enqueue_script('student-validate-js', plugins_url('/js/jquery.validate.min.js',__FILE__));
	wp_enqueue_script('student-additional-validate-js', plugins_url('/js/additional-methods.js',__FILE__)); 
}

add_action('admin_enqueue_scripts','student_stylesheet_js_custom');


function create_plugin_database_table(){
	global $wpdb;
	global $wpstud_db_version;
	$table_name = $wpdb->prefix."student_details";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name){

		$sql = "CREATE TABLE " . $table_name . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`first_name` varchar(255) NOT NULL,
				`last_name` varchar(255) NOT NULL,
				`middle_name` varchar(255) NOT NULL,
				`DOB` DATE NOT NULL,
				`hobbies` varchar(255) NOT NULL,
				`image` varchar(255) NOT NULL,
				`Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY id (id)
			);";

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta($sql);

		add_option('wpstud_db_version', $wpstud_db_version);

		$installed_ver = get_option('wpstud_db_version');
    	if ($installed_ver != $wpstud_db_version) {

   			$sql = "CREATE TABLE " . $table_name . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`first_name` varchar(255) NOT NULL,
				`last_name` varchar(255) NOT NULL,
				`middle_name` varchar(255) NOT NULL,
				`DOB` DATE NOT NULL,
				`hobbies` varchar(255) NOT NULL,
				`image` varchar(255) NOT NULL,
				`Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY id (id)
			);"; 		

    		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	        dbDelta($sql);

	        update_option('wpstud_db_version', $wpstud_db_version);
    	}
	}

}

register_activation_hook(__FILE__, 'create_plugin_database_table');

function wpstud_update_db_check() { 
    global $wpstud_db_version;
    if (get_site_option('wpstud_db_version') != $wpstud_db_version) {
        create_plugin_database_table();
    }
}

add_action('plugins_loaded', 'wpstud_update_db_check');

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Custom_std_table_create extends WP_List_Table{
	function __construct(){
		global $status, $page;

		parent::__construct(array(
			'singular'	=> 'student-detail',
			'plural'	=> 'student-details'
		));
	}

	function column_default($item, $columns_name)
    {
        return $item[$columns_name];
    }

	function column_first_name($item){

		$actions = array(
            'edit' => sprintf('<a href="?page=edit-student&id=%s">%s</a>', $item['id'], __('Edit')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete')),

            'view' => sprintf('<a href="#TB_inline?&width=100%&height=auto&inlineId=my-content" class="thickbox target-click" onclick="view_student_details('.$item['id'].')">View</a>',$item['id'], __('View')),
        );

        return sprintf('%s %s',
            $item['first_name'],
            $this->row_actions($actions)
        );
	}

    function column_cb($item)
    {
		return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />', 
            'first_name' => __('First Name'),
			'middle_name' => __('Middle Name'),
			'last_name' => __('Last Name'),
			'DOB' => __('Date Of Birth'),
		);
		return $columns;
	}

	function get_shortable_columns(){
		$sortable_columns = array(
			'first_name'	=> array('first_name', true),
			'middle_name'	=> array('middle_name', true),
			'last_name'	=> array('last_name', false),
			'DOB'	=> array('DOB', false),
		);
		return $sortable_columns;
	}

	function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

	function process_bulk_action(){
		global $wpdb;
		$table_name = $wpdb->prefix."student_details";

		if('delete' === $this->current_action()){
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array() ;
			if( is_array( $ids ))
				$ids = implode(",", $ids);
			if(!empty($ids)){
				$wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
			}
		}
	}

	function prepare_items(){
		global $wpdb;
		$table_name = $wpdb->prefix.'student_details';

		$per_page = 10;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_shortable_columns();

		$this->_column_headers = array($columns,$hidden,$sortable);

		$this->process_bulk_action();

		$total_items = $wpdb->get_var("SELECT count(id) FROM $table_name");

		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1 ) : 0 ;

		$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_shortable_columns()))) ? $_REQUEST['orderby'] : 'last_name' ;

		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'DESC' ;

		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",$per_page, $paged), ARRAY_A);

		$this->set_pagination_args(array(
			'total_items' 	=> $total_items,
			'per_page'	  	=> $per_page,
			'total_pages'	=> ceil($total_items / $per_page)
		));
	}

}
function std_display_table_data(){
	global $wpdb;
	$table = new Custom_std_table_create();
	$table->prepare_items();

	$message = '';
	if('delete' === $table->current_action()){
		$message = '<div class="updated notice notice-success is-dismissible" id="message"><p>Items deleted !</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	}
	?>

	<div class="wrap">
    	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>

    	<h2><?php _e('Student Details')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=add-student');?>"><?php _e('Add new')?></a>
    	</h2>

    	<?php echo $message; ?>
    	<?php if (isset($_GET['status']) ) : ?>
    		<div id="message" class="updated"><p>Student details successfully insert</p></div>
    	<?php endif; ?>

    	<form id="" method="POST">
    		<p class="search-box">
				<label class="screen-reader-text" for="search_id-search-input">
				search:</label> 
				<input id="search_id-search-input" type="text" name="s" value="" /> 
				<input id="search-submit" class="button" type="submit" name="" value="search" />
			</p>

    		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'];?>"/>
    	<?php $table->display(); ?>
    	</form>
    	<input type="hidden" id="std_ajax_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
	<?php add_thickbox(); ?>
		<div id="my-content" style="display:none;">
		    <p><span id="per_student_details_ajax_response"></span></p>
		</div>
	</div>
<?php } 

function add_student_form(){
	
	global $wpdb;
	$table_name = $wpdb->prefix."student_details";

	$message = '';
	$notice = '';

	$default = array(
		'id'			=> 0,
		'first_name'	=> '',
		'middle_name'	=> '',
		'last_name'		=> '',
		'DOB'			=> '',
		'hobbies'		=> '',
		'image'			=> '',
	);

	if( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__)) ){
		$item = shortcode_atts($default, $_REQUEST);

		$item_valid = wpst_validate_contact($value);

		if(isset($item_valid)) { 
			if($item['id'] == 0 ) { 

				$item['DOB'] = date('Y-m-d', strtotime($_REQUEST['DOB']));

				$hobbies = $_REQUEST['hobbies'];

				if( isset($hobbies) ) :
					$hobbies = implode("," , $hobbies);
				else : 
					$hobbies = '';
				endif;
				$item['hobbies'] = $hobbies;

				$image = $_FILES['image']['name'];
				$tmp_image = $_FILES['image']['tmp_name'];
				$path = wp_upload_dir();

				$image = pathinfo($image, PATHINFO_FILENAME).time().".".pathinfo($image, PATHINFO_EXTENSION);

				$item['image'] = $image;

				$upload_image = $path['path'].'/'.$image;

				move_uploaded_file($tmp_image, $upload_image);

				$result = $wpdb->insert($table_name, $item);
				$item['id'] = $wpdb->insert_id;

				if($result) { 
					$message = __("Student details successfully saved");
				}  else {
					$notice = __("There was an error while saving data");
				}
				echo "<script>window.location.href='?page=student-details&status=success';</script>";

			} else {

				$hobbies = $_REQUEST['hobbies'];

				if( isset($hobbies) ) :
					$hobbies = implode("," , $hobbies);
				else : 
					$hobbies = '';
				endif;

				$item['hobbies'] = $hobbies;

				$image = $_FILES['image']['name'];
				$old_image = $_REQUEST['old_image'];
				$tmp_image = $_FILES['image']['tmp_name'];
				$path = wp_upload_dir();

				if( !empty($image) ) { 
					$image = pathinfo($image, PATHINFO_FILENAME).time().".".pathinfo($image, PATHINFO_EXTENSION);
					$path = wp_upload_dir();
					$item['image'] = $image;
					$upload_image = $path['path'].'/'.$image; 
					move_uploaded_file($tmp_image, $upload_image);
					unlink($path['path'].'/'.$old_image);
				} else {
					$image = $old_image;
					$item['image'] = $image;
				}

				$item['DOB'] = date('Y-m-d', strtotime($_REQUEST['DOB']));

				$result = $wpdb->update($table_name, $item, array('id' => $item['id']));

				if(isset($result)) { 
					$message = __("Student details successfully saved");
				} 
				else {
					$notice = __("There was an error while saving data");
				}
			}
		} else {
			$notice = $item_valid;
		}

	} else {
		$item = $default;
		if( isset($_REQUEST['id']) ){
			$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A );
			if(!$item){
				$item = $default;
				$notice = __("Data Not Found");
			}
		}
	}

	add_meta_box('std_custom_meta_box', __('Student Details'), 'wpstd_std_custom_meta_box_handler', 'std_meta','normal', 'default');
	?>

	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php echo( $item['id'] > 0 ) ? _e('Edit Student') : _e('Add Student') ; ?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=add-student');?>"><?php _e('Add new')?></a>
    	</h2>
    	<?php if (!empty($notice)) : ?>
    		<div id="notice" class="error"><p><?php echo $notice; ?></p></div>
    	<?php endif; ?>
    	<?php if (!empty($message)) : ?>
    		<div id="message" class="updated"><p><?php echo $message; ?></p></div>
    	<?php endif; ?>

    	<form id="Myform" enctype="multipart/form-data" method="POST">
    		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
    		<input type="hidden" name="id" id="edit_id" value="<?php echo $item['id'] ?>"/>

    		<div class="metabox-holder" id="poststuff">
    			<div id="post-body">
    				<div id="post-body-content">
    					<?php do_meta_boxes('std_meta', 'normal', $item); ?>
    					<input type="submit" value="<?php echo( $item['id'] > 0 ) ? _e('Update') : _e('Save') ; ?>" id="submit1" class="button-primary" name="Submit">
    				</div>
    			</div>
    		</div>
    	</form>

	</div>
<?php } 

function wpstd_std_custom_meta_box_handler($item) { ?>
	<tbody>
		<div class="formdata">
			<form>
				<p><label for="first_name"><?php _e('First Name:')?><span class="st_req_field">*</span></label><br>
					<input id="first_name" name="first_name" type="text" style="width: 60%" value="<?php echo esc_attr($item['first_name']); ?>"></p>

                <p><label for="middle_name"><?php _e('Middle Name:')?><span class="st_req_field">*</span> </label><br>
					<input id="middle_name" name="middle_name" type="text" style="width: 60%" value="<?php echo esc_attr($item['middle_name']); ?>"></p>

				<p><label for="last_name"><?php _e('Last Name:')?> <span class="st_req_field">*</span> </label><br>
					<input id="last_name" name="last_name" type="text" style="width: 60%" value="<?php echo esc_attr($item['last_name']); ?>"></p>

				<p><label for="datepicker"><?php _e('Date Of Birth:'); ?> <span class="st_req_field">*</span> </label><br>
					<input id="datepicker" name="DOB" type="text" style="width: 60%" value="<?php echo $item['DOB']; ?>"></p>

				<p><label><?php _e('Hobbies :'); ?></label><br>
					<?php
						$hob = $item['hobbies'];
						$hobbies = explode(",", $hob);
					?>
					<input type="checkbox" name="hobbies[]" class="st_field_chk" value="swimming" <?php echo (in_array('swimming', $hobbies)) ? 'checked' : '' ; ?> >Swimming
					<input type="checkbox" name="hobbies[]" class="st_field_chk" value="reading" <?php echo ( in_array("reading", $hobbies) ) ? 'checked' : '' ; ?>>Reading <br>
					<input type="checkbox" name="hobbies[]" class="st_field_chk" value="playing" <?php echo ( in_array('playing', $hobbies) ) ? 'checked' : '' ; ?>>Playing 
					<input type="checkbox" name="hobbies[]" class="st_field_chk" value="writing" <?php echo ( in_array('writing', $hobbies) ) ? 'checked' : '' ; ?>>Writing
					</p>

				<p><label><?php _e(' Profile :');?><span class="st_req_field">*</span> </label><br>
					<input type="file" name="image" style="width: 60%">
					<?php	$path = wp_upload_dir();
						$upload_image = $path['url'].'/'.$item['image']; 
						if($item['id']) : ?>
						<img src="<?php echo $upload_image; ?>" width="150px">
						<input type="hidden" name="old_image" value="<?php echo $item['image']; ?>">
					<?php endif; ?>
				</p>
			</form>
			<script type="text/javascript">
				jQuery( "#datepicker" ).datepicker();
				/* custom js */
				function view_student_details( view_id ){
					var v_id = view_id;
					var ajaxUrl = jQuery('#std_ajax_url').val();
					var data = { v_id : v_id, type: 'POST', action: 'std_demo_data'};
					jQuery.post(ajaxUrl,data, function(response){
							jQuery('#per_student_details_ajax_response').html(response);
						}
					);
				}
			</script>
		</div>
	</tbody>

<?php } 

function wpst_validate_contact(){
	$message = array();
	/* if (empty($item['name'])) $messages[] = __('Name is required', 'wpstud');
    if (empty($item['lastname'])) $messages[] = __('Last Name is required', 'wpstud');
    if(!empty($item['phone']) && !absint(intval($item['phone'])))  $messages[] = __('Phone can not be less than zero');
    if(!empty($item['phone']) && !preg_match('/[0-9]+/', $item['phone'])) $messages[] = __('Phone must be number'); */

    if (empty($messages)) return true;
    /* return implode('<br />', $messages); */
}

add_action( "wp_ajax_std_demo_data", "so_wp_ajax_function" );
add_action( "wp_ajax_nopriv_std_demo_data", "so_wp_ajax_function" );
function so_wp_ajax_function() { 

  	global $wpdb;
  	$v_id = $_POST['v_id'];
	$table_name = $wpdb->prefix."student_details";
	$sql = "SELECT * FROM $table_name WHERE id = $v_id";
	$result = $wpdb->get_results( $sql ); ?>
	<span id="per_display_student_details">
		<label> First Name : <?php echo $result[0]->first_name; ?></label><br>
		<label> Middle Name : <?php echo $result[0]->middle_name; ?> </label><br>
		<label> Last Name : <?php echo $result[0]->last_name; ?> </label><br>
		<label> Date Of Birth : <?php echo $result[0]->DOB; ?> </label><br>
		<label> Hobbies : <?php echo $result[0]->hobbies; ?> </label><br>
		<?php $path = wp_upload_dir();
			$upload_path = $path['url'].'/'.$result[0]->image; ?>
		<label> Image : <img src="<?php echo $upload_path; ?>" width="140px"></label><br>
	</span>

 <?php die; } 

 function shortcode_display_table_data() { 
	wp_enqueue_style('bootstrap-min-style', plugins_url('/css/bootstrap.min.css',__FILE__));
	wp_enqueue_style('shortcode-style', plugins_url('/css/shortcode.css',__FILE__));
	wp_enqueue_script('bootstrap-min-js', plugins_url('/js/bootstrap.min.js',__FILE__)); ?>

	<div class="container">
		<div class="row">
			<?php global $wpdb;
			$table_name = $wpdb->prefix."student_details";

			$sql = "SELECT * FROM $table_name";
			$result = $wpdb->get_results( $sql, 'ARRAY_A' ); ?>


			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<h1 class="std_custom_heading"> Student Detail </h1>
			</div>

			<?php if( !empty( $result ) ) :
				foreach( $result as $key=>$row ) : ?>
				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<div class="std_box_display">
						<div class="row">
							<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
								<?php $path = wp_upload_dir();
									$image = $path['url'].'/'.$row['image'];
								?>
								<img src="<?php echo $image; ?>">
							</div>
							<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">					
								<h5>Name : <?php echo $row['middle_name']." ".$row['last_name']." ".$row['first_name']; ?> </h5>
								<h5>Date Of Birth : <?php echo date("d-m-Y", strtotime($row['DOB'])); ?></h5>
								<h5>Hobbies : <?php echo $row['hobbies']; ?></h5>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach;
				endif;  ?>
		</div>
	</div>

<?php }
add_shortcode('student-detail', 'shortcode_display_table_data'); 