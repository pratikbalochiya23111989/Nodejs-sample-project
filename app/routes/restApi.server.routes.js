var api = require('../controllers/restApi.server.controller.js');
module.exports = function(app) {
	app.post('/postFlashScreen', function(req, res) {
		api.postFlashScreen(req, res);
	});

	app.post('/postCMS', function(req, res) {
		api.postCMS(req, res);
	});

	app.post('/postSignIn', function(req, res) {
		api.postSignIn(req, res);
	});

	app.post('/postVerifyOTP', function(req, res) {
		api.postVerifyOTP(req, res);
	});

	app.post('/postCompete', function(req, res) {
		api.postCompete(req, res);
	});

	app.post('/postSocialTagUser', function(req, res) {
		api.postSocialTagUser(req, res);
	});

	app.post('/postSocialHashTag', function(req, res) {
		api.postSocialHashTag(req, res);
	});

	app.post('/postListSocial', function(req, res) {
		api.postListSocial(req, res);
	});

	app.post('/postSocial', function(req, res) {
		api.postSocial(req, res);
	});

	app.post('/postSocialFeedback', function(req, res) {
		api.postSocialFeedback(req, res);
	});

	app.post('/postAktivoScoreTodayYouHave', function(req, res) {
		api.postAktivoScoreTodayYouHave(req, res);
	});

	app.post('/postRecordValenceScore', function(req, res) {
		api.postRecordValenceScore(req, res);
	});

	app.post('/postCreateValidicUser', function(req, res) {
		api.postCreateValidicUser(req, res);
	});

	app.post('/postSaveValidicAHKGFData', function(req, res) {
		api.postSaveValidicAHKGFData(req, res);
	});

	app.post('/postExercise', function(req, res) {
		api.postExercise(req, res);
	});
};