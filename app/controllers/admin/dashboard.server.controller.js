var Administrator = require('mongoose').model('Administrator');
var User = require('mongoose').model('User');

//load chart
exports.ajax_loadchart = function(req, res, next) {
	res.render('admin/dashboard/ajax_loadchart', {
		logintype : 'ajax',
		messages: req.flash('error') || req.flash('info')
	});
};
// messages: req.flash('error') || req.flash('info')
exports.list = function(req, res, next) {
	Administrator.count({}, function(err, totadmin) {
		if (err) {
			return next(err);
		}
		else {
			User.count({}, function(err, totusers) {
				if (err) {
					return next(err);
				}
				else {
					res.render('admin/dashboard/list', {
						'logintype' : req.session.type,
						'loginid' : req.session.uniqueid,
						'loginname' : req.session.name,
						'loginemail' : req.session.email,
						'totaladmins' : totadmin,
						'totalusers' : totusers,
						'messages' : req.flash('error') || req.flash('info')
					});
				}
			});	
		}
	});
};