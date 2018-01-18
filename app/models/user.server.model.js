//define schema and mongoose 
var mongoose = require('mongoose'),
	crypto = require('crypto'),
	Schema = mongoose.Schema;

var UserSchema = new Schema({
	firstname: String,
	lastname:String,
	middlename:String,
	code:String,
	email: String,
	password: String,
	status: {type: String, enum: ['Active', 'Inactive','Deleted']},
	created_at: { type: Date, default: Date.now },
	updated_at: { type: Date, default: Date.now }
});

//encryption of password for user schema
UserSchema.pre('save',
	function(next) {
		if (this.password) {
			var md5 = crypto.createHash('md5');
			this.password = md5.update(this.password).digest('hex');
		}
		next();
	}
);

//authenticate user schema
UserSchema.methods.authenticate = function(password) {
	var md5 = crypto.createHash('md5');
	md5 = md5.update(password).digest('hex');
	return this.password === md5;
};

//find user unique name 
UserSchema.statics.findUniqueUsername = function(username, suffix, callback) {

	var _this = this;
	var possibleUsername = username + (suffix || '');
	_this.findOne(
		{username: possibleUsername},
			function(err, user) {
				if (!err) {
					if (!user) {
						callback(possibleUsername);
					}
					else {		
						return _this.findUniqueUsername(username, (suffix || 0) + 1, callback);
					}
				}
				else {
					callback(null);
				}
			}
		);
};

mongoose.model('User', UserSchema);