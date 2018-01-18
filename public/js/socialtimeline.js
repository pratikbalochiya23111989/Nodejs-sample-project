function pressLike(id){
	$.post("/social/postFeedBack",
    {
        social_id: id
    },
    function(data, status){
    	$('#getLike_'+id).html("<i class='fa fa-thumbs-o-up'></i> "+data);
    });
}

function pressComment(id){
	var comment = $('#comment_'+id).val();
	$('#comment_'+id).val('');
	if(comment==''){
		$('#comment_'+id).addClass('errSocialText');
	}
	else {
		$('#comment_'+id).removeClass('errSocialText');
		$.post("/social/postCommentFeedBack",
	    {
	        social_id : id,
	        comment : comment
	    },
	    function(data, status){
	    	$('#getComment_'+id).html("<i class='fa fa-comments'></i> "+data);

	    	$.post("/social/postListUserComment",
		    {
		        social_id : id
		    },
		    function(data, status){
		    	$('.getCommentUserList_'+id).html(data);
		    });
	    });
	}
}

function pressListLikeUsers(id){
	$.post("/social/postListLikeUsers",
    {
        social_id: id
    },
    function(data, status){
    	$('#displayListLikeUsers').html(data);
    	$('#socialLikeUserList').modal('show');
    });
}