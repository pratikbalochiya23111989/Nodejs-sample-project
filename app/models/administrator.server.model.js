//define schema and mongoose 
var mongoose = require('mongoose'),
	crypto = require('crypto'),
	Schema = mongoose.Schema;

var AdministratorSchema = new Schema({
	email:String,
	password:String,
	code: String,
	created_at: { type: Date, default: Date.now }
});

// save admin
AdministratorSchema.pre('save',
	function(next) {
		if (this.password) {
			var md5 = crypto.createHash('md5');
			this.password = md5.update(this.password).digest('hex');
		}
		next();
	}
);

mongoose.model('Administrator', AdministratorSchema);