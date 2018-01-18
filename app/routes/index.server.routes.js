var index = require('../controllers/index.server.controller.js');

module.exports = function(app) {
	app.get('/', index.render);
	app.post('/authentication', index.authentication);
	app.route('/forgotpassword')
		.get(index.forgotPassword)
		.post(index.forgotPassword);
	app.get('/logout', index.logout);
	app.get('/reset_password/:code',index.reset_password);
	app.post('/reset_password',index.reset_password);
	
};