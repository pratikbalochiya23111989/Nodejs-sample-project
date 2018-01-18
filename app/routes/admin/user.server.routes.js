
var user = require('../../controllers/admin/user.server.controller'),
	passport = require('passport');

module.exports = function(app) {

	//list user 	
	app.get('/admin/user/list', user.list);

	//edit user 
	app.get('/admin/user/edit/:id', user.edit);

	//create user 
	app.post('/admin/user/create', user.create);

	//create user 
	app.post('/admin/user/update', user.update);

	//add user 	
	app.get('/admin/user/add', user.add);	

	//user list action
	app.post('/admin/user/list', user.list_action);

};
