var Company = require('mongoose').model('Company'),
Department = require('mongoose').model('Department'),
Member = require('mongoose').model('Member'),
MembersEmotionalAnalytics = require('mongoose').model('MembersEmotionalAnalytics'),
MemberCalories = require('mongoose').model('MemberCalories'),
MemberSleep = require('mongoose').model('MemberSleep'),
MemberSleepDetail = require('mongoose').model('MemberSleepDetail'),
MemberSteps = require('mongoose').model('MemberSteps'),
MemberHeartBitRate = require('mongoose').model('MemberHeartBitRate'),
MemberActiveMinutes = require('mongoose').model('MemberActiveMinutes'),
Setting = require('mongoose').model('Setting'),
MemberSocial = require('mongoose').model('MemberSocial'),
MemberSocialMedia = require('mongoose').model('MemberSocialMedia'),
MemberSocialMediaFeedback = require('mongoose').model('MemberSocialMediaFeedback'),
Challenge = require('mongoose').model('Challenge'),
ChallengeAccept = require('mongoose').model('ChallengeAccept'),
Test = require('mongoose').model('Test'),
CMS = require('mongoose').model('CMS'),
CronRun = require('mongoose').model('CronRun'),
MemberExercise = require('mongoose').model('MemberExercise'),
FlashScreen = require('mongoose').model('FlashScreen'),
PushNotification = require('mongoose').model('PushNotification'),
Administrator = require('mongoose').model('Administrator'),
Superadmin = require('mongoose').model('Superadmin'),
MemberSocialPreview = require('mongoose').model('MemberSocialPreview'),
MemberSocialShare = require('mongoose').model('MemberSocialShare'),
MembersAktivoScore = require('mongoose').model('MembersAktivoScore'),
fs = require('fs'),
moment = require('moment'),
_ = require('underscore'),
_this = this,
request = require('request'),
async = require('async'),
monthNamesShort =  ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
path = require('path'),
appDir = path.dirname(require.main.filename),
Analyzer = require('./analyzer-v3'),
AnalyzerObj = new Analyzer('d58a3db3-826a-4bc2-857d-91e91f9c42b7');

/* Code for common functions */

exports.formatDate = function(req, res, passDate) {
    var d = new Date(passDate),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
};

exports.postCurrentDate = function(req, res) {
    var currentDate = new Date();
    var match_month = (currentDate.getMonth()+1);
    match_month = ('0' + match_month).slice(-2);
    var match_day = (currentDate.getDate());
    match_day = ('0' + match_day).slice(-2);
    return currentDate.getFullYear()+'-' +match_month+ '-'+match_day;
};

exports.postCurrentTime = function(req, res) {
    var currentTime = new Date();
    var hrs = currentTime.getHours();
    hrs = ('0' + hrs).slice(-2);
    var mins = currentTime.getMinutes();
    mins = ('0' + mins).slice(-2);
    var sec = currentTime.getSeconds();
    sec = ('0' + sec).slice(-2);
    return hrs+':'+mins+':'+sec;
};

exports.sendOTPEmail = function(req,res,toemail,subject,content) {
    var nodemailer = require('nodemailer');
    var transporter = nodemailer.createTransport({
        host: 'smtp.gmail.com',
        port: 465,
        secure: true,
        service: 'gmail',
        auth: {
            user: 'otp@aktivolabs.com',
            pass: '9924693192'
        }
    });

    var mailOptions = {
        from: '"Aktivolabs" <otp@aktivolabs.com>',
        to: toemail,
        subject: subject,
        html: content
    };

    transporter.sendMail(mailOptions, function(error, info){
        return;
    });
};

exports.randomString = function(req, res, len){
    charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var randomString = '';
    for (var i = 0; i < len; i++) {
        var randomPoz = Math.floor(Math.random() * charSet.length);
        randomString += charSet.substring(randomPoz,randomPoz+1);
    }
    return randomString;
};

exports.weekStartWeekEndDatesFromWeekNoAndYear = function(req, res, week, year){
	week-= 1;
	var weekDatesResponse = {'startDate':moment(year.toString()).add(week.toString(), 'weeks').startOf('week').format('YYYY-MM-DD'),'endDate':moment(year.toString()).add(week.toString(), 'weeks').endOf('week').format('YYYY-MM-DD')};
	return weekDatesResponse;
};

exports.postRangeStartEndDates = function(req, res, startDate, stopDate) {
    startDate = new Date(startDate);
    startDate = _this.formatDate(req,res,startDate);

    stopDate = new Date(stopDate);
    stopDate = _this.formatDate(req,res,stopDate);

    var dateArray = [];
    var currentDate = moment(startDate);
    stopDate = moment(stopDate);
    while (currentDate <= stopDate) {
        dateArray.push( moment(currentDate).format('YYYY-MM-DD') )
        currentDate = moment(currentDate).add(1, 'days');
    }
    return dateArray;
};

exports.convertSecondTimeFormatToHoursMins = function(req, res, seconds) {
    var x = seconds;   
    x = x*1000;
    var d = moment.duration(x, 'milliseconds'),
    hours = Math.floor(d.asHours()),
    mins = Math.floor(d.asMinutes()) - hours * 60;
    if(hours>0){
        if(mins>0){
        	var hoursStr = (hours>1) ? hours+" hours " : hours+" hour ";
        	var minsStr = (mins>1) ? mins+" mins" : mins+" min";
            return hoursStr+minsStr;
        }
        else {
            return (hours>1) ? hours+" hours" : hours+" hour";
        }
    }
    else {
    	return (mins>1) ? mins+" mins" : mins+" min";
    }
};

/* End of code for common functions */

exports.postExercise = function(req, res) {
	var resObj = new Object,
	fullUrl = req.protocol + '://' + req.get('host');
	if(req.body.member_id && req.body.week && req.body.year){
		var weekDates = _this.weekStartWeekEndDatesFromWeekNoAndYear(req, res, req.body.week, req.body.year),
		resDateRange = _this.postRangeStartEndDates(req,res,weekDates.startDate,weekDates.endDate),
		totalAktivityThisWeek = 0, tableActivityArr = [];
		async.forEachSeries(resDateRange.reverse(), function(singleDate, callback_singleDate) {
			MemberExercise.find({ "created_date": singleDate, member_id : req.body.member_id }, function(err, memExerciseInfo) {
				var singleDayAktivityObj = new Object(), multipleAktivity = [];
				async.forEachSeries(memExerciseInfo, function(singleExercise, callback_singleExercise) {
					totalAktivityThisWeek+= singleExercise.duration;
					typeURLTitle = singleExercise.type.replace(" ", "-");
					var singleAktivityObj = {'type':singleExercise.type,'typeURL': (fullUrl+'/img/activity/'+typeURLTitle.toLowerCase()+'.png'),'time':moment.utc(singleExercise.start_time).format('hh:mm a'),'duration':(parseInt(singleExercise.duration/60)+' min'),'average_heart_rate':((singleExercise.average_heart_rate==null) ? '0 avg. BPM' : singleExercise.average_heart_rate+' avg. BPM'),'calories':singleExercise.calories+' burned'};
					multipleAktivity.push(singleAktivityObj);
					callback_singleExercise();
				}, function (err) {
					singleDayAktivityObj = {'date':singleDate,'activites':multipleAktivity};
					tableActivityArr.push(singleDayAktivityObj);
					callback_singleDate();
				});
			});
		}, function (err) {
			var aktivityObj = {'first_slide':_this.convertSecondTimeFormatToHoursMins(req,res,totalAktivityThisWeek),'table_activity_data':tableActivityArr};
			resObj.data = aktivityObj;
			resObj.status = 1;
	        resObj.message = '';
	        res.json(resObj);
		});
	}
	else {
		resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
	}
};

// save sleep details
exports.postSaveValidicSleepDetails = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var sleep_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"+00:00&end_date="+currentDate+"+00:00&expanded=1";
                    request(sleep_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.sleep.length>0){
                                    async.forEachSeries(myjson.sleep, function(n2, callback_s2) {
                                        n2.member_id = member_id;
                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberSleepDetail.findOne({ member_id: n1._id,created_date:n2.created_date,created_time:n2.created_time}, function(err, memSleepDetails) {
                                            if(!memSleepDetails){
                                                var msd = new MemberSleepDetail(n2);
                                                msd.save(function(err) {
                                                    callback_s2();
                                                });
                                            }
                                            else {
                                                MemberSleepDetail.findByIdAndUpdate(memSleepDetails._id, n2, function(err, memSleepDetailResponse) {
                                                    callback_s2();
                                                });
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save active minutes
exports.postSaveValidicActiveMinutes = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var routine_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(routine_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.routine.length>0){
                                    async.forEachSeries(myjson.routine, function(n2, callback_s2) {
                                        n2.member_id = member_id;
                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberActiveMinutes.findOne({ member_id: n1._id,created_date:n2.created_date}, function(err, memActiveMinutes) {
                                            if(n2.active_duration!=0){
                                                if(!memActiveMinutes){
                                                    var mam = new MemberActiveMinutes(n2);
                                                    mam.save(function(err) {
                                                        callback_s2();
                                                    });
                                                }
                                                else {
                                                    MemberActiveMinutes.findByIdAndUpdate(memActiveMinutes._id, n2, function(err, activeMinutesResponse) {
                                                        callback_s2();
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save exercise - activities
exports.postSaveValidicExercise = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=1";
                    request(fitness_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.fitness.length>0){
                                    async.forEachSeries(myjson.fitness, function(n2, callback_s2) {
                                        n2.member_id = n1._id;
                                        n2.exercise_id = n2._id;
                                        n2.activity_level_sedentary_minutes = (n2.activity_level.length>0) ? n2.activity_level[0].minutes : 0;
                                        n2.activity_level_lightly_minutes = (n2.activity_level.length>0) ? n2.activity_level[1].minutes : 0;
                                        n2.activity_level_fairly_minutes = (n2.activity_level.length>0) ? n2.activity_level[2].minutes : 0;
                                        n2.activity_level_very_minutes = (n2.activity_level.length>0) ? n2.activity_level[3].minutes : 0;
                                        
                                        if(n2.heart_rate_zones!=null){
                                            n2.heart_rate_zones_out_of_range_max = n2.heart_rate_zones[0].max;
                                            n2.heart_rate_zones_out_of_range_min = n2.heart_rate_zones[0].min;
                                            n2.heart_rate_zones_out_of_range_minutes = n2.heart_rate_zones[0].minutes;
                                            n2.heart_rate_zones_fat_burn_max = n2.heart_rate_zones[1].max;
                                            n2.heart_rate_zones_fat_burn_min = n2.heart_rate_zones[1].min;
                                            n2.heart_rate_zones_fat_burn_minutes = n2.heart_rate_zones[1].minutes;
                                            n2.heart_rate_zones_cardio_max = n2.heart_rate_zones[2].max;
                                            n2.heart_rate_zones_cardio_min = n2.heart_rate_zones[2].min;
                                            n2.heart_rate_zones_cardio_minutes = n2.heart_rate_zones[2].minutes;
                                            n2.heart_rate_zones_peak_max = n2.heart_rate_zones[3].max;
                                            n2.heart_rate_zones_peak_min = n2.heart_rate_zones[3].min;
                                            n2.heart_rate_zones_peak_minutes = n2.heart_rate_zones[3].minutes;
                                        }
                                        else {
                                            n2.heart_rate_zones_out_of_range_max = 0;
                                            n2.heart_rate_zones_out_of_range_min = 0;
                                            n2.heart_rate_zones_out_of_range_minutes = 0;
                                            n2.heart_rate_zones_fat_burn_max = 0;
                                            n2.heart_rate_zones_fat_burn_min = 0;
                                            n2.heart_rate_zones_fat_burn_minutes = 0;
                                            n2.heart_rate_zones_cardio_max = 0;
                                            n2.heart_rate_zones_cardio_min = 0;
                                            n2.heart_rate_zones_cardio_minutes = 0;
                                            n2.heart_rate_zones_peak_max = 0;
                                            n2.heart_rate_zones_peak_min = 0;
                                            n2.heart_rate_zones_peak_minutes = 0;
                                        }
                                        n2.manual_values_specified_calories = n2.manual_values_specified.calories;
                                        n2.manual_values_specified_distance = n2.manual_values_specified.distance;
                                        n2.manual_values_specified_steps = n2.manual_values_specified.steps;                                    
                                        delete n2._id;
                                        delete n2.activity_level;
                                        delete n2.heart_rate_zones;
                                        delete n2.manual_values_specified;
                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        
                                        MemberExercise.find({ member_id: n1._id,created_date:n2.created_date,platform_type: {'$ne':'validic'}}, function(err, memExerciseTotalRec) {
                                            if(n2.active_duration!=0){
                                                if(memExerciseTotalRec.length>0){
                                                    MemberExercise.remove({ member_id: n1._id,created_date:n2.created_date},function(err, delRec){
                                                        var me = new MemberExercise(n2);
                                                        me.save(function(err) {
                                                            callback_s2();
                                                        });                                             
                                                    });
                                                }
                                                else {
                                                    MemberExercise.find({ member_id: n1._id,created_date:n2.created_date,created_time:n2.created_time}, function(err, exercise) {
                                                        if(exercise.length==0){
                                                            var me = new MemberExercise(n2);
                                                            me.save(function(err) {
                                                                callback_s2();
                                                            });
                                                        }
                                                        else {
                                                            var matchStatus = 'No';
                                                            async.forEachSeries(exercise, function(n3, callback_s3) {
                                                                if(n2.active_duration==n3.active_duration){
                                                                    matchStatus = 'Yes';
                                                                }
                                                                callback_s3();
                                                            }, function (err) {
                                                                if(matchStatus=='No'){
                                                                    var me = new MemberExercise(n2);
                                                                    me.save(function(err) {
                                                                        callback_s2();
                                                                    });
                                                                }
                                                                else {
                                                                    callback_s2();
                                                                }
                                                            });
                                                        }
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });

                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save validic heart rate
exports.postSaveValidicHeartRate = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var heartbitrate_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=1";
                    request(heartbitrate_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.routine.length>0){
                                    async.forEachSeries(myjson.routine, function(n2, callback_s2) {
                                        n2.member_id = n1._id;
                                        n2.heart_rate_id = n2._id;
                                        delete n2._id;

                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberHeartBitRate.findOne({ member_id: n1._id,created_date:n2.created_date}, function(err, heartRate) {
                                            if(n2.resting_heart_rate!=null && n2.resting_heart_rate!=0){
                                                if(!heartRate){
                                                    var hr = new MemberHeartBitRate(n2);
                                                    hr.save(function(err) {
                                                        callback_s2();
                                                    });
                                                }
                                                else {
                                                    MemberHeartBitRate.findByIdAndUpdate(heartRate._id, n2, function(err, heartRateResponse) {
                                                        callback_s2();
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }   
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save validic steps
exports.postSaveValidicSteps = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var steps_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(steps_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.routine.length>0){
                                    async.forEachSeries(myjson.routine, function(n2, callback_s2) {
                                        n2.member_id = n1._id;
                                        n2.steps_id = n2._id;
                                        delete n2._id;

                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberSteps.findOne({ member_id: n1._id,created_date:n2.created_date}, function(err, steps) {
                                            if(n2.steps!=0){
                                                if(!steps){
                                                    var st = new MemberSteps(n2);
                                                    st.save(function(err) {
                                                        callback_s2();
                                                    });
                                                }
                                                else {
                                                    MemberSteps.findByIdAndUpdate(steps._id, n2, function(err, stepsResponse) {
                                                        callback_s2();
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }   
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save validic sleep info
exports.postSaveValidicSleep = function(req, res,member_id,startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var sleep_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(sleep_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.sleep.length>0){
                                    async.forEachSeries(myjson.sleep, function(n2, callback_s2) {
                                        n2.member_id = n1._id;
                                        n2.sleep_id = n2._id;
                                        delete n2._id;

                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberSleep.find({ member_id: n1._id,created_date:n2.created_date,platform_type: {'$ne':'validic'}}, function(err, sleepTotalRec) {
                                            if(n2.total_sleep!=0){
                                                if(sleepTotalRec.length>0){
                                                    MemberSleep.remove({ member_id: n1._id,created_date:n2.created_date},function(err, delRec){
                                                        var sleptInfo = new MemberSleep(n2);
                                                        sleptInfo.save(function(err) {
                                                            callback_s2();
                                                        });                                             
                                                    });
                                                }
                                                else {
                                                    MemberSleep.find({ member_id: n1._id,created_date:n2.created_date,created_time:n2.created_time}, function(err, slept) {
                                                        if(slept.length==0){
                                                            var sleptInfo = new MemberSleep(n2);
                                                            sleptInfo.save(function(err) {
                                                                callback_s2();
                                                            });
                                                        }
                                                        else {
                                                            var matchStatus = 'No';
                                                            async.forEachSeries(slept, function(n3, callback_s3) {
                                                                if(n2.total_sleep==n3.total_sleep){
                                                                    matchStatus = 'Yes';
                                                                }
                                                                callback_s3();
                                                            }, function (err) {
                                                                if(matchStatus=='No'){
                                                                    var sleptInfo = new MemberSleep(n2);
                                                                    sleptInfo.save(function(err) {
                                                                        callback_s2();
                                                                    });
                                                                }
                                                                else {
                                                                    callback_s2();
                                                                }
                                                            });
                                                        }
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

// save validic calories burned
exports.postSaveValidicCaloriesBurned = function(req, res,member_id,startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var async = require('async');
    var request = require('request');
    Member.find({_id:member_id}, function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                if(n1.validic_uid){
                    var calories_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(calories_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.summary){
                            if(myjson.summary.status==200){
                                if(myjson.routine.length>0){
                                    async.forEachSeries(myjson.routine, function(n2, callback_s2) {
                                        n2.member_id = n1._id;
                                        n2.calories_id = n2._id;
                                        delete n2._id;

                                        var res_timestamp = n2.timestamp;
                                        res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                        res_timestamp = res_timestamp.replace('T', ' ');
                                        var res_timestamp = new Date(res_timestamp);
                                        var match_month = (res_timestamp.getMonth()+1);
                                        match_month = ('0' + match_month).slice(-2);
                                        var match_day = (res_timestamp.getDate());
                                        match_day = ('0' + match_day).slice(-2);
                                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        n2.created_date = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                                        
                                        var hrs = res_timestamp.getHours();
                                        hrs = ('0' + hrs).slice(-2);
                                        var mins = res_timestamp.getMinutes();
                                        mins = ('0' + mins).slice(-2);
                                        var sec = res_timestamp.getSeconds();
                                        sec = ('0' + sec).slice(-2);
                                        n2.created_time = hrs+':'+mins+':'+sec;
                                        n2.platform_type = 'validic';
                                        MemberCalories.findOne({ member_id: n1._id,created_date:n2.created_date}, function(err, cal) {
                                            if(n2.calories_burned!=0){
                                                if(!cal){
                                                    var ca = new MemberCalories(n2);
                                                    ca.save(function(err) {
                                                        callback_s2();
                                                    });
                                                }
                                                else {
                                                    MemberCalories.findByIdAndUpdate(cal._id, n2, function(err, caloriesResponse) {
                                                        callback_s2();
                                                    });
                                                }
                                            }
                                            else {
                                                callback_s2();
                                            }
                                        });
                                    }, function (err) {
                                        callback_s1();
                                    });
                                }
                                else {
                                    callback_s1();
                                }
                            }
                            else {
                                callback_s1();
                            }
                        }
                        else {
                            callback_s1();
                        }
                    });
                }
                else {
                    callback_s1();
                }   
            }, function (err) {
                return;
            });
        }
        else {
            return;
        }
    });
};

exports.postSaveValidicAHKGFData = function(req, res){
	var resObj = new Object;
    if(req.body.member_id){
        if((req.body.type=='apple_health_kit' || req.body.type=='google_fit') && req.body.jsonInfo){
            var async = require('async'),
            json2Arr = JSON.parse(req.body.jsonInfo);
            async.forEachSeries(json2Arr.calories_burned, function(singleCalRec, callback_singleCalRec) {
                singleCalRec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleCalRec.created_date);
                MemberCalories.findOne({member_id:req.body.member_id,created_date:singleCalRec.created_date},function(err,memCalRecords){
                    singleCalRec.calories_burned = Math.ceil(singleCalRec.calories_burned);
                    singleCalRec.timestamp = singleCalRec.created_date+'T'+singleCalRec.created_time+'+00:00';
                    singleCalRec.platform_type = req.body.type;
                    singleCalRec.calories_id = '';
                    singleCalRec.distance = '';
                    singleCalRec.elevation = 0;
                    singleCalRec.floors = 0;
                    singleCalRec.last_updated = singleCalRec.created_date+'T'+singleCalRec.created_time+'+00:00';
                    singleCalRec.source = '';
                    singleCalRec.source_name = '';
                    singleCalRec.steps = 0;
                    singleCalRec.user_id = '';
                    singleCalRec.utc_offset = '';
                    singleCalRec.validated = false;
                    singleCalRec.water = 0;

                    if(singleCalRec.calories_burned!=0){
                        if(memCalRecords){
                            if(memCalRecords.platform_type!='validic'){
                                MemberCalories.findByIdAndUpdate(memCalRecords._id, singleCalRec, function(err, updateRec) {
                                    callback_singleCalRec();
                                });
                            }
                            else {
                                callback_singleCalRec();
                            }
                        }
                        else {
                            singleCalRec.member_id = req.body.member_id;
                            var storeObj = new MemberCalories(singleCalRec);
                            storeObj.save(function(err) {
                                callback_singleCalRec();
                            });
                        }
                    }
                    else {
                        callback_singleCalRec();
                    }
                })
            }, function (err) {
                async.forEachSeries(json2Arr.steps, function(singleStepRec, callback_singleStepRec) {
                    singleStepRec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleStepRec.created_date);
                    MemberSteps.findOne({member_id:req.body.member_id,created_date:singleStepRec.created_date},function(err,memStepRecords){
                        singleStepRec.timestamp = singleStepRec.created_date+'T'+singleStepRec.created_time+'+00:00';
                        singleStepRec.platform_type = req.body.type;
                        singleStepRec.steps_id = '';
                        singleStepRec.calories_burned = 0;
                        singleStepRec.distance = '';
                        singleStepRec.elevation = 0;
                        singleStepRec.floors = 0;
                        singleStepRec.last_updated = singleStepRec.created_date+'T'+singleStepRec.created_time+'+00:00';
                        singleStepRec.source = '';
                        singleStepRec.source_name = '';
                        singleStepRec.user_id = '';
                        singleStepRec.utc_offset = '';
                        singleStepRec.validated = false;
                        singleStepRec.water = 0;
                        
                        if(singleStepRec.steps!=0){
                            if(memStepRecords){
                                if(memStepRecords.platform_type!='validic'){
                                    MemberSteps.findByIdAndUpdate(memStepRecords._id, singleStepRec, function(err, updateRec) {
                                        callback_singleStepRec();
                                    });
                                }
                                else {
                                    callback_singleStepRec();
                                }
                            }
                            else {
                                singleStepRec.member_id = req.body.member_id;
                                var storeObj = new MemberSteps(singleStepRec);
                                storeObj.save(function(err) {
                                    callback_singleStepRec();
                                });
                            }
                        }
                        else {
                            callback_singleStepRec();
                        }
                    })
                }, function (err) {
                    async.forEachSeries(json2Arr.heart_rate, function(singleHRRec, callback_singleHRRec) {
                        singleHRRec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleHRRec.created_date);
                        MemberHeartBitRate.findOne({member_id:req.body.member_id,created_date:singleHRRec.created_date},function(err,memHRRecords){
                            singleHRRec.timestamp = singleHRRec.created_date+'T'+singleHRRec.created_time+'+00:00';
                            singleHRRec.platform_type = req.body.type;
                            singleHRRec.heart_rate_id = '';
                            singleHRRec.activity_calories = 0;
                            singleHRRec.activity_id = '';
                            singleHRRec.calories_bmr = 0;
                            singleHRRec.calories_burned = 0;
                            singleHRRec.distance = '';
                            singleHRRec.elevation = 0;
                            singleHRRec.floors = 0;
                            singleHRRec.last_updated = singleHRRec.created_date+'T'+singleHRRec.created_time+'+00:00';
                            singleHRRec.minutes_fairly_active = 0;
                            singleHRRec.minutes_very_active = 0;
                            singleHRRec.source = '';
                            singleHRRec.source_name = '';
                            singleHRRec.steps = 0;
                            singleHRRec.user_id = '';
                            singleHRRec.utc_offset = '';
                            singleHRRec.validated = false;
                            singleHRRec.water = 0;
                            singleHRRec.member_id = req.body.member_id;

                            if(singleHRRec.resting_heart_rate!=0){
                                if(memHRRecords){
                                    if(memHRRecords.platform_type!='validic'){
                                        MemberHeartBitRate.findByIdAndUpdate(memHRRecords._id, singleHRRec, function(err, updateRec) {
                                            callback_singleHRRec();
                                        });
                                    }
                                    else {
                                        callback_singleHRRec();
                                    }
                                }
                                else {
                                    var storeObj = new MemberHeartBitRate(singleHRRec);
                                    storeObj.save(function(err) {
                                        callback_singleHRRec();
                                    });
                                }
                            }
                            else {
                                callback_singleHRRec();
                            }
                        })
                    }, function (err) {
                        async.forEachSeries(json2Arr.sleep, function(singleSleepRec, callback_singleSleepRec) {
                            singleSleepRec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleSleepRec.created_date);
                            MemberSleep.find({member_id:req.body.member_id,created_date:singleSleepRec.created_date},function(err,memSleepRecords){
                                singleSleepRec.timestamp = singleSleepRec.created_date+'T'+singleSleepRec.created_time+'+00:00';
                                singleSleepRec.platform_type = req.body.type;
                                singleSleepRec.awake = 0;
                                singleSleepRec.deep = 0;
                                singleSleepRec.last_updated = singleSleepRec.created_date+'T'+singleSleepRec.created_time+'+00:00';
                                singleSleepRec.light = 0;
                                singleSleepRec.rem = 0;
                                singleSleepRec.source = '';
                                singleSleepRec.source_name = '';
                                singleSleepRec.times_woken = 0;
                                singleSleepRec.total_sleep = (singleSleepRec.total_sleep * 60);
                                singleSleepRec.user_id = '';
                                singleSleepRec.utc_offset = '';
                                singleSleepRec.validated = false;
                                singleSleepRec.sleep_id = '';
                                singleSleepRec.member_id = req.body.member_id;

                                var validicStatus = 'No';
                                if(singleSleepRec.total_sleep>0){
                                    if(memSleepRecords.length>0){
                                        async.forEachSeries(memSleepRecords, function(singleInnerSleepRec, callback_singleInnerSleepRec) {
                                            if(singleInnerSleepRec.platform_type=='validic'){
                                                validicStatus = 'Yes';
                                            }
                                            callback_singleInnerSleepRec();
                                        }, function (err) {
                                            if(validicStatus == 'No'){
                                                MemberSleep.findOneAndUpdate({member_id:req.body.member_id,created_date:singleSleepRec.created_date},singleSleepRec, function(err,updateRec) {
                                                    callback_singleSleepRec();
                                                });
                                            }
                                            else {
                                                callback_singleSleepRec();
                                            }
                                        });
                                    }
                                    else {
                                        var storeObj = new MemberSleep(singleSleepRec);
                                        storeObj.save(function(err) {
                                            callback_singleSleepRec();
                                        });
                                    }
                                }
                                else {
                                    callback_singleSleepRec();
                                }
                            })
                        }, function (err) {
                            async.forEachSeries(json2Arr.active_minutes, function(singleAMRec, callback_singleAMRec) {
                                singleAMRec.timestamp = singleAMRec.created_date+'T'+singleAMRec.created_time+'+00:00';
                                singleAMRec.exercise_id = '';
                                singleAMRec.active_duration = 0;
                                singleAMRec.activity_category = 'Walking';
                                singleAMRec.activity_id = '';
                                singleAMRec.activity_level_sedentary_minutes = 0;
                                singleAMRec.activity_level_lightly_minutes = 0;
                                singleAMRec.activity_level_fairly_minutes = 0;
                                singleAMRec.activity_level_very_minutes = 0;
                                singleAMRec.activity_name = '';
                                singleAMRec.activity_type_id = 0;
                                singleAMRec.average_heart_rate = 0;
                                singleAMRec.calories = 0;
                                singleAMRec.distance = 0;
                                singleAMRec.distance_unit = '';
                                singleAMRec.duration = (parseFloat(singleAMRec.total_active_minutes) * 60);
                                singleAMRec.heart_rate_zones_out_of_range_max = 0;
                                singleAMRec.heart_rate_zones_out_of_range_min = 0;
                                singleAMRec.heart_rate_zones_out_of_range_minutes = 0;
                                singleAMRec.heart_rate_zones_fat_burn_max = 0;
                                singleAMRec.heart_rate_zones_fat_burn_min = 0;
                                singleAMRec.heart_rate_zones_fat_burn_minutes = 0;
                                singleAMRec.heart_rate_zones_cardio_max = 0;
                                singleAMRec.heart_rate_zones_cardio_min = 0;
                                singleAMRec.heart_rate_zones_cardio_minutes = 0;
                                singleAMRec.heart_rate_zones_peak_max = 0;
                                singleAMRec.heart_rate_zones_peak_min = 0;
                                singleAMRec.heart_rate_zones_peak_minutes = 0;
                                singleAMRec.intensity = '';
                                singleAMRec.last_modified = singleAMRec.created_date+'T'+singleAMRec.created_time+'+00:00';
                                singleAMRec.last_updated = singleAMRec.created_date+'T'+singleAMRec.created_time+'+00:00';
                                singleAMRec.log_id = 0;
                                singleAMRec.log_type = '';
                                singleAMRec.manual_values_specified_calories = false;
                                singleAMRec.manual_values_specified_distance = false;
                                singleAMRec.manual_values_specified_steps = false;
                                singleAMRec.pace = '';
                                singleAMRec.resting_heart_rate = 0;
                                singleAMRec.source = '';
                                singleAMRec.source_name = '';
                                singleAMRec.speed = '';
                                singleAMRec.start_time = singleAMRec.created_date+'T'+singleAMRec.created_time+'+00:00';
                                singleAMRec.steps = 0;
                                singleAMRec.type = 'Walk';
                                singleAMRec.user_id = '';
                                singleAMRec.utc_offset = '';
                                singleAMRec.validated = false;
                                singleAMRec.member_id = req.body.member_id;
                                singleAMRec.platform_type = req.body.type;
                                delete singleAMRec.total_active_minutes;
                                MemberExercise.find({member_id:req.body.member_id,created_date:singleAMRec.created_date},function(err,memAMRecords){
                                    var validicStatus = 'No';
                                    if(singleAMRec.duration>0){
                                        if(memAMRecords.length>0){
                                            async.forEachSeries(memAMRecords, function(singleInnerExerciseRec, callback_singleInnerExerciseRec) {
                                                if(singleInnerExerciseRec.platform_type=='validic'){
                                                    validicStatus = 'Yes';
                                                }
                                                callback_singleInnerExerciseRec();
                                            }, function (err) {
                                                if(validicStatus == 'No'){
                                                    MemberExercise.findOneAndUpdate({member_id:req.body.member_id,created_date:singleAMRec.created_date}, singleAMRec, function(err, updateRec) {
                                                        callback_singleAMRec();
                                                    });
                                                }
                                                else {
                                                    callback_singleAMRec();
                                                }
                                            })
                                        }
                                        else {
                                            var storeObj = new MemberExercise(singleAMRec);
                                            storeObj.save(function(err) {
                                                callback_singleAMRec();
                                            });
                                        }
                                    }
                                    else {
                                        callback_singleAMRec();
                                    }
                                })
                            }, function (err) {
                                async.forEachSeries(json2Arr.sb, function(singleSBRec, callback_singleSBRec) {
                                    singleSBRec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleSBRec.created_date);
                                    MemberHeartBitRate.findOne({member_id:req.body.member_id,created_date:singleSBRec.created_date},function(err,memSBRecords){
                                        singleSBRec.timestamp = singleSBRec.created_date+'T'+singleSBRec.created_time+'+00:00';
                                        singleSBRec.platform_type = req.body.type;
                                        singleSBRec.heart_rate_id = '';
                                        singleSBRec.activity_calories = 0;
                                        singleSBRec.activity_id = '';
                                        singleSBRec.calories_bmr = 0;
                                        singleSBRec.calories_burned = 0;
                                        singleSBRec.distance = '';
                                        singleSBRec.elevation = 0;
                                        singleSBRec.floors = 0;
                                        singleSBRec.last_updated = singleSBRec.created_date+'T'+singleSBRec.created_time+'+00:00';
                                        singleSBRec.minutes_fairly_active = 0;
                                        singleSBRec.minutes_very_active = 0;
                                        singleSBRec.source = '';
                                        singleSBRec.source_name = '';
                                        singleSBRec.steps = 0;
                                        singleSBRec.user_id = '';
                                        singleSBRec.utc_offset = '';
                                        singleSBRec.validated = false;
                                        singleSBRec.water = 0;
                                        if(memSBRecords){
                                            if(!memSBRecords.minutes_sedentary){
                                                singleSBRec.minutes_sedentary = singleSBRec.total_sb;
                                                delete singleSBRec.total_sb;
                                                MemberHeartBitRate.findByIdAndUpdate(memSBRecords._id, singleSBRec, function(err, updateRec) {
                                                    callback_singleSBRec();
                                                });
                                            }
                                            else {
                                                callback_singleSBRec();
                                            }
                                        }
                                        else {
                                            singleSBRec.minutes_sedentary = singleSBRec.total_sb;
                                            delete singleSBRec.total_sb;
                                            singleSBRec.member_id = req.body.member_id;
                                            var storeObj = new MemberHeartBitRate(singleSBRec);
                                            storeObj.save(function(err) {
                                                callback_singleSBRec();
                                            });
                                        }
                                    })
                                }, function (err) {
                                    async.forEachSeries(json2Arr.light_activity, function(singleLARec, callback_singleLARec) {
                                        singleLARec.created_date = _this.postSubstract1DayMinusFromDateWithFormat(req,res,singleLARec.created_date);
                                        MemberHeartBitRate.findOne({member_id:req.body.member_id,created_date:singleLARec.created_date},function(err,memLARecords){
                                            singleLARec.platform_type = req.body.type;
                                            singleLARec.timestamp = singleLARec.created_date+'T'+singleLARec.created_time+'+00:00';
                                            singleLARec.heart_rate_id = '';
                                            singleLARec.activity_calories = 0;
                                            singleLARec.activity_id = '';
                                            singleLARec.calories_bmr = 0;
                                            singleLARec.calories_burned = 0;
                                            singleLARec.distance = '';
                                            singleLARec.elevation = 0;
                                            singleLARec.floors = 0;
                                            singleLARec.last_updated = singleLARec.created_date+'T'+singleLARec.created_time+'+00:00';
                                            singleLARec.minutes_fairly_active = 0;
                                            singleLARec.minutes_very_active = 0;
                                            singleLARec.sototal_light_activityurce = '';
                                            singleLARec.source_name = '';
                                            singleLARec.steps = 0;
                                            singleLARec.user_id = '';
                                            singleLARec.utc_offset = '';
                                            singleLARec.validated = false;
                                            singleLARec.water = 0;

                                            if(memLARecords){
                                                if(!memLARecords.minutes_lightly_active){
                                                    singleLARec.minutes_lightly_active = singleLARec.total_light_activity;
                                                    delete singleLARec.total_light_activity;
                                                    MemberHeartBitRate.findByIdAndUpdate(memLARecords._id, singleLARec, function(err, updateRec) {
                                                        callback_singleLARec();
                                                    });
                                                }
                                                else {
                                                    callback_singleLARec();
                                                }
                                            }
                                            else {
                                                singleLARec.minutes_lightly_active = singleLARec.total_light_activity;
                                                delete singleLARec.total_light_activity;
                                                singleLARec.member_id = req.body.member_id;
                                                var storeObj = new MemberHeartBitRate(singleLARec);
                                                storeObj.save(function(err) {
                                                    callback_singleLARec();
                                                });
                                            }
                                        })
                                    }, function (err) {
                                    	resObj.status = 1;
								        resObj.message = '';
								        res.json(resObj);
                                    });
                                });
                            });
                        });
                    });
                });
            });
        }
        else if(req.body.type=='validic'){
            var currentDate = _this.postCurrentDate(req,res),
            d = new Date(currentDate);
            d.setMonth(d.getMonth() - 3);
            var startdate = _this.formatDate(req,res,d);

            _this.postSaveValidicCaloriesBurned(req,res,req.body.member_id,startdate);
            _this.postSaveValidicSleep(req,res,req.body.member_id,startdate);
            _this.postSaveValidicSteps(req,res,req.body.member_id,startdate);
            _this.postSaveValidicHeartRate(req,res,req.body.member_id,startdate);
            _this.postSaveValidicExercise(req,res,req.body.member_id,startdate);
            _this.postSaveValidicActiveMinutes(req,res,req.body.member_id,startdate);
            _this.postSaveValidicSleepDetails(req,res,req.body.member_id,startdate);
            setTimeout(function(){
                resObj.status = 1;
		        resObj.message = '';
		        res.json(resObj);
            }, 15000);
        }
        else {
            resObj.status = 0;
	        resObj.message = 'Platform type is incorrect.';
	        res.json(resObj);
        }
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters.';
        res.json(resObj);
    }
};

exports.postCreateValidicUser = function(req, res){
	var resObj = new Object();
	if(req.body.member_id){
		Member.findOne({ _id: req.body.member_id },{validic_uid:1,validic_access_token:1}, function(err, memberInfo) {
			if(memberInfo){
				if(memberInfo.validic_access_token){
					var validicRefreshTokenURL = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+memberInfo.validic_uid+"/refresh_token.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5";
	                request(validicRefreshTokenURL, function (error, response, body) {
	                    var validicRefreshTokenResponse = JSON.parse(body);
	                    if(validicRefreshTokenResponse.code==200){
	                        var validicRefreshTokenObj = new Object;
	                        validicRefreshTokenObj.validic_access_token = validicRefreshTokenResponse.user.authentication_token;
	                        validicRefreshTokenObj.validic_access_token_updated_datetime = new Date();
	                        Member.findByIdAndUpdate(req.body.member_id, validicRefreshTokenObj, function(err, memberInfo) {
	                        	var validicMarketPlaceURL = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+validicRefreshTokenResponse.user.authentication_token;
					            resObj.data = {'member_id' : req.body.member_id,'validic_uid' : memberInfo.validic_uid, 'validic_access_token' : validicRefreshTokenResponse.user.authentication_token, 'marketplaceurl' : validicMarketPlaceURL};
					            resObj.status = 1;
						        resObj.message = '';
						        res.json(resObj);    
	                        });             
	                    }
	                    else {
	                        var validicMarketPlaceURL = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+memberInfo.validic_access_token;
				            resObj.data = {'member_id' : req.body.member_id,'validic_uid' : memberInfo.validic_uid, 'validic_access_token' : memberInfo.validic_access_token, 'marketplaceurl' : validicMarketPlaceURL};
				            resObj.status = 1;
					        resObj.message = '';
					        res.json(resObj);
	                    }
	                });
				}
				else {
					var myObj = {
				        user: { uid: req.body.member_id}, 
				        access_token: '1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5'
				    }

				    request({
				        url: "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users.json",
				        method: "POST",
				        json: true,
				        body: myObj
				    }, function (error, response, body){
				    	if(body.code==201){
				            Member.findByIdAndUpdate(req.body.member_id, { validic_uid: body.user._id,validic_access_token : body.user.access_token}, function(err, memberInfo) {
				            	var validicMarketPlaceURL = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+body.user.access_token;
					            resObj.data = {'member_id' : req.body.member_id,'validic_uid' : body.user._id, 'validic_access_token' : body.user.access_token, 'marketplaceurl' : validicMarketPlaceURL};
					            resObj.status = 1;
						        resObj.message = '';
						        res.json(resObj);
					        });
				        }
				        else if(body.code==409){
				        	resObj.status = 0;
					        resObj.message = body.message;
					        res.json(resObj);
				        }
				        else {
				            resObj.status = 0;
					        resObj.message = 'Error while creating new user at validic.';
					        res.json(resObj);
				        }
				    });
				}
			}
			else {
				resObj.status = 0;
		        resObj.message = 'Member not exist.';
		        res.json(resObj);
			}
		});
	}
	else {
		resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
	}
};

exports.postRecordValenceScore = function(req, res){
    var resObj = new Object();
    if(req.body.member_id && req.files){
        var fileName = '';
        async.forEachSeries(req.files, function(singleAudioFile, callback_singleAudioFile) {
            var length = 10,
            fileExt = singleAudioFile.name.split('.').pop();
            fileName = Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
            fileName+= '.'+fileExt;
            singleAudioFile.mv(appDir+'/upload/samplefiles/'+fileName, function(err) {
                callback_singleAudioFile();
            });
        }, function (err) {
            AnalyzerObj.analyze(fs.createReadStream(appDir+'/upload/samplefiles/'+fileName),function(err,analyzerInfo){
                if(err || analyzerInfo.status=='failure'){
                    resObj.status = 0;
                    resObj.message = 'Recorded file was not proper.';
                    res.json(resObj);
                }
                else if(!analyzerInfo.result.analysisSegments){
                    resObj.status = 0;
                    resObj.message = 'Recorded file was not proper.';
                    res.json(resObj);
                }
                else {
                    var analysisObj = new Object;
                    analysisObj.member_id = req.body.member_id;
                    analysisObj.currentDate = _this.postCurrentDate(req,res);
                    analysisObj.currentTime = _this.postCurrentTime(req,res);
                    analysisObj.mood = analyzerInfo.result.analysisSegments[0].analysis.Mood.Group11.Primary.Phrase;
                    analysisObj.valence_score = analyzerInfo.result.analysisSegments[0].analysis.Valence.Value;
                    analysisObj.moodForHighestPoint = analyzerInfo.result.analysisSegments[0].analysis.Mood.Composite.Primary.Phrase;
                    analysisObj.moodForLowestPoint = analyzerInfo.result.analysisSegments[0].analysis.Mood.Composite.Secondary.Phrase;
                    analysisObj.temperValue = analyzerInfo.result.analysisSegments[0].analysis.Temper.Value;
                    var saveValenceObj = new MembersEmotionalAnalytics(analysisObj);
                    saveValenceObj.save(function(err) {
                        fs.unlinkSync(appDir+'/upload/samplefiles/'+fileName);
                        resObj.data = analysisObj;
                        resObj.status = 1;
                        resObj.message = 'Valence score generated successfully.';
                        res.json(resObj);
                    });
                }
            });
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postSocialTagUser = function(req, res){
    
};

exports.postSocialHashTag = function(req, res){
    
};

exports.postListSocial = function(req, res) {
    var baseUrl = req.protocol + '://' + req.get('host'),
    resObj = new Object();

    if(req.body.member_id && req.body.page){
        Member.findOne({ _id: req.body.member_id }, function(err, memberInfo) {
            if(memberInfo){
                var pageEnd = (req.body.page * 10),
                pageStart = (pageEnd - 10);
                MemberSocial.find({},{},{skip : pageStart, limit : pageEnd },function(err, memberSocialInfo) {
                    var memberSocialObj = new Object,
                    memberSocialObjArr = [],
                    memberSocialObjArrCnt = 0;

                    async.forEachSeries(memberSocialInfo, function(single_memberSocialInfo, callback_memberSocialInfo) {
                        var schemaName = '';
                        if(single_memberSocialInfo.usertype=='Super'){
                            schemaName = Superadmin;
                        }   
                        else if(single_memberSocialInfo.usertype=='Insurance' || single_memberSocialInfo.usertype=='Broker'){
                            schemaName = Administrator;
                        }
                        else if(single_memberSocialInfo.usertype=='HR'){
                            schemaName = Company;
                        }
                        else {
                            schemaName = Member;
                        }

                        schemaName.findOne({ _id: single_memberSocialInfo.member_id }, function(err, memberSocialCreator) {
                            var deptTitle = '';
                            Department.find({'_id':{$in: memberSocialCreator.multiple_departments}},function(err, departmentsInfo) {
                                async.forEachSeries(departmentsInfo, function(single_department, callback_single_department) {
                                    if(deptTitle==''){
                                        deptTitle = single_department.title;
                                    }
                                    else {
                                        deptTitle = deptTitle+', '+single_department.title;
                                    }
                                    callback_single_department();
                                }, function (err) {
                                    var socialObj = new Object;
                                    socialObj._id = single_memberSocialInfo._id;
                                    socialObj.caption_title = (single_memberSocialInfo.caption_title) ? single_memberSocialInfo.caption_title : '';
                                    socialObj.name = memberSocialCreator.firstname+' '+memberSocialCreator.lastname;
                                    socialObj.photo = baseUrl+'/member/no_image_user.png';
                                    socialObj.department = deptTitle;
                                    var createdAt = new Date(single_memberSocialInfo.created_at);
                                    socialObj.created_at = (monthNamesShort[createdAt.getMonth()]+' '+createdAt.getDate()).toUpperCase();
                                    memberSocialObjArr[memberSocialObjArrCnt] = socialObj;
                                    
                                    memberSocialObjArr[memberSocialObjArrCnt]['likes'] = [], memberSocialObjArr[memberSocialObjArrCnt]['likes_count'] = '0';
                                    memberSocialObjArr[memberSocialObjArrCnt]['comments'] = [], memberSocialObjArr[memberSocialObjArrCnt]['comments_count'] = '0';
                                    memberSocialObjArr[memberSocialObjArrCnt]['social_media'] = [];

                                    var memberLikesFeedbackArr = [];
                                    var memberLikesFeedbackArrCnt = 0;
                                    var memberCommentsFeedbackArr = [];
                                    var memberCommentsFeedbackArrCnt = 0;

                                    MemberSocialMedia.find({ social_timeline_id: single_memberSocialInfo._id }, function(err, memberSocialMediaInfo) {
                                        var memberSocialMediaArr = [];
                                        var memberSocialMediaCnt = 0;    
                                        async.forEachSeries(memberSocialMediaInfo, function(single_socialMedia, callback_single_socialMedia) {
                                            var socialMediaObj = new Object;
                                            socialMediaObj._id = single_socialMedia._id;
                                            socialMediaObj.social_timeline_id = single_socialMedia.social_timeline_id;
                                            socialMediaObj.media_type = single_socialMedia.media_type;
                                            var foldertype = (single_socialMedia.media_type=='Image') ? 'image' : 'video';
                                            if(single_socialMedia.media_type=='Link'){
                                                socialMediaObj.file = single_socialMedia.file;
                                            }
                                            else {
                                                socialMediaObj.file = (single_socialMedia.file!='') ? baseUrl+'/socialmedia/'+foldertype+'/'+single_socialMedia.file : '';
                                            }
                                            if(single_socialMedia.media_type=='Video'){
                                                socialMediaObj.thumbnail = (single_socialMedia.thumbnail!='') ? baseUrl+'/socialmedia/'+foldertype+'/'+single_socialMedia.thumbnail : '';
                                            }
                                            memberSocialMediaArr[memberSocialMediaCnt] = socialMediaObj;
                                            memberSocialObjArr[memberSocialObjArrCnt]['social_media'] = memberSocialMediaArr;
                                            memberSocialMediaCnt++;
                                            callback_single_socialMedia();
                                        }, function () {
                                            MemberSocialMediaFeedback.find({ social_id: single_memberSocialInfo._id }, function(err, memberSocialMediaFeedbackInfo) {
                                                async.forEachSeries(memberSocialMediaFeedbackInfo, function(single_memberSocialMediaFeedback, callback_single_memberSocialMediaFeedback) {
                                                    var socialFeedObj = new Object;
                                                    socialFeedObj._id = single_memberSocialMediaFeedback._id;

                                                    var schemaName = '';
                                                    if(single_memberSocialMediaFeedback.usertype=='Super'){
                                                        schemaName = Superadmin;
                                                    }   
                                                    else if(single_memberSocialMediaFeedback.usertype=='Insurance' || single_memberSocialMediaFeedback.usertype=='Broker'){
                                                        schemaName = Administrator;
                                                    }
                                                    else if(single_memberSocialMediaFeedback.usertype=='HR'){
                                                        schemaName = Company;
                                                    }
                                                    else {
                                                        schemaName = Member;
                                                    }

                                                    schemaName.findOne({ _id: single_memberSocialMediaFeedback.member_id }, function(err, memberFeedbackInfo) {
                                                        socialFeedObj.member_id = memberFeedbackInfo._id;
                                                        socialFeedObj.name = memberFeedbackInfo.firstname+' '+memberFeedbackInfo.lastname;
                                                        socialFeedObj.photo = (memberFeedbackInfo.photo!='') ? baseUrl+'/member/'+memberFeedbackInfo.photo : '';
                                                        socialFeedObj.social_id = single_memberSocialMediaFeedback.social_id;
                                                        socialFeedObj.feedbacktype = single_memberSocialMediaFeedback.feedbacktype;
                                                        socialFeedObj.created_at = moment(single_memberSocialMediaFeedback.created_at).format('HH:mm MMMM DD, YYYY');
                                                        if(single_memberSocialMediaFeedback.feedbacktype=='Comment'){
                                                            socialFeedObj.comment = single_memberSocialMediaFeedback.comment;
                                                            memberCommentsFeedbackArr[memberCommentsFeedbackArrCnt] = socialFeedObj;
                                                            memberCommentsFeedbackArrCnt++;
                                                        }
                                                        else if(single_memberSocialMediaFeedback.feedbacktype=='Like'){
                                                            memberLikesFeedbackArr[memberLikesFeedbackArrCnt] = socialFeedObj;
                                                            memberLikesFeedbackArrCnt++;
                                                        }
                                                        
                                                        memberSocialObjArr[memberSocialObjArrCnt]['likes'] = memberLikesFeedbackArr;
                                                        memberSocialObjArr[memberSocialObjArrCnt]['comments'] = memberCommentsFeedbackArr;
                                                        memberSocialObjArr[memberSocialObjArrCnt]['likes_count'] = memberLikesFeedbackArr.length.toString();
                                                        memberSocialObjArr[memberSocialObjArrCnt]['comments_count'] = memberCommentsFeedbackArr.length.toString();
                                                        callback_single_memberSocialMediaFeedback();
                                                    });
                                                }, function () {
                                                    memberSocialObjArrCnt++;
                                                    callback_memberSocialInfo();
                                                });
                                            }).sort({created_at: 'desc'})
                                        });
                                    });
                                });
                            });
                        });
                    }, function (err) {
                        resObj.data = memberSocialObjArr;
                        resObj.status = 1;
                        resObj.message = '';
                        res.json(resObj);
                    });
                }).sort({created_at: 'desc'})
            } //end of main if
            else {
                resObj.status = 0;
                resObj.message = 'Member not exist.';
                res.json(resObj);
            }
        }); // end of main
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.singleSocialPostInfo = function(req, res, social_id){
    var resObj = new Object(),
    baseUrl = req.protocol + '://' + req.get('host');
    MemberSocial.find({_id:social_id},function(err, memberSocialInfo) {
        var memberSocialObj = new Object,
        memberSocialObjArr = [],
        memberSocialObjArrCnt = 0;

        async.forEachSeries(memberSocialInfo, function(single_memberSocialInfo, callback_memberSocialInfo) {
            var schemaName = '';
            if(single_memberSocialInfo.usertype=='Super'){
                schemaName = Superadmin;
            }   
            else if(single_memberSocialInfo.usertype=='Insurance' || single_memberSocialInfo.usertype=='Broker'){
                schemaName = Administrator;
            }
            else if(single_memberSocialInfo.usertype=='HR'){
                schemaName = Company;
            }
            else {
                schemaName = Member;
            }

            schemaName.findOne({ _id: single_memberSocialInfo.member_id }, function(err, memberSocialCreator) {
                var deptTitle = '';
                Department.find({'_id':{$in: memberSocialCreator.multiple_departments}},function(err, departmentsInfo) {
                    async.forEachSeries(departmentsInfo, function(single_department, callback_single_department) {
                        if(deptTitle==''){
                            deptTitle = single_department.title;
                        }
                        else {
                            deptTitle = deptTitle+', '+single_department.title;
                        }
                        callback_single_department();
                    }, function (err) {
                        var socialObj = new Object;
                        socialObj._id = single_memberSocialInfo._id;
                        socialObj.caption_title = (single_memberSocialInfo.caption_title) ? single_memberSocialInfo.caption_title : '';
                        socialObj.name = memberSocialCreator.firstname+' '+memberSocialCreator.lastname;
                        socialObj.photo = baseUrl+'/member/no_image_user.png';
                        socialObj.department = deptTitle;
                        var createdAt = new Date(single_memberSocialInfo.created_at);
                        socialObj.created_at = (monthNamesShort[createdAt.getMonth()]+' '+createdAt.getDate()).toUpperCase();
                        memberSocialObjArr[memberSocialObjArrCnt] = socialObj;
                        
                        memberSocialObjArr[memberSocialObjArrCnt]['likes'] = [], memberSocialObjArr[memberSocialObjArrCnt]['likes_count'] = '0';
                        memberSocialObjArr[memberSocialObjArrCnt]['comments'] = [], memberSocialObjArr[memberSocialObjArrCnt]['comments_count'] = '0';
                        memberSocialObjArr[memberSocialObjArrCnt]['social_media'] = [];

                        var memberLikesFeedbackArr = [];
                        var memberLikesFeedbackArrCnt = 0;
                        var memberCommentsFeedbackArr = [];
                        var memberCommentsFeedbackArrCnt = 0;

                        MemberSocialMedia.find({ social_timeline_id: single_memberSocialInfo._id }, function(err, memberSocialMediaInfo) {
                            var memberSocialMediaArr = [];
                            var memberSocialMediaCnt = 0;    
                            async.forEachSeries(memberSocialMediaInfo, function(single_socialMedia, callback_single_socialMedia) {
                                var socialMediaObj = new Object;
                                socialMediaObj._id = single_socialMedia._id;
                                socialMediaObj.social_timeline_id = single_socialMedia.social_timeline_id;
                                socialMediaObj.media_type = single_socialMedia.media_type;
                                var foldertype = (single_socialMedia.media_type=='Image') ? 'image' : 'video';
                                if(single_socialMedia.media_type=='Link'){
                                    socialMediaObj.file = single_socialMedia.file;
                                }
                                else {
                                    socialMediaObj.file = (single_socialMedia.file!='') ? baseUrl+'/socialmedia/'+foldertype+'/'+single_socialMedia.file : '';
                                }
                                if(single_socialMedia.media_type=='Video'){
                                    socialMediaObj.thumbnail = (single_socialMedia.thumbnail!='') ? baseUrl+'/socialmedia/'+foldertype+'/'+single_socialMedia.thumbnail : '';
                                }
                                memberSocialMediaArr[memberSocialMediaCnt] = socialMediaObj;
                                memberSocialObjArr[memberSocialObjArrCnt]['social_media'] = memberSocialMediaArr;
                                memberSocialMediaCnt++;
                                callback_single_socialMedia();
                            }, function () {
                                MemberSocialMediaFeedback.find({ social_id: single_memberSocialInfo._id }, function(err, memberSocialMediaFeedbackInfo) {
                                    async.forEachSeries(memberSocialMediaFeedbackInfo, function(single_memberSocialMediaFeedback, callback_single_memberSocialMediaFeedback) {
                                        var socialFeedObj = new Object;
                                        socialFeedObj._id = single_memberSocialMediaFeedback._id;

                                        var schemaName = '';
                                        if(single_memberSocialMediaFeedback.usertype=='Super'){
                                            schemaName = Superadmin;
                                        }   
                                        else if(single_memberSocialMediaFeedback.usertype=='Insurance' || single_memberSocialMediaFeedback.usertype=='Broker'){
                                            schemaName = Administrator;
                                        }
                                        else if(single_memberSocialMediaFeedback.usertype=='HR'){
                                            schemaName = Company;
                                        }
                                        else {
                                            schemaName = Member;
                                        }

                                        schemaName.findOne({ _id: single_memberSocialMediaFeedback.member_id }, function(err, memberFeedbackInfo) {
                                            socialFeedObj.member_id = memberFeedbackInfo._id;
                                            socialFeedObj.name = memberFeedbackInfo.firstname+' '+memberFeedbackInfo.lastname;
                                            socialFeedObj.photo = (memberFeedbackInfo.photo!='') ? baseUrl+'/member/'+memberFeedbackInfo.photo : '';
                                            socialFeedObj.social_id = single_memberSocialMediaFeedback.social_id;
                                            socialFeedObj.feedbacktype = single_memberSocialMediaFeedback.feedbacktype;
                                            socialFeedObj.created_at = moment(single_memberSocialMediaFeedback.created_at).format('HH:mm MMMM DD, YYYY');
                                            if(single_memberSocialMediaFeedback.feedbacktype=='Comment'){
                                                socialFeedObj.comment = single_memberSocialMediaFeedback.comment;
                                                memberCommentsFeedbackArr[memberCommentsFeedbackArrCnt] = socialFeedObj;
                                                memberCommentsFeedbackArrCnt++;
                                            }
                                            else if(single_memberSocialMediaFeedback.feedbacktype=='Like'){
                                                memberLikesFeedbackArr[memberLikesFeedbackArrCnt] = socialFeedObj;
                                                memberLikesFeedbackArrCnt++;
                                            }
                                            
                                            memberSocialObjArr[memberSocialObjArrCnt]['likes'] = memberLikesFeedbackArr;
                                            memberSocialObjArr[memberSocialObjArrCnt]['comments'] = memberCommentsFeedbackArr;
                                            memberSocialObjArr[memberSocialObjArrCnt]['likes_count'] = memberLikesFeedbackArr.length.toString();
                                            memberSocialObjArr[memberSocialObjArrCnt]['comments_count'] = memberCommentsFeedbackArr.length.toString();
                                            callback_single_memberSocialMediaFeedback();
                                        });
                                    }, function () {
                                        memberSocialObjArrCnt++;
                                        callback_memberSocialInfo();
                                    });
                                }).sort({created_at: 'desc'})
                            });
                        });
                    });
                });
            });
        }, function (err) {
            resObj.data = memberSocialObjArr;
            resObj.status = 1;
            resObj.message = '';
            res.json(resObj);
        });
    })
};

exports.postSocialFeedback =  function(req, res){
    var resObj = new Object();
    if(req.body.social_id && req.body.feedback_type && req.body.member_id){
        if(req.body.feedback_type=='Like' || req.body.feedback_type=='Unlike'){
            MemberSocialMediaFeedback.findOne({social_id:req.body.social_id,member_id:req.body.member_id,$or:[{feedbacktype:"Like"},{feedbacktype:"Unlike"}]}, function(err, memberSocialFeedbackInfo) {
                if(memberSocialFeedbackInfo){
                    if(memberSocialFeedbackInfo.feedbacktype == 'Like'){
                        MemberSocialMediaFeedback.findOneAndUpdate({ _id: memberSocialFeedbackInfo._id },{feedbacktype : 'Unlike'}, function(err) { 
                            _this.singleSocialPostInfo(req, res, req.body.social_id);
                        });
                    }
                    else {
                        MemberSocialMediaFeedback.findOneAndUpdate({ _id: memberSocialFeedbackInfo._id },{feedbacktype : 'Like'}, function(err) { 
                            _this.singleSocialPostInfo(req, res, req.body.social_id);
                        });
                    }
                }
                else {
                    var feedbackObj = {
                        'member_id' : req.body.member_id,
                        'social_id' : req.body.social_id,
                        'feedbacktype' : req.body.feedback_type,
                        'usertype' : 'Member'
                    };
                    var feedbackSaveObj = new MemberSocialMediaFeedback(feedbackObj);
                    feedbackSaveObj.save(function(err) {
                        _this.singleSocialPostInfo(req, res, req.body.social_id);
                    });
                }
            })
        }
        else {
            var feedbackObj = {
                'member_id' : req.body.member_id,
                'social_id' : req.body.social_id,
                'feedbacktype' : req.body.feedback_type,
                'usertype' : 'Member',
                'comment' : req.body.comment
            };
            var feedbackSaveObj = new MemberSocialMediaFeedback(feedbackObj);
            feedbackSaveObj.save(function(err) { 
                _this.singleSocialPostInfo(req, res, req.body.social_id);
            });
        }
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postAktivoScoreTodayYouHave = function(req, res){
    var resObj = new Object();
    if(req.body.member_id){
    	var aktivoScoreTodayYouHave = {
            'slider' : ['Your Aktivo score is <b>better than 35%</b> of people with similar work as you.','Do you <b>sleep well</b>? Read about how to improve <b>Sleep Quality</b> in your Social Wall','<b>Team Alpha</b> is <b>leading</b> the Leader Board Check your Ranking.','Feeling <b>stressed</b>? People in your network are <b>20% more positive</b> than you.'],
            'calories_burned' : "Burned 1,061 calories - you've burned <b>23% more</b> than yesterday, keep going!",
            'steps_taken' : "3458 steps - you've walked <b>18% more</b> than yesterday, keep going!",
            'resting_heart_rate' : "78 bpm - you've <b>22% more</b> than yesterday, keep going!",
            'active_minutes' : "68 active minutes - you've <b>32% more</b> than yesterday, keep going!",
            'sleep' : "6 hrs 35 mins - you've <b>16% more slept</b> than yesterday, keep going!"
        };

		// code for check data is available or not for last 2 days
        var currentDate = _this.postCurrentDate(req,res);
        var startDate = new Date(currentDate);
        startDate.setDate(startDate.getDate()-1);
        startDate = _this.formatDate(req,res,startDate);

        var endDate = new Date(currentDate);
        endDate.setDate(endDate.getDate()-2);
        endDate = _this.formatDate(req,res,endDate);

        var matchDateArr = [startDate,endDate];
        MembersAktivoScore.findOne({member_id : req.body.member_id,aktivo_score:{$ne:0}},function(err, singleAktivoScore){
            aktivoScoreTodayYouHave.aktivo_score = (singleAktivoScore) ? Math.ceil(singleAktivoScore.aktivo_score).toLocaleString() : 0;
            MemberCalories.find({member_id:req.body.member_id,platform_type:'validic',created_date: {$in: matchDateArr}}, function(err, memCalories) {
                var platTypeArr = ['apple_health_kit','google_fit'];
                MemberCalories.find({member_id:req.body.member_id,platform_type: {$in: platTypeArr},created_date: {$in: matchDateArr}}, function(err, memCaloriesAppleGoogle) {
                    if(memCalories.length>0 || memCaloriesAppleGoogle.length>0){
                        resObj.data = aktivoScoreTodayYouHave;
                    	resObj.availability_status = 'available';
				        resObj.status = 1;
				        resObj.message = '';
				        res.json(resObj);
                    }
                    else {
                    	resObj.data = aktivoScoreTodayYouHave;
                    	resObj.availability_status = 'not_available';
				        resObj.status = 1;
				        resObj.message = '';
				        res.json(resObj);
					}
                });
            });
        }).sort({created_at: 'desc'})
        // end of code for check data is available or not for last 2 days
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postSocial = function(req, res){
    var resObj = new Object(),
    path = require('path'),
    appDir = path.dirname(require.main.filename);
    if(req.body.member_id){
        Member.findOne({ _id: req.body.member_id },{'_id':0,'company_id':1}, function(err, memberInfo) {
            if(memberInfo){
                var postObj = {
                    'member_id' : req.body.member_id,
                    'usertype' : 'Member'
                };

                if(req.body.text){
                    postObj.caption_title = req.body.text;

                    var allHashTags = req.body.text.match(/#\w+/g);
                    postObj.hash_tags = allHashTags;

                    var allUserTags = req.body.text.match(/@\w+/g);
                    postObj.user_tags = allUserTags;
                }
                
                var saveObj = new MemberSocial(postObj);
                saveObj.save(postObj, function (error, socialRes) {
                    async.forEachSeries(req.files, function(single_media, callback_single_media) {
                        var length = 10;
                        var fileName = Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
                        var fileExtLower = single_media.name.split('.').pop().toLowerCase();
                        fileName = fileName+'.'+fileExtLower;
                        var folderName = '',mediaType = '';
                        if(fileExtLower=='jpg' || fileExtLower=='jpeg' || fileExtLower=='png' || fileExtLower=='gif'){
                            folderName = 'image';
                            mediaType = 'Image';
                        }
                        else {
                            folderName = 'video';
                            mediaType = 'Video';
                        }

                        single_media.mv(appDir+'/upload/socialmedia/'+folderName+'/'+fileName, function(err) {
                            if(mediaType=='Image'){
                                var mediaObj = {
                                    'social_timeline_id' : socialRes._id,
                                    'media_type' : mediaType,
                                    'file' : fileName,
                                    'thumbnail' : ''
                                };

                                var saveMediaObj = new MemberSocialMedia(mediaObj);
                                saveMediaObj.save(function(err) {
                                    callback_single_media();
                                });
                            }
                            else {
                                var randomString = _this.randomString(req,res,8);
                                var ffmpeg = require('ffmpeg');
                                var process = new ffmpeg(appDir+'/upload/socialmedia/'+folderName+'/'+fileName);
                                process.then(function (video) {
                                    video.fnExtractFrameToJPG(appDir+'/upload/socialmedia/'+folderName, {
                                        frame_rate : 1,
                                        number : 1,
                                        file_name : 'thumb_'+randomString
                                    }, function (error, files) {
                                        if(!error){
                                            var mediaObj = {
                                                'social_timeline_id' : socialRes._id,
                                                'media_type' : mediaType,
                                                'file' : fileName,
                                                'thumbnail' : 'thumb_'+randomString+'_1.jpg'
                                            };

                                            var saveMediaObj = new MemberSocialMedia(mediaObj);
                                            saveMediaObj.save(function(err) {
                                                callback_single_media();
                                            });
                                        }
                                        else {
                                            callback_single_media();
                                        }
                                    });
                                }, function (err) {
                                    callback_single_media();
                                });
                            }
                        });
                    }, function () {
                        if(req.body.link){
                            var mediaObj = {
                                'social_timeline_id' : socialRes._id,
                                'media_type' : 'Link',
                                'file' : req.body.link,
                                'thumbnail' : ''
                            };

                            var saveMediaObj = new MemberSocialMedia(mediaObj);
                            saveMediaObj.save(function(err) {
                                resObj.status = 1;
                                resObj.message = '';
                                res.json(resObj);
                            });
                        }
                        else {
                            resObj.status = 1;
                            resObj.message = '';
                            res.json(resObj);
                        }
                    });
                });
            }
            else {
                resObj.status = 0;
                resObj.message = 'Member not exist.';
                res.json(resObj);        
            }
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postCompete = function(req, res){
    var resObj = new Object(),
    baseUrl = req.protocol + '://' + req.get('host'),
    currentDate = _this.postCurrentDate(req,res);
    if(req.body.member_id){
        Challenge.find({ member_id: { $elemMatch: { $eq: req.body.member_id } },status: {'$eq':'Active'} },{'_id':1,'photo':1,'company_id':1,'title':1,'description':1,'startdate':1,'enddate':1,'days':1,'target':1,'video_url':1,'category':1}, function(err, challengesInfo) {
            var ongoingArr = [], almostOverArr = [], overArr = [];
            async.forEachSeries(challengesInfo, function(singleChallenge, callback_singleChallenge) {
                singleChallenge = JSON.parse(JSON.stringify(singleChallenge));
                var startDate = new Date(singleChallenge.startdate),
                endDate = new Date(singleChallenge.enddate);
                singleChallenge.dayRange = (monthNamesShort[startDate.getMonth()]+' '+startDate.getDate())+' - '+(monthNamesShort[endDate.getMonth()]+' '+endDate.getDate());
                singleChallenge.photo = (singleChallenge.photo!='') ? baseUrl+'/challenges/'+singleChallenge.photo : baseUrl+'/challenges/no-image.png';
                    
                var competitors = [{'name':'Stephen Anderson','achieve':'9.5 km'},{'name':'Steven Smith','achieve':'9.2 km'},{'name':'Brett Lee','achieve':'9.0 km'}];
                singleChallenge.competitors = competitors;

                if(moment(currentDate).isBefore(singleChallenge.startdate)){
                    ongoingArr.push(singleChallenge);
                }
                else if(moment(currentDate).isAfter(singleChallenge.enddate)){
                    overArr.push(singleChallenge);
                }
                else {
                    almostOverArr.push(singleChallenge);
                }
                callback_singleChallenge();
            }, function (err) {
                var competeObj = {
                    'ongoing' : ongoingArr,
                    'almost_over' : almostOverArr,
                    'over' : overArr
                };
                resObj.data = competeObj;
                resObj.status = 1;
                resObj.message = '';
                res.json(resObj);
            });
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postVerifyOTP = function(req, res){
    var resObj = new Object(),
    baseUrl = req.protocol + '://' + req.get('host');
    if(req.body.otp && req.body.email){
        Member.findOne({ otp: req.body.otp,email:req.body.email },{'_id':1,'member_code':1,'firstname':1,'lastname':1,'email':1,'phone':1,'company_id':1,'validic_uid':1,'validic_access_token':1,'age':1,'sex':1,'height':1,'weight':1,'photo':1,'smoker':1,'smokes':1,'alcohol':1,'drinks':1,'date_of_birth':1,'aware_weight':1,'drinker':1,'daily_activity_level':1,'physical_activity':1,'otp_posted':1}, function(err, memberInfo) {
            if(memberInfo){
                var currentDate = new Date(),
                otpPosted = new Date(memberInfo.otp_posted),
                diff = moment(currentDate).diff(moment(otpPosted), 'hours');
                if(diff<24){
                    delete memberInfo.otp_posted;
                    memberInfo.photo = (memberInfo.photo) ? baseUrl+'/member/'+memberInfo.photo : baseUrl+'/member/no_image_user.png';
                    memberInfo.home_background_image = baseUrl+'/background/login_background.jpg';
                    if(memberInfo.validic_uid){
                        var autoRefreshURL = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+memberInfo.validic_uid+"/refresh_token.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5";
                        request(autoRefreshURL, function (error, responseMarket, body) {
                            var autoRefreshResponse = JSON.parse(body),
                            tokenObj = new Object;
                            tokenObj.otp = '';
                            tokenObj.validic_access_token = autoRefreshResponse.user.authentication_token;
                            tokenObj.validic_access_token_updated_datetime = new Date();
                            memberInfo.validic_access_token = tokenObj.validic_access_token
                            Member.findByIdAndUpdate(memberInfo._id, tokenObj, function(err, memberResponse) {
                                resObj.data = memberInfo;
                                resObj.status = 1;
                                resObj.message = '';
                                res.json(resObj);
                            });
                        });
                    }
                    else {
                        memberInfo.validic_uid = '';
                        memberInfo.validic_access_token = '';
                        memberInfo.market_place_url = '';
                        resObj.data = memberInfo;
                        resObj.status = 1;
                        resObj.message = '';
                        res.json(resObj);
                    }
                }
                else {
                    resObj.status = 0;
                    resObj.message = 'Sorry, your otp was expired.';
                    res.json(resObj);
                }
            }
            else {
                resObj.status = 0;
                resObj.message = 'Sorry, your otp not matched.';
                res.json(resObj);
            }
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

/*exports.postVerifyOTP = function(req, res){
    var resObj = new Object(),
    baseUrl = req.protocol + '://' + req.get('host');
    if(req.body.otp && req.body.email){
        Member.findOne({ otp: req.body.otp,email:req.body.email },{'_id':1,'member_code':1,'firstname':1,'lastname':1,'email':1,'phone':1,'company_id':1,'validic_uid':1,'validic_access_token':1,'age':1,'sex':1,'height':1,'weight':1,'photo':1,'smoker':1,'smokes':1,'alcohol':1,'drinks':1,'date_of_birth':1,'aware_weight':1,'drinker':1,'daily_activity_level':1,'physical_activity':1,'otp_posted':1}, function(err, memberInfo) {
            if(memberInfo){
                var currentDate = new Date(),
                otpPosted = new Date(memberInfo.otp_posted),
                diff = moment(currentDate).diff(moment(otpPosted), 'hours');
                if(diff<24){
                    delete memberInfo.otp_posted;
                    memberInfo.photo = (memberInfo.photo) ? baseUrl+'/member/'+memberInfo.photo : baseUrl+'/member/no_image_user.png';
                    memberInfo.home_background_image = baseUrl+'/background/login_background.jpg';
                    if(memberInfo.validic_uid){
                        var autoRefreshURL = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+memberInfo.validic_uid+"/refresh_token.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5";
                        request(autoRefreshURL, function (error, responseMarket, body) {
                            var autoRefreshResponse = JSON.parse(body),
                            tokenObj = new Object;
                            tokenObj.otp = '';
                            tokenObj.validic_access_token = autoRefreshResponse.user.authentication_token;
                            tokenObj.validic_access_token_updated_datetime = new Date();
                            memberInfo.validic_access_token = tokenObj.validic_access_token
                            Member.findByIdAndUpdate(memberInfo._id, tokenObj, function(err, memberResponse) {
                                resObj.data = memberInfo;
                                resObj.status = 1;
                                resObj.message = '';
                                res.json(resObj);
                            });
                        });
                    }
                    else {
                        var memberValidicObj = {
                            user: { uid: memberInfo._id},
                            access_token: '1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5'
                        }
                        request({
                            url: "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users.json",
                            method: "POST",
                            json: true,
                            body: memberValidicObj
                        }, function (error, response, body){
                            if(body.code==201){
                                Member.findByIdAndUpdate(memberInfo._id, { validic_uid: body.user._id,validic_access_token : body.user.access_token, otp : '', otp_posted : ''}, function(err, memberResponse) {
                                    var marketPlaceURL = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+body.user.access_token;
                                    memberInfo.validic_uid = body.user._id;
                                    memberInfo.validic_access_token = body.user.access_token;
                                    memberInfo.market_place_url = marketPlaceURL;
                                    resObj.data = memberInfo;
                                    resObj.status = 1;
                                    resObj.message = '';
                                    res.json(resObj);
                                });
                            }
                            else {
                                var validicErr = (body.errors) ? body.errors : body.message;
                                resObj.status = 0;
                                resObj.message = 'Validic error : '+validicErr;
                                res.json(resObj);            
                            }
                        });
                    }
                }
                else {
                    resObj.status = 0;
                    resObj.message = 'Sorry, your otp was expired.';
                    res.json(resObj);
                }
            }
            else {
                resObj.status = 0;
                resObj.message = 'Sorry, your otp not matched.';
                res.json(resObj);
            }
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};*/

exports.postSignIn = function(req, res){
    var resObj = new Object();
    if(req.body.email){
        Member.findOne({ email: req.body.email },{'_id':1,'company_id':1,'status':1}, function(err, memberInfo) {
            if(memberInfo){
                if(memberInfo.status=='Active'){
                    Company.findOne({ _id: memberInfo.company_id }, function(err, companyInfo) {
                        if(companyInfo){
                            if(companyInfo.status=='Active'){
                                var otp = Math.floor(Math.random()*89999+10000),
                                currentDate = _this.postCurrentDate(req,res),
                                currentTime = _this.postCurrentTime(req,res);

                                var dbObj = {
                                    otp : otp,
                                    otp_posted : new Date()
                                };

                                Member.findByIdAndUpdate(memberInfo._id, dbObj, function(err, memberResponse) {
                                    var otpEmailTemplate = "<html xmlns='http://www.w3.org/1999/xhtml'><head><meta name='viewport' content='width=device-width' /><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' /><title>Email</title><style type='text/css'>table tr td a{color:#ffffff!important;text-decoration:none}.reset_password{color:#ffffff!important;text-decoration:none!important}</style></head><body bgcolor='#FFFFFF'><table width='96%' border='0' cellspacing='0' cellpadding='0' bgcolor='#f2f5f7' style='border-right:1px solid #d3d9dd;border-bottom:20px solid #000;padding:1% 2% 2% 2%;margin:0 0 0 2%;border-radius:10px'><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#000' style='border-radius:5px;padding:20px 0'><tr><td align='center' valign='middle'><img src='https://i.imgur.com/n2nLbtv.png' alt='' width='200'></td></tr></table></td></tr><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0' style='padding:10px 0 0 0'><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:15px 0 10px 10px'><span>Dear #NAME#,</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 10px 10px'><span>Your One-Time Password (OTP) is - #OTP#,</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 10px 10px'><span>This OTP is to be used for activating aktivolabs application to your smartphone as requested on #OTPDATE#, at #OTPTIME# SGT and it is valid for 24 hours only.</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 0 10px'>Thank you,<br>Aktivolabs</td></tr></table></td></tr></table></td></tr></table></body></html>";
                                    otpEmailTemplate = otpEmailTemplate.replace("#NAME#", memberInfo.firstname+' '+memberInfo.lastname);
                                    otpEmailTemplate = otpEmailTemplate.replace("#OTP#", otp);
                                    otpEmailTemplate = otpEmailTemplate.replace("#OTPDATE#", moment(Date()).format('MMMM Do YYYY'));
                                    otpEmailTemplate = otpEmailTemplate.replace("#OTPTIME#", moment(Date()).format('LT'));
                                    
                                    _this.sendOTPEmail(req,res,req.body.email,'Your One-Time Password Request',otpEmailTemplate);
                                    
                                    resObj.status = 1;
                                    resObj.message = 'OTP has been sent to <b>your inbox</b>';
                                    res.json(resObj);
                                });
                            }
                            else {
                                resObj.status = 0;
                                resObj.message = 'Your organization has been frozen, please contact to administrator';
                                res.json(resObj);
                            }
                        }
                        else {
                            resObj.status = 0;
                            resObj.message = 'Organization not assigned to this email address';
                            res.json(resObj);
                        }
                    });
                }
                else {
                    resObj.status = 0;
                    resObj.message = 'Your account has been frozen. please contact to administrator';
                    res.json(resObj);
                }
            }
            else {
                resObj.status = 0;
                resObj.message = 'Email Address is not registered <b> Verify your email and try again.</b>';
                res.json(resObj);
            }
        });
    }
    else {
        resObj.status = 0;
        resObj.message = 'Not passed required parameters';
        res.json(resObj);
    }
};

exports.postCMS = function(req, res){
    var resObj = new Object();
    CMS.find(function(err, cms) {
        resObj.data = cms;
        resObj.status = 1;
        resObj.message = '';
        res.json(resObj);
    });
};

exports.postFlashScreen = function(req, res){
    var resObj = new Object();
    baseUrl = req.protocol + '://' + req.get('host'),
    path = require('path'),
    basePath = path.dirname(require.main.filename);
    FlashScreen.find({},{_id:0,image:1,description:1},function(err, flashscreens) {
        for(var scr=0;scr<flashscreens.length;scr++){
            var baseFlashUploadPath = basePath+'/upload/flashscreen/'+flashscreens[scr].image;
            if (fs.existsSync(baseFlashUploadPath)) {
                flashscreens[scr].image = baseUrl+'/flashscreen/'+flashscreens[scr].image;
            }
            else {
                flashscreens[scr].image = baseUrl+'/flashscreen/no_image.jpg';
            }
        }
        resObj.data = flashscreens;
        resObj.login_background_image = baseUrl+'/background/login_background.jpg';
        resObj.status = 1;
        resObj.message = '';
        res.json(resObj);
    });
};

