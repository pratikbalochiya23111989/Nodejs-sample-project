//check types of environment 
process.env.NODE_ENV = process.env.NODE_ENV || 'development';
process.env.TZ = 'Asia/Singapore';

//config
var config = require('./config/config'),
	mongoose = require('./config/mongoose'),
	express = require('./config/express'),
	passport = require('./config/passport');

var db = mongoose(),
app = express(),
passport = passport();

var http = require('http').Server(app);


// Listen application request on port 3000
http.listen(config.port, function(){
	console.log(process.env.NODE_ENV + ' server running at http://localhost:' + config.port);
});
var cron = require('node-cron');
var curDate = new Date();
// cron run at every 8 hrs
cron.schedule('0 0 */8 * * *', function(){
	/*var request = require('request');
	var autoRefreshTokenDevelopmentURL = 'http://brio-development.coderspreview.com:1338/apiCronAutoRefreshValidicAccessToken';
	request.post(autoRefreshTokenDevelopmentURL, function (error, response, body) {
		console.log('running a task every 8 hrs for refresh token - '+curDate);
	});*/
});

// cron run at every 4 hrs
cron.schedule('0 0 */4 * * *', function(){
	/*var request = require('request');
	var autoSaveCaloriesDevelopmentURL = 'http://brio-development.coderspreview.com:1338/apiSaveValidicCaloriesBurned';
	request.post(autoSaveCaloriesDevelopmentURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save validic calories burned - '+curDate);
	});*/
});

// cron run at every 4 hrs
cron.schedule('0 0 */4 * * *', function(){
	/*var request = require('request');
	var autoSaveSleepURL = 'http://brio-development.coderspreview.com:1338/apiSaveValidicSleep';
	request.post(autoSaveSleepURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save validic sleep - '+curDate);
	});*/
});

// cron run at every 4 hrs
cron.schedule('0 0 */4 * * *', function(){
	/*var request = require('request');
	var autoSaveStepsURL = 'http://brio-development.coderspreview.com:1338/apiSaveValidicSteps';
	request.post(autoSaveStepsURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save validic steps - '+curDate);
	});*/
});

// cron run at every 4 hrs
cron.schedule('0 0 */4 * * *', function(){
	/*var request = require('request');
	var autoSaveHeartRateURL = 'http://brio-development.coderspreview.com:1338/apiSaveValidicHeartRate';
	request.post(autoSaveHeartRateURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save validic steps - '+curDate);
	});*/
});

// cron run at every 4 hrs
cron.schedule('0 0 */4 * * *', function(){
	/*var request = require('request');
	var autoSaveExerciseURL = 'http://brio-development.coderspreview.com:1338/apiSaveValidicExercise';
	request.post(autoSaveExerciseURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save validic exercise - '+curDate);
	});*/
});

// cron run at 11:00 PM everyday
cron.schedule('0 0 23 * * *', function(){
	/*var request = require('request');
	var autoSaveBrioURL = 'http://brio-development.coderspreview.com:1338/apiSaveBrioScore';
	request.post(autoSaveBrioURL, function (error, response, body) {
		console.log('running a task every 4 hrs for save brio - '+curDate);
	});*/
});

//listen port of the app
module.exports = app;


