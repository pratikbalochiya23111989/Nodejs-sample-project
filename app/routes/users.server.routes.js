var users = require('../../app/controllers/users.server.controller'),
	passport = require('passport');

module.exports = function(app) {

	//get users list and list different action
	app.route('/users/list')
		.get(users.list)
		.post(users.list_action);
	
	//add user 	
	app.get('/users/add', users.add);

	//create user 
	app.post('/users/create', users.create);

	//edit user 	
	app.get('/users/edit/:userId', users.edit);

	//update user 
	app.post('/users/update', users.update);
	
	//get users login logs 
	app.get('/users/loginlogs', users.loginlogs);

	
	//login route 
	app.route('/login')
		.get(users.renderLogin)
		.post(passport.authenticate('local', {
			successRedirect: '/',
			failureRedirect: '/login',
			failureFlash: true
		}));

	//logout user 	
	app.get('/logout', users.logout);

	app.get('/users/changepassword', users.changepassword);
	app.get('/users/accountsetting', users.accountsetting);
};
