<?php
/**
 * @package Livit_Instagram_Test
 * @version 1.6
 */
/*
Plugin Name: Livit Instagram Test
Plugin URI: http://didiroesmana.com
Description: A Plugin that add shortcode to display instagram feed based on user or hashtag and user can put comment and rate on single image.
Author: Didi Roesmana
Version: 0.0.1
Author URI: http://didiroesmana.com
*/
require_once('class.instagram.php');
require_once('livit-database.php');
use MetzWeb\Instagram\Instagram;
register_activation_hook( __FILE__, 'livit_plugin_activated' );

function livit_plugin_activated(){
	$livitDB = new LivitDatabase();
	$livitDB->create_database();
	$page = get_page_by_title( 'instagram' );
	// if (isset($page)) {
		if ($page->post_title != 'instagram' ) {
			$my_page = array(
			  'post_title'    => 'instagram',
			  'post_content'  => '',
			  'post_status'   => 'publish',
			  'post_author'   => 1,
			  'post_type'   => 'page',
			  'comment_status' => 'closed',
			);
			wp_insert_post( $my_page );
		}
	// }
}

function livit_instagram_plugin_menu() {
add_menu_page('Livit Instagram Settings', 'Livit Instagram Settings', 'administrator', 'livit-instagram-settings', 'livit_instagram_settings_page', 'dashicons-admin-generic');
add_action( 'admin_init', 'livit_instagram_plugin_settings' );
}
add_action('admin_menu', 'livit_instagram_plugin_menu');

function livit_instagram_settings_page() {?>
<div class="wrap">
<h2>Livit Instagram Settings Page</h2>
<a href="<?php echo getLoginUrl() ?>">Get Access Token</a> 
<form method="post" action="options.php">
<?php settings_fields( 'livit-instagram-plugin-settings-group' ); ?>
<?php do_settings_sections( 'livit-instagram-plugin-settings-group' ); ?>
<table class="form-table">
<tr valign="top">
<th scope="row">Your Access Token</th>
<td><input type="text" name="livit_access_token" value="<?php echo esc_attr( get_option('livit_access_token') ); ?>" /></td>
</tr>
<tr valign="top">
<th scope="row">Client ID</th>
<td><input type="text" name="livit_client_id" value="<?php echo esc_attr( get_option('livit_client_id') ); ?>" /></td>
</tr>
<tr valign="top">
<th scope="row">Secret Key</th>
<td><input type="text" name="livit_secret_key" value="<?php echo esc_attr( get_option('livit_secret_key') ); ?>" /></td>
</tr>
<tr valign="top">
<th scope="row">Call Back Url</th>
<td><input type="text" name="livit_call_back_url" value="<?php echo esc_attr( get_option('livit_call_back_url') ); ?>" /></td>
</tr>
</table>
<?php submit_button(); ?>
</form>
<p>Set Your Callback url to <?php echo plugins_url('livit-callback.php',__FILE__ )?></p>
</div>
<?php
} 

function livit_instagram_plugin_settings() {
register_setting( 'livit-instagram-plugin-settings-group', 'livit_access_token' );
register_setting( 'livit-instagram-plugin-settings-group', 'livit_client_id' );
register_setting( 'livit-instagram-plugin-settings-group', 'livit_secret_key' );
register_setting( 'livit-instagram-plugin-settings-group', 'livit_call_back_url' );
} 


add_filter( 'the_content', 'instagram_content_filter' );

function displayDetailPage($id){
	$instagram = init_instagram();
	$media = $instagram->getMedia($id);
	$livitDB = new LivitDatabase();
	$content = "<div class='instagram-page'>";
	if ($media->meta->code == '200' ) {
		$rate = $livitDB->get_rating_by_id($media->data->id);
		if ($media->data->type === 'video') {
			// video
			$poster = $media->data->images->low_resolution->url;
			$source = $media->data->videos->standard_resolution->url;
			$content .= "<video class=\"media video-js vjs-default-skin\" width=\"250\" height=\"250\" poster=\"{$poster}\"
			data-setup='{\"controls\":true, \"preload\": \"auto\"}'>
			<source src=\"{$source}\" type=\"video/mp4\" />
			</video>";
		} else {
			// image
			$image = $media->data->images->standard_resolution->url;
			$content .= "<img class=\"media\" src=\"{$image}\"/>";
		}
			// create meta section
			$avatar = $media->data->user->profile_picture;
			$username = $media->data->user->username;
			$comment = (!empty($media->data->caption->text)) ? $media->data->caption->text : '';
			$content .= "<div class=\"content\">
			<div class=\"avatar\" style=\"float:left;margin-right:15px;margin-top:10px;width:150px;height:150px;border-radius:10px;background-image: url({$avatar})\"></div>
			<p class='username-instagram'>{$username}</p>
			<div class=\"comment\">{$comment}</div>
			</div>";

		// comments list
			$content .='<div class="comment-insta">';
			$content .= displayComments($media->data,$livitDB,true);

		// rating
			$content .= displayRating($rate,$media->data);
			$content .= '<script>'.init_web_rating().'</script>';
		// comment form
			
			$content .= displayCommentForm($media->data);
			

	}
	$content.="</div>";
	return $content;
	

}

function instagram_content_filter( $content ) {

   if (isset($_GET['ig']) ) {
   	$instagram_media_id = $_GET['ig'];
   }	

   if ( is_page( 'instagram' && $instagram_media_id != '' ) ) {
		$content = '';
		$content .= displayDetailPage($instagram_media_id);
   } 

   return $content;
}


function countMediaTags($medias,$tag){
	$count = 0;
	foreach ($medias as $media) {
		// check if media has specified tags
		if (in_arrayi($tag,$media->tags)){
			$count++;
		}
	}
	
	if ( $count > 0 ) 
		return true;
	else false;
}
function getDisplayFeed($username,$tag=false){
	$instagram = init_instagram();
	$id = lookup_user_id($username);
	$medias = $instagram->getUserMedia($id);
	if (countMediaTags($medias->data,$tag) == false ) { $tag=false;}
	
	$content="";
	$livitDB = new LivitDatabase();
	foreach ($medias->data as $media) {
		
		// check if media has specified tags
		if (in_arrayi($tag,$media->tags) or $tag === false){

			$livitDB->insert_media_id($media->id);
			$content .= "<li class='instagram-item'>";
			// output media
			if ($media->type === 'video') {
			// video
				$poster = $media->images->low_resolution->url;
				$source = $media->videos->standard_resolution->url;
				$content .= "<video class=\"media video-js vjs-default-skin\" width=\"250\" height=\"250\" poster=\"{$poster}\"
				data-setup='{\"controls\":true, \"preload\": \"auto\"}'>
				<source src=\"{$source}\" type=\"video/mp4\" />
				</video>";
			} else {
			// image
				$image = $media->images->standard_resolution->url;
				$content .= "<a class='various' href='#inline{$media->id}'><img class=\"media\" src=\"{$image}\"/><a/>";
			}
			// create meta section
				$content .= displayPopUp($media,$livitDB);
				$avatar = $media->user->profile_picture;
				$username = $media->user->username;
				$comment = (!empty($media->caption->text)) ? $media->caption->text : '';
				$content .= "<div class=\"content\">
				<div class=\"avatar\" style=\"background-image: url({$avatar})\"></div>
				<p>{$username}</p>
				<div class=\"comment\">{$comment}</div>
				</div>";
			// output media
			$content .="</li>";
		}
	}
	return $content;	
}

function displayComments($media,$livitDB,$full=false){
	$content = '<h3>Comments</h3>';
	$content .= '<div id="comments'.$media->id.'">';
	if ($full) {
		$comments = $livitDB->get_all_comment($media->id);
	} else {
		$comments = $livitDB->get_recent_comment($media->id);
	}
	foreach ($comments as $comment) {
		$content .= '<div id="comment'.$comment->id.'">';
		//comment author
		$content .= '<span class="livit-comment-user">';
		$content .= stripslashes($comment->Name);
		$content .= '&nbsp;:&nbsp;</span>';
		//comment content
		$content .= '<p class="livit-comment-content">';
		$content .= stripslashes($comment->comment);
		$content .= '</p>';
		$content .= '</div>';
	}
	if (!$full) {
		$page = get_page_by_title( 'instagram' );
		$content .= '<a href="'.get_page_link($page->ID).'?ig='.$media->id.'"> Readmore </a>';
	}
	$content .= '</div>';

	return $content;	
}

function displayRating($rate,$media){
	$content = '<p>
					<div class="divClass" data-webRating="'.$rate->rating_point.'" data-webRatingN="'.$rate->rating_total.'" data-webRatingArg=\'{"type":"book","instagram_media_id":"'.$media->id.'"}\'></div>
				</p>';
	return $content;
}


function displayCommentForm($media){
	$content = '<div id="form'.$media->id.'" class="comment_form" class="form-style">
    				<div class="form-style-heading">Dare to Comment Me ?</div>
    					<div id="comment_body">
        					<label><span>Name <span class="required">*</span></span>
            					<input type="text" class="input-field" required="true" id="insta-name" name="insta-name">
        					</label>    
        				<p class="comment-form-comment">
            				<label for="comment">Comment</label> 
            				<textarea id="comment" name="insta-comment" cols="45" rows="4" describedby="form-allowed-tags" required="true"></textarea>
        				</p>
        				<label>
            				<span>&nbsp;</span><input type="submit" value="Submit" class="submit_btn" data-id="'.$media->id.'">
        				</label>
    				</div>
				</div>';
	return $content;
}

function displayPopUp($media,$livitDB){
	$rate = $livitDB->get_rating_by_id($media->id);
	$content = '<div style="display:none;width:500px;" id="inline'.$media->id.'">';
	//comment list
	
	$content .= displayComments($media,$livitDB);	

	//rating div 

	$content .= displayRating($rate,$media);

	// comment form
	
	$content .= displayCommentForm($media);

	$content .= '</div>';
	return $content;
}

function lookup_user_id($username){
	$instagram = init_instagram();
	$user = $instagram->searchUser($username,1);
	$id = $user->data[0]->id;
	return $id;
}

function init_instagram(){
	$client_id = get_option('livit_client_id');
	$secret_key = get_option('livit_secret_key'); 
	$call_back = get_option('livit_call_back_url');
	$access_token = get_option('livit_access_token');
	$instagram = new Instagram(array(
	'apiKey' => $client_id,
	'apiSecret' => $secret_key,
	'apiCallback' => $call_back
	));
	if ($access_token != '' ) {
		$instagram->setAccessToken($access_token);
	} 
	return $instagram;
}

function getLoginUrl(){
	$instagram = init_instagram();
	$login_url = $instagram->getLoginUrl();
	return $login_url;
}

function in_arrayi($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function display_feed_shortcode($atts,$content=null){
	$a = shortcode_atts( array(
	    'user' => 'didileeee',
	    'hashtag' => 'haphap',
	), $atts );
	$users = explode(",", $a['user']);
	$hashtag = explode(",", $a['hashtag']);
	$content = '<div class="instagram"><ul id="container-masonry" class="masonry-container" >';
	foreach ($users as $key => $value) {
		if (isset($hashtag[$key])){
			$content .= getDisplayFeed($value,$hashtag[$key]);
		} else {
			$content .= getDisplayFeed($value,false);
		}
		
	}
	$content .= '</ul></div>';
	$content .= '<script>'.init_web_rating().'</script>';
	return $content;

}
add_shortcode('instalivit','display_feed_shortcode');


function livit_css_and_js() {
	wp_register_style('livit_css', plugins_url('assets/instalivit.css',__FILE__ ));
	wp_enqueue_style('livit_css');
	wp_register_style('fancybox_css', plugins_url('assets/fancybox/jquery.fancybox.css',__FILE__ ));
	wp_enqueue_style('fancybox_css');
	wp_register_script('imagesmasonry_js', plugins_url('assets/imagesloaded.pkgd.min.js',__FILE__ ), array('jquery') );
	wp_enqueue_script('imagesmasonry_js');
	wp_register_script('masonry_js', 'http://cdnjs.cloudflare.com/ajax/libs/masonry/3.2.2/masonry.pkgd.min.js', array('jquery') );
	wp_enqueue_script('masonry_js');
	wp_register_script('fancybox_js', plugins_url('assets/fancybox/jquery.fancybox.pack.js',__FILE__ ), array('jquery'));
	wp_enqueue_script('fancybox_js');
	wp_register_script('webrating_js', plugins_url('assets/jquery.webRating.min.js',__FILE__ ), array('jquery'));
	wp_register_script('masonry_layout_js', plugins_url('assets/masonry-layout.js',__FILE__ ), array('jquery'));
	wp_enqueue_script('masonry_layout_js');
	wp_localize_script( 'webrating_js', 'LRATE_Ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'livitRateNonce' => wp_create_nonce( 'livitRate-nonce' ))
	); 
	wp_enqueue_script('webrating_js');
}
add_action( 'init','livit_css_and_js');
add_action( 'wp_ajax_ajax-livitrateSubmit', 'livitrateSubmit_func' );
add_action( 'wp_ajax_nopriv_ajax-livitrateSubmit', 'livitrateSubmit_func' ); 
add_action( 'wp_ajax_ajax-livitcommentSubmit', 'livitcommentSubmit_func' );
add_action( 'wp_ajax_nopriv_ajax-livitcommentSubmit', 'livitcommentSubmit_func' ); 

function livitcommentSubmit_func(){
	$nonce = $_POST['livitRateNonce'];
	if ( ! wp_verify_nonce( $nonce, 'livitRate-nonce' ) )
	die ( 'Busted!');
	$response['status'] = 'error';
	if (isset($_POST['comment']) && isset($_POST['name'])) {
		$livitDB = new LivitDatabase();
		$data['comment'] = $_POST['comment'];
		$data['Name'] = $_POST['name'];
		$data['instagram_media_id'] = $_POST['instagram_media_id'];
		if ($livitDB->insert_comment($data)){
			$response['status']='ok';
			$response['message']='updated';
		}
	}

	$response = json_encode( $response );
	// response output
	header( "Content-Type: application/json" );
	echo $response;
	// IMPORTANT: don't forget to "exit"
	exit;	
}

function livitrateSubmit_func() {
	// check nonce
	$nonce = $_POST['livitRateNonce'];
	if ( ! wp_verify_nonce( $nonce, 'livitRate-nonce' ) )
	die ( 'Busted!');
	$response['status'] = 'error';
	// generate the response
	if (isset($_POST['data']) && isset($_POST['score']) && isset($_POST['count'])) {
		$livitDB = new LivitDatabase();
		$datas = json_decode(str_replace("\\", "", $_POST['data']));
		$data['rating_point'] = $_POST['score'];
		$data['rating_total'] = $_POST['count'];
		$data['instagram_media_id'] = $datas->instagram_media_id;
		if ($livitDB->set_rating_media($data)){
			$response['status']='ok';
			$response['message']='updated';
		}
		
	}
	
	$response = json_encode( $response );
	// response output
	header( "Content-Type: application/json" );
	echo $response;
	// IMPORTANT: don't forget to "exit"
	exit;
} 


function init_web_rating(){
	$content = 'jQuery("div").webRating({     
        // count
        ratingCount     : 5,

        // image & color
        imgSrc          : "'.plugins_url('assets/images/icons.png',__FILE__ ).'",
        xLocation: 80,
		yLocation: 31,
		width: 30, //in px
		height: 30, //in px
		autoParentWidth: true,
		onClass: \'starOn\',
		offClass: \'starOff\',
		onClassHover: \'starOnHover\',
		offClassHover: \'starOffHover\',

        //on click funcitons
        cookieEnable  : false,
        cookiePrefix  : "myRating_",
        maxClick      : 1,
        onClick       : function(clickScore, data){
            //Your function & post action
            //saveReviewData(clickScore, data);
            var divContainer = jQuery("div.divClass[data-webRatingArg=\'"+data+"\'] #bgDiv");
            console.log(clickScore + "->" + data);
            var score = jQuery(divContainer).data("score");
            var count = jQuery(divContainer).data("count");
            jQuery.post(
            	LRATE_Ajax.ajaxurl,
            	{
		            // wp ajax action
		            action : \'ajax-livitrateSubmit\',
		            // vars
		            data : data,
		            score: score,
		            count: count,
		            // send the nonce along with the request
		            livitRateNonce : LRATE_Ajax.livitRateNonce
		        },
		        function( response ) {
		            console.log( response );
		        }
            ); 
        },

        //Tooltip
        tp_showAverage  : true,
        prefixAverage   : "Avg",
        tp_eachStar     : {\'1\':\'Very Bad\',\'2\':\'Bad\',\'3\':\'Ok\',\'4\':\'Good\',\'5\':\'Very Good\'} //Rating guide
	}); 
	';
	return $content;
}


// Register Custom Post Type
function livit_custom_post_type() {

	$labels = array(
		'name'                => _x( 'Instagrams', 'Post Type General Name', 'livit_instagram' ),
		'singular_name'       => _x( 'instagram', 'Post Type Singular Name', 'livit_instagram' ),
		'menu_name'           => __( 'Livit Instagram', 'livit_instagram' ),
		'name_admin_bar'      => __( 'Livit Instagram', 'livit_instagram' ),
		'parent_item_colon'   => __( 'Parent Item:', 'livit_instagram' ),
		'all_items'           => __( 'All Items', 'livit_instagram' ),
		'add_new_item'        => __( 'Add New Item', 'livit_instagram' ),
		'add_new'             => __( 'Add New', 'livit_instagram' ),
		'new_item'            => __( 'New Item', 'livit_instagram' ),
		'edit_item'           => __( 'Edit Item', 'livit_instagram' ),
		'update_item'         => __( 'Update Item', 'livit_instagram' ),
		'view_item'           => __( 'View Item', 'livit_instagram' ),
		'search_items'        => __( 'Search Item', 'livit_instagram' ),
		'not_found'           => __( 'Not found', 'livit_instagram' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'livit_instagram' ),
	);
	$rewrite = array(
		'slug'                => 'insta',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);
	$args = array(
		'label'               => __( 'livit_insta', 'livit_instagram' ),
		'description'         => __( 'livit_insta', 'livit_instagram' ),
		'labels'              => $labels,
		'supports'            => array( ),
		'taxonomies'          => array( 'category', 'post_tag' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'post',
	);
	register_post_type( 'livit_insta', $args );

}

// Hook into the 'init' action
// add_action( 'init', 'livit_custom_post_type', 0 );
?>