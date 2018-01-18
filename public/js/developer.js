$(document).ready(function(){
	$("#check_all").change(function(){
		$('input:checkbox').prop('checked', this.checked);
	});
	$("#btn-active").click(function() {
		alert("");
		$("#btnAction").val("Active");
		var atLeastOneIsChecked = $('input[name="iId[]"]:checked').length > 0;
		if(atLeastOneIsChecked == false){
	        $(".message-modal-body").html( "<p class='modaltpbtmspc'>Please Select Atleast One Record </p>" );
    	    $("#MessageModal").modal('show');
        	return false;
        }
	});
	$("#btn-inactive").click(function() {
		$("#btnAction").val("Inactive");
		var atLeastOneIsChecked = $('input[name="iId[]"]:checked').length > 0;
		if(atLeastOneIsChecked == false){
	        $(".message-modal-body").html( "<p class='modaltpbtmspc'>Please Select Atleast One Record </p>" );
    	    $("#MessageModal").modal('show');
        	return false;
        }
	});
	$("#btn-delete").click(function() {
		$("#btnAction").val("Deleted");
		var atLeastOneIsChecked = $('input[name="iId[]"]:checked').length > 0;
		if(atLeastOneIsChecked == false){
	        $(".message-modal-body").html( "<p class='modaltpbtmspc'>Please Select Atleast One Record </p>" );
    	    $("#MessageModal").modal('show');
        	return false;
        }
	});
	$('.validationimg').hide();
	setTimeout(function(){
		$('#msg_display_area').fadeOut(2000);
 	}, 2000);
 });

function cancel(url){
	window.location.href = url;
}

$(function () {
    $("body").on('click keypress', function () {
        ResetThisSession();
    });
});

var timeInSecondsAfterSessionOut = 300; // change this to change session time out (in seconds).
var secondTick = 0;

function ResetThisSession() {
    secondTick = 0;
}

function StartThisSessionTimer() {
    secondTick++;
    var timeLeft = ((timeInSecondsAfterSessionOut - secondTick) / 60).toFixed(0); // in minutes
	timeLeft = timeInSecondsAfterSessionOut - secondTick; // override, we have 30 secs only 
	
	if (secondTick > timeInSecondsAfterSessionOut) {
        clearTimeout(tick);
		
		$.ajax({url: "/check_session_created", success: function(result){
			if(result=='yes'){
				window.location = "/session_timeout";
			}
		}});
    }
    tick = setTimeout("StartThisSessionTimer()", 1000);
}

StartThisSessionTimer();

