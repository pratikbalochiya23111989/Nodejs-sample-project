var api = require('../controllers/api.server.controller.js');
module.exports = function(app) {
	app.post('/api', function(req, res) {
		var action = req.body.action
		switch(action) {
			case 'postTest2':
				api.postTest2(req, res);
		        break;
		    case 'postEmailSend':
				api.postEmailSend(req, res);
		        break;
			case 'postSaveMemberSleep':
				api.postSaveMemberSleep(req, res);
		        break;
			case 'postSaveSetting':
		        api.postSaveSetting(req, res);
		        break;
		    case 'postVerifyUserOrganization':
		        api.postVerifyUserOrganization(req, res);
		        break;
		    case 'postVerifyUser':
		        api.postVerifyUser(req, res);
		        break;
		    case 'postSendOTP':
		    	api.postSendOTP(req, res);
		        break;
		    case 'postMatchOTP':
		    	api.postMatchOTP(req, res);
		        break;
		    case 'postSaveBeyondVerbal':
		    	api.postSaveBeyondVerbal(req, res);
		        break;
		    case 'postListBeyondVerbalHistory':
		    	api.postListBeyondVerbalHistory(req, res);
		        break;
		    case 'postProvisioningUser':
		    	api.postProvisioningUser(req, res);
		        break;
		    case 'postValidicDashboard':
		    	api.postValidicDashboard(req, res);
		        break;
		    case 'postSaveValidic':
		    	api.postSaveValidic(req, res);
		        break;
		    case 'postDailyValidicCaloriesChartInfo':
		    	api.postDailyValidicCaloriesChartInfo(req, res);
		        break;
		    case 'postValidicCaloriesChartInfo':
		    	api.postValidicCaloriesChartInfo(req, res);
		        break;
		    case 'postValidicActiveMinutes':
		    	api.postValidicActiveMinutes(req, res);
		        break;
		    case 'postDailyValidicStepsChartInfo':
		    	api.postDailyValidicStepsChartInfo(req, res);
		        break;
		    case 'postValidicStepsChartInfo':
		    	api.postValidicStepsChartInfo(req, res);
		        break;
		    case 'postDailyValidicSlmember_ideepsChartInfo':
		    	api.postDailyValidicSleepsChartInfo(req, res);
		        break;
		    case 'postValidicSleepsChartInfo':
		    	api.postValidicSleepsChartInfo(req, res);
		        break;
		    case 'postDailyValidicHeartBitRateChartInfo':
		    	api.postDailyValidicHeartBitRateChartInfo(req, res);
		        break;
		    case 'postValidicHeartBitRateChartInfo':
		    	api.postValidicHeartBitRateChartInfo(req, res);
		        break;
		    case 'postIMGUploadBase64':
		    	api.postIMGUploadBase64(req, res);
		        break;
		    case 'postGetRandomFile':
		    	api.postGetRandomFile(req, res);
		        break;
		    case 'postSocialMedia':
		    	api.postSocialMedia(req, res);
		        break;
		    case 'postSocialMediaFeedback':
		    	api.postSocialMediaFeedback(req, res);
		        break;
		    case 'postListSocial':
		    	api.postListSocial(req, res);
		        break;
		    case 'postListChallenges':
		    	api.postListChallenges(req, res);
		        break;
		    case 'postAcceptChallenge':
		    	api.postAcceptChallenge(req, res);
		        break;
		    case 'postListAcceptChallengeUser':
		    	api.postListAcceptChallengeUser(req, res);
		        break;
		    case 'postDisplayMemberProfile':
		    	api.postDisplayMemberProfile(req, res);
		        break;
		    case 'postUpdateMemberProfile':
		    	api.postUpdateMemberProfile(req, res);
		        break;
		    case 'postCreateThumbFromVideo':
		    	api.postCreateThumbFromVideo(req, res);
		        break;
		    case 'postCMS':
		    	api.postCMS(req, res);
		    	break;
		    case 'postAktivoScore':
		    	api.postAktivoScore(req, res);
		    	break;
		    case 'postHeartRateDetailsPerDay':
		    	api.postHeartRateDetailsPerDay(req, res);
		    	break;
		    case 'postSleepDetailsPerDay':
		    	api.postSleepDetailsPerDay(req, res);
		    	break;
		    case 'postAutoRefreshValidicAccessToken':
		    	api.postAutoRefreshValidicAccessToken(req, res);
		    	break;
		    case 'postGetSetWeeklyExerciseGoals':
		    	api.postGetSetWeeklyExerciseGoals(req, res);
		    	break;
		    case 'postExercise':
		    	api.postExercise(req, res);
		    	break;
		    case 'postExerciseTrueFalseSlide':
		    	api.postExerciseTrueFalseSlide(req, res);
		    	break;
		    case 'postExerciseDurationSlide':
		    	api.postExerciseDurationSlide(req, res);
		    	break;
		    case 'postExerciseDistanceSlide':
		    	api.postExerciseDistanceSlide(req, res);
		    	break;
		    case 'postExerciseHeartRateZonesSlide':
		    	api.postExerciseHeartRateZonesSlide(req, res);
		    	break;
		    case 'postExerciseCaloriesBurnedSlide':
		    	api.postExerciseCaloriesBurnedSlide(req, res);
		    	break;
		    case 'postExerciseImpact':
		    	api.postExerciseImpact(req, res);
		    	break;
		    case 'postAutoPullValidicInfo':
		    	api.postAutoPullValidicInfo(req, res);
		    	break;
		    case 'postFileUploadMultiPart':
		    	api.postFileUploadMultiPart(req, res);
		    	break;
		    case 'postSaveBeyondVerbalSample':
		    	api.postSaveBeyondVerbalSample(req, res);
		    	break;
		    case 'postSaveValidicCaloriesBurned':
		    	api.postSaveValidicCaloriesBurned(req, res);
		    	break;
		    case 'postSaveValidicSleep':
		    	api.postSaveValidicSleep(req, res);
		    	break;
		    case 'postSaveValidicSteps':
		    	api.postSaveValidicSteps(req, res);
		    	break;
		    case 'postSaveValidicHeartRate':
		    	api.postSaveValidicHeartRate(req, res);
		    	break;
		    case 'postSaveValidicExercise':
		    	api.postSaveValidicExercise(req, res);
		    	break;
		    case 'postSaveValidicActiveMinutes':
		    	api.postSaveValidicActiveMinutes(req, res);
		    	break;
		    case 'postSaveValidicSleepDetails':
		    	api.postSaveValidicSleepDetails(req, res);
		    	break;
		    case 'postSaveInfo':
		    	api.postSaveInfo(req, res);
		    	break;
		    case 'postFlashScreen':
		    	api.postFlashScreen(req, res);
		    	break;
		    case 'postDeleteValidicRecords':
		    	api.postDeleteValidicRecords(req, res);
		    	break;
		    case 'postRegisterDevice':
		    	api.postRegisterDevice(req, res);
		    	break;
		    case 'postIOSStaticPushNotification':
		    	api.postIOSStaticPushNotification(req, res);
		    	break;
		    case 'postAndroidStaticPushNotification':
		    	api.postAndroidStaticPushNotification(req, res);
		    	break;
		    case 'postAutoPushNotification':
		    	api.postAutoPushNotification(req, res);
		    	break;
		    case 'postFetchTokenMarketPlaceURL':
		    	api.postFetchTokenMarketPlaceURL(req, res);
		    	break;
		    case 'postPreviewSocialTimeLineCount':
		    	api.postPreviewSocialTimeLineCount(req, res);
		    	break;
		    case 'postShareSocialTimeLineCount':
		    	api.postShareSocialTimeLineCount(req, res);
		    	break;
		    case 'postReadRFile':
		    	api.postReadRFile(req, res);
		    	break;
		    case 'generateAktivoScore':
		    	api.generateAktivoScore(req, res);
		    	break;
		    case 'postEditSleepTime':
		    	api.postEditSleepTime(req, res);
		    	break;
		    default:
		        res.send("Wrong API")
		}
	});

	app.get('/userStatisticsAPIFirst', function(req, res) {
		api.userStatisticsAPIFirst(req, res);
	});

	app.get('/userStatisticsAPISecond', function(req, res) {
		api.userStatisticsAPISecond(req, res);
	});

	app.get('/userStatisticsAPIThird', function(req, res) {
		api.userStatisticsAPIThird(req, res);
	});

	app.get('/userStatisticsAPIFourth', function(req, res) {
		api.userStatisticsAPIFourth(req, res);
	});

	app.get('/userStatisticsAPIFifth', function(req, res) {
		api.userStatisticsAPIFifth(req, res);
	});

	app.get('/userStatistics', function(req, res) {
		api.userStatistics(req, res);
	});

	app.post('/setStatistics', function(req, res) {
		api.setStatistics(req, res);
	});
	
	app.post('/apiAutoPushNotification', function(req, res) {
		api.postAutoPushNotification(req, res);
	});
	/*app.post('/apiCronDeleteValidicRecords', function(req, res) {
		api.postDeleteValidicRecords(req, res);
	});

	app.post('/apiCronAutoRefreshValidicAccessToken', function(req, res) {
		api.postAutoRefreshValidicAccessToken(req, res);
	});

	app.post('/apiCronSaveInfoCallAtThricePerDay', function(req, res) {
		api.postSaveInfoCallAtThricePerDay(req, res);
	});

	app.post('/apiCronSaveInfoCallAtEvery5Mins', function(req, res) {
		api.postSaveInfoCallAtEvery5Mins(req, res);
	});*/

	/*
	app.post('/apiSaveValidicCaloriesBurned', function(req, res) {
		api.postSaveValidicCaloriesBurned(req, res);
	});

	app.post('/apiSaveValidicSleep', function(req, res) {
		api.postSaveValidicSleep(req, res);
	});

	app.post('/apiSaveValidicSteps', function(req, res) {
		api.postSaveValidicSteps(req, res);
	});

	app.post('/apiSaveValidicHeartRate', function(req, res) {
		api.postSaveValidicHeartRate(req, res);
	});

	app.post('/apiSaveValidicExercise', function(req, res) {
		api.postSaveValidicExercise(req, res);
	});*/
};