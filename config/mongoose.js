var config = require('./config'),
mongoose = require('mongoose');


require('../app/models/administrator.server.model');
require('../app/models/user.server.model');



module.exports = function() {
	mongoose.Promise = global.Promise;	
	var db = mongoose.connect(config.db);
	return db;
};