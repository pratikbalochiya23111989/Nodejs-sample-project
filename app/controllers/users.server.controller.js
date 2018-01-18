var User = require('mongoose').model('User'),passport = require('passport');
var moment = require('moment')

//login page 
exports.renderLogin = function(req, res, next) {
	if (!req.user) {
		res.render('users/login', {
			layout:false,
			messages: req.flash('error') || req.flash('info')
		});
	}
	else {
		return res.redirect('/');
	}
};

//forgot password page 
exports.renderForgotPassword = function(req, res, next) {
	if (!req.user) {
		res.render('users/forgotpassword', {
			layout:false,
			messages: req.flash('error') || req.flash('info')
		});
	}
	else {
		return res.redirect('/');
	}
};

exports.changepassword = function(req, res, next) {
	res.render('changepassword/changepassword', {
		logintype : req.session.type,
		loginid : req.session.uniqueid,
		loginname : req.session.name,
		loginemail : req.session.email,
		messages: req.flash('error') || req.flash('info'),
		
	});
};

exports.accountsetting = function(req, res, next) {
	res.render('accountsetting/accountsetting', {
		logintype : req.session.type,
		loginid : req.session.uniqueid,
		loginname : req.session.name,
		loginemail : req.session.email,
		messages: req.flash('error') || req.flash('info'),
		
	});
};


//list of all users 
exports.list = function(req, res, next) {
	User.find({}, function(err, users) {
		if (err) {
			return next(err);
		}
		else {
			res.render('users/list', {
				users: users,
				messages: req.flash('error') || req.flash('info'),
				messages:req.flash('info'),
				moment: moment
			});
		}
	});
};

//active , inactive , delete action 
exports.list_action = function(req, res, next) {
	var action = req.body.btnAction
	
	switch(action)
	{
		case "active":
		case "inactive":
			User.updateMany(
				{ '_id':{ $in : req.body.users } },
				{ $set: { "status": req.body.btnAction } },
				function (err,val) {
					if (err) {
						return next(err);
					}
					else {
						res.redirect('/users/list');

					}
				}
			)
			break;
		case "delete":
			User.deleteMany(
				{ '_id':{ $in : req.body.users } },
				function (err,val) {
					if (err) {
						return next(err);
					}
					else {
						res.redirect('/users/list');

					}
				}
			)
			break;
	}
	
};

//add new user
exports.add = function(req, res, next) {
	res.render('users/add', {messages: req.flash('error') || req.flash('info')});
};

//create new user 
exports.create = function(req, res, next) {	
	var user = new User(req.body);
	user.save(function(err) {
		if (err) {
			return next(err);
		}
		else {
			req.flash('info', 'New Administrator is added successfully.');
			return res.redirect('/users/list');
		}
	});
};

//edit user
exports.edit = function(req, res, next) {
	var userId = req.params.userId;
	User.findOne({
			_id: userId
		}, 
		function(err, user) {
			if (err) {
				return next(err);
			}
			else {
				res.render('users/edit', {
					user: user,
					messages: req.flash('error') || req.flash('info')
				});

			}
		}
	);


};

//update user
exports.update = function(req, res, next) {
	User.findByIdAndUpdate(req.body.id, req.body, function(err, user) {
		if (err) {
			return next(err);
		}
		else {
			req.flash('info', 'Administrator is updated successfully.');
			return res.redirect('/users/list');
		}
	});
};

//users login logs 
exports.loginlogs = function(req, res, next) {
	User.find({}, function(err, users) {
		if (err) {
			return next(err);
		}
		else {
			res.render('users/list_login_logs', {
				users: users,
				messages: req.flash('info'),
				moment: moment
			});
		}
	});
};