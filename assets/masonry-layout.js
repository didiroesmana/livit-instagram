jQuery(document).ready(function() {
	var $container = jQuery('#container-masonry');
	var gutter = 20;
 	var min_width = 200;
	// initialize
	//$container.masonry({
	//  columnWidth: 200,
	//  itemSelector: 'li.instagram-item'
	//});
	
	$container.imagesLoaded( function(){

		$container.masonry({
			columnWidth: 200,
			itemSelector: ".instagram-item",
			gutterWidth: gutter,
			isAnimated: true,
			
		});
		
	});


	jQuery(".various").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: false,
		width		: '80%',
		height		: '80%',
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none'
	});

	jQuery(".comment_form .submit_btn").click(function() {
       
        var proceed = true;
        var id = jQuery(this).attr('data-id');
        if (id == '') {
            proceed = false;
        }
        if(proceed) //everything looks good! proceed...
        {
            //get input field values data to be sent to server
            post_data = {
            	'action' : 'ajax-livitcommentSubmit',
                'instagram_media_id'     : id,
                'name'    : jQuery('#form'+id+' input[name=insta-name]').val(),
                'comment'  : jQuery('#form'+id+' textarea[name=insta-comment]').val(),
                'livitRateNonce' : LRATE_Ajax.livitRateNonce
            };
           
            //Ajax post data to server
            jQuery.post(LRATE_Ajax.ajaxurl, post_data, function(response){  
                if(response.status == 'error'){ //load json data from server and output message    
                    output = '<div class="error">'+response.text+'</div>';
                }else{
                    output = '<div class="success">'+response.text+'</div>';
                    //reset values in all input fields
                    jQuery(".comment_form  input[required=true], .comment_form textarea[required=true]").val('');
                    <!-- jQuery(".comment_form #comment_body").slideUp(); //hide form after success -->
                }
                
            }, 'json');
        }
    });
});