//all required configurations
var config = require('./config'),
	express = require('express'),
	bodyParser = require('body-parser'),
	passport = require('passport'),
	flash = require('connect-flash'),
	session = require('express-session'),
	breadcrumbs = require('express-breadcrumbs'),
	expressLayouts = require('express-ejs-layouts'),
	 fileUpload = require('express-fileupload');
	//parse multipart/form-data    
	fs = require('fs');
	var moment = require('moment');
	moment().format();
//ends here

//exports modules
module.exports = function() {
	var app = express();
	app.use(bodyParser.urlencoded({limit: '500mb',extended: true}));
	
	var timeout = require('connect-timeout');
	app.use(timeout(600000));

	app.use(bodyParser.json());
	app.use(fileUpload());
	app.use(session({
		saveUninitialized: true,
		resave: false,
		secret: 'OurSuperSecretCookieSecret'
	}));

	//set path for views 
	app.set('layout', './layouts/main_admin');
	app.set('views', './app/views');
	app.set('view engine', 'ejs');
	app.use(expressLayouts);
	//ends here 

	app.use(flash());
	app.use(passport.initialize());
	app.use(passport.session());

	app.use(breadcrumbs.init());
	// fs.readdirSync('./app/routes').forEach(function (file) {
	// 	if(file.substr(-3) == '.js') {
	// 		require('../app/routes/' + file);
	// 		//console.log(route)
	// 		//route.controller(app);
	// 	}
	// });
	require('../app/routes/index.server.routes.js')(app);
	// routes for admin
	require('../app/routes/admin/dashboard.server.routes.js')(app);
	require('../app/routes/admin/user.server.routes.js')(app);

	
	app.use(express.static('./public'));
	app.use(express.static('./upload'));
	return app;
};

