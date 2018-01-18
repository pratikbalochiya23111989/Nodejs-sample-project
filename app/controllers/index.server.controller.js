var Administrator = require('mongoose').model('Administrator');
var User = require('mongoose').model('User'),passport = require('passport');
exports.render = function(req, res) {
	res.render('login', {
		layout:false,
		remember_me : req.session.remember_me,
		remember_email : req.session.remember_email,
		remember_password : req.session.remember_password,
		messages : req.flash('error') || req.flash('info'),
		messages : req.flash('info'),
	});
};

exports.authentication = function(req, res) {
	var ip = require('ip');
	if(req.body.remember_me=='on'){
		req.session.remember_me = "yes";
		req.session.remember_email = req.body.email;
		req.session.remember_password = req.body.password;
	}
	else {
		req.session.remember_me = "no";
		req.session.remember_email = '';
		req.session.remember_password = '';
	}
	var email = req.body.email;
	var md5 = require('md5');
	var password = md5(req.body.password);
	
	var date = new Date();
	Administrator.findOne({email : email, password : password}, function(err, admin) {
		if(admin){
			req.session.type = 'admin';
			req.session.uniqueid = admin._id;
			req.session.name = admin.first_name+" "+admin.last_name;
			req.session.email = admin.email;
			return res.redirect('/admin/dashboard/list');
		}
		else {
			req.flash('info', 'Invalid Email Or Password.');
			return res.redirect('/');
		}
	});
};

exports.forgotPassword = function(req, res) {
	if(req.body.email){
		var length = 6;
		code = Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1),
		fullUrl = req.protocol + '://' + req.get('host'),
		link = fullUrl+'/reset_password/'+code,
		emailTemplate = "<html xmlns='http://www.w3.org/1999/xhtml'><head>	<meta name='viewport' content='width=device-width' /><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />	<title>Email</title>	<style type='text/css'>table tr td a{color:#ffffff!important;text-decoration:none}.reset_password{color:#ffffff!important;text-decoration:none!important}</style></head><body bgcolor='#FFFFFF'><table width='96%' border='0' cellspacing='0' cellpadding='0' bgcolor='#f2f5f7' style='border-right:1px solid #d3d9dd;border-bottom:20px solid #000;padding:1% 2% 2% 2%;margin:0 0 0 2%;border-radius:10px'>	<tr>	<td><table width='100%' border='0' cellspacing='0' cellpadding='0'>	<tr>	<td>	<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#000' style='border-radius:5px;padding:20px 0'><tr><td style='font-family:Arial,Helvetica,sans-serif;color:white;padding-left:45%'><h1>DEMO</h1></td></tr></table></td></tr>				<tr>					<td>						<table width='100%' border='0' cellspacing='0' cellpadding='0' style='padding:10px 0 0 0'><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 10px 10px'><span>Dear, <a href='javascript:void(0);' style='color:#000 !important; text-decoration: none !important;'>#NAME#</a></td></tr>							<tr>								<td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:0 0 0 10px'><p>You requested for reset password</p></td>							</tr>							<tr>								<td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:0 0 0 10px'><p>Click on below button to reset your password</p>								</td>							</tr>							<tr>								<td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:0 0 10px 10px'>									<p></p>								</td>								</tr>								<tr style=''>									<td style='font-size:20px;font-family:Clan,Helvetica,Arial,sans-serif;font-weight:normal;color:#ffffff!important;text-decoration:none;background-color:#000;border-top:12px solid #000;border-bottom:12px solid #000;border-right:10px solid #000;border-left:10px solid #000;display:block;text-transform:uppercase;width:90%;text-align:center!important;margin:0 auto' target='_blank'>										<a href='#LINK#' class='reset_password' style='font-size:20px;font-family:Clan,Helvetica,Arial,sans-serif;font-weight:normal;text-decoration:none;background-color:#000;border-top:0 solid #000;border-bottom:0 solid #000;border-right:0 solid #000;border-left:10px solid #000;display:block;text-transform:uppercase,width:14%;margin:0 auto;color:#ffffff!important' target='_blank'><span style='text-decoration:none!important;color:#ffffff!important'>Reset Password</span></a></td>									</tr>									<tr>										<td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:0 0 10px 10px'><p></p></td></tr></table></td></tr></table></td></tr></table></body></html>";
		api_key = 'key-43cf4c016eb85a389fc22df0dd7bf6f4',
		domain = 'dotzapper.com',
		mailgun = require('mailgun-js')({apiKey: api_key, domain: domain});
		
		User.findOne({email : req.body.email}, function(err, user) {
			console.log(user);
			if(user){
				emailTemplate = emailTemplate.replace("#NAME#", user.email);
				emailTemplate = emailTemplate.replace("#LINK#", link);
				
				var data = {
				  from: 'DEMO <demo1.testing1@gmail.com>',
				  to: user.email,
				  subject: 'DEMO - You requested for reset password',
				  html: emailTemplate
				};
				 
				mailgun.messages().send(data, function (error, body) {
					User.findByIdAndUpdate(user._id, { code: code }, function(err, userRes) {
						console.log(userRes);
						req.flash('info', 'We sent you a link for reset password at your registered email id!');
						res.redirect('/');
					});
				});
			}
			else {
				req.flash('info', 'Please enter correct email id.');
				return res.redirect('/');
			}
		});
	} // end of if
	else {
		res.render('forgotpassword', {
			layout:false
		});
	}
};

exports.logout = function(req, res) {
	var date = new Date();
	req.flash('info', 'Thank you for using control panel!')
	res.redirect('/');
};

exports.reset_password = function(req, res){
	if(req.body.code){
		var md5 = require('md5');
		var updateObj = {
			code : '',
			password : md5(req.body.password)
		}
		User.findOne({code : req.body.code}, function(err, user) {
			if(user){
				console.log("inif");
				User.findByIdAndUpdate(user._id, updateObj, function(err, userRes) {
					req.flash('info', 'Your password changed successfully!')
					res.redirect('/');
				});	
			}
			else {
				console.log("else");
				req.flash('info', "You can't use same email for reset password!")
				res.redirect('/');
			}
		});
	}
	else {
		var code = req.params.code;
		res.render('resetpassword', {
			code: code,
			layout:false
		});
	}
};

exports.resigter = function(req, res) {
	var date = new Date();
	req.flash('info', 'Thank you for using control panel!')
	res.redirect('/');
};

