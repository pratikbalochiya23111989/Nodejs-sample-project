function check_fname_validation(str) {	
	if(str.trim() != ''){
		var strRegex = new RegExp(/^[a-zA-Z()]+$/);
		var validstr = strRegex.test(str);
		if(!validstr){
			$('.errAreaFName').show();
			$('#lblFirstname').html('Please enter your correct firstname.');
			return 0;
		}
		else {
			$('.errAreaFName').hide();
			return 1;
		}
	}
	else {
		$('.errAreaFName').show();
		$('#lblFirstname').html('Please enter your firstname.');
		return 0;
	}
}
function check_lname_validation(str) {	
	if(str.trim() != ''){
		var strRegex = new RegExp(/^[a-zA-Z()]+$/);
		var validstr = strRegex.test(str);
		if(!validstr){
			$('.errAreaLName').show();
			$('#lblLirstname').html('Please enter your correct lastname.');
			return 0;
		}
		else {
			$('.errAreaLName').hide();
			return 1;
		}
	}
	else {
		$('.errAreaLName').show();
		$('#lblLirstname').html('Please enter your lastname.');
		return 0;
	}
}

function check_mname_validation(str) {	
	if(str.trim() != ''){
		var strRegex = new RegExp(/^[a-zA-Z()]+$/);
		var validstr = strRegex.test(str);
		if(!validstr){
			$('.errMName').show();
			$('#lblMiddlename').html('Please enter your middlename.');
			return 0;
		}
		else {
			$('.errMName').hide();
			return 1;
		}
	}
	else {
		$('.errMName').show();
		$('#lblMiddlename').html('Please enter your middlename.');
		return 0;
	}
}

function check_email_validation(str){
	if(str.trim() == ''){
		$('.errAreaEmail').show();
		$('#lblEmail').html('Please enter your email ID.');
		return 0;
	}
	else {
		var emailRegex = new RegExp(/^([\w\.\-]+)@([\w\-]+)((\.(\w){2,3})+)$/i);
		var validemail = emailRegex.test(str);
		if(!validemail){
			$('.errAreaEmail').show();
			$('#lblEmail').html('Please enter your correct email ID.');
			return 0;
		}
		else {
			$('.errAreaEmail').hide();
			return 1;
		}
	}
}
function check_email_validation_without_msg(email_id){
	if(email_id == ''){
		return 1;
	}
	else {
		var emailRegex = new RegExp(/^([\w\.\-]+)@([\w\-]+)((\.(\w){2,3})+)$/i);
		var validemail = emailRegex.test(email_id);
		if(!validemail){
			return 2;
		}
		else {
			return 0;
		}
	}
}
function check_password_validation(str){
	if(str.trim() != ''){		
		$('.errAreaPassword').hide();
		return 1;
	}
	else {
		$('.errAreaPassword').show();
		$('#lblPassword').html('Please enter your password.');
		return 0;
	}
}

function check_confirm_password_validation(password,confirm_password){
	if(confirm_password.trim() != ''){
		if(password!=confirm_password){
			$('.errAreaCnfPassword').show();
			$('#lblCnfPassword').html('Password & confirm password must be same.');
			return 0;
		}
		else {
			$('.errAreaCnfPassword').hide();
			return 1;
		}
	}
	else {
		$('.errAreaCnfPassword').show();
		$('#lblCnfPassword').html('Please enter your confirm password.');
		return 0;
	}
}

function check_status(status){
	if(status!=''){
		$('#err_status_area').hide();
		return 1;
	}
	else {
		$('#err_status_area').show();
		$('#lblstatuserrmsg').html('Please select your Status.');
		return 0;
	}
}

function check_empty_validation(str,errtitle,errlbl,msg){
	if($.trim(str) == ''){
		$('.'+errtitle).show();
		$('#'+errlbl).html(msg);
		return 0;
	}
	else {
		$('.'+errtitle).hide();
		return 1;
	}
}

function check_title_validation(str){
	if(str.trim() == ''){
		$('.errAreaTitle').show();
		$('#lblTitle').html('Please enter title.');
		return 0;
	}
	else {
		$('.errAreaTitle').hide();
		return 1;
	}
}

function check_businessname_validation(str){
	if(str.trim() == ''){
		$('.errAreaBName').show();
		$('#lblBusinessName').html('Please enter your business name.');
		return 0;
	}
	else {
		$('.errAreaBName').hide();
		return 1;
	}
}

function check_phone_validation(str){
	if(str.trim() == ''){
		$('.errAreaPhone').show();
		$('#lblPhone').html('Please enter your phone no.');
		return 0;
	}
	else {
		$('.errAreaPhone').hide();
		return 1;
	}
}

function check_content_description(str){
	if(str.trim() == ''){
		$('.errAreaContent').show();
		$('#lblContent').html('Please enter your content.');
		return 0;
	}
	else {
		$('.errAreaContent').hide();
		return 1;
	}
}
function check_description_validation(str){
	if(str.trim() == ''){
		$('.errAreaDesc').show();
		$('#lblDesc').html('Please enter description.');
		return 0;
	}
	else {
		$('.errAreaDesc').hide();
		return 1;
	}
}

function check_startdate_validation(str){
	if(str.trim() == ''){
		$('.errAreaStartDate').show();
		$('#lblStartDate').html('Please enter startdate.');
		return 0;
	}
	else {
		$('.errAreaStartDate').hide();
		return 1;
	}
}
function check_enddate_validation(str){
	if(str.trim() == ''){
		$('.errAreaEndDate').show();
		$('#lblEndDate').html('Please enter enddate.');
		return 0;
	}
	else {
		$('.errAreaEndDate').hide();
		return 1;
	}
}
function check_department_validation(str){
	if(str == null){
		$('.errAreaDepartment').show();
		$('#lblDepartment').html('Please choose department.');
		return 0;
	}
	else {
		$('.errAreaDepartment').hide();
		return 1;
	}
}
function check_member_validation(str){
	if(str == null){
		$('.errAreaMember').show();
		$('#lblMember').html('Please choose member.');
		return 0;
	}
	else {
		$('.errAreaMember').hide();
		return 1;
	}
}
function check_team_validation(str){
	if(str == null){
		$('.errAreaTeam').show();
		$('#lblTeam').html('Please choose team.');
		return 0;
	}
	else {
		$('.errAreaTeam').hide();
		return 1;
	}
}
function check_target_validation(str){
	if(str.trim() == ''){
		$('.errAreaTarget').show();
		$('#lblTarget').html('Please enter target.');
		return 0;
	}
	else {
		$('.errAreaTarget').hide();
		return 1;
	}
}
function check_category_validation(str){
	if(str.trim() == ''){
		$('.errAreaCat').show();
		$('#lblCat').html('Please choose category.');
		return 0;
	}
	else {
		$('.errAreaCat').hide();
		return 1;
	}
}
function check_photo_validation(str){
	if(str.trim() == ''){
		$('.errAreaPhoto').show();
		$('#lblPhoto').html('Please choose photo.');
		return 0;
	}
	else {
		$('.errAreaPhoto').hide();
		return 1;
	}
}
function check_photo_formate(status)
{
	var a = status.substring(status.lastIndexOf('.') + 1).toLowerCase();
	if(a == 'jpg'  ||a == 'JPG' ||a == 'jpeg' ||a == 'JPEG' ||a == 'png'){
         $('.msgformat1').hide();
		$('.errAreaPhoto1').hide();
			return 1;
   		}
    	else{
    		$('.msgformat1').show();
    	$('.errAreaPhoto1').show();
       	$('#lblPhoto1').html('Error while uploading.');
		return 0;
   		 }
}

function check_type_validation(str){
	if(str.trim() == ''){
		$('.errAreaType').show();
		$('#lblType').html('Please select type.');
		return 0;
	}
	else {
		$('.errAreaType').hide();
		return 1;
	}
}
function check_user_validation(str){
	if(str == null){
		$('.errAreaUser').show();
		$('#lblUser').html('Please select users.');
		return 0;
	}
	else {
		$('.errAreaUser').hide();
		return 1;
	}
}