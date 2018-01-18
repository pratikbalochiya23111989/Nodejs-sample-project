var passport = require('passport'),
	LocalStrategy = require('passport-local').Strategy, 
	Administrator = require('mongoose').model('Administrator');

module.exports = function() {
	passport.use(new LocalStrategy(function(email, password, done) {
		Administrator.findOne({email: email},
			function(err, administrators) {
				console.log(administrators);
				if (err) {
					return done(err);
				}
				if (!administrators) {
					return done(null, false, {message: 'Invalid Email ID'});
				}		
				if (!administrators.authenticate(password)) {
					return done(null, false, {message: 'Invalid Password'});
				}
				return done(null, administrators);
			}
		);
	}));
};

     