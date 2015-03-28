<?php
class LivitDatabase 
{
	public $media_table;
	public $rate_table;
	public $comments_table;
	
	function __construct()
	{
		if (!isset($wpdb)) $wpdb = $GLOBALS['wpdb'];
		$this->media_table = $wpdb->prefix . 'livit_insta'; 
		$this->rate_table = $wpdb->prefix . 'livit_insta_rate';
		$this->comments_table = $wpdb->prefix . 'livit_insta_comment';
	}

	public function create_database(){
		if (!isset($wpdb)) $wpdb = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql = "CREATE TABLE IF NOT EXISTS {$this->rate_table} (
		  	id int(11) NOT NULL AUTO_INCREMENT,
		    instagram_media_id varchar(50) NOT NULL,
		    rating_total int(5) NOT NULL DEFAULT '0',
		    rating_point float NOT NULL DEFAULT '0',
		    PRIMARY KEY (id),
		    UNIQUE KEY instagram_media_id (instagram_media_id)
		) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$this->comments_table} (
		  	id int(11) NOT NULL AUTO_INCREMENT,
		  	instagram_media_id varchar(50) NOT NULL,
		  	comment text NOT NULL,
		  	Name text NOT NULL,
		  	PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$this->media_table} (
		  	id int(11) NOT NULL AUTO_INCREMENT,
		  	instagram_media_id varchar(50) NOT NULL,
		  	PRIMARY KEY (id),
		  	UNIQUE KEY instagram_media_id (instagram_media_id)
		) $charset_collate;";
		dbDelta( $sql );
		
	}

	public function insert_media_id($id){
		global $wpdb;
	 	if ($this->check_media_id($id)){
			$wpdb->insert( 
				$this->media_table, 
				array( 
			        'instagram_media_id' => $id
				), 
				array( 
			         '%s'
				) 
			);
			$wpdb->insert( 
				$this->rate_table, 
				array( 
			        'instagram_media_id' => $id,
			        'rating_total' => 0,
			        'rating_point' => 0,
				), 
				array( 
			         '%s',
			         '%d',
			         '%f'
				) 
			);
		}
	}

	public function check_media_id($id){
		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare( 
			"
				SELECT COUNT(*) 
				FROM `".$this->media_table."`
				WHERE `instagram_media_id` = %s
			", 
			$id
		) );
		// print_r($count);
		// echo "</br>";
		// print_r($id);
		if ( $count == 0 ) {
			return true;	
		} else {
			return false;
		}
	}

	public function get_rating_by_id($id){
		global $wpdb;
		$rating = $wpdb->get_row("SELECT * FROM {$this->rate_table} WHERE instagram_media_id = '{$id}'");
		return $rating;
	}

	public function set_rating_media($data){
		global $wpdb;
		$result = $wpdb->update( 
			$this->rate_table, 
			array( 
				'rating_total' => $data['rating_total'],	// integer
				'rating_point' => $data['rating_point']	// float 
			), 
			array( 'instagram_media_id' => $data['instagram_media_id'] ), 
			array( 
				'%d',	// value1
				'%f'	// value2
			), 
			array( '%s' ) 
		);
		// print_r($result);
		if ($result == 0 ) {
			return false;
		} else {
			return true;
		}
	}

	public function insert_comment($data){
		global $wpdb;
		$result = $wpdb->insert( 
			$this->comments_table, 
			array( 
		        'instagram_media_id' => $data['instagram_media_id'] ,
		        'comment' => $data['comment'],
		        'Name' => $data['Name'],
			), 
			array( 
		         '%s',
		         '%s',
		         '%s'
			) 
		);
		// print_r($result);
		if ($result == 0 ) {
			return false;
		} else {
			return true;
		}

	}

	public function get_recent_comment($id){
		global $wpdb;
		$results = $wpdb->get_results( 
			"
			SELECT * 
			FROM {$this->comments_table}
			WHERE instagram_media_id = '{$id}'
			ORDER BY id desc
			LIMIT 3
			"
		);

		return $results;
	}

	public function get_all_comment($id){
		global $wpdb;
		$results = $wpdb->get_results( 
			"
			SELECT * 
			FROM {$this->comments_table}
			WHERE instagram_media_id = '{$id}'
			ORDER BY id desc
			"
		);

		return $results;
	}
}
?>