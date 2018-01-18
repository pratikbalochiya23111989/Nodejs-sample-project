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
moment = require('moment');
_ = require('underscore');
// verify user and organization
var _this = this;

exports.postReadRFile = function(req, res) {
    var R = require("r-script");
    var out = R("/var/www/html/nodejs/aktivo/public/scoreCalculation.R")
    .data("5a1b6aa61f7f6279a2a13628", "2017-12-08")
    .callSync();
    console.log(out);
    return false;
};

exports.postEditSleepTime = function(req, res, next){
    var resarr = new Object();
    if(req.body.member_id && req.body.created_date && req.body.badtime && req.body.wakeup){
        function diff_seconds(dt2, dt1) 
        {
            var diff =(dt2.getTime() - dt1.getTime()) / 1000;
            return Math.abs(Math.round(diff));
        }

        var createdDate = new Date(req.body.created_date);
        createdDate.setDate(createdDate.getDate()-1);
        createdDate = _this.formatDate(req,res,createdDate);
        
        dt1 = new Date(req.body.wakeup); 
        dt2 = new Date(req.body.badtime);

        var editSleepObj = {
            'total_sleep' : parseInt(diff_seconds(dt1, dt2))
        };
        MemberSleep.findOneAndUpdate({member_id:req.body.member_id,created_date:createdDate},editSleepObj,function(err, sleepResponse){
            resarr.msg = 1;
            res.json(resarr);
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.generateAktivoScore = function(req, res, next){
    var resarr = new Object();
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var R = require("r-script");
    var currentDate = _this.postCurrentDate(req,res);
    if(req.body.member_id){
        R(appDir+"/public/rAktivoScore/AktivoScoreCalculation.R").data(req.body.member_id, currentDate).call(function(err, d) {
            if (err) {
                
            }
            else {
                resarr.msg = 1;
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.setStatistics = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    console.log(req.body);
    if(req.body.id && req.body.generated_score){
        async.forEachSeries(req.body.generated_score, function(singleScore, callback_singleScore) {
            var aktivoGeneratedDate = new Date(singleScore.date);
            aktivoGeneratedDate.setDate(aktivoGeneratedDate.getDate()+1);
            aktivoGeneratedDate = _this.formatDate(req,res,aktivoGeneratedDate)
            var aktivoScoreObj = {
                'member_id': req.body.id,
                'aktivo_score' : singleScore.aktivo_score,
                'loaded_score' : singleScore.loaded_score,
                'LIPA_Modified' : singleScore.LIPA_Modified,
                'MVPA_Modified' : singleScore.MVPA_Modified,
                'Sleep_Modified' : singleScore.Sleep_Modified,
                'SB_Modified' : singleScore.SB_Modified,
                'created_date' : aktivoGeneratedDate,
                'created_at' : aktivoGeneratedDate+'T00:00:00.000Z'
            };
            MembersAktivoScore.findOne({member_id:req.body.id,created_date:aktivoScoreObj.created_date},function(err, aktivoInfo){
                if(aktivoInfo){
                    MembersAktivoScore.findOneAndUpdate({_id:aktivoInfo._id},aktivoScoreObj, function(err) { 
                        callback_singleScore();
                    });
                }
                else {
                    var aktivoStore = new MembersAktivoScore(aktivoScoreObj);
                    aktivoStore.save(function(err) { 
                        callback_singleScore();
                    });
                }
            })
        }, function (err) {
            resarr.msg = 1;
            res.json(resarr);
        }); 
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.userStatisticsAPIFirst = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var date = req.query.date;
        var id = req.query.id, age=35, gender='M';
        Member.findOne({_id:id},function(err, memInfo){
            if(memInfo){
                age = (memInfo.age) ? memInfo.age : 35;
                gender = (memInfo.sex=='Male') ? 'M' : 'F';
            }

            var aktivoLoaderArr = [],historyAktivoLoadedArr = [];
            var dataResponse = {
                id : id,
                age : age,
                gender : gender,
                date : '2017-12-22'
            }

            var resDateRange = ['2017-12-21','2017-12-20','2017-12-19','2017-12-18','2017-12-17','2017-12-16','2017-12-15','2017-12-14','2017-12-13','2017-12-11','2017-12-10','2017-12-09','2017-12-08','2017-12-07','2017-12-06','2017-12-05','2017-12-04','2017-12-03','2017-12-02','2017-12-01'];
            var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
            var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
            var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
            var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];

            async.forEachSeries(resDateRange, function(singleDate, callback_singleDate) {
                var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                var currentDateObj = {
                    LIPA : liPA[randomNo],
                    MVPA : mvPA[randomNo],
                    Sleep : sleepPA[randomNo],
                    SB : sbPA[randomNo],
                    date : singleDate
                }
                aktivoLoaderArr.push(currentDateObj);
                callback_singleDate();
            }, function (err) {
                var responseObj = {
                    statistics : dataResponse,
                    scores : aktivoLoaderArr,
                    historical_scores : historyAktivoLoadedArr
                }
                console.log(" ");
                console.log("======== Request =========");
                console.log(" ");
                console.log(responseObj);
                console.log(" ");
                resarr.data = responseObj;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.userStatisticsAPISecond = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var date = req.query.date;
        var id = req.query.id, age=35, gender='M';
        Member.findOne({_id:id},function(err, memInfo){
            if(memInfo){
                age = (memInfo.age) ? memInfo.age : 35;
                gender = (memInfo.sex=='Male') ? 'M' : 'F';
            }

            var aktivoLoaderArr = [],historyAktivoLoadedArr = [];
            var dataResponse = {
                id : id,
                age : age,
                gender : gender,
                date : '2017-12-22'
            }

            var resDateRange = ['2017-12-21','2017-12-20','2017-12-19','2017-12-18','2017-12-17','2017-12-16','2017-12-15','2017-12-14','2017-12-13','2017-12-11','2017-12-10','2017-12-09','2017-12-08','2017-12-07','2017-12-06','2017-12-05','2017-12-04','2017-12-03','2017-12-02','2017-12-01'];
            var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
            var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
            var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
            var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];

            var resHistoryDateRange = ['2017-11-30','2017-11-29','2017-11-28','2017-11-27','2017-11-26','2017-11-24'];
            async.forEachSeries(resDateRange, function(singleDate, callback_singleDate) {
                var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                var currentDateObj = {
                    LIPA : liPA[randomNo],
                    MVPA : mvPA[randomNo],
                    Sleep : sleepPA[randomNo],
                    SB : sbPA[randomNo],
                    date : singleDate
                }
                aktivoLoaderArr.push(currentDateObj);
                callback_singleDate();
            }, function (err) {
                var aktivoScore = [71.55,70.25,76.36,80.29,65.25,69.96,75.85,64.25,57.95,63.85,77.58,82.35,81.80,78.90,77.99,74.45,76.32,71.10,72.22,73.36,69.25,72.25];
                var loadedScore = [71.50,70.10,76.25,80.36,65.20,69.75,75.22,64.10,57.15,63.10,77.05,82.20,81.14,78.13,77.17,74.28,76.22,71.30,72.12,73.16,69.15,72.10];
                var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
                var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
                var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
                var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];
                var sleepPAModified = [655.78,962.630,688.235,595.275,859.701,768.262,658.432,791.120,886.699,906.248,856.538,724.381,1002.389,831.488,784.450,824.493,826.175,992.125,1062.205,992.242,1122.711,332.930];
                var mvPAModified = [109.297,165.698,113.823,99.779,219.701,145.772,147.078,51.143,42.561,41.926,35.813,36.511,140.259,122.277,79.795,140.278,122.764,30.236,9.125,132.611,64.606,55.979];
                var sbPAModified = [669.449,299.835,628.676,739.275,339.104,504.295,608.836,556.182,461.083,427.323,484.974,613.387,261.818,460.339,554.884,458.051,474.470,370.393,332.167,271.462,218.225,1031.202];
                var liPAModified = [5.464,11.835,9.264,5.669,21.492,21.668,25.653,41.553,49.655,64.501,62.673,65.720,35.532,25.894,20.869,17.176,16.589,47.244,36.501,43.683,34.456,19.887];
                async.forEachSeries(resHistoryDateRange, function(singleHistoryDate, callback_singleHistoryDate) {
                    var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                    var currentHistoryDateObj = {
                        aktivo_score:aktivoScore[randomNo],
                        loaded_score:loadedScore[randomNo],
                        LIPA:liPA[randomNo],
                        LIPA_Modified:liPAModified[randomNo],
                        MVPA:mvPA[randomNo],
                        MVPA_Modified:mvPAModified[randomNo],
                        Sleep:sleepPA[randomNo],
                        Sleep_Modified:sleepPAModified[randomNo],
                        SB:sbPA[randomNo],
                        SB_Modified:sbPAModified[randomNo],
                        date : singleHistoryDate
                    }
                    historyAktivoLoadedArr.push(currentHistoryDateObj);
                    callback_singleHistoryDate();
                }, function (err) {
                    var responseObj = {
                        statistics : dataResponse,
                        scores : aktivoLoaderArr,
                        historical_scores : historyAktivoLoadedArr
                    }
                    console.log(" ");
                    console.log("======== Request =========");
                    console.log(" ");
                    console.log(responseObj);
                    console.log(" ");
                    resarr.data = responseObj;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.userStatisticsAPIThird = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var date = req.query.date;
        var id = req.query.id, age=35, gender='M';
        Member.findOne({_id:id},function(err, memInfo){
            if(memInfo){
                age = (memInfo.age) ? memInfo.age : 35;
                gender = (memInfo.sex=='Male') ? 'M' : 'F';
            }

            var aktivoLoaderArr = [],historyAktivoLoadedArr = [];
            var dataResponse = {
                id : id,
                age : age,
                gender : gender,
                date : '2017-12-22'
            }

            var resDateRange = ['2017-12-21','2017-12-20','2017-12-18'];
            var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
            var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
            var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
            var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];

            async.forEachSeries(resDateRange, function(singleDate, callback_singleDate) {
                var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                var currentDateObj = {
                    LIPA : liPA[randomNo],
                    MVPA : mvPA[randomNo],
                    Sleep : sleepPA[randomNo],
                    SB : sbPA[randomNo],
                    date : singleDate
                }
                aktivoLoaderArr.push(currentDateObj);
                callback_singleDate();
            }, function (err) {
                var responseObj = {
                    statistics : dataResponse,
                    scores : aktivoLoaderArr,
                    historical_scores : historyAktivoLoadedArr
                }
                console.log(" ");
                console.log("======== Request =========");
                console.log(" ");
                console.log(responseObj);
                console.log(" ");
                resarr.data = responseObj;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.userStatisticsAPIFourth = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var date = req.query.date;
        var id = req.query.id, age=35, gender='M';
        Member.findOne({_id:id},function(err, memInfo){
            if(memInfo){
                age = (memInfo.age) ? memInfo.age : 35;
                gender = (memInfo.sex=='Male') ? 'M' : 'F';
            }

            var aktivoLoaderArr = [],historyAktivoLoadedArr = [];
            var dataResponse = {
                id : id,
                age : age,
                gender : gender,
                date : '2017-12-22'
            }

            var resDateRange = ['2017-12-21','2017-12-20','2017-12-18'];
            var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
            var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
            var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
            var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];

            var resHistoryDateRange = ['2017-12-17','2017-12-16','2017-12-15','2017-12-13','2017-12-12','2017-12-11'];
            async.forEachSeries(resDateRange, function(singleDate, callback_singleDate) {
                var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                var currentDateObj = {
                    LIPA : liPA[randomNo],
                    MVPA : mvPA[randomNo],
                    Sleep : sleepPA[randomNo],
                    SB : sbPA[randomNo],
                    date : singleDate
                }
                aktivoLoaderArr.push(currentDateObj);
                callback_singleDate();
            }, function (err) {
                var aktivoScore = [71.55,70.25,76.36,80.29,65.25,69.96,75.85,64.25,57.95,63.85,77.58,82.35,81.80,78.90,77.99,74.45,76.32,71.10,72.22,73.36,69.25,72.25];
                var loadedScore = [71.50,70.10,76.25,80.36,65.20,69.75,75.22,64.10,57.15,63.10,77.05,82.20,81.14,78.13,77.17,74.28,76.22,71.30,72.12,73.16,69.15,72.10];
                var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
                var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
                var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
                var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];
                var sleepPAModified = [655.78,962.630,688.235,595.275,859.701,768.262,658.432,791.120,886.699,906.248,856.538,724.381,1002.389,831.488,784.450,824.493,826.175,992.125,1062.205,992.242,1122.711,332.930];
                var mvPAModified = [109.297,165.698,113.823,99.779,219.701,145.772,147.078,51.143,42.561,41.926,35.813,36.511,140.259,122.277,79.795,140.278,122.764,30.236,9.125,132.611,64.606,55.979];
                var sbPAModified = [669.449,299.835,628.676,739.275,339.104,504.295,608.836,556.182,461.083,427.323,484.974,613.387,261.818,460.339,554.884,458.051,474.470,370.393,332.167,271.462,218.225,1031.202];
                var liPAModified = [5.464,11.835,9.264,5.669,21.492,21.668,25.653,41.553,49.655,64.501,62.673,65.720,35.532,25.894,20.869,17.176,16.589,47.244,36.501,43.683,34.456,19.887];
                async.forEachSeries(resHistoryDateRange, function(singleHistoryDate, callback_singleHistoryDate) {
                    var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                    var currentHistoryDateObj = {
                        aktivo_score:aktivoScore[randomNo],
                        loaded_score:loadedScore[randomNo],
                        LIPA:liPA[randomNo],
                        LIPA_Modified:liPAModified[randomNo],
                        MVPA:mvPA[randomNo],
                        MVPA_Modified:mvPAModified[randomNo],
                        Sleep:sleepPA[randomNo],
                        Sleep_Modified:sleepPAModified[randomNo],
                        SB:sbPA[randomNo],
                        SB_Modified:sbPAModified[randomNo],
                        date : singleHistoryDate
                    }
                    historyAktivoLoadedArr.push(currentHistoryDateObj);
                    callback_singleHistoryDate();
                }, function (err) {
                    var responseObj = {
                        statistics : dataResponse,
                        scores : aktivoLoaderArr,
                        historical_scores : historyAktivoLoadedArr
                    }
                    console.log(" ");
                    console.log("======== Request =========");
                    console.log(" ");
                    console.log(responseObj);
                    console.log(" ");
                    resarr.data = responseObj;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.userStatisticsAPIFifth = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var date = req.query.date;
        var id = req.query.id, age=35, gender='M';
        Member.findOne({_id:id},function(err, memInfo){
            if(memInfo){
                age = (memInfo.age) ? memInfo.age : 35;
                gender = (memInfo.sex=='Male') ? 'M' : 'F';
            }

            var aktivoLoaderArr = [],historyAktivoLoadedArr = [];
            var dataResponse = {
                id : id,
                age : age,
                gender : gender,
                date : '2017-12-22'
            }

            var resDateRange = ['2017-12-21'];
            var sleepPA = [480,488,520,525,360,390,385,495,625,562,574,496,536,578,639,576,498,525,582,636,782,452];
            var mvPA = [80,84,86,88,92,74,86,32,30,26,24,25,75,85,65,98,74,16,5,85,45,76];
            var sbPA = [490,152,475,652,142,256,356,348,325,265,325,420,140,320,452,320,286,196,182,174,152,140];
            var liPA = [4,6,7,5,9,11,15,26,35,40,42,45,19,18,17,12,10,25,20,28,24,27];
            async.forEachSeries(resDateRange, function(singleDate, callback_singleDate) {
                var randomNo = Math.floor(Math.random() * (22 - 1) + 1);
                var currentDateObj = {
                    LIPA : liPA[randomNo],
                    MVPA : mvPA[randomNo],
                    Sleep : sleepPA[randomNo],
                    SB : sbPA[randomNo],
                    date : singleDate
                }
                aktivoLoaderArr.push(currentDateObj);
                callback_singleDate();
            }, function (err) {
                var responseObj = {
                    statistics : dataResponse,
                    scores : aktivoLoaderArr,
                    historical_scores : historyAktivoLoadedArr
                }
                console.log(" ");
                console.log("======== Request =========");
                console.log(" ");
                console.log(responseObj);
                console.log(" ");
                resarr.data = responseObj;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.modifyPA = function(req, res, LIPA, MVPA, Sleep, SB) {
    if(Sleep>=4 || SB>=4 || LIPA>=4 || MVPA>=4){
        if(Sleep>=4){
            if(SB<1){
                SB = 1;
                Sleep-= 1;
            }
            if(LIPA<1){
                LIPA = 1;
                Sleep-= 1;
            }
            if(MVPA<1){
                MVPA = 1;
                Sleep-= 1;
            }
        }
        else if(SB>=4){
            if(Sleep<1){
                Sleep = 1;
                SB-= 1;
            }
            if(LIPA<1){
                LIPA = 1;
                SB-= 1;
            }
            if(MVPA<1){
                MVPA = 1;
                SB-= 1;
            }   
        }
        else if(LIPA>=4){
            if(Sleep<1){
                Sleep = 1;
                LIPA-= 1;
            }
            if(SB<1){
                SB = 1;
                LIPA-= 1;
            }
            if(MVPA<1){
                MVPA = 1;
                LIPA-= 1;
            }
        }
        else {
            if(Sleep<1){
                Sleep = 1;
                MVPA-= 1;
            }
            if(SB<1){
                SB = 1;
                MVPA-= 1;
            }
            if(LIPA<1){
                LIPA = 1;
                MVPA-= 1;
            }
        }
    }
    else {
        Sleep = (Sleep<1) ? 1 : Sleep;
        SB = (SB<1) ? 1 : SB;
        LIPA = (LIPA<1) ? 1 : LIPA;
        MVPA = (MVPA<1) ? 1 : MVPA;
    }
    var paObj = {
        'LIPA' : parseFloat(LIPA.toFixed(2)),
        'MVPA' : parseFloat(MVPA.toFixed(2)),
        'Sleep' : parseFloat(Sleep.toFixed(2)),
        'SB' : parseFloat(SB.toFixed(2))
    };
    return paObj;
};

exports.userStatistics = function(req, res, next){
    var resarr = new Object();
    var async = require('async');
    if(req.query.id && req.query.date){
        var id=req.query.id, date=req.query.date, age=0, gender='M';

        Member.findOne({_id : id},{'age':1,'sex':1,'_id':0},function(err, memberInfo){
            age = (memberInfo.age) ? memberInfo.age : 0;
            gender = (memberInfo.sex) ? (memberInfo.sex=='Male') ? 'M' : 'F' : 'M';
            MembersAktivoScore.findOne({member_id : id,aktivo_score:{$ne:0}},function(err, singleAktivoScore){
                if(!singleAktivoScore){
                    var aktivoStartDate = new Date(date);
                    aktivoStartDate.setDate(aktivoStartDate.getDate()-60);
                    var singleAktivoScore = {
                        'created_date' : _this.formatDate(req,res,aktivoStartDate)
                    };
                }
                var generateAktivoStart = new Date(singleAktivoScore.created_date);
                generateAktivoStart.setDate(generateAktivoStart.getDate()+1);
                generateAktivoStart = _this.formatDate(req,res,generateAktivoStart);
                
                var generateAktivoEnd = new Date(date);
                generateAktivoEnd.setDate(generateAktivoEnd.getDate()-1);
                generateAktivoEnd = _this.formatDate(req,res,generateAktivoEnd);

                var historyStart = new Date(singleAktivoScore.created_date);
                historyStart.setDate(historyStart.getDate()-7);
                historyStart = _this.formatDate(req,res,historyStart);
                
                var historyEnd = new Date(singleAktivoScore.created_date);
                historyEnd.setDate(historyEnd.getDate()-1);
                historyEnd = _this.formatDate(req,res,historyEnd);

                var resDateRangeGenerateAktivo = _this.postRangeStartEndDates(req,res,generateAktivoStart,generateAktivoEnd);
                var resDateRangeHistory = _this.postRangeStartEndDates(req,res,historyStart,historyEnd);
                
                var historicalScores = [],paScores = [];
                async.forEachSeries(resDateRangeGenerateAktivo.reverse(), function(singleDate, callback_singleDate) {
                    var LIPA=0, MVPA=0, Sleep=0,SB=0;

                    MemberHeartBitRate.findOne({member_id:id,created_date:singleDate},function(err, hrInfo){
                        if(hrInfo){
                            LIPA = (hrInfo.minutes_lightly_active) ? parseFloat((hrInfo.minutes_lightly_active/60).toFixed(2)) : 0;
                            SB = (hrInfo.minutes_sedentary) ? parseFloat((hrInfo.minutes_sedentary/60).toFixed(2)) : 0;
                        }
                        MemberExercise.findOne({member_id:id,created_date:singleDate},function(err, exerciseInfoInner){
                            if(exerciseInfoInner){
                                MVPA = (exerciseInfoInner.duration) ? parseFloat((exerciseInfoInner.duration/60).toFixed(2)) : 0;
                            }
                            MemberSleep.findOne({member_id:id,created_date:singleDate},function(err, sleepInfoInner){
                                if(sleepInfoInner){
                                    Sleep = (sleepInfoInner.total_sleep) ? parseFloat((sleepInfoInner.total_sleep/60).toFixed(2)) : 0;
                                }

                                var totalPA = (LIPA + MVPA + Sleep + SB);
                                if((!LIPA && !MVPA && !Sleep && !SB) || (totalPA>1440)){
                                    callback_singleDate();
                                }
                                else {
                                    var modifyPAResponse = _this.modifyPA(req, res, LIPA, MVPA, Sleep, SB);
                                    modifyPAResponse.date = singleDate;
                                    paScores.push(modifyPAResponse);
                                    callback_singleDate();
                                }
                            });
                        })
                    })
                }, function (err) {
                    async.forEachSeries(resDateRangeHistory.reverse(), function(singleHistoryDate, callback_singleHistoryDate) {
                        MembersAktivoScore.findOne({member_id : id,created_date : singleHistoryDate},{'aktivo_score':1,'loaded_score':1,'LIPA_Modified':1,'MVPA_Modified':1,'Sleep_Modified':1,'SB_Modified':1,'created_date':1,'_id':0},function(err, singleDatedAktivoScore){
                            if(singleDatedAktivoScore){
                                var historyObj = {
                                    'aktivo_score' : singleDatedAktivoScore.aktivo_score,
                                    'loaded_score' : singleDatedAktivoScore.loaded_score,
                                    'LIPA_Modified' : singleDatedAktivoScore.LIPA_Modified,
                                    'MVPA_Modified' : singleDatedAktivoScore.MVPA_Modified,
                                    'Sleep_Modified' : singleDatedAktivoScore.Sleep_Modified,
                                    'SB_Modified' : singleDatedAktivoScore.SB_Modified,
                                    'date' : singleDatedAktivoScore.created_date
                                };
                                historicalScores.push(historyObj);
                            }
                            callback_singleHistoryDate();
                        });
                    }, function (err) {
                        if(paScores.length==0){
                            historicalScores = [];
                        }
                        var aktivoScoreResObj = {
                            'statistics' : {
                                'id' : id,
                                'age' : age,
                                'gender' : gender,
                                'date' : date
                            },
                            'scores' : paScores,
                            'historical_scores' : historicalScores
                        };
                        console.log(aktivoScoreResObj);
                        resarr.data = aktivoScoreResObj;
                        resarr.msg = 1;
                        res.json(resarr);
                    }); 
                });
            }).sort({created_at: 'desc'})
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postShareSocialTimeLineCount = function(req,res){
    var resarr = new Object();
    var currentDate = _this.postCurrentDate(req,res),
    currentTime = _this.postCurrentTime(req,res);
    share = currentDate+' '+currentTime;
    if(req.body.member_id && req.body.social_timeline_id){
        MemberSocialShare.findOne({social_timeline_id:req.body.social_timeline_id,member_id:req.body.member_id},function(err, shareRec){
            if(shareRec){
                shareRec.share_datetime.push(share);
                var shareObj = {
                    share_datetime : shareRec.share_datetime,
                    total_count : (shareRec.total_count+1)
                }
                MemberSocialShare.findByIdAndUpdate(shareRec._id, shareObj, function(err, updateRec) {
                    resarr.msg = 1;
                    res.json(resarr);   
                });
            }
            else {
                var shareObj = {
                    member_id : req.body.member_id,
                    social_timeline_id : req.body.social_timeline_id,
                    share_datetime : [share],
                    total_count : 1
                }
                var msp = new MemberSocialShare(shareObj);
                msp.save(function(err) { 
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postPreviewSocialTimeLineCount = function(req,res){
    var resarr = new Object();
    var currentDate = _this.postCurrentDate(req,res),
    currentTime = _this.postCurrentTime(req,res);
    preview = currentDate+' '+currentTime;
    if(req.body.member_id && req.body.social_timeline_id){
        MemberSocialPreview.findOne({social_timeline_id:req.body.social_timeline_id,member_id:req.body.member_id},function(err, previewRec){
            if(previewRec){
                previewRec.preview_datetime.push(preview);
                var previewObj = {
                    preview_datetime : previewRec.preview_datetime,
                    total_count : (previewRec.total_count+1)
                }
                MemberSocialPreview.findByIdAndUpdate(previewRec._id, previewObj, function(err, updateRec) {
                    resarr.msg = 1;
                    res.json(resarr);   
                });
            }
            else {
                var previewObj = {
                    member_id : req.body.member_id,
                    social_timeline_id : req.body.social_timeline_id,
                    preview_datetime : [preview],
                    total_count : 1
                }
                var msp = new MemberSocialPreview(previewObj);
                msp.save(function(err) { 
                    resarr.msg = 1;
                    res.json(resarr);
                });     
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postAutoPushNotification = function(req,res){
    // code for ios push notification
    var apn = require('apn');
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var p8FilePath = appDir+'/public/AuthKey_N7D8LP36YD.p8';
    var options = {
        token: {
            key: p8FilePath,
            keyId: "N7D8LP36YD",
            teamId: "JEVW5NUQGW"
        },
        production: false
    };
    var apnProvider = new apn.Provider(options);
    // end of code for ios push notification

    // code for android push notification
    var FCM = require('fcm-node');
    var serverKey = 'AIzaSyClYcBgo2bpV3A0qOq4H0VAgULipnIoVX4'; //put your server key here
    var fcm = new FCM(serverKey);
    // end of code for android push notification

    var async = require('async'),
    currentDate = _this.postCurrentDate(req,res),
    currentTime = _this.postCurrentTimeNoSec(req,res);
    mergeDateTime = currentDate+' '+currentTime;
    PushNotification.find({types:"Auto"}, function(err, pushRecords) {
        async.forEachSeries(pushRecords, function(singleRec, callback_singleRec) {
            if(mergeDateTime==singleRec.senddatetime){
                if(singleRec.usertype=='Member'){
                    Member.find({_id: {$in: singleRec.users}}, function(err, members) {
                        async.forEachSeries(members, function(secondRec, callback_secondRec) {
                            if(secondRec.device_type){
                                if(secondRec.device_type=='Android'){
                                    var message = { //this may vary according to the message type (single recipient, multicast, topic, et cetera)
                                        to: secondRec.device_token, 
                                        collapse_key: 'green',
                                        
                                        notification: {
                                            body: singleRec.title
                                        },
                                        
                                        data: {  //you can send only notification or only data(or include both)
                                            my_key: 'my value',
                                            my_another_key: 'my another value'
                                        }
                                    };
                                    fcm.send(message, function(err, response){
                                        callback_secondRec();
                                    });
                                }
                                else if(secondRec.device_type=='IOS'){
                                    var deviceToken = secondRec.device_token;
                                    var note = new apn.Notification();

                                    note.expiry = Math.floor(Date.now() / 1000) + 3600; // Expires 1 hour from now.
                                    note.badge = 3;
                                    note.sound = "ping.aiff";
                                    note.alert = singleRec.title;
                                    note.payload = {'messageFrom': 'Aktivo'};
                                    note.topic = "com.activolabdemo";

                                    apnProvider.send(note, deviceToken).then((result) => {
                                        callback_secondRec();
                                    });
                                }
                                else {
                                    callback_secondRec();
                                }
                            }
                            else {
                                callback_secondRec();
                            }
                        }, function (err) {
                            callback_singleRec();       
                        });
                    });
                }
                else {
                    callback_singleRec();
                }
            }
            else {
                callback_singleRec();
            }
        }, function (err) {
            console.log('done');
            return;
        });
    });
};

exports.postAndroidStaticPushNotification = function(req, res){
    var FCM = require('fcm-node');
    var serverKey = 'AIzaSyClYcBgo2bpV3A0qOq4H0VAgULipnIoVX4'; //put your server key here
    var fcm = new FCM(serverKey);
 
    var message = { //this may vary according to the message type (single recipient, multicast, topic, et cetera)
        to: 'c9913B04xKg:APA91bHVMujyreavtqJL7ZahkImr_BICyctQ-Ns1keD0xRYUGaqeoZtq-SMz7NjqftnlkE3uymx95uKYASymM8UJHuSAN80V43OTbdpIootVpgk5Tj7gaWEHOc8_9wOn7iVdhzxC6f97', 
        collapse_key: 'green',
        
        notification: {
            body: 'Body of your push notification'
        },
        
        data: {  //you can send only notification or only data(or include both)
            my_key: 'my value',
            my_another_key: 'my another value'
        }
    };
    
    fcm.send(message, function(err, response){
        if (err) {
            console.log(err);
            console.log("Something has gone wrong!");
        } else {
            console.log("Successfully sent with response: ", response);
        }
    });
};

exports.postIOSStaticPushNotification = function(req, res){
    var apn = require('apn');
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var p8FilePath = appDir+'/public/AuthKey_N7D8LP36YD.p8';
    var options = {
        token: {
            key: p8FilePath,
            keyId: "N7D8LP36YD",
            teamId: "JEVW5NUQGW"
        },
        production: false
    };

    var apnProvider = new apn.Provider(options);
    var deviceToken = "1be94b45df07fa44e7dd598c85517b598075008d225b89eda059e6ba00ece6b0";
    
    var note = new apn.Notification();

    note.expiry = Math.floor(Date.now() / 1000) + 3600; // Expires 1 hour from now.
    note.badge = 3;
    note.sound = "ping.aiff";
    note.alert = "You have a new message";
    note.payload = {'messageFrom': 'Aktivo'};
    note.topic = "com.activolabdemo";

    apnProvider.send(note, deviceToken).then((result) => {
        console.log(result);return false;
    });
};

exports.postRegisterDevice = function(req, res){
    var resarr = new Object();
    if(req.body.member_id && req.body.device_id && req.body.device_name && req.body.device_type && req.body.device_token){
        var memObj = {
            device_id : req.body.device_id,
            device_name : req.body.device_name,
            device_type : req.body.device_type,
            device_token : req.body.device_token
        }
        
        Member.findByIdAndUpdate(req.body.member_id, memObj, function(err, memberres) {
            resarr.msg = 1;
            res.json(resarr);               
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postFlashScreen = function(req, res){
    var resarr = new Object();
    var fullUrl = req.protocol + '://' + req.get('host');
    FlashScreen.find({}, function(err, flashscreens) {
        for(var scr=0;scr<flashscreens.length;scr++){
            flashscreens[scr].image = fullUrl+'/flashscreen/'+flashscreens[scr].image;
        }
        resarr.msg = 1;
        resarr.data = flashscreens;
        res.json(resarr);
    });
};

exports.postFileUploadMultiPart = function(req, res) {
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var multer  = require('multer');

    var filenametmp = '';
    var storage = multer.diskStorage({
        destination: function (req, file, callback) {
            callback(null, appDir+'/upload/challenges');
        },
        filename: function (req, file, callback) {
            var uniq_no = Math.floor(Math.random()*89999+10000);
            filenametmp = file.originalname;
            filenametmp = uniq_no+filenametmp;
            callback(null, filenametmp);
        }
    });
    var upload = multer({ storage : storage}).single('photo');
    upload(req,res,function(err) {
        if(err) {
            console.log(err);
        }
        else {
            console.log('heheehehehe');
        }
    });
    return false;
};

exports.postExerciseImpact = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id && req.body.selected_date){
        var request = require('request'),
        passDate = req.body.selected_date,
        toDate = new Date(passDate),
        year = toDate.getFullYear(),
        month = (toDate.getMonth()+1),
        day = (toDate.getDate()),
        month = ('0' + month).slice(-2),
        day = ('0' + day).slice(-2),
        toDateFormat = year+'-'+month+'-'+day;
        var toDateMinus1 = new Date(passDate);
        toDateMinus1.setDate(toDateMinus1.getDate() - 1);
        var yearMinus = toDateMinus1.getFullYear(),
        monthMinus = (toDateMinus1.getMonth()+1),
        dayMinus = (toDateMinus1.getDate()),
        monthMinus = ('0' + monthMinus).slice(-2),
        dayMinus = ('0' + dayMinus).slice(-2),
        toDateFormatMinus = yearMinus+'-'+monthMinus+'-'+dayMinus;
        Member.findOne({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+passDate+"+00:00&end_date="+toDateFormat+"T23:59:59+00:00&expanded=1";
                request(fitness_url, function (error, response, body) {
                    var myjson = JSON.parse(body),
                    impactObj = new Object,
                    stepsObj = new Object,
                    caloriesBurnedObj = new Object,
                    activeMinsObj = new Object;
                    if(myjson.fitness.length>0){
                        var responseInfo = myjson.fitness[myjson.fitness.length-1];
                        stepsObj.first = '+'+responseInfo.steps;
                        caloriesBurnedObj.first = '+'+responseInfo.calories;
                        activeMinsObj.first = '+'+(parseInt(responseInfo.duration/60));
                        activeMinsObj.second = 'of '+(parseInt(responseInfo.duration/60)).toLocaleString()+' active minutes';
                    }
                    else {
                        stepsObj.first = '+0';
                        caloriesBurnedObj.first = '+0';
                        activeMinsObj.first = '+0';
                        activeMinsObj.second = 'of 0 active minutes';
                    }

                    var steps_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+toDateFormatMinus+"T00:00:00+00:00&end_date="+toDateFormatMinus+"T23:59:59+00:00&expanded=0";
                    request(steps_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.routine.length>0){
                            var responseInfo = myjson.routine[myjson.routine.length-1]; 
                            stepsObj.second = 'of '+responseInfo.steps.toLocaleString()+' steps taken';
                            caloriesBurnedObj.second = 'of '+responseInfo.calories_burned.toLocaleString()+' calories burned';
                        }
                        else {
                            stepsObj.second = 'of 0 steps taken';
                            caloriesBurnedObj.second = 'of 0 calories burned';
                        }
                        
                        impactObj.stepsZone = stepsObj;
                        impactObj.caloriesBurnedZone = caloriesBurnedObj;
                        impactObj.activeMinsZone = activeMinsObj;

                        resarr.data = impactObj;
                        resarr.msg = 1;
                        res.json(resarr);
                    });
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExerciseCaloriesBurnedSlide = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesWithoutAddSubDays(req,res,startDate,endDate,month,year),
                monthly_calories_chart_arr = [],
                table_info = new Object;

                async.forEachSeries(returndates.reverse(), function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            if(origDate<=todayday){
                                var caloriesBurnedObj = new Object,
                                dayCaloriesBurnedCnt = 0;
                                caloriesBurnedObj.dates = n2;
                                if(memExe.length>0){
                                    for(var act = 0; act<memExe.length; act++){
                                        dayCaloriesBurnedCnt+= memExe[act].calories;
                                    }
                                    caloriesBurnedObj.count = dayCaloriesBurnedCnt;
                                }
                                else {
                                    caloriesBurnedObj.count = 0;
                                }
                                monthly_calories_chart_arr.push(caloriesBurnedObj);
                            }
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = monthly_calories_chart_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExerciseHeartRateZonesSlide = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesWithoutAddSubDays(req,res,startDate,endDate,month,year),
                monthly_heart_rate_zones_chart_arr = [],
                table_info = new Object;

                async.forEachSeries(returndates.reverse(), function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            if(origDate<=todayday){
                                var heartRateZoneObj = new Object,
                                peakCnt = 0,
                                cardioCnt = 0,
                                fatburnCnt = 0;
                                heartRateZoneObj.dates = n2;
                                if(memExe.length>0){
                                    for(var act = 0; act<memExe.length; act++){
                                        peakCnt+= parseInt(memExe[act].heart_rate_zones_peak_minutes);
                                        cardioCnt+= parseInt(memExe[act].heart_rate_zones_cardio_minutes);
                                        fatburnCnt+= parseInt(memExe[act].heart_rate_zones_fat_burn_minutes);
                                    }
                                }
                                heartRateZoneObj.peak = peakCnt;
                                heartRateZoneObj.cardio = cardioCnt;
                                heartRateZoneObj.fatburn = fatburnCnt;
                                monthly_heart_rate_zones_chart_arr.push(heartRateZoneObj);
                            }
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = monthly_heart_rate_zones_chart_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExerciseDistanceSlide = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesWithoutAddSubDays(req,res,startDate,endDate,month,year),
                monthly_duration_chart_arr = [],
                table_info = new Object;

                async.forEachSeries(returndates.reverse(), function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            if(origDate<=todayday){
                                var distanceObj = new Object,
                                dayDistanceCnt = 0;
                                distanceObj.dates = n2;
                                if(memExe.length>0){
                                    for(var act = 0; act<memExe.length; act++){
                                        dayDistanceCnt+= parseInt(memExe[act].distance);
                                    }
                                    distanceObj.count = dayDistanceCnt;
                                }
                                else {
                                    distanceObj.count = 0;
                                }
                                monthly_duration_chart_arr.push(distanceObj);
                            }
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = monthly_duration_chart_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExerciseDurationSlide = function(req, res) {
var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesWithoutAddSubDays(req,res,startDate,endDate,month,year),
                monthly_duration_chart_arr = [],
                table_info = new Object;

                async.forEachSeries(returndates.reverse(), function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            if(origDate<=todayday){
                                var durationObj = new Object,
                                dayDurationCnt = 0;
                                durationObj.dates = n2;
                                if(memExe.length>0){
                                    for(var act = 0; act<memExe.length; act++){
                                        dayDurationCnt+= parseInt(memExe[act].duration/60);
                                    }
                                    durationObj.count = dayDurationCnt;
                                }
                                else {
                                    durationObj.count = 0;
                                }
                                monthly_duration_chart_arr.push(durationObj);
                            }
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = monthly_duration_chart_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExerciseTrueFalseSlide = function(req, res) {
var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesWithoutAddSubDays(req,res,startDate,endDate,month,year),
                monthly_true_false_chart_arr = [],
                table_info = new Object;
                async.forEachSeries(returndates.reverse(), function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            var trueFalseDataObj = new Object;
                            trueFalseDataObj.date = n2;
                            if(memExe.length>0){
                                trueFalseDataObj.result = 'true';
                            }
                            else {
                                trueFalseDataObj.result = 'false';
                            }
                            monthly_true_false_chart_arr.push(trueFalseDataObj);
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = monthly_true_false_chart_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postExercise = function(req, res) {
var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                var fullUrl = req.protocol + '://' + req.get('host');
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDatesPlus1Day(req,res,startDate,endDate,month,year),
                weekly_activity_arr = [],
                weeksarr = [],
                weeksArrCnt = 0,
                table_info = new Object,
                weekly_info = new Object;

                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]),
                    single_week = new Object,
                    weekly_activity_arr = [],
                    dayArr = [],
                    weekly_activity_arr_cnt = 0,
                    weekly_total_duration = 0;
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        /*var fitness_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(fitness_url, function (error, response, body) {*/
                        MemberExercise.find({ created_date: n2, member_id : req.body.member_id }, function(err, memExe) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            if(memExe.length>0){
                                for(var act = (memExe.length-1); act>=0; act--){
                                    var timeExtract = new Date(memExe[act].start_time);
                                    var currentHours = ("0" + timeExtract.getHours()).slice(-2);
                                    var currentMins = ("0" + timeExtract.getMinutes()).slice(-2);
                                    var timeFormatted = (currentHours+':'+currentMins);
                                    var dateFormatted = (timeExtract.getFullYear()+'-' + (timeExtract.getMonth()+1) + '-'+timeExtract.getDate());
                                    var startDateTime = memExe[act].start_time.substring(0, memExe[act].start_time.indexOf("+")),
                                    date = moment.utc(dateFormatted).format("DD MMM"),
                                    time = timeFormatted,
                                    time24Format = moment.utc(memExe[act].timestamp).format("HH:MM:SS"),
                                    res_timestamp = new Date(memExe[act].timestamp),
                                    selmonth = (res_timestamp.getMonth()+1),
                                    selmonth = ('0' + selmonth).slice(-2),
                                    seldate = res_timestamp.getDate(),
                                    seldate = ('0' + seldate).slice(-2),
                                    res_timestamp_str = res_timestamp.getFullYear()+'-' +selmonth+ '-'+seldate;
                                    res_datetime_str = res_timestamp_str+'T'+time24Format;
                                    if(dayArr.indexOf(res_timestamp_str) === -1) {
                                        dayArr.push(res_timestamp_str);
                                    }
                                    var actObj = new Object;
                                    actObj.type = memExe[act].type;
                                    typeImgTitle = memExe[act].type.replace(" ", "-");
                                    actObj.typeURL = fullUrl+'/img/activity/'+typeImgTitle.toLowerCase()+'.png';
                                    actObj.resDateTime = res_datetime_str;
                                    if(todaysFormatDate==n2){
                                        actObj.datetime = 'Today at '+time;
                                    }
                                    else {
                                        actObj.datetime = date+' at '+time;
                                    }
                                    actObj.duration = (parseInt(memExe[act].duration/60)+' min');
                                    actObj.calories = memExe[act].calories+' cals';
                                    actObj.average_heart_rate = (memExe[act].average_heart_rate==null) ? '0 avg bpm' : memExe[act].average_heart_rate+' avg bpm';
                                    
                                    var heartRateZoneObj = new Object,
                                    dayPeakCnt = 0,
                                    dayCardioCnt = 0,
                                    dayFatBurnCnt = 0;
                                    
                                    dayPeakCnt+= memExe[act].heart_rate_zones_peak_minutes;
                                    dayCardioCnt+= memExe[act].heart_rate_zones_cardio_minutes;
                                    dayFatBurnCnt+= memExe[act].heart_rate_zones_fat_burn_minutes;
                                    
                                    heartRateZoneObj.peak = dayPeakCnt+' min';
                                    heartRateZoneObj.cardio = dayCardioCnt+' min';
                                    heartRateZoneObj.fatburn = dayFatBurnCnt+' min';
                                    actObj.heartRateZone = heartRateZoneObj;

                                    var caloriesZoneObj = new Object;
                                    caloriesZoneObj.calories = memExe[act].calories+' calories';
                                    var calPerMin = parseFloat(memExe[act].calories/(parseFloat(memExe[act].duration/60)));
                                    caloriesZoneObj.calpermin = calPerMin.toFixed(2)+' cals/min';
                                    actObj.caloriesZone = caloriesZoneObj;
                                    weekly_activity_arr[weekly_activity_arr_cnt] = actObj;
                                    weekly_total_duration+= memExe[act].duration;
                                    weekly_activity_arr_cnt++;
                                }
                            }
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1),
                        lastMon = (lastDate.getMonth()+1);
                        if(weeksArrCnt==0){
                            if(member[0].weekly_exercise_goal){
                                var activityURL = _this.postGetActivityImage(req,res,'exercise_slide', dayArr.length, member[0].weekly_exercise_goal);
                            }
                            else {
                                var activityURL = _this.postGetActivityImage(req,res,'exercise_slide', dayArr.length, 1);
                            }
                            single_week.daysSliderURL = activityURL;
                        }

                        if(member[0].weekly_exercise_goal){
                            if(member[0].weekly_exercise_goal>1){
                                single_week.weekly_exercise = dayArr.length+" of "+member[0].weekly_exercise_goal;
                            }
                            else {
                                single_week.weekly_exercise = dayArr.length+" of "+member[0].weekly_exercise_goal;
                            }
                        }
                        else {
                            single_week.weekly_exercise = dayArr.length+" of 0";        
                        }
                        single_week.daysURL = _this.postGetActivityImage(req,res,'exercise_table', dayArr.length, member[0].weekly_exercise_goal);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }
                        var x = weekly_total_duration,
                        x = x*1000,
                        d = moment.duration(x, 'milliseconds'),
                        hours = Math.floor(d.asHours()),
                        mins = (Math.floor(d.asMinutes()) - hours * 60);
                        if(hours==0){
                            var minLBL = (mins>1) ? 'mins' : 'min';
                            single_week.week_total_duration = mins+" "+minLBL+' total';
                        }
                        else {
                            var hourLBL = (hours>1) ? 'hrs' : 'hr',
                            minLBL = (mins>1) ? 'mins' : 'min';
                            single_week.week_total_duration = (hours+" "+hourLBL+" "+mins+" "+minLBL)+' total';
                        }

                        var x = (weekly_total_duration>0) ? (weekly_total_duration/dayArr.length) : 0,
                        x = x*1000,
                        d = moment.duration(x, 'milliseconds'),
                        hours = Math.floor(d.asHours()),
                        mins = (Math.floor(d.asMinutes()) - hours * 60);
                        if(hours==0){
                            var minLBL = (mins>1) ? 'mins' : 'min';
                            single_week.week_avg_duration = mins+' '+minLBL+' avg';
                        }
                        else {
                            var hourLBL = (hours>1) ? 'hrs' : 'hr',
                            minLBL = (mins>1) ? 'mins' : 'min';
                            single_week.week_avg_duration = (hours+" "+hourLBL+" "+mins+" "+minLBL)+' avg';
                        }

                        single_week.weekly_activity_table_information = weekly_activity_arr.reverse();
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    resarr.data = weeksarr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postGetSetWeeklyExerciseGoals = function(req, res) {
    var resarr = new Object;
    if(req.body.type && req.body.member_id){
        Member.findOne({ _id: req.body.member_id}, function(err, member) {
            if(member){
                if(req.body.type=='Set'){
                    if(req.body.days){
                        var insObj = new Object;
                        insObj.weekly_exercise_goal = req.body.days;
                        Member.findByIdAndUpdate(member._id, insObj, function(err, memberres) {
                            resarr.msg = 1;
                            res.json(resarr);               
                        });             
                    }
                    else {
                        resarr.msg = 0;
                        res.json(resarr);       
                    }
                }
                else if(req.body.type=='Get'){
                    var resObj = new Object;
                    resObj.days = member.weekly_exercise_goal;
                    resarr.data = resObj;
                    resarr.msg = 1;
                    res.json(resarr);       
                }   
                else {
                    resarr.msg = 0;
                    res.json(resarr);       
                }   
            }
            else {
                resarr.msg = 2;
                res.json(resarr);       
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postAutoPullValidicInfo = function(req, res) {
    var request = require('request');
    var resarr = new Object;
    if(req.body.member_id){
        Member.findOne({ status: 'Active'}, function(err, member) {
            if(member){
                var appURL = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/apps.json?authentication_token="+member.validic_access_token;
                request(appURL, function (error, response, body) {
                    var jsonParse = JSON.parse(body);
                    resarr.data = jsonParse;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postAutoRefreshValidicAccessToken = function(req, res) {
    // auto refresh pull data from market place apps
    // https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/apps.json?authentication_token=8MWNsYkmHb_Li-7n5rda
    var request = require('request');
    var async = require('async');
    var resarr = new Object;
    Member.find({ status: 'Active'}, function(err, member) {
        async.forEachSeries(member, function(n1, callback_s1) {
            if(n1.validic_uid){
                var auto_refresh_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+n1.validic_uid+"/refresh_token.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5";
                request(auto_refresh_url, function (error, response, body) {
                    var jsonParse = JSON.parse(body);
                    if(jsonParse.code==200){
                        var tokenObj = new Object;
                        tokenObj.validic_access_token = jsonParse.user.authentication_token;
                        tokenObj.validic_access_token_updated_datetime = new Date();
                        Member.findByIdAndUpdate(n1._id, tokenObj, function(err, memberres) {
                            callback_s1();
                        });             
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
            resarr.msg = 1;
            res.json(resarr);
        });
    });
};

exports.postChangeTimeFormatFrom24To12 = function(req, res, passTime) {
    var ts = passTime;
    var H = +ts.substr(0, 2);
    var h = (H % 12) || 12;
    h = (h < 10)?("0"+h):h;  // leading 0 at the left for 1 digit hours
    var ampm = H < 12 ? " AM" : " PM";
    ts = h + ts.substr(2, 3) + ampm;
    return ts;
};

exports.postChangeTimeFormatFromXToHRMIN = function(req, res, passTime) {
    var x = passTime;   
    x = x*1000;
    var d = moment.duration(x, 'milliseconds'),
    hours = Math.floor(d.asHours()),
    mins = Math.floor(d.asMinutes()) - hours * 60;
    if(hours>0){
        if(mins>0){
            return hours+" hr "+mins+" min";
        }
        else {
            return hours+" hr";
        }
    }
    else {
        return mins+" min";
    }
};

exports.postSleepDetailsPerDay = function(req, res) {
    var resarr = new Object;
    if(req.body.start_date && req.body.end_date && req.body.member_id){
        Member.findOne({ _id: req.body.member_id}, function(err, member) {
            if(member){
                var request = require('request');
                var sleep_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+req.body.start_date+"+00:00&end_date="+req.body.end_date+"+00:00&expanded=1";
                request(sleep_url, function (error, response, body) {
                    var myjson = JSON.parse(body);
                    var sleepObj = new Object;
                    if(myjson.sleep.length>0){
                        var startTime = myjson.sleep[0].start_time,
                        afterT = startTime.substr(startTime.indexOf("T") + 1),
                        beforePlus = afterT.substring(0,afterT.indexOf("."));
                        
                        sleepObj.timeAsleep = (myjson.sleep[0].total_sleep) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,myjson.sleep[0].total_sleep) : '0 hr 0 min';
                        sleepObj.sleepStartSchedule = _this.postChangeTimeFormatFrom24To12(req,res,beforePlus);
                        
                        var endTime = myjson.sleep[0].end_time,
                        afterT = endTime.substr(endTime.indexOf("T") + 1),
                        beforePlus = afterT.substring(0,afterT.indexOf("."));
                        
                        sleepObj.sleepEndSchedule = _this.postChangeTimeFormatFrom24To12(req,res,beforePlus);
                        sleepObj.sleepAwake = (myjson.sleep[0].summary_wake_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,myjson.sleep[0].summary_wake_minutes) : '0';
                        sleepObj.sleepRem = (myjson.sleep[0].summary_rem_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,myjson.sleep[0].summary_rem_minutes) : '0';
                        sleepObj.sleepLight = (myjson.sleep[0].summary_light_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,myjson.sleep[0].summary_light_minutes) : '0';
                        sleepObj.sleepDeep = (myjson.sleep[0].summary_deep_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,myjson.sleep[0].summary_deep_minutes) : '0';
                    }
                    else {
                        sleepObj.timeAsleep = "0 min";
                        sleepObj.sleepStartSchedule = '00 : 00';
                        sleepObj.sleepEndSchedule = '00 : 00';
                        sleepObj.sleepAwake = '0 min';
                        sleepObj.sleepRem = '0 min';
                        sleepObj.sleepLight = '0 min';
                        sleepObj.sleepDeep = '0 min';
                    }
                    resarr.data = sleepObj;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postHeartRateDetailsPerDay = function(req, res) {
    var resarr = new Object;
    if(req.body.selected_date && req.body.member_id){
        var heartRateObj = new Object;
        MemberCalories.findOne({ member_id: req.body.member_id,created_at: req.body.selected_date}, function(err, cal) {
            heartRateObj.caloriesburned = 2155;
            heartRateObj.bpm = 67;
            heartRateObj.exercise_zones_hrs = 1;
            heartRateObj.exercise_zones_mins = 50;
            heartRateObj.exercise_zones_calories = (cal) ? cal.total_calories : 0;
            MemberHeartBitRate.findOne({ member_id: req.body.member_id,created_at: req.body.selected_date}, function(err, heartrate) {
                heartRateObj.bpm = (heartrate) ? heartrate.total_heartrate : 0;
                Member.findOne({ _id: req.body.member_id}, function(err, member) {
                    var request = require('request');
                    var heartbitrate_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/fitness.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+req.body.selected_date+"T00:00:00+00:00&end_date="+req.body.selected_date+"T23:59:59+00:00&expanded=1";
                    request(heartbitrate_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        if(myjson.fitness.length>0){
                            heartRateObj.peak = myjson.fitness[0].heart_rate_zones[3].minutes;
                            heartRateObj.cardio = myjson.fitness[0].heart_rate_zones[2].minutes;
                            heartRateObj.fatburn = myjson.fitness[0].heart_rate_zones[1].minutes;
                            heartRateObj.fatburn_percentage = 45;
                        }
                        else {
                            heartRateObj.peak = 0;
                            heartRateObj.cardio = 0;
                            heartRateObj.fatburn = 0;
                            heartRateObj.fatburn_percentage = 0;
                        }
                        resarr.data = heartRateObj;
                        resarr.msg = 0;
                        res.json(resarr);               
                    });     
                }); 
            }); 
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.intToStringKFormat = function(req, res, value, type) {
    var suffixes = ["", "k", "m", "b","t"];
    var suffixNum = Math.floor((""+value).length/3);
    var shortValue = parseFloat((suffixNum != 0 ? (value / Math.pow(1000,suffixNum)) : value).toPrecision(2));
    if (shortValue % 1 != 0) {
        var shortNum = shortValue.toFixed(1);
    }

    if(type=='no'){
        return shortValue;
    }
    else {
        return shortValue+suffixes[suffixNum];
    }
};

exports.returnDateRange = function(req, res, startDate, stopDate) {
    var dateArray = [];
    var currentDate = moment(startDate);
    var stopDate = moment(stopDate);
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var runner = require("child_process");
    while(currentDate <= stopDate) {
        dateArray.push( moment(currentDate).format('YYYY-MM-DD') )
        currentDate = moment(currentDate).add(1, 'days');
    }
    return dateArray;
};

exports.convertToDecimal2Places = function(req , res, num, precision){
    return Math.ceil(num * precision) / precision;
};

exports.postAktivoScore = function(req, res) {
    var resarr = new Object;
    if(req.body.member_id && req.body.screen){
        Member.findOne({_id:req.body.member_id},function(err, memberInfo){
            if(memberInfo){
                if((req.body.screen=='keepItUp') && (req.body.selected_date)){
                    MembersAktivoScore.findOne({member_id : req.body.member_id,aktivo_score:{$ne:0},created_date:req.body.selected_date},function(err, singleAktivoScore){
                        var scoreObj = new Object;
                        scoreObj.kcal = 2150;
                        scoreObj.minutes = 55;
                        scoreObj.aktivo_score = (singleAktivoScore) ? Math.ceil(singleAktivoScore.aktivo_score) : 0;
                        resarr.data = scoreObj;
                        resarr.msg = 1;
                        res.json(resarr);
                    });
                }
                else if((req.body.screen=='averageScoreThisWeek') && (req.body.start_date) && (req.body.end_date)){
                    var startDateFormatted = req.body.start_date+'T00:00:00.000Z';
                    var endDateFormatted = req.body.end_date+'T23:59:59.000Z';
                    var pipeline = [
                        {"$match": { "member_id": req.body.member_id,"created_at":{$gte:startDateFormatted,$lte:endDateFormatted}} },
                        {
                            "$group": {
                                "_id": null,
                                "average": { "$avg": "$aktivo_score" }
                            }
                        }
                    ];
                    MembersAktivoScore.aggregate(pipeline).exec(function (err, result){
                        var scoreObj = new Object;
                        scoreObj.title = 'This Score Is Better Than Your Last Week Performance.';
                        scoreObj.aktivo_score = (!err) ? ((result.length>0) ? Math.ceil(result[0].average) : 0) : 0;
                        scoreObj.percentage = 42;
                        resarr.data = scoreObj;
                        resarr.msg = 1;
                        res.json(resarr);   
                    })
                }
                else if((req.body.screen=='averageMonthly') && (req.body.month) && (req.body.year)){
                    var startDate = moment([req.body.year, req.body.month - 1]),
                    endDate = moment(startDate).endOf('month'),
                    startDateFormatted = moment(startDate).format('YYYY-MM-DD')+'T00:00:00.000Z',
                    endDateFormatted = moment(endDate).format('YYYY-MM-DD')+'T23:59:59.000Z';
                    var pipeline = [
                        {"$match": { "member_id": req.body.member_id,"created_at":{$gte:startDateFormatted,$lte:endDateFormatted}} },
                        {
                            "$group": {
                                "_id": null,
                                "average": { "$avg": "$aktivo_score" }
                            }
                        }
                    ];
                    MembersAktivoScore.aggregate(pipeline).exec(function (err, result){
                        var scoreObj = new Object;
                        scoreObj.title = 'This Score Is Better Than 32% Of The People Like You.';
                        scoreObj.aktivo_score = (!err) ? ((result.length>0) ? Math.ceil(result[0].average) : 0) : 0;
                        scoreObj.percentage = 28;
                        resarr.data = scoreObj;
                        resarr.msg = 1;
                        res.json(resarr);
                    })
                }
                else if(req.body.screen=='averageScoreInNetwork'){
                    if(memberInfo.company_id){
                        Member.find({company_id:memberInfo.company_id},function(err,allMembers){
                            var memIds = _.pluck(allMembers, '_id');
                            memIds = memIds.join().split(',');
                            var pipeline = [
                                {"$match": { "member_id": {$in : memIds}}},
                                {
                                    "$group": {
                                        "_id": null,
                                        "average": { "$avg": "$aktivo_score" }
                                    }
                                }
                            ];
                            MembersAktivoScore.aggregate(pipeline).exec(function (err, result){
                                var scoreObj = new Object;
                                scoreObj.title = 'This Score Is Better Than Your Last Week Performance.';
                                scoreObj.aktivo_score = (!err) ? ((result.length>0) ? Math.ceil(result[0].average) : 0) : 0;
                                scoreObj.percentage = 42;
                                resarr.data = scoreObj;
                                resarr.msg = 1;
                                res.json(resarr);
                            })      
                        })
                    }
                    else {
                        MembersAktivoScore.findOne({member_id : req.body.member_id,aktivo_score:{$ne:0}},function(err, singleAktivoScore){
                            var scoreObj = new Object;
                            scoreObj.title = 'This Score Is Better Than Your Last Week Performance.';
                            scoreObj.aktivo_score = (singleAktivoScore) ? Math.ceil(singleAktivoScore.aktivo_score) : 0;
                            scoreObj.percentage = 59;
                            resarr.data = scoreObj;
                            resarr.msg = 1;
                            res.json(resarr);
                        });
                    }
                }
                else {
                    resarr.msg = 2;
                    res.json(resarr);   
                }
            } // end of if
            else {
                resarr.msg = 3;
                res.json(resarr);       
            }
        })
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.loadhtmlfile = function(req, res) {
    res.render('index', {
        messages: req.flash('error') || req.flash('info')
    });
};

exports.postCMS = function(req, res) {
    var resarr = new Object;
    CMS.find({}, function(err, cms) {
        resarr.data = cms;
        resarr.msg = 1;
        res.json(resarr);
    });
};

exports.postCreateThumbFromVideo = function(req, res) {
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var pathToFile = appDir+"/upload/videos/Baahubali_2.mp4";
    var pathToSnapshot = appDir+"/upload/videos/thumb/";
    var ffmpeg = require('ffmpeg');

    try {
        var process = new ffmpeg(pathToFile);
        process.then(function (video) {
            // Callback mode
            video.fnExtractFrameToJPG(pathToSnapshot, {
                frame_rate : 1,
                number : 5,
                file_name : 'my_frame_%t_%s'
            }, function (error, files) {
                console.log(files);
                if (!error)
                    console.log('Frames: ' + files);
            });
        }, function (err) {
            console.log('Error: ' + err);
        });
    } catch (e) {
        console.log(e.code);
        console.log(e.msg);
    }
    return false;





    // Also a default node module
    require('child_process').exec(('ffmpeg -ss 00:02:24 -i ' + pathToFile + ' -vframes 1 -q:v 2 ' + pathToSnapshot), function () {

        console.log('Saved the thumb to:', pathToSnapshot);

    });
    return false;

    var fullUrl = req.protocol + '://' + req.get('host');
    var video_url = fullUrl+'/videos/Baahubali_2.mp4';

    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var source_path = appDir+"/upload/videos/Baahubali_2.mp4";
    var destination_path = appDir+"/upload/videos/1.png";

    var thumbler = require('video-thumb');
    console.log(video_url);
    //console.log(source_path+" = "+destination_path);return false;
    thumbler.extract(video_url, 'snapshot.png', '00:00:10', '200x125', function(){

        console.log('snapshot saved to snapshot.png (200x125) with a frame at 00:00:22');

    });
    
    res.end('Done');
    return false;
};

exports.postDisplayMemberProfile = function(req, res) {
    var fullUrl = req.protocol + '://' + req.get('host');
    var resarr = new Object();
    if(req.body.member_id){
        Member.findOne({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var photo = (member.photo!='') ? fullUrl+'/member/'+member.photo : fullUrl+'/member/no_image_user.png';
                var member_obj = {
                    _id:member._id,
                    firstname:member.firstname,
                    lastname:member.lastname,
                    email:member.email,
                    phone:member.phone,
                    photo:photo,
                    date_of_birth:member.date_of_birth,
                    height:(member.height) ? member.height.toString() : '0',
                    aware_weight:member.aware_weight,
                    weight:(member.weight) ? member.weight.toString() : '0',
                    smoker:member.smoker,
                    smokes:(member.smokes) ? member.smokes.toString() : '0',
                    daily_activity_level:member.daily_activity_level,
                    physical_activity:member.physical_activity,
                    badtime:member.badtime,
                    wakeup:member.wakeup
                }
                resarr.data = member_obj;
                resarr.msg = 1;
                res.json(resarr);
            }
            else {
                resarr.msg = 2;
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postUpdateMemberProfile = function(req, res) {
    var resarr = new Object();
    var fs = require('fs');
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    if(req.body.member_id){
        var member_obj = new Object;
        if(req.body.firstname){
            member_obj.firstname = req.body.firstname;
        }
        if(req.body.lastname){
            member_obj.lastname = req.body.lastname;
        }
        if(req.body.phone){
            member_obj.phone = req.body.phone;
        }
        if(req.body.photo && req.body.photo_extension){
            var uniq_no = Math.floor(Math.random()*89999+10000);
            var fl = uniq_no+'.'+req.body.photo_extension;
            var imagefile = req.body.photo;
            //var imagefile = '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';
            var imagedata = imagefile.replace(/^data:image\/\w+;base64,/, '');
            fs.writeFile(appDir+'/upload/member/'+fl, imagedata, {encoding: 'base64'}, function(err){});
            member_obj.photo = fl;
        }

        if(req.body.date_of_birth){
            member_obj.date_of_birth = req.body.date_of_birth;
        }

        if(req.body.height){
            member_obj.height = req.body.height;
        }

        if(req.body.aware_weight){
            member_obj.aware_weight = req.body.aware_weight;
        }

        if(req.body.weight){
            member_obj.weight = req.body.weight;
        }

        if(req.body.smoker){
            member_obj.smoker = req.body.smoker;
        }

        if(req.body.smokes){
            member_obj.smokes = req.body.smokes;
        }

        if(req.body.daily_activity_level){
            member_obj.daily_activity_level = req.body.daily_activity_level;
        }

        if(req.body.physical_activity){
            member_obj.physical_activity = req.body.physical_activity;
        }

        if(req.body.badtime){
            member_obj.badtime = req.body.badtime;
        }

        if(req.body.wakeup){
            member_obj.wakeup = req.body.wakeup;
        }

        Member.findOneAndUpdate({ _id: req.body.member_id },member_obj, function(err) { 
            resarr.msg = 1;
            res.json(resarr);   
        });     
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postListAcceptChallengeUser = function(req, res) {
    var fullUrl = req.protocol + '://' + req.get('host');
    var resarr = new Object();
    var async = require('async');
    var request = require('request');
    if(req.body.challenge_id){
        Challenge.findOne({ _id: req.body.challenge_id }, function(err, challenge) {
            ChallengeAccept.find({ challenge_id: challenge._id}, function(err, challenge_accept) {
                var challenges_accept_arr = [];
                var challenges_accept_arr_cnt = 0;
                async.forEachSeries(challenge_accept, function(singleChalAcc, callback_singleChalAcc) {
                    Member.findOne({ _id: singleChalAcc.member_id }, function(err, member) {
                        var mem_obj = new Object;
                        mem_obj.name = (member.firstname+' '+member.lastname);
                        mem_obj.photo = (member.photo!='') ? fullUrl+'/member/'+member.photo : fullUrl+'/member/no_image_user.png';
                        if(challenge.category=='Aktivo Score Challenge'){
                            var targetValue = 0;
                            mem_obj.target = challenge.target;
                            mem_obj.totaltargetvalue = targetValue;
                            mem_obj.percentage = parseInt((targetValue*100)/challenge.target);
                            challenges_accept_arr[challenges_accept_arr_cnt] = mem_obj;
                            challenges_accept_arr_cnt++;
                            callback_singleChalAcc();
                        }
                        else if(challenge.category=='Positivity Challange'){
                            var startDateFormatted = challenge.startdate+'T00:00:00.000Z';
                            var endDateFormatted = challenge.enddate+'T23:59:59.000Z';
                            MembersEmotionalAnalytics.find({member_id:member._id,created_at:{$gte:startDateFormatted,$lte:endDateFormatted}},function(err, memValence){
                                var targetValue = 0;
                                async.forEachSeries(memValence, function(singleValence, callback_singleValence) {
                                    targetValue+= singleValence.valence_score;
                                    callback_singleValence();
                                }, function (err) {
                                    mem_obj.target = challenge.target;
                                    mem_obj.totaltargetvalue = targetValue;
                                    mem_obj.percentage = (targetValue>challenge.target) ? 100 : parseInt((targetValue*100)/challenge.target);
                                    challenges_accept_arr[challenges_accept_arr_cnt] = mem_obj;
                                    challenges_accept_arr_cnt++;
                                    callback_singleChalAcc();
                                });
                            })
                        }
                        else if(challenge.category=='Steps Challange'){
                            var startDateFormatted = challenge.startdate+'T00:00:00+00:00';
                            var endDateFormatted = challenge.enddate+'T23:59:59+00:00';
                            MemberSteps.find({member_id:member._id,timestamp:{$gte:startDateFormatted,$lte:endDateFormatted}},function(err, memSteps){
                                var targetValue = 0;
                                async.forEachSeries(memSteps, function(singleStep, callback_singleStep) {
                                    targetValue+= singleStep.steps;
                                    callback_singleStep();
                                }, function (err) {
                                    mem_obj.target = challenge.target;
                                    mem_obj.totaltargetvalue = targetValue;
                                    mem_obj.percentage = parseInt((targetValue*100)/challenge.target);
                                    challenges_accept_arr[challenges_accept_arr_cnt] = mem_obj;
                                    challenges_accept_arr_cnt++;
                                    callback_singleChalAcc();
                                });
                            })
                        }
                        else if(challenge.category=='Calories Challenge'){
                            var startDateFormatted = challenge.startdate+'T00:00:00+00:00';
                            var endDateFormatted = challenge.enddate+'T23:59:59+00:00';
                            MemberCalories.find({member_id:member._id,timestamp:{$gte:startDateFormatted,$lte:endDateFormatted}},function(err, memCal){
                                var targetValue = 0;
                                async.forEachSeries(memCal, function(singleCal, callback_singleCal) {
                                    targetValue+= singleCal.calories_burned;
                                    callback_singleCal();
                                }, function (err) {
                                    mem_obj.target = challenge.target;
                                    mem_obj.totaltargetvalue = targetValue;
                                    mem_obj.percentage = parseInt((targetValue*100)/challenge.target);
                                    challenges_accept_arr[challenges_accept_arr_cnt] = mem_obj;
                                    challenges_accept_arr_cnt++;
                                    callback_singleChalAcc();
                                });
                            })
                        }
                        else if(challenge.category=='Active Minutes Challenge'){
                            var startDateFormatted = challenge.startdate+'T00:00:00+00:00';
                            var endDateFormatted = challenge.enddate+'T23:59:59+00:00';
                            MemberActiveMinutes.find({member_id:member._id,timestamp:{$gte:startDateFormatted,$lte:endDateFormatted}},function(err, memAM){
                                var targetValue = 0;
                                async.forEachSeries(memAM, function(singleAM, callback_singleAM) {
                                    targetValue+= singleAM.active_duration;
                                    callback_singleAM();
                                }, function (err) {
                                    mem_obj.target = challenge.target;
                                    mem_obj.totaltargetvalue = targetValue;
                                    mem_obj.percentage = parseInt((targetValue*100)/challenge.target);
                                    challenges_accept_arr[challenges_accept_arr_cnt] = mem_obj;
                                    challenges_accept_arr_cnt++;
                                    callback_singleChalAcc();
                                });
                            })
                        }
                    });
                }, function (err) {
                    challenges_accept_arr.sort(function(a,b) {
                        return b.percentage - a.percentage;
                    });
                    resarr.data = challenges_accept_arr;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postAcceptChallenge = function(req, res) {
    var resarr = new Object();
    var async = require('async');
    if(req.body.member_id && req.body.challenge_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member.length>0){
                Challenge.find({ _id: req.body.challenge_id }, function(err, challenge) {
                    if(challenge.length>0){
                        ChallengeAccept.find({ challenge_id: req.body.challenge_id, member_id: req.body.member_id}, function(err, challenge_accept) {
                            if(challenge_accept.length==0){
                                var challenge_accept_obj = new Object;
                                challenge_accept_obj.member_id = req.body.member_id;
                                challenge_accept_obj.challenge_id = req.body.challenge_id;
                                
                                var ca = new ChallengeAccept(challenge_accept_obj);
                                ca.save(function(err) { });     
                            }
                            
                            resarr.msg = 1;
                            res.json(resarr);
                        });
                    }
                    else {
                        resarr.msg = 3;
                        res.json(resarr);
                    }
                });
            }
            else {
                resarr.msg = 2;
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postListChallenges = function(req, res) {
    var fullUrl = req.protocol + '://' + req.get('host');
    var resarr = new Object();
    var async = require('async');
    if(req.body.member_id){
        Challenge.find({ member_id: { $elemMatch: { $eq: req.body.member_id } },status: {'$ne':'Deleted'} }, function(err, challenge) {
            var challenges_arr = [];
            var challenges_arr_cnt = 0;
            async.forEachSeries(challenge, function(n1, callback_s1) {
                ChallengeAccept.find({ member_id: req.body.member_id,challenge_id: n1._id }, function(err, challengeacceptcnt) {
                    var challenge_obj = new Object;
                    challenge_obj._id = n1._id;
                    challenge_obj.category = n1.category;
                    challenge_obj.type = n1.type;
                    challenge_obj.title = n1.title;
                    if(challengeacceptcnt){
                        if(challengeacceptcnt.length>0){
                            challenge_obj.accepted = 'yes';
                        }
                        else {
                            challenge_obj.accepted = 'no';
                        }
                    }
                    else {
                        challenge_obj.accepted = 'no';
                    }
                    challenge_obj.description = n1.description;
                    challenge_obj.startdate = n1.startdate;
                    challenge_obj.enddate = n1.enddate;
                    challenge_obj.duration = n1.days;
                    challenge_obj.photo = (n1.photo!='') ? fullUrl+'/challenges/'+n1.photo : fullUrl+'/challenges/no-image.png';
                    challenges_arr[challenges_arr_cnt] = challenge_obj;
                    challenges_arr_cnt++;
                    callback_s1();
                });
            }, function (err) {
                resarr.data = challenges_arr;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postListSocial = function(req, res) {
    var timeAgo = require('node-time-ago');
    var fullUrl = req.protocol + '://' + req.get('host');
    var resarr = new Object();
    var async = require('async');

    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member.length>0){
                MemberSocial.find(function(err, membersocial) {
                    var member_social_main_obj = new Object;
                    var member_social_main_arr = [];
                    var member_social_main_arr_cnt = 0;
                    async.forEachSeries(membersocial, function(n1, callback_s1) {
                        var schemaName = '';
                        if(n1.usertype=='Super'){
                            schemaName = Superadmin;
                        }   
                        else if(n1.usertype=='Insurance' || n1.usertype=='Broker'){
                            schemaName = Administrator;
                        }
                        else if(n1.usertype=='HR'){
                            schemaName = Company;
                        }
                        else {
                            schemaName = Member;
                        }

                        schemaName.find({ _id: n1.member_id }, function(err, membersocialcreator) {
                            var deptTitle = '';
                            Department.find({}, function(err, departments) {
                                async.forEachSeries(departments, function(top, callback_top) {
                                    if(top.multiple_members.indexOf(member[0]._id)>=0){
                                        if(deptTitle==''){
                                            deptTitle = top.title;
                                        }
                                        else {
                                            deptTitle = deptTitle+', '+top.title;   
                                        }
                                    }
                                    callback_top();
                                }, function (err) {
                                    var n1_obj = new Object;
                                    n1_obj._id = n1._id;
                                    n1_obj.caption_title = n1.caption_title;
                                    n1_obj.name = membersocialcreator[0].firstname+' '+membersocialcreator[0].lastname;
                                    n1_obj.photo = fullUrl+'/member/no_image_user.png';
                                    n1_obj.department = deptTitle;
                                    n1_obj.created_ago = timeAgo(n1.created_at);
                                    n1_obj.created_at = moment(n1.created_at).format('HH:mm MMMM DD, YYYY');
                                    member_social_main_arr[member_social_main_arr_cnt] = n1_obj;
                                    
                                    member_social_main_arr[member_social_main_arr_cnt]['likes'] = [];
                                    member_social_main_arr[member_social_main_arr_cnt]['comments'] = [];
                                    member_social_main_arr[member_social_main_arr_cnt]['social_media'] = [];
                                    var member_likes_feedback_arr = [];
                                    var member_likes_feedback_arr_cnt = 0;
                                    var member_comments_feedback_arr = [];
                                    var member_comments_feedback_arr_cnt = 0;

                                    MemberSocialMedia.find({ social_timeline_id: n1._id }, function(err, membersocialmedia) {
                                        var member_social_media_arr = [];
                                        var member_social_media_cnt = 0;    
                                        async.forEachSeries(membersocialmedia, function(n3, callback_s3) {
                                            var n3_obj = new Object;
                                            n3_obj._id = n3._id;
                                            n3_obj.social_timeline_id = n3.social_timeline_id;
                                            n3_obj.media_type = n3.media_type;
                                            var foldertype = (n3.media_type=='Image') ? 'image' : 'video';
                                            
                                            n3_obj.file = (n3.file!='') ? fullUrl+'/socialmedia/'+foldertype+'/'+n3.file : '';
                                            member_social_media_arr[member_social_media_cnt] = n3_obj;
                                            member_social_main_arr[member_social_main_arr_cnt]['social_media'] = member_social_media_arr;
                                            member_social_media_cnt++;
                                            callback_s3();
                                        }, function () {
                                            MemberSocialMediaFeedback.find({ social_id: n1._id }, function(err, membersocialmediafeedback) {
                                                async.forEachSeries(membersocialmediafeedback, function(n2, callback_s2) {
                                                    var feed_obj = new Object;
                                                    feed_obj._id = n2._id;

                                                    var schemaName = '';
                                                    if(n2.usertype=='Super'){
                                                        schemaName = Superadmin;
                                                    }   
                                                    else if(n2.usertype=='Insurance' || n2.usertype=='Broker'){
                                                        schemaName = Administrator;
                                                    }
                                                    else if(n2.usertype=='HR'){
                                                        schemaName = Company;
                                                    }
                                                    else {
                                                        schemaName = Member;
                                                    }

                                                    schemaName.findOne({ _id: n2.member_id }, function(err, member_feedback) {
                                                        feed_obj.member_id = member_feedback._id;
                                                        feed_obj.name = member_feedback.firstname+' '+member_feedback.lastname;
                                                        feed_obj.photo = (member_feedback.photo!='') ? fullUrl+'/member/'+member_feedback.photo : '';
                                                        feed_obj.social_id = n2.social_id;
                                                        feed_obj.feedbacktype = n2.feedbacktype;
                                                        feed_obj.created_ago = timeAgo(n2.created_at);
                                                        feed_obj.created_at = moment(n2.created_at).format('HH:mm MMMM DD, YYYY');
                                                        if(n2.feedbacktype=='Comment'){
                                                            feed_obj.comment = n2.comment;
                                                            member_comments_feedback_arr[member_comments_feedback_arr_cnt] = feed_obj;
                                                            member_comments_feedback_arr_cnt++;
                                                        }
                                                        else if(n2.feedbacktype=='Like'){
                                                            member_likes_feedback_arr[member_likes_feedback_arr_cnt] = feed_obj;
                                                            member_likes_feedback_arr_cnt++;
                                                        }
                                                        
                                                        member_social_main_arr[member_social_main_arr_cnt]['likes'] = member_likes_feedback_arr;
                                                        member_social_main_arr[member_social_main_arr_cnt]['comments'] = member_comments_feedback_arr;
                                                        callback_s2();
                                                    });
                                                }, function () {
                                                    member_social_main_arr_cnt++;
                                                    callback_s1();
                                                });
                                            });
                                        });
                                    });
                                });
                            });
                        });
                    }, function (err) {
                        resarr.data = member_social_main_arr;
                        resarr.msg = 1;
                        res.json(resarr);
                    });
                }).sort({created_at: 'desc'})
            } //end of main if
            else {
                resarr.msg = 2;
                res.json(resarr);       
            }
        }); // end of main
    }
    else {
        resarr.msg = 0;
        res.json(resarr);
    }
};

exports.postSocialMediaFeedback = function(req, res) {
    var response = new Object;
    if(req.body.social_id && req.body.feedbacktype && req.body.member_id){
        var feedbackparam = new Object;
        if(req.body.feedbacktype=='Like' || req.body.feedbacktype=='Unlike'){
            MemberSocialMediaFeedback.find({social_id:req.body.social_id,member_id:req.body.member_id,$or:[{feedbacktype:"Like"},{feedbacktype:"Unlike"}]}, function(err, membersocialfeedback) {
                if(membersocialfeedback.length>0){
                    if(membersocialfeedback[0]['feedbacktype']=='Like'){
                        MemberSocialMediaFeedback.findOneAndUpdate({ _id: membersocialfeedback[0]['_id'] },{feedbacktype : 'Unlike'}, function(err) { });
                    }
                    else {
                        MemberSocialMediaFeedback.findOneAndUpdate({ _id: membersocialfeedback[0]['_id'] },{feedbacktype : 'Like'}, function(err) { });
                    }
                }
                else {
                    feedbackparam.member_id = req.body.member_id;
                    feedbackparam.social_id = req.body.social_id;
                    feedbackparam.feedbacktype = req.body.feedbacktype;
                    feedbackparam.usertype = 'Member';
                    var fp = new MemberSocialMediaFeedback(feedbackparam);
                    fp.save(function(err) { });     
                }
            })
        }
        else {
            feedbackparam.member_id = req.body.member_id;
            feedbackparam.social_id = req.body.social_id;
            feedbackparam.feedbacktype = req.body.feedbacktype;
            feedbackparam.usertype = 'Member';
            feedbackparam.comment = req.body.comment;
            var fp = new MemberSocialMediaFeedback(feedbackparam);
            fp.save(function(err) { });
        }
        response.msg = 1;
        res.json(response); 
    }else {
        response.msg = 0;
        res.json(response); 
    }
};

exports.postSocialMedia = function(req, res) {
    var response = new Object;
    var async = require('async');
    if(req.body.member_id && req.body.caption_title){
        var captionMsg = req.body.caption_title;
        var allHashTags = captionMsg.match(/#\w+/g);
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var socialparam = new Object;
            socialparam.member_id = req.body.member_id;
            socialparam.usertype = 'Member';
            socialparam.caption_title = req.body.caption_title;
            socialparam.hash_tags = allHashTags;
            socialparam.company_id = member[0].company_id;
            var sp = new MemberSocial(socialparam);
            sp.save(function(err,saverec) {
                var path = require('path');
                var appDir = path.dirname(require.main.filename);
                var fs = require('fs');
                
                async.forEachSeries(req.files, function(n1, callback_s1) {
                    var length = 10;
                    var fileName = Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
                    var fileExt = n1.name.split('.').pop();
                    var fileExtLower = fileExt.toLowerCase();
                    fileName = fileName+'.'+fileExt;
                    var foldername = '',media_type = '';
                    if(fileExtLower=='jpg' || fileExtLower=='jpeg' || fileExtLower=='png' || fileExtLower=='gif'){
                        foldername = 'image';
                        media_type = 'Image';
                    }
                    else {
                        foldername = 'video';
                        media_type = 'Video';
                    }
                    
                    n1.mv('./upload/socialmedia/'+foldername+'/'+fileName, function(err) {
                        var img_obj = {
                            social_timeline_id : saverec._id,
                            media_type : media_type,
                            file : fileName
                        };

                        var smp = new MemberSocialMedia(img_obj);
                        smp.save(function(err) {
                            callback_s1();
                        });
                    });
                }, function () {
                    response.msg = 1;
                    res.json(response);
                });
            });
        });
    }
    else {
        response.msg = 0;
        res.json(response); 
    }
};

exports.postSaveMemberSleep = function(req, res) {
    var request = require('request');
    var url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/58f9d13a9b03e622410258a0/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date=2017-04-01T00:00:00+00:00&end_date=2017-04-01T23:59:59+00:00&expanded=1";
    request(url, function (error, response, body) {
        var resarr = new Object;
        var myjson = JSON.parse(body);
        var tmp = new Object;
        tmp.member_id = '58f74f5a8438111f84fee113';
        if(myjson.sleep.length>0){
            tmp.total_sleep = myjson.sleep[0].total_sleep;
            tmp.created_at = '2017-04-01T23:59:00+00:00';
            var tmpsave = new MemberSleep(tmp);
            tmpsave.save(function(err) {});
        }
        resarr.msg = 1;
        res.json(resarr);
    }); 
};

exports.postGetRandomFile = function(req, res) {
    var filearr = ['1.wav','audioFile.wav','audioFile2.wav','audioFile3.wav','audioFile4.wav','audioFile5.wav','audioFile6.wav','audioFile7.wav','audioFile8.wav','audioFile9.wav','audioFile10.wav'];
    var min = 0;
    var max = (filearr.length-1);
    var randno = (Math.floor(Math.random()*(max-min+1)+min));
    var fs = require('fs');
    var Analyzer = require('./analyzer-v3');
    var analyzer = new Analyzer('d58a3db3-826a-4bc2-857d-91e91f9c42b7');

    analyzer.analyze(fs.createReadStream('./allwavfiles/'+filearr[randno]),function(err,analysis){
        var resarr = new Object;
        resarr.filename = filearr[randno];
        resarr.data = analysis;
        resarr.msg = 0;
        res.json(resarr);
    });
};

exports.postSaveSetting = function(req, res) {
    var resarr = new Object;
    var myset = new Object;
    myset.title = 'Organization Token';
    myset.value = 'org-token';
    var set = new Setting(myset);
    set.save(function(err) {
        if (err) {
            resarr.msg = 0;
            res.json(resarr);
        }
        else {
            resarr.msg = 1;
            res.json(resarr);
        }
    });
};

// image upload using base64
exports.postIMGUploadBase64 = function(req, res) {
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var uniq_no = Math.floor(Math.random()*89999+10000);
    var fl = uniq_no+'.wav';
    //var audiofile = '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XQBhAFIATwBCADUAKQAUABQACQD6//b/6f/r/+b/4f/i/9z/5v/e//T/6//3////AgAOAAcAFwAPABgAFgATABkAEgAcAAwAGwAOABoAFQAaACMALgAjADMALQA3ADsAMwA0ACUAKwAhACMAGgAgABkAEgAQAAQACQAKABYADQAZABUAJwAZACEAEgAQAPX/7f/b/9v/yv/L/7f/uf+i/6b/nf+h/6L/oP+o/7D/uv/N/83/0f/W/9T/5P/e/+//3//q/9b/1P/N/87/yv/R/9X/1//b/9//4f/y//H/AgAJACcAKQA9AEgAUwBZAFUAVABbAFsAWABJAE4AQQBAADwAOQAxADMAIwAuAC8AMAA/AC4APQA0ACsAJgAUABMACAADAAAA8v/m/9n/y//B/8D/wf+6/8L/zP/O/+b/3//x//L///8MABQAIAAmACYAKgApAC8AOAA8AE8ASwBXAFcAYwBsAG0AgQB/AJoAkgCjAJwAlgCVAH8AggBsAGoAWgBPAD8ANgAmACYAHAAWABcACwAWABAACQAKAPn/9f/r/97/2//K/8//x//A/77/tf/D/7r/vf/G/8r/3v/j/+X/8//0//3/9v8DAPn/AQAFAP//BQD//wIA9//0//T/9v/4/wMAAwALAAwABwAIAAMAAwACAAAACQAAAAoA+v/+/+z/6//i/9//1P/S/9L/1P/W/9f/0P/T/8n/0v/Q/9j/3P/a/+X/6f/1//3//f/7//f/+f/7////CgALABQAEgAWABwAJQAnACsANQA2ADwAPAA5ADkAPAAzAD0ANQA8ADkANwA0AC8AJgAvAC8AOQA3ADwAPQA6ADsAMwA4ACwALQAkACAAIQAaABUAEQAJAAAABwAHABUAFgAbACUAKQAvAC8AMQAsAC8AJAAiABoAFwANAAkA/f8AAOn/7f/b/9//2P/U/9f/2v/d/93/2v/g/9r/4P/d/+z/6P/s/+P/5v/q/+H/5v/p/+j//v/6/w0AEQAdACQALwAzAD4ARgBAAEIAMgA9AC0APgAtADcALwArACwALQApADIAJgAxACwAOQA2AD0AQwBDAEsAVQBVAGUAZABuAGoAYgBhAFoAVABNAD0ANQAiABYACAAAAPn/8//w//b/+v8AAPr/BAACAAwABAATAAoADwAMAAwAEQARAB8AFAAbABcAIAAsACsAMwA2ADoAQgA7AEoARABHADQAMAA1ACcANgA0ADsARwBGAEkAUwBMAFwAWwBfAG8AXwB0AFkAbQBWAGUAWQBmAFwAXABPAEsAQAA9AD0ANwAqACYAGwAVAA8A+//+/+f/8P/g/+n/3//y/+7//f/2//7/AwD+/wMAAQAFAA0AEAATAB4AGAAnACQAJQAmADIALQA/ADcARQA6ADsAMAArAC0AJwAtADEANQA3ADgAOgA5AEMAQABQAEQATgA9ADoALgAcABEA///0/+P/3//d/9j/5//r//b/+//9/wQACgARABEAFwAGAP//7P/e/8//zv/L/9b/4f/t/wEACwAbAC4AQgBcAGoAdQCEAI4AmACaAJgAmACOAIoAhgB9AHoAbABwAGIAZwBYAFAASwBNAE4AWABNAE4AVABWAGAAWgBuAFQAZgBOAFIAQgA2AC0AHgAOAAAA///1//3/8//1/+j/6//r/+j/7P/p//L/5v/k/93/0//d/9j/5f/d/+D/5v/h//L/8P///xAAEgApACkAMAA9AEIAVQBEAFYAOAA2ABwAFQAQAAoABQAGAPb/9f/h/+P/4//j/+r/7f/2//T/9v/4//n/+v/x//T/6v/w/+X/4//i/8//2//R/+D/6//r//b/7/8AAAQADwARACUAIgAtACwAOAAyADgALgAxACQALgAmADYAMAAzACgANQA0AD8AQQA7AEAAOQA7AD4APAA4ADMAKgAgABwAFQAOABMADwAVABEAEQAMABcAEgAiACUAMAA0ADMALQAdABAA+f/w/9X/wf+2/6z/qv+g/6P/oP+k/6j/tv/C/9D/4f/w/wYACwAgABgAKAAZACMAHgAfABsADgAXAAQADAAEAP///f/8/wQABQALABMAHwAfACwALwA4AD0ATABQAFUAVQBRAFMARgA7ACcAGgAQAAoACQADAP7/8f/x/97/6//b/9//4//c/+b/3P/a/+D/2P/g/9P/4P/K/9H/wP/K/8//1//d/9//8v/u//z/AQANAB0AJAAsADYALwA0ACgALgAwACoAOAA0AEAARQBQAFcAZwBwAIIAfwCFAIcAggCBAG4AaABgAFYATAA5ADEAFgAZAPv/AAD0//r//v/9/wIAAwAEAA0ACAAeAB0AJQAfABcAHQAVABAACwACAP3/9//2//3/+v/9/wQABQAAABAA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ZgXaBSUGPgYdBuUFnAU+Bd8EVwTgA0EDtAIZAooB6gBUAL//L/+u/kv+Df7y/fP9DP40/nL+qf7e/v/+OP9C/1r/Zv95/6D/1/8iAJ0ANgHrAagCcwMcBLQEIAViBYIFhgVcBR0FxgRoBCAEvQNsAxUDrAJVAv4BqgE+AcYAUwC6/0b/x/5n/hD+8f3f/fD9Cf5G/pv+5/5B/6v/GACUABkBlQELAn0C8AJnA80DIgRhBIwEngSVBGwEKwTPA3QDDgOmAjQCuwFSAegAoABVABYAyv+Q/3X/V/86/wj/2P6e/nr+MP4V/vz9A/4W/kj+mv4L/57/OwDtAIABJwK0AjQDoQMnBKAFdwXdBHYEzQMZA5ICbQI/AusBqwFUAQsBBQH1AMcAnQB7AFAAIQAeACwAXgCXAMkA1wDMANYAyAChAHsAaQBXAFgAeQClAOQAJQGFAfQBgQLeAsEDxwQuBIoDXQM0AwwDDAMhA/8C0gJ+Al0CIwL3AdUBlQFKASoB7ADFAOcA9AAcAToBUgFNATkBNgEXAQgB+gDlAN0AyADIAM4A3wD3AAcBMwFKAWUBhQGxAe8BEAIvAkMCewKKApQCkwKFAnkCWQIvAuIBpAFiASQB2QC2AJcAYABtAIcAsACqAMIAwAC9AMYAxwC0ALAAtgCpAJoAqwCsALUA2gAFARsBSgFxAYQBxgHUAd4BCAIXAhgCGQIsAh8CHgIIAvsB3gHJAa0BjgF4AVcBNQEaAf0A5gDMALUAmACRAH0AdwBjAG8AYQBrAFIARgAsAB0AAwD3/+X/6//l////EwBKAGcAmgC/AP4AEgEwAUMBQQE3ARsB/QDxAOoA7ADuAPIA9AAPASIBTQFLAU0BMAEeAQMB4gDBAJ0AmgCXAJgAkQCaAJgAtwC7AMMA1gDcAOQA6gD0AAIBHQEvAUcBbgGOAYsBlAF/AYMBcgFbAUoBNgEmAf8A+QDkAPYA/AATARUBIwE0AUUBUAFdAT4BJAENAfIA8ADgAOAA4QDkAOUA8AD9AB0BRQFFAVcBQgE7ASUBGQEaARgBIgEpATgBPgE+AT4BKgEnAQkB8wDTAKwAtgClALwAxgDwAA0BQAFkAYgBkgGRAZIBfQFwAVMBMwEMAfUA3gDAALAAkACeAIsAlACKAIgAjwCNAJEAkACJAJkAjgCdAKQAxADQAOIA4QDRAMEA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qv/j/wEAIwAxAFIAawCJAJYApgCtAKEAtwCeAL4AqQDJALsAxgDJANIA1QDTAN8A3QDZAMwAvwCmAJIAjQBqAFMATAAyADAAIwAfACIALQAnADQANwBPAHYAeACUALMAuwDMANAA0ADRAOAA3QDOANIAxgC6ALcAugDBAM4A0wDaAM0A5QDVANwA4gDlAOAA3ADWAMwAtwC2AI0AhgBlAFoANQA5AC8AMgBLAE8AZABhAGwAfQCLAKgAwgDTAOYA9ADhAOQAzgDOAMAAvACvAKsArQCtAKkAvQC6ANIA1QDmAOQA9QDoAPEA2wDUAMcAwwCxAJ0AhgCAAF8AVwBLAEsATwBSAGEAZQBzAIMAkQCWAJwArAC1AMoA3ADjAPcAAgEGARYBLAEzATwBRQE/AUsBVgFnAXYBhgGYAZwBpgGkAakBoQGcAYYBfgFgAVIBPQEnARAB8ADbAMsAvgCvALAAnwC+AMoA3gDuAA0BFgEwATABOQE4ATkBMAEXAQcB9QDYAMwAwwDDAMUAxwDPAM8A2QDzAAYBDgEnASsBPAE8AUYBRAFLATsBLgEkARoBEwH/AP8A+gD3AAcBCQEmASgBQwFAAVQBXwFGAT8BOQEkAR0BFAESARIBGwEcARgBIgEXASoBHAE8ATABQQFQAV8BbwF9AYYBkgGUAaYBngGfAZABgAFrAVoBQgEqASMBHgEcAR8BIAEkASABLgEpAVEBTwFlAXABbgGGAYMBkQGSAZYBmwGmAasBtQG1AbMBswGmAaUBjwGYAY4BhwGBAXABaAFTAUEBIQELAfEA3wDEAK0AnACHAIMAggCIAJsAmQC6AMQA1QDhAOIA4gDZAMcAyQDIAMkA0ADLANgAzADiANUA4gDhAOoA5AD0APMA8QAAAQkBGgEkAS8BMQE0ATIBLAEiAQ8BBAHuAO8A7QDvAOwA7gD1APwA/AD6AAQBBgEMARsBHgEkASMBGQEdAQYBDAH5AAwBAwEZAR8BIAElAS8BNQE3AU0BUgFVAVgBXQFUAWMBaAFoAW4BYgFdAVcBTgFIATcBMQEkASEBHQEZAR8BEwEXAQYBDQEGAQgBDQERARsBJQEfARcBHgEVASABKQEtAToBNwFJAUwBSAFRAT4BQAEjASYBAwH+AOIA2wC+ALAAoACLAIcAcwBpAFoAUQBLAE8ATwBbAE8AVABLAFQAXQBuAHgAggCLAIsAgwB/AGgAXgBcAE0ASwA7AEIAMwA+ADsAVABaAGYAcwCBAH4AjgCGAJAAoAClAK0ArQCyALQAvQDJAM4A0ADTANgA3QDnAOIA9wD4APQA/ADwAPMA6ADfAMsAywC2ALoAngCYAJQAfgCBAG8AdgB1AHsAewBuAHMAYABtAFcAZABYAF0AXABgAGIAYwBiAGQAXABLAEcAKwAbAAwADwABABoAEQAoACcAMQA0AC4ASgA/AFYATQBSAEwAVABSAGEAXwB2AGwAgABxAHgAagBwAF0AXQBXAFIAQQBEADQAMgArACUAGgAhABIAFgATABUAFgAaACEAHwAfACsAJgAwACgAHgAZAAoADAADAAsADQAdABwAHgAkABwAJQAcACYAGgAaABUAGwAVAB8AGwAYABMAHgAgACYAIwAmACgAIgAkABMAHQAfAC0AMwA+AEIATABQAE4AXQBVAF4AWwBgAF0AUwBQAD0AQQAmACIAEAD9/+3/3v/M/9L/yf/M/8r/0f/Q/8r/0f/L/87/1v/J/8//yv/K/7//uv+8/7X/sP+t/6j/qf+z/7D/t/+9/7v/s/+3/6z/sP+n/7P/rf+4/8X/xf/S/9D/3f/f/+D/6//p/+v/4//n/9L/0v/G/7P/tv+k/6f/mv+f/5T/mv+b/5n/mP+d/5//qP+r/6z/sP+o/7v/r/+2/7D/rv+q/6//qf+3/6r/tP+s/7X/q/+v/57/nP+Q/4//gf9//3X/fv97/4j/if+S/5T/nP+e/6r/n/+x/6P/tv+4/7j/xP/C/8v/yf/L/8r/0f/R/9X/1v/Z/9f/1P/e/+D/5f/q//D/+P/5//H/8f/m/+H/2v/T/8//yv/N/8X/xv/H/9f/1P/d/9v/2v/m/+//9f/0/////P8BAAMAAAAGAAEA9//y/93/3//O/9D/yP/E/8L/0f/P/9j/2P/h/+L/8P/y//7//f8OABIADwAbABQAEQAAAP//6v/t/9z/1//L/8L/wf+//7f/vv+x/7r/t/+9/7b/tf+x/6H/nf+R/4v/hP+S/4f/if9//3f/d/9n/3D/Yf9i/1b/V/9N/0//Sv9C/0v/Q/9N/0L/Tv9M/1H/Vf9X/2b/Y/9s/23/fP+O/6D/q/+9/8D/0f/O/9v/z//W/8v/yP/B/7r/v/+2/7L/r/+1/67/p/+m/53/l/+R/5P/kP+L/4L/ef93/2//aP9e/1b/Tf9M/0f/Sf9E/0P/Pv80/zH/M/8y/z3/Pv9D/0T/R/9Q/0//Wf9W/1j/Wv9b/1z/Xv9S/0n/R/9F/0v/Tv9T/1D/YP9g/2j/a/92/3j/e/94/37/e/96/4n/hv+T/5b/oP+p/6L/of+d/5j/ov+i/57/mP+S/47/jf94/4X/a/97/2//dv9s/3f/ef+H/4f/hv+E/4H/if+A/4P/iP+P/47/iv+B/3P/e/9q/2b/Yv9h/2f/Xv9h/1n/Yv9U/1b/Tf9Q/1P/U/9i/1//av93/3n/j/+Y/5//sf+v/8L/w//Q/9T/2P/a/+L/3P/g/9//4//k/+r/4//p/9n/4v/P/9f/w/+4/7L/rP+g/5X/m/+V/5//ov+g/6b/p/+0/7H/tf+w/6j/pv+X/4//h/9//3P/a/9k/1//Wf9g/1v/bv9r/3T/e/98/4T/j/+d/6r/sf+y/7f/t/+0/7X/vP+5/7z/uf/A/6//sv+e/57/kv+W/5D/mP+R/5T/k/+Z/5j/mf+f/53/pP+h/6D/n/+R/47/gf95/3X/bf9k/2P/Wv9Q/1X/Uv9Z/17/YP9g/1j/Wf9T/1b/Tf9O/0f/Rv82/zr/Nf89/z7/S/9M/1D/Uf9Y/1j/Zf9f/2//cP9w/3f/cv93/3r/fv9+/37/h/+M/5T/mf+W/6P/mf+o/6f/pf+1/7H/vv/G/8r/1f/O/9b/2P/S/9v/1P/X/9D/0f/T/9j/2f/h/97/4P/a/9L/1P+9/8D/s/+z/63/pP+g/57/lf+Y/5X/jf+M/4L/fP9//3b/ff9y/3j/bf9v/3H/c/96/3//h/+V/5H/j/+W/4//kv+R/5H/oP+T/5b/j/+L/47/g/+E/4z/iv+W/5L/mv+j/53/rf+h/6n/qP+0/7z/wv/J/8X/yv/H/8n/xf/G/8D/w/+2/7j/n/+f/4//g/99/2n/af9j/2X/Y/9q/2z/c/99/4f/kf+e/6z/uf/L/8f/1f/R/9L/zv/F/7n/q/+k/5z/mP+P/4P/ff9y/3D/Yv9k/2D/XP9k/1z/X/9c/2H/XP9x/2X/d/9u/3T/av9u/2z/b/9v/2z/bP9l/2T/Zv9e/2H/X/9b/2j/Xf9Z/1b/Tf9L/03/Sv9G/0H/P/9B/03/U/9e/2f/av96/4D/if+I/5L/mP+a/6P/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9//4//X/7////+n/8P/q/+v/5//q/+b/7v/q/+7/9v/x/wAA/P8IABEAFAAkACAAIwAgACIAJgAhACcAIAAdACUAIgAdACIAEAAaABQAHwAkACgAMQA5AD0ARQBEAEUAQQBAAD8AOwA0ADAAIgAkABAACwD///f/9f/l/+v/7v/r//H/6//r//L/+//+/wcACQARABEAGwAMAAsA+f/y//j/6v/y/+z/4v/v/+D/7v/l/+P/5//s//P/+v/7/wEAAQAEAAoAFAAUACUAMgBBAFAAUQBgAF0AYwBWAFoASgBLADsAMgAmACEAEAASAAkA/P/+/////f/8/wcAAgASAAAACwADAAQACQD8//z/9f/5//T//v8FAAUAGgAaACgAMwBAAEcAUABaAGEAawBwAHcAcwB/AHgAfwB4AG8AcABtAHEAbgB/AHcAfwB9AHQAeQB4AIMAlgChALEAuADAAMAAxgDGAMQAuAC4AKYApACPAIUAdwBqAFoATgBIADYAOAAoAC8AJgAnACwALQA1ADQAPwA4ADYAMgAmACUAEgANAAEABQD3//r/5f/q/9v/2P/W/87/1//X/9r/4P/e/+T/4//j/+H/6//t//H/7//9/wIAAwAEAAYACgAMACIAGgApABcAJgAnACYAIwAiABgAEAD8//X/3v/W/77/sP+j/5f/j/+A/3D/gf9z/4T/i/+L/5T/mP+m/67/vv++/9D/2P/Z/8r/0/+//8j/wv/I/8//0//c/9f/4v/d/+P/4v/s/+r/+P8DABcAJgBBADgAUABEAEwATwBGAEcAPAA6ADYAMABHAD0ATgBAAFAASgBOAFsAVABdAGAAUABSAD0AOwAwACEAFQANAP///v/2//r/9f/0//P/+P/w//L/9f/u//P/8f/8//X/AAACAPz/AwD//wcABgAWAAEAEgAEAA4ADgAPAAYABQABAAQABAABAAoABgALABYAEwAqAC8ANgBBAEMASQBOAFUAWQBmAGUAaQBrAHQAegB7AHwAdAB3AGAAYQBJAD8ANAAlACAACQACAPz/9//1/+v/8P/y//T//P8NABAAHwAhADAANwA+AD8ASAA7AEUAQAA/AD8ANQA9ADgAOgBGADoARwBBAE4ARwBLAEEARAA/AE8ATABZAFsAYABcAFYAVwBZAFoAZwBhAGEAZABSAFMATwBAADoANQAnACQAEwD6/+7/2v/Q/8z/vP+y/6n/n/+h/5j/ov+Y/5T/lf+Q/5X/jP+M/5P/nP+p/6r/s/+6/77/2P/X/+7/6//1/+z/9f/0//L/+f8AAAEAEgALABkAFwAYABAAFAAWACEAHwAvADAANwA2ADUANQA2ADsAPgA/AEYARABMAEwAQQBKADUASAAzADgAJQAsAB0AGQAUAAsADwALAAgACwAQAAwACwAFAP//CwAFABMAFQASACgAJAA4AC0AMwAiACIAHQAhACoAJwAuACMAIwAnABoAIAAcAB0AIAAkACQAKAAkACUANAAqADYANABEAEgATABKAEkATABFAFIAUABZAF0AVABiAFAAXgBPAE8ASgA+ADcAKwAbAAwA8//u/9j/1v/U/9D/zv/M/8//yf/L/7z/tf+w/6n/o/+h/6T/rP+h/53/n/+Z/5T/k/+N/5H/if+D/5f/iP+Z/5z/ov+l/6v/o/+q/7P/r/+2/7L/yP/A/83/wv/N/8//zP/O/8z/0f/H/9P/wv/O/8H/xv/B/7//wf+//7v/vP++/8L/zP/K/83/2f/b/+X/3//s/+j/8f/0/wQAAgARAAoAHAAMABUABgAKAAQABwACAAQABwAJABMADwAgACUAIAAmACUAKAAjACwAMgAxAEQAQgBOAFEAWQBlAGoAcQBwAH4AfwCNAIYAjgCCAIYAcgByAGgAWgBYAEsATQBCAD0ANgA3ACUAIQAcAA0AEwALAAkABQD6//P/5v/h/8z/z//D/8X/uf++/7b/v/+8/8L/wP/I/8z/4f/j/wMA+f8OAAYAEAASAA4AFQAYABwAIwAgABsAFgAIAAUAAwAFAP//DAAAABsAFQAwADAARABQAGAAbwB1AIMAewB7AHcAbwBoAF4AUQBCADUAKwAeABUACgADAPT/7P/i/93/1P/V/9j/2v/f/+f/7v/1/+/////t/wAA5//y/+D/6//b/9n/zv/T/8z/xv/P/9P/2//r//T/AgACAAgABAAJAAwACQAEAAQABgAKABEAFAARABAAEQATABUADAAYABIAGwAVAA8ADgAQAAgACQD///f/9f/v/+X/4v/U/+D/2P/j/9//6//l/+L/1//b/9b/0P/I/8j/vf/D/7n/t/+6/7L/sv+p/7D/rP+v/7n/vP/A/73/v/+3/7b/vf+9/8T/x//W/87/5f/l//P/+/8IAAgAFQAUABoAGgAdABwAMAA0AD0AQgA9AEQAOgBGADgAUQBLAE8AOwBFADgANwAsACgAFAAJAPX/9P/o/9z/3v/P/9D/xv/E/87/0f/c/9v/5P/k/+7/8P/2//n/BgAJAA4AEAATABIAHgAcAB0AKAAlADIAPQA9ADwAQABEAEUAQwBGAEUATwBRAFEAWQBQAFcAVQBPAFkAUgBcAEkAUgBHAEcAPgAvADEAEAANAAAA+P/w/9n/0f/A/7n/rf+i/5r/k/+M/43/jv+U/4z/lf+Z/6L/pv+r/67/tf+y/7T/s/+w/7T/rv+0/7n/uf+//8L/xP/G/87/yP/b/9z/9P/1//7/BAAOABYAGQAgADgAOQBCAEIAQwBJAEoASQBPAEsATwBOAEEAQAA3ACsAJgAbACIAGAAYABsAFQAUABQACQADAAAAAAAAAP/////5/+3/6P/T/9j/yf/R/87/zv/Q/9P/1//d/+r/8P/0//7/9/8CAPf/AQD+//7/AwAAAAsABwAKAAwAAgAOAA0AHwAOAC0AKwBBAEUAZQBoAHwAdgCNAIYAkgCJAIUAdQBmAF4AXQBHAEwAPQAzAC4AGAADAPv/4v/m/9n/zf/M/8D/w//A/7f/tP+x/8D/yP/M/9f/2f/a/+P/3P/Y/9H/wP/C/6f/qv+Z/5z/lf+T/5L/pv+m/7v/yf/d/+r/+v8EABgAJwA3AFAAVgBqAHUAhACJAI0AmgCnAKsAtwC8AMYAvACsAKwAlgCOAIIAgwBsAGYAUwBLADEALQAZABIACAD///r/8//8/+7/7//e/9z/0f/V/8r/zv/H/8j/xv/I/8D/uv+2/7r/vv+//77/t/+v/6//p/+g/6z/qv+4/7T/vP+3/8T/wP/K/8r/1f/a/+j/5f/6//r/AgD//wkACwATABcADQAPAAQADAACAAsA+v/7/+v/7//l/+D/1//S/83/xP/C/7v/vf+1/7v/tP+2/6v/qv+h/6X/n/+w/63/tv+8/8T/wv/T/9b/4P/l/+b/8v/w//7/9//3////9v////z/+P/7//v/AAD//xAACwAkAB0AKwAoADQANQBCAEAARABLAEAAPQA2AC8AKwAnABwAIAAUACEACwAPAPf/+//m/+7/2f/k/9r/5//l/+7//P/9/w0AEgARABUAEgAgAB0AJwAjACgAKgAnACIAHAAWAAsAAQACAP//+//1//3/9v/6//H/9P/q/+v/5v/k/97/3P/n//P/9P/7////CwAIABMAHAAjADMAKAA7ACsANgArADcAIQAjABMAGQAIABIABQAEAP7/8f/0/+D/6//a/+f/3//j/+L/5f/n//b/+P/7//f/9v/6//T/+P/t/+7/3//d/9f/z//W/8z/0f/I/8n/yv/Y/9X/3f/d/9//2v/a/+L/6f/v//b/+v/7/wEA9P/4//j/+P8FAPv////0//f/7//3/+//8P/0/+T/6v/V/9r/yf/W/83/zv/T/9H/2//X/9n/2//f/+z/6P/t/+T/3v/d/9X/3v/b/9r/6//o//X/4//0/9v/6P/d/9b/zv/G/8D/xv+5/8H/t/+4/7z/uv/E/87/3v/i//X/8f8EAAUAAwAEAP//+f/5//L/9v/u/+v/5P/k/9r/2f/V/9j/yf/S/8T/w/+8/7v/uf+v/63/nv+l/5z/mv+Y/5D/lv+V/5j/lf+m/6T/sf+y/7f/t/+//7X/wP+3/7//t/++/7v/tP+v/7H/sP+x/6z/q/+y/7X/vv/H/9T/5P/s/wAABgAaACEALAAiACEAIgAjAC0AJAAnACgAKAAqAC8AIwAjAB8AKAAqADsAOgBJAEgAUwBTAE0AUABQAEEASwA4AEQAMQA7AC8ALQAaABMACAD+//X/7//m/9//1//T/83/0v/G/8//0v/Z/9X/3v/T/+L/3//o//T/6f/0/+//9P/5/wYAEQAXAB8ALgAwADUAOgBCAEQAQQBDADQAOgArADwAKwAxABUAEwAAAAIA6//p/97/5v/j/+b/1P/d/8b/0P/G/8j/zf/M/8z/1f/Q/8z/xv/J/8T/zf/M/8//1P/L/8z/wf/K/87/1v/b/+P/7P/3//n/9//5//f//v8OAA0AGwAUACAAJAAqACsALgAzADsAQQBNAEoATABJAEcATgBBAEUAQQA6AD0ALQAwACkAHgAcABwAHAAdABEAFQALAAQACwAEAAcABAD+////AwAFAA0ADAATABcADwAMAAMAAwD6//r/8v/w//L/8f/u/+3/5v/y//7/AwAQAA8AGAAaAB0AKAAnACoANQAyAEwASABcAE8AVwBIAFEARAA/AEcAOwBFADEAPgAsAC4AKQAZACcAFgAtACoAKgArACgALQAqACoA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of6p/pn+m/6i/tX+Ff9F/zD/Iv8e/wz/zf5p/gD+7/0W/mz+kP6f/l7+Hv7l/df92v3h/ev9Nf50/tb+Kv9b/17/Nv8B/7v+cP5s/p7+/f5F/4v/1/8jACwAFgDC/3H/Gf/v/vn+AP8J/yz/bv/H/9T/uP+w/7z/AwBGAHUAfACdAJ0AhQBGAB4A4v+t/3f/a/+b/9b/9/8kADYAZQB2AHsAjwDEAPAA9ADGAL0AxADMAJcAaQBWAJMAvADuADQBfgGaAXABGwHsAN0AxQCoAKEAsgDaAAQBFgEBAboAdgA4ACEAHQAdABgANABdAE4APgAuAF0AVQBfAHIApwDsADgBXgFlAXoBjgFzAUwBJgEHAfsA6QDwAAkBIAEoAQsB3gC1AI4AYAA+AEUAawCPAMcAyADAAKMAjgB5AIoAogCxANcAAgE2AVsBRAEVAdkAkABXAE0AXgCAAJoAvADhAPoAGgEVAfwAxQCGAE8AHAAuAFoAjgCjAL4AzwDSAL0AvgDIAOMA9QAKARIBIQEPARQB8wDtAN4AwACfAIcApACvALYArgChALIAoQCqALQAyADJAK4AowCVAJUAowCIAKcApgC3AMMA3wAbATsBMQErATgBMwEsARMBBwHxAAAB/AATAScBIgEbAfIA6gDgAN0A1wC5AKkArACfAJEAigCTAMoA6wD6ABcBSgFlAWgBUAEtAQUB5QC9ALEAxQDWAOQA9gDwAOEAtgCRAIkAdgBwAHEAhgC9ANwA5wDpANcAtgCNAH8AhACtANQAAgErAUUBTgE9ASMBDgH3AN4AuQC0AL4A1wDUAM8A0wDeAOkA5gD7AAABEwEbAUQBZwGQAZ8BggFkAVsBVwFNAVkBXwFrAWcBcwF5AZoBmQGmAacBogGXAXwBbAF9AYABhgFcAUsBOAE8AUQBTgFAAUUBJQERARQBFwEjARoBRAFKAVEBRwFCATkBSAFEAUcBTQFeAWUBYgFfAWUBdQGBAYMBdwF+AYQBfwF2AWcBZAFVAVUBWQFsAYwBmQGoAacBqwGXAZMBlgGvAcABxAHBAcAB0gHEAcgBtQGoAY4BewFqAWIBbAFlAWsBdwGQAZEBhQF7AV4BZAFCAToBJAE4AVIBYQF5AYMBlgGBAXEBUgFRAV0BbgF+AX0BhwGJAY8BhAF7AXUBXAFUAUMBRQFRAVEBTQFOAVgBawFfAXABWwFtAVsBeAGFAZMBmgGAAWoBVQFXAVIBRQFHAUcBRQE/ATwBSAFJAUABLAEfASQBMAE6ATQBKgE0AS0BLAEvAS0BNQEsATQBPwFBAVIBNwEvASMBJwEyATABQwFAATYBHgEXAQUBDAH3APAA8wD6ABgBFQEhARwBHwEWAQ4BDAENARQBEQEKARIBCQH3ANgAoQCsALEAsAC+ALgAygDJANkA6wD0APkA6wDoAOkA9QDzAPoA/wDuAO0A3wDfAOkA6wDoAOUA3gDmAO0A7QDZALwApQCgAKgAuwDKANAAzQDMALoAugCyALMAqgC0ALEArQCnALQAuQCwALUApACkAKcApACdAJoAkACgAK8AvADHAMIAywDHAMMAtgCxALYAwgDNAOIA7gDuAOsA2wDOAMIArgCsALMAtgDEANAA0QDQALgAqQChAI4AlQB+AIEAdAB6AHMAfQB8AIgApACoAK0AswCuALMApACyALQAvQDGAMgAyQDHAMIAtgC1ALEAvgC/ANIA1ADmANwA3gDUANYAywDBALMAtQC+AMsAywDOAM0AxwDBAKIAkAB4AGIAQwAwABwAIgAZAAoAFAAIAAkA/v8BAP7///8EAAkACwAUAA8AFgAPABsAGQARAA8ADwAMABYACgAQAAUACgD+//v/8//o/9z/5//p/+3/8P/t//H/7P/m/9//3P/Q/9T/3//p/+//9v/9//3//f8MAAgAGAANACkALQBQAGIAbgCDAHUAdABfAGEATQBMAEsASgA8ACIAJgAVACQALAApADIAJgAsACMAKQAmACcAIwAfACQAIwAdAB8AGQAaABEAAQAEAPP/7v/y/+v/4P/f/8v/xP+p/6f/qv+0/7v/vf/S/9H/7v/b/+P/2P/h/9r/7v/k//X/6v/v/+b/3f/n/9j/1//S/9n/3//m/+H/3v/b/83/0//H/8D/xv/F/87/0P/d//H//f8WAA4AGgAOABQADwAOAAMABwD2/wMA+f/z//n/5v/l/+P/4//l/+j/5v/l/+H/4f/X/9H/z//K/9D/0//X/9z/1v/g/9r/5v/g/+f/8P/w//X/+f/0/+7/6//i/+n/5f/l/+z/6//w/+3/4v/d/9//3f/V/8n/xf/B/8T/vv/C/8H/vf+z/7X/rP+v/6r/tf+2/7r/tP++/7n/v/+2/7r/rv/C/7H/yf/C/73/wP+1/7T/rf+d/5X/h/+B/33/cf9x/2T/YP9a/0//V/9S/1r/Z/9z/4v/j/+W/5f/nP+j/6P/of+g/5f/kP+H/4n/ff+M/4r/h/+C/3v/c/90/2//f/97/43/iv+Q/5D/hv9+/2b/aP9S/1X/U/9R/1P/Vv9R/1L/Sv9S/1P/XP9k/2//dP+C/4z/lv+a/5j/nf+f/6H/oP+d/6n/pf+t/6f/pf+X/5v/kv+Z/5n/o/+q/8P/w//O/8j/yv/E/8v/0f/P/83/xv+8/7f/tv+2/7L/sv+r/6v/rP+m/6n/qf+u/67/qv+0/6n/rP+l/6P/qv+Z/5j/h/+F/3b/cP9n/2H/Wv9Q/1X/SP9C/0f/O/9G/0D/TP9L/0z/TP87/0T/Mf86/zP/PP86/zj/PP87/0D/Qf9K/1P/YP9o/2j/e/+D/53/of+n/63/vP+4/7r/uv+k/6b/gf9+/2r/ZP9X/1v/Uv9Z/1b/YP9g/27/hf+N/7H/tf/J/83/1f/h/+P/7f/1//b/+v///wMABQD+//r/AgDz/+7/5P/d/9r/0//Q/8//1P/S/9D/xP/M/8H/w//B/8f/w/+7/7v/tv+1/6z/nf+X/33/fv90/3P/f/99/4j/iP+R/5v/pP+k/6b/rf+z/77/yf/I/9f/2f/W/9b/1f/E/83/vf/L/83/0P/T/9T/0f/L/8X/zf/G/8T/vP/D/8f/u/+z/7D/nv+o/5X/lf+X/4//kf+B/4D/e/9y/3n/bv97/2r/aP9g/2P/Wf9S/0D/NP8u/xX/IP8P/xf/EP8c/x3/I/8k/yP/JP8X/xv/E/8a/xv/Lv8v/zz/R/9Q/17/a/+A/3v/lP+N/6P/qv+v/8b/w//P/87/0f/T/8//yP/J/7v/wv+1/7f/rv+m/5P/jf99/3f/dv9r/2P/W/9P/0T/Of8t/yr/Jf8j/yX/Jv8d/y3/Iv8v/yr/QP85/z3/S/9K/1H/U/9W/1b/Xf9b/17/WP9T/07/Sf9H/0z/Rf9M/0r/TP9Q/1b/WP9c/2n/av95/23/c/90/3T/fv+D/4X/jP+L/4j/hv+H/4b/fP93/27/af9j/1//Y/9k/2z/cv96/3z/jf+L/5H/m/+d/7L/rP+7/67/uv+t/7f/qf+m/53/n/+g/6n/qf+y/7T/t/+7/77/v//M/9T/1//g/+n/9P/2//f//P/2//b/7f/v/+7/6v/3/+r/8f/n/+P/3v/W/8z/yf/E/8X/zv/O/8//1//a/+P/5v/g/9z/1v/I/8v/uP+7/6j/m/+N/4D/bf9h/1f/Tv9H/0H/Mv8v/yX/KP8j/x7/Hf8a/xv/Ev8k/x3/Hv8j/yL/Kf8l/yn/Hv8l/xr/Fv8N/w3/Av8O/wX/C/8J/wf/Bv8F/wL/CP8N/yP/IP80/y3/O/87/0X/SP9K/0H/Rv9A/zr/Pv87/z7/Q/9F/0f/Vf9a/1f/Uf9b/1T/Wv9X/1//Zf9m/2X/cf9s/2//cP9y/33/kf+g/7v/zP/X/+j/6//7//f/BgD7/wQA+v8CAP3/BQD3/+3/3//h/9X/0v/V/9T/2f/S/9z/2P/h/+P/5//s/+r/6P/i/+P/4v/k//D/5//n/9j/0P/H/7r/tv+r/6L/of+U/53/if+M/37/ev9v/2P/V/9H/0T/NP8x/zv/Qf9L/1b/Yf9l/2v/d/98/4X/jv+S/5r/mP+S/4z/hf+H/4D/jf+I/4v/jP+H/4b/i/+P/57/p/+w/77/yf/I/8D/w//E/8v/yf/F/8P/sf+t/6j/mv+g/43/kf+D/4b/ef92/3X/f/+B/4n/gf+J/3b/hP+G/4X/jP+T/4//hv+S/4r/i/+Q/5b/p/+t/7b/v//O/8T/1f/I/9H/y//K/8P/wf+7/7T/uf+i/6f/mP+Y/5T/j/+O/4n/j/+U/5P/oP+g/6v/o/+u/6//rv+o/5P/mv+K/5X/h/+W/4j/l/+Y/5v/mP+u/7j/tP/O/9L/4P/b/+L/1v/R/9L/0v/S/7f/qf+H/4T/dP+8/9v/LwCdAOYACAEgARUBygBwAO//YP+P/s39EP1o/OH7ofu3+/r7i/xX/S7+SP9BAFEBOwIRA7UDUQSqBP0EJgU5BTkFCAXkBHcEEgRYA6oCzgH1ABIAQf97/vL9nf19/cb9N/71/uj/3gDSAb0CkQM6BN4EVwWvBekF/AX6BdcFkwU7BbgEFwQ/A2MCTAEvAPr+0v24/KT73vqV+nX60PrN+8/8Ev6Q/88A/QEVAwgE4QSQBS8GqQb4BhkHLgc5BxYHPQcQB+kGmQYsBnwFqQTCA88CzQHSAOL//P4O/jT9c/yj+yn7rvpW+jv6HPpO+pj6P/se/EH95P5gAP0BoAPpBO8F2AZZB74H9AciCCEI/gfvB6YHPAfzBl4G3AUzBZYE8gMZA0YCWgFTAD//If64/Ez72Pl8+GX3lPev98v4Evu7/P3+TgEyA9sEYQaWB3AI2gg+CTYJ6AioCCkIcwfJBt8F/QT9AwgDOgJTAZ0A+/9F/6f+Fv5m/d78Xvzw+4b7OfvS+oH6RvoI+vL58/kR+kb6qPoU+6D7MvzO/Hf9Hf6x/j7/uv8rAIgAzwAXATgBXQF0AX8BYgFWATAB9QDBAHsAIgDK/2D//v5u/uL9bP3A/GD86fts+0/7Ifsz+4v78fud/HL9bv6V/7wA+AEoAx8EAgWqBfgFLQYKBrAFLgV4BKIDvQK1Aa4An/9//mf9V/wa+/X5t/hu9//1l/Qn8+rxJPGy8SryGfQg92r5Y/xb/58BogOjBRkHJgi4CEMJ/QifCEMIfQetBgIGDgUcBBUDIAL7APX/zv7D/Xr8XftO+mv5vvgv+M33l/dT92L3dffQ9zP41PiI+Tv6D/sb/AL9J/5P/1kAXgFYAh4DyANzBN4EMgVgBWYFTgUnBegElwQ2BNADVQPnAoACEAKZASUBugA1AK3/9P5q/nf9nvya+1L6Jfn998f3Ifj3+FT74P0aABwDbwUwB+kIQArzCmcLcgszC3EK3Qk5CWcIpAf9BgcG5wTPA4oC+QCj/zr+uvxl+xP66fjN9yL3kPYP9tr1mfVu9WP1T/Vv9bf17PV99kD3YPer+FL50vlK+2v8bP1D/4wAwgHuAtsDgATkBF0FhwVzBWIFEAVaBMUDBAM7ApEB3gA1AHn/0P4A/jH9SPw6+zD6w/g+93H1zPNW8tjxzvFs84L1PPjq+wP/+gE2BWwHaQkcCx8MsQzeDLUMRAyEC8cK6Qm1CLEHYgYFBXwD8wE5AKT+A/1r+xz67/gU+Kv3bvdN93n3bvd29473gPef97j36PfW9+T3uvdr95H3iffy+BL6lvs2/tv/JQEJA/EDiQSSBQQGLgZiBkMG0wWABQYFnQQqBL4DdgMWA6oCUgKhAf8ALgAw/yz+Nf0Y/CT7MvpO+XH4qPd49jn1tvNg8mjxX/E18gH0lfaz+dD8nv9VAnEERga4B9AIVQmaCYoJMAmcCAUIOQd1Bo4FcQSPA0oCLAEwAA7/3v3V/Mn7vPr6+Yr5GPnn+MP4hfg7+AL4w/ed94H3s/e797X3//fD95f30fev92P4DPry+vT8YP+YAEQC+AO/BLIFmAYCB3wHVQdJB/oGNwa3BUIFgQT5A50D4AJFAqEBzAD2/wX/Gf4A/eH79Pr0+RH5VviV99j28vXT9M7zmfL88c3ypfMf9h/5HvwN/9MB/APGBR0HGgjcCAAJEgnyCI0IMgjpB4gHNQelBuwFRwUZBPsCzAFtAPr+nv0+/Av7Gvpp+QT5xPiT+Gj4Ovjr99X34ffS9+r3Kvj599P3l/dF9/L2BPdx+IL5g/s7/lUAOgLqAyAFEwa0BjoHwgfAB5wHhAcIB38GMwa3BWUFBwWdBCYEpgPIAvwBBgHF/7H+m/2m/N37X/ve+pb6PvoP+sT5ovlo+Q/5TPhg90T2TfVx9XX25/dX+g39h/93AVcD/QQ1BnUHhgh2CcUJEAoTCqgJPAmnCNgHGwclBlIFYgRiA08CIQHN/4X+Nv31+wL7QvrS+bX5rPma+ZD5YPka+Qj5BPn++Nv4u/gS+Gb3yvbc9gD4KPlG+w7+JgATAtYDSgV6BkQHGAjOCN0IswilCPwHgQcLB5wGVAYNBo0FRwWzBOsD8AL2AbcAcP8//kj9g/zV+337OfsJ+6L6bfos+gb66vnP+Zn5+fjd99f2yfVW9dL1xPaE+Lb6/fwu/yUBVQNLBeEGOQhuCf4JQwoyCtIJTgm7COMHLwdyBs8FCgVDBHsDrAKyAbAAyf/+/l/+4v2l/Wj9OP3e/Gv8AfyW+yb73Pqs+oL6avoB+nj50vjj9x/3u/aq9iT3CPho+U/7RP1F/9AB6gP+BesHRAkvChoLcwt/C4gLNwu0CiAKTAlwCJUHkQavBZoEhgNuAk8BNABC/0r+b/27/Or7H/tt+qr5/Pia+Fb4QvhN+Fn4MPjg93r38var9uH2c/eJ+Dr6V/zG/gYBegO9BY4HEwk+CtMKRQt9C1UL+wq+CioKgwnYCAYIGwdMBmsFjASjA6cClgGsAKP/vP4Y/lr9uPwh/Hz79vp8+vH5wPmN+Wb5WvlI+QH5u/hi+Lb3Sfdn97P3j/jH+Zf79f0eAFECrwSnBj4IqAllCvAKegt8C1gLHgvpCn0KBQqLCQYJWAihB+IG8QXsBPQD2AK+AaoAm/+S/pz9sPzU+xn7lfoG+pH5Mfn1+LL4cPgf+MD3gfcs9/n2Evck96z3vfgE+tT7Kf5BAJQC/wSuBhkIgwlBCt8KTQtkC04LSwsVC7UKSQrKCS8Jbgh9B2cGQAUKBLQCdAFYAD//X/5+/bv8APxh+9f6Qfqv+UT57fiB+Dj46/eq95H3gfeI97T34fdJ+Bn5LfrW+9r92v/UAdIDnwXrBh4INgkXCroK/AoDCy4LHgu+Cm8KRArqCVYJmQi4B8gGnQVFBNYCkAFEAOf+pv2f/Mr7tPqr+dT4G/hy96/2P/Yf9uX11fU59r/2RPcb+Ef5kvpX/OT+dwGTA6IFjgfhCKoJQwr+CmcLrQt+C0ELuwotCncJvAiECDEI9gd4B+EGDgbZBHwD8gGxAFz/L/4t/Uf8kPu0+uP5Efk++Cr3gfY59tL1ffXX9QD2H/b79rn3Y/ls+zn+uAF8BBoHIAmSCtgLjQxBDdwNQQ7sDVoNXgwgC8AJiwhyB6cG3AXtBAIE+gKbARkAx/6G/TP8+vr7+SP5gPja91b3CfcN9yv3Cvd69yz4zvhZ+Vr6cfuq/Bz+FgBVAskE/QadCOkJLQuyC64L2QvqC5IL1wrjCbEIfwcuBuIE2wM1A4oC5gEuAXYAef9z/lj9Ovxa+9r6L/pv+Uj5DvmG+Pj3G/gu+G34E/nM+Tz6LvvW/Eb+AwArAr8E4gZ9CNYJlwo+C7sLxQvJC/ALewtKChUJswc7Bt4EnANsAn4BkABO/yL+8vzZ+4v6yfkU+ZL4dvhg+ED4LfhJ+EL4ZvjU+Fn5S/qz+xz9av71/ygCRwRQBjEIuQnDCjcLKAv0CuwKogrsCRkJGAjxBm8FFATgAu8B6wDY/9v+2f2T/Bj7o/mg+N33TfcI91T31fft99z3S/jp+Gn5bvqg+7r8yv0J/1MA8wHdA8AFXQetCOoJcAq1CtoKvQpsCsUJ6giZB14GNAXiA5cCZgGnAOH/+f7x/RP9H/wc+7n5m/jX91L3u/Za9if2/PXV9SL29fba97z4Afpj+xr9yf6SAKsCBQV7B1IJhwpXC8ML6QvQC9ALhwszC1UKIwmvBzAGyQR9Az4CFwH6//X+lf07/Pb6sflS+Fj3xfaC9nv2UvYk9i72efa+9gH3sffa+Gv6yvtn/Vv/1gFrBNMGwQieCt4LkwzsDO8M6wzMDBwMGAuyCSsIoAY0BeQDqQKoAYoAZf9E/hX9qftq+nP5lfjL91L3Tvcj97D2TfZR9pf29Pab9174Zfm2+iX85f0HAGwCwQTYBucIcwpxCyEMvgzwDPIMpwzzC+QKkgkiCKgGXAUhBO0CwwGSAFz/Bv6w/Gj7OvoQ+Tf4p/dK9/L2c/Yn9vf1IvaJ9iH36vf3+AP6S/sQ/XD/vQHmAxsGOgi0CbcKaQveC1oMlwxtDM0L8grECXwIMAfhBa8EegN1AkkB//+f/kX99vvX+pr5n/jR92P39/aU9iD2AvY69oT24PZq9zv4Q/mJ+hr85v0UAJAC/wQtB7AIngk4ChALcwu1C7YLbAuQCl8J9AeJBmkFcwRtA08CUAFQAB7/2/2V/Gj7Tvpi+bL4KPis9yn3tfZQ9jf2VPbq9mH3+Pe8+ED6yvtw/YD/TQLfBJ4Gugd7CFwJWQoqC1QLEgufCvoJ/wjLB6wGlgXQBBYELAP/AeoAvv+M/mX9i/yu++D6D/pR+dL4dfhG+OD3V/cR9w33Nvdw9/r3pPiC+bH65/wGAAgDDQUXBuYG1AeZCJIJlgozC2cLEAs/Cg8JBQgaB1wG5wV4BecE5APMAooBTwBI/2b+vf0l/c38YfzS+yP7cvrX+XL5Nvnc+FH4tvdC9+D2u/Yb99L3sPjV+en72f5MAcoClQMvBA0FOga8B7YIIgkTCb0IJQh+BwsHgAY2Bg8G2AVPBZoEnwN1ApoBywAmAJ7/Ev+Z/hn+0f2b/Vb9N/0d/cj8N/yt+wr7d/oL+qL5Rvng+ID42ffc9kf2CfcZ+WD7WP21/q//ZwAVAVkCvQP2BDsGBAc4BwoHuwY+BkgGsAYJBxMHwAY2BpUF1wT9A+wC5AHzAE4Aw/9G/+X+nv4y/u/9o/1l/SD9y/xo/Aj8kvtk+0z7Ovv9+pv6HPrJ+dH5Q/rm+r37g/x1/Wb+Yf9ZAE4BFQLpAosDEARZBH0EhwSsBJ8EbAQ0BO0DhgMvA+sCmAIAAl4BcgCH/7L+3f0K/Ub8afuw+hL6nPkk+Zf4HPiH9yn3LffY9wv5zPoZ/YL/fQG7Ap4DgQSfBQIHWwgnCVMJ7AgCCAQHMAaMBQgFcATTAzIDdgKPAY4APf/v/dX8IvzO+4n7gvt2+2f7Zftn+077SPsr++X6q/qR+nX6b/pQ+kL6Dvrt+fv5Y/rc+sD7CP2C/v//WwFiAvkCcAPWA6AEjAU+BosGZgb4BbUFigVtBVoFMQXkBJYESQTSAzYDkwKwAeMAFgBj/6z+9/1N/az8I/y4+1j7zvpE+m75uPi8+Lr5Wvto/Wn/IwFFAt8CGQQLBXcGEwgfCYIJdwn1CDgIuwc0B7kGIgaBBa0EygPNArgBhABr/4X+zv1Q/fH8cvwH/Mf7nfup+7X7sfuD+0X7A/ve+u/6CPtN+2L7qPvs+1D8vPxV/aT9D/5+/un+bP8PAJAAEQGwAfQBFgJBAj8CWwKFApwCpQKoAr0CswJzAisCqgE6AeoAygC2AJoAUwD4/5L/M//D/mX+7/2D/Vb9Q/0U/cX8avzn+7n7CPz8/IH+IABnAV8CDQONA24EXgVqBj8HsweUBx0HeAbJBTEFtgQzBIcDvgKfAWEAMv8R/tn80fvW+uv5Lvmp+CX4vfd+91L3S/dO9yT3x/Z09pP2lvdX+cr7cf60ANwBngJNA/0DawUJBykIkAg8CB0HBwZEBRIF/wT2BJ4E2gPoAiACaQHTADgAa/92/or98Px//GH8cvx+/G78ZPxU/Gz8jPzN/Az9Tf2r/Tj+uv4//+T/dQDaAFwB1QFTAscCMAOdA80D4QPFA6cDggN3A5kDoQOVA4gDUAMGA74CjAI6AgsCtQFFAdAAYADo/4X/Ev+3/mz+E/7l/ej98P01/o3+A/+M/x8A9gC2AYMCbQMmBMcEXgXeBTUGeAacBncGMwbUBTwFsgQWBIQDugLzASYBUACO/+T+NP5d/Yf8tfvu+lz63flx+SD58/i6+Jf4fPik+N/4nPnP+mz8JP7j/1wBcgJRAzUE8ATcBc8GaQesB3wH7AY1BmAF0gRJBMsDOgNiAl4BMgD3/tv94vzg+xr7TPqL+R35tPh6+G/4MPjo99X38/e0+Aj6wfv4/SIAwAEOAyEEJwVyBgAIkgmxCnoLdwv3CkcKvQlRCRYJ9QiZCOAH2AajBUwEDwMAAgkBLgCg//f+hv4P/pv9Q/36/Oz8+PwV/Tr9df2c/dH9Mv6k/k7/5/+FAAwBogFAAvMClgMSBHAEkwSXBJMEiAR9BHUEWAQlBLsDUAPcAm4CMAIAAsYBcwEWAWgAuf8G/23+1v1y/SH9yvx8/EH8GvwJ/Cb8XvzO/D391P2O/jH/AwDPAI0BRQLzAnMD6gMfBEQELwQLBPQDoQNGA7wCDQJCAW0Aqf/1/kP+oP3e/P77J/tE+oP52Ph5+DT4EPjU9873wfft90v41/it+Y76sPvq/BT+P/9iAEMBJgLxApkDIASMBNsE9gT6BLwEhwQNBJIDBANiAp4B/QA0AGD/kv6s/bv8+vtA+7/6Tvof+vT50fnF+cz5/vlF+rv6UPsJ/Az9Gf4a/ygAPwEmAgwDDwTvBL4FdQbiBgcHBwerBkYGyQU/BasEAQRjA6QC3QENAToAb/+p/uz9S/23/Cn8mvsi+8X6dvpC+jn6WfqW+gv7dPsZ/NX8sv23/tD/5ADqAdcC2QOiBFEF7QVZBooGhgZbBvMFkQUqBYwEBwR8A8ICGAJyAa0A+/8t/3T+tf0D/Vj8t/sg+6X6QPoM+u/55Pn3+Tz6dvrW+lz78PuY/H39Yf4H//v/wQBlAToC0wJsA+EDRgRoBGkEXwRFBAcEugNjA+ACZALKASkBggD6/2r/6/54/uj9Yf3Z/HH8D/zj+737tvur+7r70fvU+wD8Q/yU/OD8PP2j/fz9kP4W/6f/LgCiAA0BXgG7ARkCXwKeAsACsgKVAksCFgLQAaIBYwEeAdQAjwAsAN7/bv8H/5n+Hf6m/T39/vy7/In8YPxM/Db8MvxH/Gv8rvz6/En9jf3o/Tv+ff70/k7/v/8ZAHkArgDiABYBMwExAUYBRQElASYB+AC2AGYAJwDZ/37/N//a/pX+Pf4I/tj9qv2B/Wv9OP0c/Qn98/z6/An9Jv08/W39rv3V/Rb+U/56/sn+Av9R/47/yP/t/wEAEAAGABMABQD9//f/zf+y/3v/Sv8f//P+5/7S/rv+q/6g/n/+dv5o/kn+RP5K/ib+FP4N/vb9AP4X/jj+ff6g/sv+Af8k/z3/Zf9z/4z/qf+u/7//zP+0/7n/of+d/7n/lP+t/6D/kv+Q/4n/cf9o/2j/R/8t/xD/Af/7/vj+6f70/t/+vv6k/qv+xP7W/un+DP8g/xj/F/8X/xv/Jf8z/z7/Xf9z/4X/if+S/5v/o/+n/7b/sP+0/7n/tv+p/7H/pv+2/6j/of+q/6P/uf+p/6L/p/+K/37/cv9o/3j/av93/3v/af+A/2f/Zf9p/1T/Q/9P/0b/Sf9K/0P/R/9A/0v/Yf+K/5D/sv+z/6j/kv+Q/3b/fP9//1v/af9f/0f/Tv8y/0P/Qv9S/2f/ev+J/63/qP+5/7n/vv+1/8H/sf/M/9v/7v/7/wMA8//9/wQA//8JAAoA/P8NAAIADQAuAC4ATwA7AFEAWQBbAGgAWQBbAEYAQgAsACUAGQAgACMAHQAqADEAKABDADkAMwA0ACsAKAAZAAwA+//0//X/7v/r//D/8v/3/wcAGAAjADsATABTAFEAWABZAGkAZAB1AHMAfQB0AHcAggBlAH0AcABpAHIAZwB5AHUAhABxAFsAUgAuABgAOAAuAEcAQwBQAFEATQBGAEkASQBeAGIAWwBgAF0ATQBDADQAQgA0ACIALwAvADkAOQA+AD8ARABZAFEAPAA5ADUAKwAuAC4AOQA2AEkAQAA9AF0AZwCBAKcAwQDmAOwAAQH4APwA8QDnAOIA0gDdANQA1QDOAMkApwCOAGkAUAAzACIADgD///f/AADy/wgAEAAiADMARwBVAGUAZQCAAIQAjwCWAJIAkwCXAKcArACXAKEAjwByAGwATQA5ABMA+//0/9f/1P+9/7v/t//D/8v/5P/g//X/AwAKABsAMgA6AEsAUgBpAHcAgQCPAJQAoQCpAKAAswCqALkAsACgAJgAgQB7AHIAbAB+AHsAgwCLAIcAqACxAM0A2gDfAOEA3gDjANMA1gDcAN0A0wDRAMoAyADBAMAAtACtAKQAjgCCAHQAaQBjAGkAdgCDAJMAjACfAKwAqwDEANAA3gDtAPQA+AALAQ0BGgEdAScBKwEsASkBFwEOAeYA3ADSALgAtwClAJMAjgB0AH8AcgCMAI4ApQCnALEAtwDAAMUAzwDTAMwA1QDMAM0AwADMALgAnQCSAH8AbwBtAFUASwA9AD8ANQBCAD4ASgA+ADkALAATABUACgAAAPv////4//b/6P/g/9b/1f/Q/9f/5f/n//b/+f8JAAwAIgAnADUARQBRAGcAaQB4AHEAewCBAH0AfABuAH4AgACLAI0AlgCTAI4AlgCWAJcAmwClAJkAlQCHAIoAdwCDAH8AgQBtAGgAUgBCADwAOgAoACwALwAuAC4AQgBLAD8ATAAzACoAJgAtACwANQAzADUALAAjABsAEwAQAAIABAAAAPz/AQD6//3/+f/1//X/7/8CAPX/BAD5////AgACABAAFwAzAEQAWABoAG0AdgB5AHYAcwBrAGoAXwBbAE8ASQBGAEYAPAA9ADkAMgA5ADYAPABDAEQARQBFAD8AQgBGAEIATQBEAEUANQApACMAFQAWABUAEAAFAPj/7P/n/+f/5//t/+T/8f/l/+f/5v/p/+z/9/8BAPz/BgAGABMAGwAZABUAGwAXABoAHQAkACgALQAuADEAOgAuADgALQA3ADkANwBCAEAATgA5AEgAQAA0ADoANQAoADQAHwAsABYAFQAJAAsAAwAJAP7/FAACAAUA+/8FAP//+//7//j//P8CAA0ADwAiACcAKAAqACkAKwAsADEAMQAhAC4AKQAyAC8AMQAtADIAMgAwADYAJgA9AD8AUgBaAF4AaQBxAH4AegCFAHUAfwBsAFoATQA6ADAAIQATABMAAgD6/+r/1f/U/73/u/+x/7f/s//H/77/1P/Z/+7/8f8BAAoADgALAAMA9f/s/+D/2P/N/7//vP+0/7D/nP+o/6T/qv+v/7P/tf+5/8X/vv/I/8b/zv/S/+D/3P/n/+3/9f/8//b//f/2/wEAAQAPABUAHgArACcAOgA8AEQATgBPAFsAXABfAGUAWQBgAEwAUQA+ADsANAApACMAHwAUABYAGAAaABwAIAAkACEAKwAnADAAKQArACAAJAANAP//9v/w//T/8P/g/+H/3f/n/+P/6P/x//z//v8KAAMADwANABIAAwAFAPv/9f/u/9//4v/M/9j/t//M/6f/vf+h/6f/lv+c/5T/mv+S/5T/iv+M/4b/i/+H/3b/ff9s/3L/af9h/1r/T/9K/07/Q/9B/z3/N/86/z//Pv9C/0T/Rv9D/03/Uv9Z/2P/c/9t/3X/cf9v/3H/bP9y/1//YP9N/07/T/9M/1z/Uf9l/2L/cf9z/4b/jP+S/5H/o/+h/6v/p/+k/6v/nP+r/7H/uP/D/8L/y//K/8X/y//L/8z/wP/A/7P/q/+h/5X/mf+N/4j/iP92/4D/bP9z/2j/Zv9i/2b/dP91/5H/k/+m/7X/wf/a/93/6v/u//7/CgAOABwAHQArADQANwA8ADoAPgA+ADkAPgBBAD8AQQA0ACQACwD9/+3/5f/i/9T/2/+//87/t//A/7X/uf+y/7P/s/+y/7H/qf+i/5r/i/+F/4P/e/9z/3H/YP9j/1T/Wf9N/1f/T/9d/1//Zf9w/3H/ff95/4P/g/+S/4z/lv+U/5n/lv+e/5f/p/+g/6f/qP+5/7H/x//B/8r/zv/b/9f/7f/u//X/9f/0/wAA+f8FAPb/+//g/9r/xv+//7j/q/+Y/4v/g/95/3X/ev9z/4f/iv+T/5f/p/+q/6v/qv+l/6b/pP+b/6f/m/+g/43/j/+F/4b/e/9w/3P/av9s/2v/df93/37/hf+F/4r/iP+N/5P/lf+O/5//kf+Z/5D/iv+I/33/cv9u/2f/Yf9k/1n/Z/9e/2X/av9x/4D/i/+W/53/p/+s/7b/pf+7/6z/sP+v/7X/of+r/5j/nP+R/5T/hf+O/33/f/9r/2f/Vv9Q/0z/Sf9P/07/Uv9Q/0v/S/9M/0r/Vf9Y/1j/Yv9e/2f/Z/9r/2z/cP9+/4T/hv+N/5n/pP+t/67/w//S/9f/0f/X/9b/1v/Q/7r/vP+k/6P/lf+L/4b/e/9w/3b/bf9y/3v/eP+N/5D/qv+z/7v/wP/A/77/vP+n/6T/kv+T/3//h/94/3z/e/94/37/fP99/4L/hP+N/5j/oP+k/63/sf+6/8P/z//O/9H/2f/b/+T/5f/1//7/CwAaACUALQAxACoAJQAnABsAJgArACcALgAkAB4AGQAbABIAEAD6/+//6v/e/8v/x/+6/7f/rv+q/63/p/+e/5X/iv+E/3//hv+F/47/if+R/5L/hP+S/4P/jP+N/5X/kv+U/4r/kf+F/37/gf91/3v/ef99/3b/df98/3z/g/+J/5f/kv+s/6j/vv/H/9X/2v/n/+H/5//u/+z/+//1/wEA+P8AAP7/+//2/+3/5v/V/97/2P/k/+z/9P/7/wIACgAMAAwAEAAJAAkACgALAAMA+f/r/+H/0P/B/7X/n/+d/4z/kP+C/4b/h/+K/4//mf+X/57/lf+m/6L/qP+n/6v/tv+3/7//wP/D/8b/y//N/9D/zf/L/8X/wP/A/77/vP+8/7//xf/E/8//zf/U/8n/y//P/8X/vf+8/7D/wv+h/7n/of+h/5X/jv+V/4X/iP94/33/cP9y/2T/Yv9Y/1P/S/9K/zX/Ov8v/zX/MP8y/yf/Lv80/0X/Sv9Z/2v/ef98/4z/i/+O/47/mP+Y/6f/o/+s/6z/pv+k/6D/ov+a/5//mP+l/6P/lv+b/4X/gv9w/2r/af9s/2v/eP9+/4v/kv+W/6b/sP+w/7P/v/+6/87/wv/U/9f/2v/W/9D/yP+3/7T/pf+n/5j/lf+W/47/jv+H/4X/ff92/23/bv90/3f/ff+J/5j/nv+q/7f/uv/G/8L/0//M/9f/2//W/+v/5v/1//r///8JABUAGAA3AEEARgBHAFEATABLAEQAQAAxAC8AHgAXAPv/8f/l/9L/yf++/7T/sP+k/5r/iv+G/3n/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1QDCALMAuACdAJYAhgB6AGIAVQBFADQAPQA3AFcATQBZAFQAVwBkAF8AcwBrAIAAfQB2AGMASQArABYACgD6//3/BQAMABgAGwAhACsANgBLAFsAaAB3AIMAfwCAAHYAeABtAGQARAA4ACcAKgA2AEIARgBNAE0AVwBRAEkAUABJAFgAZAB3AI4ApACrAK0AoACsAKgAvwDVAPUA9wADAfQA9QDnAN8A6gDgAPEA4gDVALsArACcAJUAkACWAI4AmwCDAIMAegBrAHIAZAB0AHAAcwBzAHEAbwBlAFYAVQBLAD4AMQAuACEAMgAkACQAHQAPAA4ACwArAEMAVABkAHQAeACSAJEApQCrALQAsACyALAAsgCuALAAqwCtAKYApwCbAJIAhAB2AG8AdQCMAJ4AvwC6ALkAsQCuAKYAqwC4ALAAqgCrAKIAqgCoALQAnQCrAJYAkgB3AGMATQArAB4ADQALAP//7/8BAOf/6P/1//j/EgAoAFEAUwBxAHgAfQCNAJAAnQCfAKMApgCqAJQAjACMAJoAlACYAKcAlwCsAJoAfAB/ALUAZQBQAVMCHQK/AQgBOgBn/93+3P4s/83/QABSAFoAYwBWAIQAhgCQAIkAdABgAIYAowC0AOQABwEsATABKAEAAeAA0ADAAK4AgQByAG0AYwBfAEcAPAAqADUAIgAkACYASABzAIoArgC3ANUA6ADuAPoA9wDvAPAA6wDvAAABCgEkARwBHgEPASMBJQEUAfgA2wCZAGEAPAAkACIALgBCADkATABNAEYAUgBUAF4ATgBjAFcAQgA1AEYAUABLAGoAZQB1AGcAWQAiACMAHgAaABMABgD+//X/+v/l/+D/1//S/7//uv+w/8X/yP/D/9H/1//X/87/6P/p/+n/y/+8/7z/uf+5/6H/m/+U/53/oP+L/5j/oP+2/8b/sf++/8//x/+v/5z/kv+g/6X/vv/U/9L/zv+n/6b/s/+o/6X/nf+W/4r/gP9a/1j/UP9D/0D/NP9C/1f/UP9Z/0D/WP9U/2X/aP98/5j/mf+O/4j/jP+S/5P/lv+f/5j/jv9//4f/lf+0/83/1f/A/5n/jP9z/4L/h/+Q/4r/bP9q/2T/av9y/4z/qv+0/53/pP+u/8P/5//v/wUACADw/97/w//T/+T/AwASABUA/f/e/8T/uf+m/6T/of+r/5j/f/9f/2D/Yv9v/5D/hf+a/5v/pv+V/47/fv9x/2n/Wv9W/0j/Pv8+/0f/Q/9O/zr/Uf9h/33/lf+W/57/q/+u/7j/uP/K/9z/3//h/83/1v/Y/+D/9v/9/xYAEwAHAAsAAwAAAP//+P/3//7/8P/y/wAA/f8NAPX//P/j/+j/0P/S/8T/uP+l/5j/mv+T/43/ov+r/77/xf+z/7n/sP+y/6f/q/+x/7z/0f/H/8L/sP+u/7L/rv/B/77/xf+q/43/d/9u/2H/Zv9m/3X/hP93/23/a/9w/2j/Zf9i/17/W/9L/0z/Uv9g/2T/ZP9z/27/bP9e/13/Vf9O/0H/I/8q/wr/DP///v3++P71/vH+9f7s/vD+6/7p/vv+AP8c/xz/L/8o/yv/Jv8k/x//I/8f/xH/A//5/vv+8/7//v3+Ff8f/y3/J/8s/zD/Nv84/zv/Nv8+/z//RP9D/2X/Y/9z/2//av9j/13/Vf9c/2T/af9v/3j/ef+C/3X/ev9w/4X/jP+W/6r/p/+n/5P/h/9+/3T/e/9//4f/ev97/27/af9a/2r/Tv9l/2D/Z/9i/2D/V/9F/zb/Kf8q/yL/KP8s/y7/OP8m/0D/Pv9N/0//Sf9K/03/Rv9S/z7/UP9J/1H/Tf9M/0D/Tv9H/1D/Uv9P/1b/Vf9a/1r/Y/9e/1v/S/9A/zL/K/8b/xP/A/8B/+z+7/7s/vj+/v4F/xn/HP8y/zf/R/9L/1z/ZP9t/27/d/+B/4f/hP+H/3//f/98/3T/b/92/3j/dP+C/4H/h/+G/4T/h/+U/5f/of+m/7b/sP+7/7X/uv+v/7n/pP+s/5L/l/+J/4j/gf+A/4H/ff+L/5D/mf+l/6//wf+y/7z/q/+k/53/jf+S/4n/g/+H/4j/iP+B/3L/cv9z/2r/cf93/3f/fv98/4T/ff+E/37/gv9+/37/f/95/3v/dP9l/2T/Yv9b/2D/Zv9s/3r/hP+Q/6j/q//C/8f/0v/f/9//7//3//b/9//l/+7/3v/n/9P/5f/T/9r/yv/J/8D/uP+6/7f/yf+4/8L/wv++/9X/xf/a/9f/5v/r//L////9/wsACQAJAP7/7v/2/9//5v/c/9f/2//K/9D/wf/K/7r/wP+2/8X/rP+6/63/ov+r/5z/rv+z/7v/xv/K/9j/2f/a/93/3//c/97/0//S/8H/xP/A/73/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MgErAScBLQEpAT4BNwEsAQcBFgEUAf4A/gAFAQUB/wAEAegAwwCqAJIAfwB8AFsAWwBTAE8ARwBFADEATgBAAEQAOgBFAFMAbQB9AIsAlQCsALEAvwDBANIA0wDWANQA3QDVAO4A4gDmAPcAAQH9AP8A/AAXARMBGQEgAScBMQE5AScBMwEhAQwB7wDaAMcAwwCfAJYAfgCEAGoAUwBEAD8APwBAADkALgA1AE4AQABNAFsAdgCJAJ8AvgDZAPgAEgEdATABLwFAAT0BRgFOAVIBWgFkAWABUgE/ATYBKgEWASEB7wD6AN4A1wC2ALwAngCaAJMAewB0AFUAUQBQAEYALwAnABkACgD+////9f8KAAwADwAXAB8AGgAjAC4AOgA1AD4ASABMAFQAawB0AI0ApgDCANkA+wAEARsBJgEmASYBLQEpAS0BKgElARsBCQELAfYA4gDOAL4AqACLAIUAdgBrAH8AiACYAKgAtgC7ANEA2QDvAOYA9AD7AAgBDQEQARUBGwEgAR0BEAEHAfYA/ADiAOsA1gDhANIA1ADRAMYAxgC/ALQAqgCUAJ4AlQCLAHoAhgCAAHsAewB0AHMAcgB4AHoAgQCAAI4AmgCnAMIA1gDXAO4A6gDwAPoAAgEcASEBKwEtAUcBSQFRAU4BVQFZAWMBZAFLAUoBQwFIATwBTgE/AT0BNgEvASQBBgEGAfEA4wDPAMIAkACBAHAAUwBXAD8AOgArACMAKQAmACIAHAAwADYARwBNAGEAbQBtAGsAbwB5AIYAlgCOAKYAuADBAMMAxgC+AM8A5QDvAAQBBAETARoBEwEOAQIBCgH/AAAB5wDnAOMAywDTAM0AwwDNANgAzQDWALcAwwCuALIArgC8ALIAvwC5AL4AsQCyAK0ApwCsAJwAkgCJAIgAkgCBAIgAdQCEAIAAdwBzAHEAdgB5AHMAfACPAH0AfwB1AIMAdwB6AHwAhACEAJMAkACNAJsAqQCmAKAApACKAIsAjACHAH0AdABnAFsATwBEADYAMAAgAB8AEwAPABMABgAHAP/////0//X/6P/1//H////w/wQACAAdABcAJwAnADMANQAuAB4AIAAQAA4ACQAFAP//BAD0//b/9v/y//P/+f/5////CAAHAAcADgAJAA0AEwAgADEARgBYAF4AawB0AHgAdQB5AG8AawBeAEkA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1P/k/8//zv/L/9X/1f/g/+L/7/8HAAwAIgAnADAANwBAADEASABCAEUAQgBAAD0AOgAsAB0AEgACAAAA/v/7//P/9f/4//r/AQACABsAFgAlACcAKAAmADwANgBMAEoASwBPAEMASQBRAGIAbAB0AHQAbABsAGkAXQBiAEIAUQBQAD8ANAAjAAwABQD2//L/8v/i/+7/3v/c/9n/3v/h/+f/8P/o/+r/5f/t//H/6v/1/+L/8v/f/+L/z//P/8b/vv/F/8T/v/+9/7P/vf+4/8H/xP/Q/9b/3f/v//X//P8UABAAIwAvADcASABUAF4AbgB3AHQAfABvAGwAYQBRAEsARwAqAC0AGwAmACYANgAqADQANABHAFwAaAB3AI4AkwCeAJ8AmgChAIwAiwB/AHcAaABoAFoAVABQADYANwArACUAJwAoAA0AFQAJAAgADAAYACQALQA1ADcATgBQAGIAagBnAG8AaABjAF8AVgBcAEgARQAtACMADwD///L/7f/c/9D/wf++/7r/vP/E/8f/wf/N/8n/1v/o//T/BwAXAB8AKwAwADgASABEAFEAVgBbAE8AQQAxABwACQD6////9P/2/+f/5v/h/93/7P/z/wYAFAAZAB4ALQA0AEAASABOAGIAYgBsAG4AagBmAFMAVQBGAEcAPQA4AC4AJgAhABUAFQD9/wAA7//h/+T/2//a/8P/wP+5/6r/p/+x/7X/zP/N//H/+v8SACQAMgA8AEcAUwBfAFQAawBbAFsAWwBYAFQAWABBAEYAIgAbAAwAAAD6//j/6//3/+v/7P/r/+//BAALACMAHwAoACAAKQAqADQAOQBHAEEARwA3AD8ANgA/ADkARgBEADsAQAA4ADsAOAAxADQAJAAeABUAFAAQACoAKgBBAEYASgBZAGAAawB5AIAAggCHAH8AiQCHAHkAewBjAFsAVQBJAEkAPQA5ADQAJwAjABYAGAAbAA0ADQAGABAA//8SAAQAFQAUACsAJgAzACEAIwAbABIAEwAGAAoA/P/7//H/8f/w/+n/3v/W/93/1f/R/9T/0f/U/8T/yv/N/83/4P/f/+f/7f/s/wIAAQAJAA0A//8PAAMADQAFAAgABQAIAP7/+P/x//H/9/////v/FAAQAC4ALABCADcAQgBAAEUAPQBGADcAPAAwACUAMAArACwANgA2ADYA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KgAaAB0AGwAqACwAKgAbAB8AHAARAAgAAQADAP7//v8GAAUAEQASABsALwAzAEgAVQBYAFIAVQBJAFMARAA6AD0AIwAtACIAKQAlADIAKQAfACAAHQAaACIAEQATAAYAAQABAAoABAAHAAoADAAMABAAGgAhADAAIQA5ADIANgBBAFMAZgBlAHwAcwBzAHEAYgBtAG4AXwBcAFIARABJAEcARwBGAE8AQgBNAEIARABCAEkAQABOAEoASgBBAE0ATQBdAFkAYwBRAE8AOAA4ACkAEgACAAgA/P8BAPn/BwD4//r/5v/4/+//8P/4/wAACgASAA8ABgACAP7//P///+3//P/3//X/6f/2/+L/6v/c/+X/2//V/83/z//T/9n/0f/Y/8L/wf/Q/8X/z//N/9H/yP/E/8T/x//Q/8v/3v/n/+r/9P8DAAgADwAKABwAIAAzADIARgBIAFAASgBbAEgASgBCADMAMQAjABIACgAEAPj/+v/9//3/8/8BAP3/AQD4//D//P////7//P/5/wMADQAPAAwAEwAXABoAEgATABYAEgAZAA4AJQAlABEAGQAQABwAIAAgACQALAAuACwAIwArABAADwABAAIA9//y/+P/3//k/93/4f/c/+j/3v/i/+b/5v/r/9f/5P/c/9//3P/h/+f/4P/O/8v/vf+3/7D/qP+q/6r/qP+u/7b/uP/V/9L/5f/p/wAABQAKAAYAEAD6/wIA/P8FAP7/8//+//n//v/w//L/AwD7//r/9f/4/wUABwAFAAsADQAdAB8AJAAyAEEASgBVAFgAYwBUAF0AbABkAGoAVABdAFQAZQBUAFwAWwBaAEMAQQA0ADEALQAPACEACgAIAPP/+v/4//L/7f/d/+f/2f/h/8z/3f/K/83/1P/c/+X/6f/m//7/9v/1/9//3f/Z/9n/zP/F/8f/zv++/8L/w//Q/9P/xP/Q/9n/xv/G/7z/0P/U/9X/0P/o/+f/3v/b/+T//f/3//z/AgABAP7/9P/1/+v/9f/e/+b/3f/g/9r/3f/c/9//2v/U/+r/6P/3//T/BAAPABoAEwAjACUALAAqACoAPgBBADgANgA9ADIALgAkACoAJwAWACgAGwApACMAJAAiACgAKwAiAC4AKwA2AEEAPABFAEYAQwBEAEIARQBEADQALwAmAB0AHAANABsACgAQAAgA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2gDRAN4AFAE5AT0BEgH3ALQAXgA0ADwAbgCAAKgAqgCmAIUAgQBhAEwAVgBaAGYAdwB3AIcAoQC3AM0AuwCkAJIAegCHAJ4AzwD0ACkBKAEeARAB9QC0ANUA0gDpAA8BUQGDAZABjwFqAUgBFAH2AOUAxQDHAPQAAQEHAfwA6QCpAGcAMgAEAPT/IgBWAI4AwQAoASYBJgEjASMBPQE1ARYBJQEbASIBQgFZAVwBHAHaALMAdABCAGcAmgDeABIBWgFSAUYBNQFLAVABQgEyAT8BNAERAR8BRAEqAQsB6wC6AHgATAA6AEIATgBzAIcAiACOAJ0AyADrACkBcAGnAawBkAGkAaYBegF4AVoBKQHZALMAnQB/AHsAiAB2AHYAbgB+AKoAnQC2AOsAGQE4AUgBUgFAAQsBzACNAEEALwAcABsASQB5AKgAtADIAKcAvgCvAMoA3wD6APYAEwFiAYkBoQGGAWIBCQHFAIYAawCJAKYA1wDPAMsA4wC7ALQAoAC9AOoAAAEeAR4BKAEQAQ4BEgEDAfQA8wC1AKcAqwDCAN4A1ADVAOcA1wC/AL0A1QDUAPMAJQEcAT4BQAFOAU4BMAEqASMBNwEUAUsBagFtAV0BYQFLASgB7QC+AKcAbgBnAGkAfQCTAKIAuADIAO8A8QDhAPYA4AAGAQoBGwEWATIBAQH0AK0AggBNAB0ACQADABcAPwB9AJMAtgDGAM8A3QDxAAABKQEPAQIBAAH4AOEA6QCvAJwAYwBLAEUAMwBEAEgAcwByAIQAdABeAHUAdABvAHoAdgB2AGcAZgByAJIAnACSAJIAjABiAGYAXwBqAJEAoQC8AKcAgwB3AHwAfABpADIAEQDx/+H/zv/K/9P/4//w/+7/4f/q//n/FQA5AG8AfACJAKYAoACiAKIAkACIAHIAKwDl/7X/hP9k/0n/X/9v/5f/hv92/3//sv/b/xcALQBNAEUAKAD0/9T/ov+f/5b/f/+N/4P/k/96/2H/M/8//yP/BP8H/xL/Kf9m/63/9f9CAFMAZgA/ADEAJAAlABEABwD2/9f/uf+l/3r/dv9j/2P/bv9+/4z/uv/O/x0ARwCLAMcA3wDpAAIBDAEEAQYB/gDPAI4AQQAAANL/Zv80/wT/+P7x/ij/c/+q/+P/HQBiAG8AkQC3AAwBJwFUAWQBTAFDAeIAowBJAPP/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XwBpAGUAZgBUAFUAPQA/AC4AKAAXABMABwABAAAA/v/6//7/BQAGABIAEgAWABQAEAAIAPv/8v/o/+j/5P/f/+D/2//V/9X/w//C/8T/tP++/63/t/+u/7b/tf/J/7v/zP/I/8b/w/+6/7b/vP+0/7T/vv/D/9T/2P/f/+T/4P/h/+H/5v/m/+b/6//v//H//f/+/wsAEgAYABYAJAAaABoAIAAWABoADwALAAcA///+/+r/7v/i/+P/zv/S/83/zP/X/93/8P/1/wwAEwAnACoANAA2AD8AMQAyACQALwAmABgAFwAMAAUA8v/v/+b/2v/N/8r/v//E/7//yP/P/+L/6P/6////CwADAAYA9//0/+X/3//P/8n/vP+3/67/qv+i/6X/kv+V/5X/jv+V/6P/rf+5/7v/zv/U/9z/8P/v/wIABgANABMAGQAsACYALwBBADoAQAA8AEkAPgA8ADUALAAyAB4ALQAbACEAGAAUAA0AEwAGAA8ABwALAAUA/P/9//T/+P/4//D/9P/q/+n/4//i/+f/3v/l/97/5v/r//X/9v8EAAAAEAAMAA8AGwAZACgAJAAkACEAHQAOABMAAQAMAP3/AQD3/wAA+P/+//3/BQAEAA4AFgAUAA8AEgARAAkAEQD//xoABAAUAAEADgABAAMAAAD6//r/8P/z//z/+P///wMAEwAeACQANAA6AE8ASgBZAFAAUwBMAEYAPgAoACsAFQAYAAAA///v/+D/z/+//7b/p/+q/6b/qf+m/6//s//C/9D/4f/t/wEAAwAVABUAKgAwAEYASQBbAFgAZgBiAHEAcAB2AGwAYwBVAEwASQA/AEQARABCAEQAPwBEAEAARAA/AD0AOwAtADEAIAAgAA4ABAD1/+j/4P/G/8f/rv+f/4v/ff9p/3H/YP90/3b/hf+H/5P/kv+i/6P/tP/D/9H/3P/o//b/9v8HAP7/DQAKABUAIAAgACgAJAAuADYANQA+AEUATwBMAFQAUQBTAEoAQwBAAEEAOAAzACgAJAAXABoADwAPAA4ADgAMAAkA/f8DAOz/8//r/+f/7f/w//j/AQD7/wQAAgALAAUADAABAAIA/f/1/+7/5v/n/+n/8v/v////+v////3/9//8//D/8//u//L/8f/y//H/+//8//7/BwABAA0AAgAOAAgA//8FAAAAEAASABkAHAAoACUAKgAkACEAIgAXABMADAAKAAQA//8FAP3/AAD9//3/AAD6//n/8P/u/+7/6f/r/+b/6f/n/+v/5P/h/9H/xf+9/7H/p/+r/6f/sv+4/7f/y//H/9//2P/h/93/4P/f/+n/5P/y//P/BQAEABgAGQAoADUANABBAEAAQQBLAEUAVQBSAF4AWgBfAFoAWABIADwALAAfAA8AAwD6/+j/4P/b/9X/2f/T/9z/1P/j/9j/4P/b/9L/1//J/9f/zv/Q/8z/1f/V/9v/2//c/93/4//n//P/9f8FAA4AGAAkACEALgArADYANAA4ADkAPQBHAEoAUABLAFYASwBWAEYAUQA9AEEAJAAkAA8ABQD///j/8//5//7//P8BAP3/BwADAAkADgAQABYAFgAZABUAEwAKAAYACAAJAAYAAwAPAAIADAAAAAIAAAD6//7/AwAMABUAEQAbAA0AEAD///7/7v/y/93/4v/R/9n/0f/b/97/4P/u/+P/6//j/+n/4f/n/+b/7f/1//X/BwD6/xEABwAYABAAFQAUAAwAEQAHAAYABQAGAAcABwADAAIA+v/1/+//5v/T/9D/vf+x/7H/n/+x/6z/u//E/8z/0//X/9//3f/m/+f/6v/t//L/8P/3/w==';
    var audiofile = req.body.audiofile;
    var audiodata = audiofile.replace(/^data:audio\/\w+;base64,/, '');

    var fs = require('fs');
    fs.writeFile(appDir+'/upload/beyondverbal/'+fl, audiodata, {encoding: 'base64'}, function(err){
        
    });

    var base64param = new Object;
    base64param.base64str = audiofile;

    var sp = new Test(base64param);
    sp.save(function(err) {});

    var resarr = new Object;
    resarr.msg = 1;
    res.json(resarr);
};

// validic daily calories burned chart info
exports.postDailyValidicHeartBitRateChartInfo = function(req, res) {
    var currentdates = _this.postCurrentWeekStartEndDates();
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var request = require('request');
            var heartbitrate_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+currentdates[0]+"T00:00:00+00:00&end_date="+currentdates[1]+"T23:59:59+00:00&expanded=1";
            request(heartbitrate_url, function (error, response, body) {
                var myjson = JSON.parse(body);
                var startdatesplit = currentdates[0].split('-');
                var enddatesplit = currentdates[1].split('-');
                var day_arr = [];
                var day_table_arr = [];
                var count_arr = [];
                var counter = 0;
                var days_arr = ['S','M','T','W','T','F','S'];
                var days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                var todayday = new Date();
                var todaydd = parseInt(todayday.getDate());
                for(var j=todaydd;j>=parseInt(startdatesplit[2]);j--){
                    var modmonth = startdatesplit[1];
                    modmonth = ('0' + modmonth).slice(-2);
                    var moddate = j;
                    moddate = ('0' + moddate).slice(-2);
                    var currentdate = startdatesplit[0]+"-"+modmonth+"-"+moddate;
                    var tmp_d = new Date(currentdate);
                    var tmp_dayName = days_arr[tmp_d.getDay()];
                    day_arr[counter] = tmp_dayName;
                    day_table_arr[counter] = (todaydd==j) ? 'Today' : days_full_arr[tmp_d.getDay()];
                    var recordcheck = 'no';
                    for (var k = 0; k < myjson.routine.length; k++) {
                        var res_timestamp = new Date(myjson.routine[k].timestamp);
                        var selmonth = (res_timestamp.getMonth()+1);
                        selmonth = ('0' + selmonth).slice(-2);
                        var seldate = res_timestamp.getDate();
                        seldate = ('0' + seldate).slice(-2);
                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +selmonth+ '-'+seldate;
                        if(res_timestamp_str==currentdate){
                            recordcheck = 'yes';
                            count_arr[counter] = (myjson.routine[k].resting_heart_rate==null) ? 0 : myjson.routine[k].resting_heart_rate;
                            break;
                        }
                    }
                    if(recordcheck=='no'){
                        count_arr[counter] = 0;
                    }
                    counter++;
                }
                var type_info = new Object;
                var weekarr = new Object;
                weekarr.days = day_arr;
                weekarr.count = count_arr;
                weekarr.days_table = day_table_arr;
                type_info.daily = weekarr;

                resarr.data = type_info;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

// validic heart bit rate info
exports.postValidicHeartBitRateChartInfo = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDates(req,res,startDate,endDate,month,year);
                var day_arr = [],
                day_full_arr = [],
                count_arr = [],
                chart_arr = [],
                chart_day_arr = [],
                chart_arr_cnt = 1,
                formatted_count_arr = [],
                dates_arr = [],
                short_dates_arr = [],
                table_full_arr = [],
                weeksarr = [],
                week_chart_arr = [],
                week_wise_chart_arr = [],
                weeksArrCnt = 0,
                days_arr = ['S','M','T','W','T','F','S'],
                days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                table_info = new Object,
                chart_info = new Object,
                type_info = new Object,
                weekly_info = new Object;

                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    var single_week = new Object,
                    total_weekly_heartbitrate = 0,
                    daysArrCnt = 0,
                    day_arr = [],
                    day_full_arr = [],
                    count_arr = [],
                    formatted_count_arr = [],
                    dates_arr = [],
                    short_dates_arr = [],
                    table_full_arr = [],
                    week_wise_chart_arr = [],
                    days_data_count = 0;
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        n2 = new Date(n2);
                        n2.setDate(n2.getDate() - 1);
                        forMM = (n2.getMonth()+1),
                        forMM = ('0' + forMM).slice(-2),
                        forDD = n2.getDate(),
                        forDD = ('0' + forDD).slice(-2),
                        n2 = n2.getFullYear()+'-'+forMM+'-'+forDD;
                        /*var heartbitrate_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=1";
                        request(heartbitrate_url, function (error, response, body) {*/
                        MemberHeartBitRate.find({ created_date: n2, member_id : req.body.member_id }, function(err, heartrate) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            if(heartrate.length>0){
                                heartrate[0].resting_heart_rate = (heartrate[0].resting_heart_rate==null) ? 0 : (heartrate[0].resting_heart_rate);
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = heartrate[0].resting_heart_rate;
                                if(heartrate[0].resting_heart_rate>0 && heartrate[0].resting_heart_rate!=null){
                                    chart_arr.push(heartrate[0].resting_heart_rate);
                                    chart_day_arr.push(chart_arr_cnt);
                                    week_wise_chart_arr.push(heartrate[0].resting_heart_rate);
                                    chart_arr_cnt++;
                                    days_data_count++;
                                }
                                formatted_count_arr[daysArrCnt] = heartrate[0].resting_heart_rate;
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+(heartrate[0].resting_heart_rate+' resting bpm');
                                total_weekly_heartbitrate+= heartrate[0].resting_heart_rate;
                            }
                            else {
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = 0;
                                formatted_count_arr[daysArrCnt] = '0';
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+('0 resting bpm');
                            }
                            daysArrCnt++;
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1);
                        var lastMon = (lastDate.getMonth()+1);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }
                        week_chart_arr = week_chart_arr.concat(week_wise_chart_arr.reverse());
                        var avgHeartRate = (total_weekly_heartbitrate>0) ? Math.round(total_weekly_heartbitrate/days_data_count,0) : 0;
                        single_week.bpm = avgHeartRate.toLocaleString()+' bpm resting avg';
                        weekObj.days = day_arr.reverse();
                        weekObj.daysFull = day_full_arr.reverse();
                        weekObj.count = count_arr.reverse();
                        weekObj.formatted_count = formatted_count_arr.reverse();
                        weekObj.dates = dates_arr.reverse();
                        weekObj.short_dates = short_dates_arr.reverse();
                        weekObj.table_day_full_str = table_full_arr.reverse();
                        single_week.information = weekObj;
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    var information = new Object;
                    information.days = chart_day_arr;
                    information.count = week_chart_arr;
                    weekly_info.table_info = weeksarr;
                    weekly_info.chart_info = information;
                    type_info.weekly = weekly_info;
                    resarr.data = type_info;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};


/*exports.postValidicHeartBitRateChartInfo = function(req, res) {
    var resarr = new Object();

    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var table_info = new Object;
            var type_info = new Object();
            var weekly_info = new Object();
            var weeksarr = [];
            for (var i = 0; i < 4; i++) {
                var single_week = new Object;
                single_week.week_title = 'Week '+(i+1);
                single_week.bpm = (i*20);
                
                var day_arr = [];
                var count_arr = [];
                var days_arr = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                var weeksnestedarr = new Object();
                for (var j = 0; j < days_arr.length; j++) {
                    day_arr[j] = days_arr[j];
                    count_arr[j] = (j*10);
                };

                weeksnestedarr.days = day_arr;
                weeksnestedarr.count = count_arr;
                single_week.information = weeksnestedarr;
                weeksarr[i] = single_week;
            }
            weekly_info.table_info = weeksarr;
            type_info.weekly = weekly_info;
            resarr.data = type_info;
            resarr.msg = 1;
            res.json(resarr);
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};*/

// get current month start date and end date
exports.postCurrentMonthStartEndDates = function(req,res,startDate,endDate,month,year) {
    var f = new Date(startDate.toDate()),
    fs = f.getDate() - f.getDay(),
    fds = new Date(f.setDate(fs)).toUTCString();

    var d = new Date(fds),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);

    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    startDate = yyyy+"-"+mm+"-"+dd;

    var l = new Date(endDate.toDate()),
    ls = l.getDate() - l.getDay(),
    lf = ls + 6,
    ldf = new Date(l.setDate(lf)).toUTCString();

    var d = new Date(ldf),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);
    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    finishDate = yyyy+"-"+mm+"-"+dd;

    var todayday = new Date(),
    main_custom_date = new Date(year+'-'+month+'-'+'01');
    if((year==todayday.getFullYear()) && (month==todayday.getMonth()+1)){
        var noOfDaysInMonth = parseInt(todayday.getDate());
    }
    else {
        var noOfDaysInMonth = new Date(year, month, 0).getDate();
    }

    var startWeekArr = new Array(),
    weekRangeDatesArr = new Array();
    for(var i=1;i<=noOfDaysInMonth;i++){
        var newDate = new Date(main_custom_date.getFullYear(),main_custom_date.getMonth(),i)
        if(newDate.getDay()==0){
            var passDD = ('0' + i).slice(-2),
            passMM = ('0' + month).slice(-2);
            var passDate = year+'-'+passMM+'-'+passDD;
            var weekRangeDates = _this.postSpecificWeekStartEndDates(req, res, passDate);
            weekRangeDatesArr.push(weekRangeDates);
            startWeekArr.push(year+'-'+month+'-'+i);
        }
    }
    
    if (startWeekArr[0] != year+'-'+month+'-'+1) {
        var passMM = ('0' + month).slice(-2);
        var weekRangeDates = _this.postSpecificWeekStartEndDates(req, res, year+'-'+passMM+'-'+1);
        weekRangeDatesArr.unshift(weekRangeDates);
        startWeekArr.unshift(year+'-'+month+'-'+1);
    }
    weekRangeDatesArr.reverse();
    return weekRangeDatesArr;
};

exports.postCurrentMonthStartEndDatesPlus1Day = function(req,res,startDate,endDate,month,year) {
    var f = new Date(startDate.toDate()),
    fs = f.getDate() - f.getDay(),
    fds = new Date(f.setDate(fs)).toUTCString();

    var d = new Date(fds),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);

    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    startDate = yyyy+"-"+mm+"-"+dd;

    var l = new Date(endDate.toDate()),
    ls = l.getDate() - l.getDay(),
    lf = ls + 6,
    ldf = new Date(l.setDate(lf)).toUTCString();

    var d = new Date(ldf),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);
    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    finishDate = yyyy+"-"+mm+"-"+dd;

    var todayday = new Date(),
    main_custom_date = new Date(year+'-'+month+'-'+'01');
    if((year==todayday.getFullYear()) && (month==todayday.getMonth()+1)){
        var noOfDaysInMonth = parseInt(todayday.getDate());
    }
    else {
        var noOfDaysInMonth = new Date(year, month, 0).getDate();
    }

    var startWeekArr = new Array(),
    weekRangeDatesArr = new Array();
    for(var i=1;i<=noOfDaysInMonth;i++){
        var newDate = new Date(main_custom_date.getFullYear(),main_custom_date.getMonth(),i)
        if(newDate.getDay()==0){
            var passDD = ('0' + i).slice(-2),
            passMM = ('0' + month).slice(-2);
            var passDate = year+'-'+passMM+'-'+passDD;
            var weekRangeDates = _this.postSpecificWeekStartEndDatesPlus1Day(req, res, passDate);
            weekRangeDatesArr.push(weekRangeDates);
            startWeekArr.push(year+'-'+month+'-'+i);
        }
    }
    
    if (startWeekArr[0] != year+'-'+month+'-'+1) {
        var passMM = ('0' + month).slice(-2);
        var weekRangeDates = _this.postSpecificWeekStartEndDatesPlus1Day(req, res, year+'-'+passMM+'-'+1);
        weekRangeDatesArr.unshift(weekRangeDates);
        startWeekArr.unshift(year+'-'+month+'-'+1);
    }
    weekRangeDatesArr.reverse();
    return weekRangeDatesArr;
};

exports.postCurrentMonthStartEndDatesWithoutAddSubDays = function(req,res,startDate,endDate,month,year) {
    var f = new Date(startDate.toDate()),
    fs = f.getDate() - f.getDay(),
    fds = new Date(f.setDate(fs)).toUTCString();

    var d = new Date(fds),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);

    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    startDate = yyyy+"-"+mm+"-"+dd;

    var l = new Date(endDate.toDate()),
    ls = l.getDate() - l.getDay(),
    lf = ls + 6,
    ldf = new Date(l.setDate(lf)).toUTCString();

    var d = new Date(ldf),
    mm = (d.getMonth() + 1);
    mm = ('0' + mm).slice(-2);
    var dd = d.getDate();
    dd = ('0' + dd).slice(-2);
    var yyyy = d.getFullYear(),
    finishDate = yyyy+"-"+mm+"-"+dd;

    var todayday = new Date(),
    main_custom_date = new Date(year+'-'+month+'-'+'01');
    if((year==todayday.getFullYear()) && (month==todayday.getMonth()+1)){
        var noOfDaysInMonth = parseInt(todayday.getDate());
    }
    else {
        var noOfDaysInMonth = new Date(year, month, 0).getDate();
    }

    var startWeekArr = new Array(),
    weekRangeDatesArr = new Array();
    for(var i=1;i<=noOfDaysInMonth;i++){
        var newDate = new Date(main_custom_date.getFullYear(),main_custom_date.getMonth(),i)
        if(newDate.getDay()==0){
            var passDD = ('0' + i).slice(-2),
            passMM = ('0' + month).slice(-2);
            var passDate = year+'-'+passMM+'-'+passDD;
            var weekRangeDates = _this.postSpecificWeekStartEndDatesWithoutAddSubDays(req, res, passDate);
            weekRangeDatesArr.push(weekRangeDates);
            startWeekArr.push(year+'-'+month+'-'+i);
        }
    }
    
    if (startWeekArr[0] != year+'-'+month+'-'+1) {
        var passMM = ('0' + month).slice(-2);
        var weekRangeDates = _this.postSpecificWeekStartEndDatesWithoutAddSubDays(req, res, year+'-'+passMM+'-'+1);
        weekRangeDatesArr.unshift(weekRangeDates);
        startWeekArr.unshift(year+'-'+month+'-'+1);
    }
    weekRangeDatesArr.reverse();
    return weekRangeDatesArr;
};

// get current month start date and end date
exports.postSpecificWeekStartEndDates = function(req, res, passDate) {
    var dateParts = passDate.split('-'),
    MM = ('0' + dateParts[1]).slice(-2),
    DD = ('0' + dateParts[2]).slice(-2),
    passDate = dateParts[0]+'-'+MM+'-'+DD;
    var curr = new Date(passDate),
    first = curr.getDate() - curr.getDay(),
    d = new Date(curr.setDate(first));
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    var firstDate = [year, month, day].join('-');

    var d2 = new Date(curr.setDate((curr.getDate()) - curr.getDay()+6)),
    month2 = '' + (d2.getMonth() + 1),
    day2 = '' + d2.getDate(),
    year2 = d2.getFullYear();
    if (month2.length < 2) month2 = '0' + month2;
    if (day2.length < 2) day2 = '0' + day2;
    var lastDate = [year2, month2, day2].join('-');

    var responsearr = [];
    responsearr[0] = firstDate;
    responsearr[1] = lastDate;
    return responsearr;
};

exports.postSpecificWeekStartEndDatesWithoutAddSubDays = function(req, res, passDate) {
    var dateParts = passDate.split('-'),
    MM = ('0' + dateParts[1]).slice(-2),
    DD = ('0' + dateParts[2]).slice(-2),
    passDate = dateParts[0]+'-'+MM+'-'+DD;
    var curr = new Date(passDate),
    first = curr.getDate() - curr.getDay(),
    d = new Date(curr.setDate(first));
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    var firstDate = [year, month, day].join('-');

    var d2 = new Date(curr.setDate(curr.getDate() - curr.getDay()+6));
    month2 = '' + (d2.getMonth() + 1),
    day2 = '' + d2.getDate(),
    year2 = d2.getFullYear();
    if (month2.length < 2) month2 = '0' + month2;
    if (day2.length < 2) day2 = '0' + day2;
    var lastDate = [year2, month2, day2].join('-');

    var responsearr = [];
    responsearr[0] = firstDate;
    responsearr[1] = lastDate;
    return responsearr;
};

exports.postSubstract1DayMinusFromDateWithFormat = function(req , res, passDate){
    var passDate = new Date(passDate);
    passDate.setDate(passDate.getDate()-1);
    passDate = _this.formatDate(req,res,passDate);
    return passDate;
};

exports.postSpecificWeekStartEndDatesPlus1Day = function(req, res, passDate) {
    var dateParts = passDate.split('-'),
    MM = ('0' + dateParts[1]).slice(-2),
    DD = ('0' + dateParts[2]).slice(-2),
    passDate = dateParts[0]+'-'+MM+'-'+DD;
    var curr = new Date(passDate),
    first = curr.getDate() - curr.getDay(),
    d = new Date(curr.setDate(first));
    d.setDate(d.getDate() + 1),
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    var firstDate = [year, month, day].join('-');

    var d2 = new Date(curr.setDate((curr.getDate()+1) - curr.getDay()+6));
    month2 = '' + (d2.getMonth() + 1),
    day2 = '' + d2.getDate(),
    year2 = d2.getFullYear();
    if (month2.length < 2) month2 = '0' + month2;
    if (day2.length < 2) day2 = '0' + day2;
    var lastDate = [year2, month2, day2].join('-');

    var responsearr = [];
    responsearr[0] = firstDate;
    responsearr[1] = lastDate;
    return responsearr;
};

// get current month start date and end date
exports.postCurrentWeekStartEndDates = function(req, res) {
    var curr = new Date; // get current date
    var first = curr.getDate() - curr.getDay(); // First day is the day of the month - the day of the week
    var last = first + 6; // last day is the first day + 6
    var firstDay = new Date(curr.setDate(first)).toUTCString();
    var lastDay = new Date(curr.setDate(last)).toUTCString();
    var fftodaydate = new Date(firstDay);
    var ffday = fftodaydate.getDate();
    var mytodaydate = new Date;
    var totalbigdays = 0;
    var mylastdate = new Date(lastDay);
    if(ffday > mytodaydate.getDate()){
        var tmpstart = mytodaydate.getDate();
        while(tmpstart<=mylastdate.getDate()){
            if(ffday > mytodaydate.getDate()){
                totalbigdays++;
            }
            tmpstart++;
        }
        var curset = new Date();
        first = curset.getDate(); // First day is the day of the month - the day of the week
        var bigdaydiff = (7 - totalbigdays);
        var last = first + (6 - bigdaydiff); // last day is the first day + 6
        firstDay = new Date(curset.setDate(first)).toUTCString();
        lastDay = new Date(curset.setDate(last)).toUTCString();
    }
    
    for(var i = 1; i <= 2; i++){
        var daytype = (i==1) ? firstDay : lastDay;
        var d = new Date(daytype);
        var month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        if(i==1){
            month_start_date = [year, month, day].join('-');
        }
        else {
            month_end_date = [year, month, day].join('-');  
        }
    }
    var responsearr = [];
    responsearr[0] = month_start_date;
    responsearr[1] = month_end_date;
    return responsearr;

    var responsearr = [];
    responsearr[0] = month_start_date;
    responsearr[1] = month_end_date;
    return responsearr;
};


// validic sleep chart info
exports.postDailyValidicSleepsChartInfo = function(req, res) {
    var currentdates = _this.postCurrentWeekStartEndDates();
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var request = require('request');
            var sleep_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+currentdates[0]+"T00:00:00+00:00&end_date="+currentdates[1]+"T23:59:59+00:00&expanded=0";
            request(sleep_url, function (error, response, body) {
                var myjson = JSON.parse(body);
                var startdatesplit = currentdates[0].split('-');
                var enddatesplit = currentdates[1].split('-');
                var day_arr = [];
                var day_table_arr = [];
                var count_arr = [];
                var count_table_arr = [];
                var counter = 0;
                var days_arr = ['S','M','T','W','T','F','S'];
                var days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                var todayday = new Date();
                var todaydd = parseInt(todayday.getDate());
                for(var j=todaydd;j>=parseInt(startdatesplit[2]);j--){
                    var modmonth = startdatesplit[1];
                    modmonth = ('0' + modmonth).slice(-2);
                    var moddate = j;
                    moddate = ('0' + moddate).slice(-2);

                    var currentdate = startdatesplit[0]+"-"+modmonth+"-"+moddate;
                    var tmp_d = new Date(currentdate);
                    var tmp_dayName = days_arr[tmp_d.getDay()];
                    day_arr[counter] = tmp_dayName;
                    day_table_arr[counter] = (todaydd==j) ? 'Today' : days_full_arr[tmp_d.getDay()];
                    var recordcheck = 'no';
                    for (var k = 0; k < myjson.sleep.length; k++) {
                        var res_timestamp = new Date(myjson.sleep[k].timestamp);
                        var selmonth = (res_timestamp.getMonth()+1);
                        selmonth = ('0' + selmonth).slice(-2);
                        var seldate = res_timestamp.getDate();
                        seldate = ('0' + seldate).slice(-2);
                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +selmonth+ '-'+seldate;
                        if(res_timestamp_str==currentdate){
                            recordcheck = 'yes';
                            var x = myjson.sleep[k].total_sleep;
                            x = x*1000;
                            var d = moment.duration(x, 'milliseconds');
                            var hours = Math.floor(d.asHours());
                            var mins = Math.floor(d.asMinutes()) - hours * 60;
                            count_arr[counter] = hours;
                            count_table_arr[counter] = hours+" hr "+mins+" min";
                            break;
                        }
                    }
                    if(recordcheck=='no'){
                        count_arr[counter] = 0;
                        count_table_arr[counter] = "0 hr 0 min";
                    }
                    counter++;
                }
                var type_info = new Object;
                var weekarr = new Object;
                weekarr.days = day_arr;
                weekarr.count = count_arr;
                weekarr.days_table = day_table_arr;
                weekarr.count_table = count_table_arr;
                type_info.daily = weekarr;

                resarr.data = type_info;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

// validic sleep chart info
exports.postValidicSleepsChartInfo = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDates(req,res,startDate,endDate,month,year);
                var day_arr = [],
                day_full_arr = [],
                platform_arr = [],
                count_arr = [],
                formatted_count_arr = [],
                dates_arr = [],
                short_dates_arr = [],
                timestamp_start_dates_arr = [],
                timestamp_end_dates_arr = [],
                table_days = [],
                table_short_dates = [],
                table_dates = [],
                table_days_value = [],
                tableDaysInfoCnt = 0,
                weeksarr = [],
                weeksArrCnt = 0,
                days_arr = ['S','M','T','W','T','F','S'],
                days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                table_info = new Object,
                type_info = new Object,
                weekly_info = new Object;

                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    var single_week = new Object,
                    total_weekly_sleep_duration = 0,
                    day_arr = [],
                    day_full_arr = [],
                    count_arr = [],
                    count_min_arr = [],
                    count_hrs_min_arr = [],
                    dates_arr = [],
                    platform_arr = [],
                    sleep_details_arr = [],
                    table_days = [],
                    table_short_dates = [],
                    table_dates = [],
                    table_days_value = [],
                    short_dates_arr = [],
                    timestamp_start_dates_arr = [],
                    timestamp_end_dates_arr = [];
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        n2 = new Date(n2);
                        n2.setDate(n2.getDate() - 1);

                        forMM = (n2.getMonth()+1),
                        forMM = ('0' + forMM).slice(-2),
                        forDD = n2.getDate(),
                        forDD = ('0' + forDD).slice(-2),
                        n2 = n2.getFullYear()+'-'+forMM+'-'+forDD;
                        
                        MemberSleep.find({ created_date: n2, member_id : req.body.member_id }, function(err, memslept) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            day_arr.push(days_arr[tmp_d.getDay()]);
                            day_full_arr.push((todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()]);
                            dates_arr.push(origDateFormat);
                            short_dates_arr.push((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)));
                            var totalSleepMins = 0;
                            if(memslept.length>0){
                                async.forEachSeries(memslept, function(n3, callback_s3) {
                                    var res_timestamp = n3.timestamp;
                                    res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                                    var resTimeStampArr = res_timestamp.split("T");
                                    var momentOfTime = new Date(resTimeStampArr[0]+' '+resTimeStampArr[1]);
                                    myTimeSpan = 5*60*1000;// 5 minutes in milliseconds
                                    momentOfTime.setTime(momentOfTime.getTime() - myTimeSpan);

                                    momentOfTime = new Date(momentOfTime);
                                    forMM = (momentOfTime.getMonth()+1),
                                    forMM = ('0' + forMM).slice(-2),
                                    forDD = momentOfTime.getDate(),
                                    forDD = ('0' + forDD).slice(-2);

                                    var hrs = momentOfTime.getHours();
                                    hrs = ('0' + hrs).slice(-2);
                                    var mins = momentOfTime.getMinutes();
                                    mins = ('0' + mins).slice(-2);
                                    var sec = momentOfTime.getSeconds();
                                    sec = ('0' + sec).slice(-2);
                                    momentOfTime = momentOfTime.getFullYear()+'-'+forMM+'-'+forDD+'T'+hrs+':'+mins+':'+sec;

                                    var momentOfTimeSec = new Date(resTimeStampArr[0]+' '+resTimeStampArr[1]);
                                    myTimeSpanSec = 5*60*1000;// 5 minutes in milliseconds
                                    momentOfTimeSec.setTime(momentOfTimeSec.getTime() + myTimeSpanSec);

                                    momentOfTimeSec = new Date(momentOfTimeSec);
                                    forMM = (momentOfTimeSec.getMonth()+1),
                                    forMM = ('0' + forMM).slice(-2),
                                    forDD = momentOfTimeSec.getDate(),
                                    forDD = ('0' + forDD).slice(-2);

                                    var hrs = momentOfTimeSec.getHours();
                                    hrs = ('0' + hrs).slice(-2);
                                    var mins = momentOfTimeSec.getMinutes();
                                    mins = ('0' + mins).slice(-2);
                                    var sec = momentOfTimeSec.getSeconds();
                                    sec = ('0' + sec).slice(-2);
                                    
                                    totalSleepMins+= n3.total_sleep;

                                    momentOfTimeSec = momentOfTimeSec.getFullYear()+'-'+forMM+'-'+forDD+'T'+hrs+':'+mins+':'+sec;
                                    total_weekly_sleep_duration+= n3.total_sleep;
                                    timestamp_start_dates_arr.push(momentOfTime);
                                    timestamp_end_dates_arr.push(momentOfTimeSec);
                                    table_days.push((todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()]);
                                    table_short_dates.push((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)));
                                    table_dates.push(origDateFormat);
                                    
                                    var x = n3.total_sleep;
                                    x = x*1000;
                                    var d = moment.duration(x, 'milliseconds'),
                                    hours = Math.floor(d.asHours()),
                                    mins = Math.floor(d.asMinutes()) - hours * 60;
                                    table_days_value.push(hours+" hr "+mins+" min");
                                    platform_arr.push(n3.platform_type);
                                    MemberSleepDetail.findOne({ created_date: n3.created_date,created_time:n3.created_time, member_id : req.body.member_id }, function(err, memsleptdetail) {
                                        var sleepObj = new Object;
                                        if(memsleptdetail){
                                            var startTime = memsleptdetail.start_time,
                                            afterT = startTime.substr(startTime.indexOf("T") + 1),
                                            beforePlus = afterT.substring(0,afterT.indexOf("."));
                                            
                                            sleepObj.timeAsleep = (memsleptdetail.total_sleep) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,memsleptdetail.total_sleep) : '0 hr 0 min';
                                            sleepObj.sleepStartSchedule = _this.postChangeTimeFormatFrom24To12(req,res,beforePlus);
                                            
                                            var endTime = memsleptdetail.end_time,
                                            afterT = endTime.substr(endTime.indexOf("T") + 1),
                                            beforePlus = afterT.substring(0,afterT.indexOf("."));
                                            
                                            sleepObj.sleepEndSchedule = _this.postChangeTimeFormatFrom24To12(req,res,beforePlus);
                                            sleepObj.sleepAwake = (memsleptdetail.summary_wake_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,memsleptdetail.summary_wake_minutes) : '0';
                                            sleepObj.sleepRem = (memsleptdetail.summary_rem_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,memsleptdetail.summary_rem_minutes) : '0';
                                            sleepObj.sleepLight = (memsleptdetail.summary_light_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,memsleptdetail.summary_light_minutes) : '0';
                                            sleepObj.sleepDeep = (memsleptdetail.summary_deep_minutes) ? _this.postChangeTimeFormatFromXToHRMIN(req,res,memsleptdetail.summary_deep_minutes) : '0';
                                        }
                                        else {
                                            sleepObj.timeAsleep = "0 min";
                                            sleepObj.sleepStartSchedule = '00 : 00';
                                            sleepObj.sleepEndSchedule = '00 : 00';
                                            sleepObj.sleepAwake = '0 min';
                                            sleepObj.sleepRem = '0 min';
                                            sleepObj.sleepLight = '0 min';
                                            sleepObj.sleepDeep = '0 min';
                                        }
                                        sleep_details_arr.push(sleepObj);
                                        callback_s3();
                                    });
                                }, function (err) {
                                    var x = totalSleepMins;
                                    x = x*1000;
                                    var d = moment.duration(x, 'milliseconds'),
                                    hours = Math.floor(d.asHours()),
                                    mins = Math.floor(d.asMinutes()) - hours * 60;
                                    count_arr.push(hours);
                                    count_min_arr.push(mins);
                                    count_hrs_min_arr.push(hours+" hr "+mins+" min");
                                    callback_s2();
                                });
                            }
                            else {
                                count_arr.push(0);
                                count_min_arr.push(0);
                                count_hrs_min_arr.push("0 hr 0 min");
                                callback_s2();
                            }
                        }).sort({created_date: 'asc', created_time: 'asc'})
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1);
                        var lastMon = (lastDate.getMonth()+1);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }

                        if(total_weekly_sleep_duration==0){
                            single_week.duration = "0 hr 0 min avg";
                        }
                        else {
                            var x = parseInt(total_weekly_sleep_duration/table_dates.length);
                            x = x*1000;
                            var d = moment.duration(x, 'milliseconds');
                            var hours = Math.floor(d.asHours());
                            var mins = Math.floor(d.asMinutes()) - hours * 60;
                            single_week.duration = hours+" hr "+mins+" min avg";
                        }
                        weekObj.days = day_arr.reverse();
                        weekObj.daysFull = day_full_arr.reverse();
                        weekObj.count = count_arr.reverse();
                        weekObj.count_min = count_min_arr.reverse();
                        weekObj.count_hrs_min = count_hrs_min_arr.reverse();
                        weekObj.dates = dates_arr.reverse();
                        weekObj.sleep_details = sleep_details_arr.reverse();
                        weekObj.short_dates = short_dates_arr.reverse();
                        weekObj.table_days = table_days.reverse();
                        weekObj.table_short_dates = table_short_dates.reverse();
                        weekObj.table_dates = table_dates.reverse();
                        weekObj.table_days_value = table_days_value.reverse();
                        weekObj.platform_type = platform_arr.reverse();
                        weekObj.start_dates = timestamp_start_dates_arr.reverse();
                        weekObj.end_dates = timestamp_end_dates_arr.reverse();
                        single_week.information = weekObj;
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    weekly_info.table_info = weeksarr;
                    type_info.weekly = weekly_info;
                    resarr.data = type_info;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

// validic daily steps chart info
exports.postDailyValidicStepsChartInfo = function(req, res) {
    var currentdates = _this.postCurrentWeekStartEndDates();
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var request = require('request');
            var steps_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+currentdates[0]+"T00:00:00+00:00&end_date="+currentdates[1]+"T23:59:59+00:00&expanded=0";
            request(steps_url, function (error, response, body) {
                var myjson = JSON.parse(body);
                var startdatesplit = currentdates[0].split('-');
                var enddatesplit = currentdates[1].split('-');
                var day_arr = [];
                var day_table_arr = [];
                var count_arr = [];
                var counter = 0;
                var days_arr = ['S','M','T','W','T','F','S'];
                var days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                var todayday = new Date();
                var todaydd = parseInt(todayday.getDate());
                for(var j=todaydd;j>=parseInt(startdatesplit[2]);j--){
                    var modmonth = startdatesplit[1];
                    modmonth = ('0' + modmonth).slice(-2);
                    var moddate = j;
                    moddate = ('0' + moddate).slice(-2);
                    var currentdate = startdatesplit[0]+"-"+modmonth+"-"+moddate;
                    var tmp_d = new Date(currentdate);
                    var tmp_dayName = days_arr[tmp_d.getDay()];
                    day_arr[counter] = tmp_dayName;
                    day_table_arr[counter] = (todaydd==j) ? 'Today' : days_full_arr[tmp_d.getDay()];
                    var recordcheck = 'no';
                    for (var k = 0; k < myjson.routine.length; k++) {
                        var res_timestamp = new Date(myjson.routine[k].timestamp);
                        var selmonth = (res_timestamp.getMonth()+1);
                        selmonth = ('0' + selmonth).slice(-2);
                        var seldate = res_timestamp.getDate();
                        seldate = ('0' + seldate).slice(-2);
                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +selmonth+ '-'+seldate;
                        if(res_timestamp_str==currentdate){
                            recordcheck = 'yes';
                            count_arr[counter] = myjson.routine[k].steps;
                            break;
                        }
                    }
                    if(recordcheck=='no'){
                        count_arr[counter] = 0;
                    }
                    counter++;
                }
                var type_info = new Object;
                var weekarr = new Object;
                weekarr.days = day_arr;
                weekarr.count = count_arr;
                weekarr.days_table = day_table_arr;
                type_info.daily = weekarr;

                resarr.data = type_info;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};


// validic steps chart info
exports.postValidicStepsChartInfo = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDates(req,res,startDate,endDate,month,year);
                var day_arr = [],
                day_full_arr = [],
                count_arr = [],
                formatted_count_arr = [],
                dates_arr = [],
                short_dates_arr = [],
                table_full_arr = [],
                weeksarr = [],
                weeksArrCnt = 0,
                days_arr = ['S','M','T','W','T','F','S'],
                days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                table_info = new Object,
                type_info = new Object,
                weekly_info = new Object;

                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    var single_week = new Object,
                    total_weekly_steps = 0,
                    daysArrCnt = 0,
                    day_arr = [],
                    day_full_arr = [],
                    count_arr = [],
                    formatted_count_arr = [],
                    dates_arr = [],
                    short_dates_arr = [],
                    table_full_arr = [],
                    week_days_count = 0;
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        n2 = new Date(n2);
                        n2.setDate(n2.getDate() - 1);
                        forMM = (n2.getMonth()+1),
                        forMM = ('0' + forMM).slice(-2),
                        forDD = n2.getDate(),
                        forDD = ('0' + forDD).slice(-2),
                        n2 = n2.getFullYear()+'-'+forMM+'-'+forDD;
                        /*var steps_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=0";
                        request(steps_url, function (error, response, body) {*/
                        MemberSteps.find({ created_date: n2, member_id : req.body.member_id }, function(err, memsteps) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            if(memsteps.length>0){
                                total_weekly_steps+= memsteps[0].steps;
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = memsteps[0].steps;
                                formatted_count_arr[daysArrCnt] = memsteps[0].steps.toLocaleString();
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+(memsteps[0].steps.toLocaleString()+' steps');
                                week_days_count = (memsteps[0].steps>0) ? (week_days_count+1) : week_days_count;
                            }
                            else {
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = 0;
                                formatted_count_arr[daysArrCnt] = '0';
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+('0 steps');
                            }
                            daysArrCnt++;
                            callback_s2();
                        });
                        /*});*/
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1);
                        var lastMon = (lastDate.getMonth()+1);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }
                        single_week.avg_steps = (total_weekly_steps>0) ? (Math.round(total_weekly_steps/week_days_count,0)).toLocaleString()+' steps avg' : '0 steps avg';
                        single_week.steps = total_weekly_steps.toLocaleString()+' steps total';
                        weekObj.days = day_arr.reverse();
                        weekObj.daysFull = day_full_arr.reverse();
                        weekObj.count = count_arr.reverse();
                        weekObj.formatted_count = formatted_count_arr.reverse();
                        weekObj.dates = dates_arr.reverse();
                        weekObj.short_dates = short_dates_arr.reverse();
                        weekObj.table_day_full_str = table_full_arr.reverse();
                        single_week.information = weekObj;
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    weekly_info.table_info = weeksarr;
                    type_info.weekly = weekly_info;
                    resarr.data = type_info;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};
/*exports.postValidicStepsChartInfo = function(req, res) {
    var resarr = new Object();

    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var table_info = new Object;
            var type_info = new Object();
            var weekly_info = new Object();
            var weeksarr = [];
            for (var i = 0; i < 4; i++) {
                var single_week = new Object;
                single_week.week_title = 'Week '+(i+1);
                single_week.steps = ((i+1)*1000);
                
                var day_arr = [];
                var count_arr = [];
                var days_arr = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                var weeksnestedarr = new Object();
                for (var j = 0; j < days_arr.length; j++) {
                    day_arr[j] = days_arr[j];
                    count_arr[j] = (j*10);
                };

                weeksnestedarr.days = day_arr;
                weeksnestedarr.count = count_arr;
                single_week.information = weeksnestedarr;
                weeksarr[i] = single_week;
            }
            weekly_info.table_info = weeksarr;
            type_info.weekly = weekly_info;
            resarr.data = type_info;
            resarr.msg = 1;
            res.json(resarr);
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};*/

// validic daily calories burned chart info
exports.postDailyValidicCaloriesChartInfo = function(req, res) {
    var currentdates = _this.postCurrentWeekStartEndDates();
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var request = require('request');
            var calories_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+currentdates[0]+"T00:00:00+00:00&end_date="+currentdates[1]+"T23:59:59+00:00&expanded=0";
            request(calories_url, function (error, response, body) {
                var myjson = JSON.parse(body);
                var startdatesplit = currentdates[0].split('-');
                var enddatesplit = currentdates[1].split('-');
                var day_arr = [];
                var day_table_arr = [];
                var count_arr = [];
                var counter = 0;
                var days_arr = ['S','M','T','W','T','F','S'];
                var days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                var todayday = new Date();
                var todaydd = parseInt(todayday.getDate());
                for(var j=todaydd;j>=parseInt(startdatesplit[2]);j--){
                    var modmonth = startdatesplit[1];
                    modmonth = ('0' + modmonth).slice(-2);
                    var moddate = j;
                    moddate = ('0' + moddate).slice(-2);
                    var currentdate = startdatesplit[0]+"-"+modmonth+"-"+moddate;
                    var tmp_d = new Date(currentdate);
                    var tmp_dayName = days_arr[tmp_d.getDay()];
                    day_arr[counter] = tmp_dayName;
                    day_table_arr[counter] = (todaydd==j) ? 'Today' : days_full_arr[tmp_d.getDay()];
                    var recordcheck = 'no';
                    for (var k = 0; k < myjson.routine.length; k++) {
                        var res_timestamp = new Date(myjson.routine[k].timestamp);
                        var selmonth = (res_timestamp.getMonth()+1);
                        selmonth = ('0' + selmonth).slice(-2);
                        var seldate = res_timestamp.getDate();
                        seldate = ('0' + seldate).slice(-2);
                        var res_timestamp_str = res_timestamp.getFullYear()+'-' +selmonth+ '-'+seldate;
                        if(res_timestamp_str==currentdate){
                            recordcheck = 'yes';
                            count_arr[counter] = myjson.routine[k].calories_burned;
                            break;
                        }
                    }
                    if(recordcheck=='no'){
                        count_arr[counter] = 0;
                    }
                    counter++;
                }
                var type_info = new Object;
                var weekarr = new Object;
                weekarr.days = day_arr;
                weekarr.count = count_arr;
                weekarr.days_table = day_table_arr;
                type_info.daily = weekarr;

                resarr.data = type_info;
                resarr.msg = 1;
                res.json(resarr);
            });
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
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

exports.postValidicActiveMinutes = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDates(req,res,startDate,endDate,month,year);
                var day_arr = [],
                day_full_arr = [],
                count_arr = [],
                formatted_count_arr = [],
                dates_arr = [],
                short_dates_arr = [],
                table_full_arr = [],
                weeksarr = [],
                weeksArrCnt = 0,
                days_arr = ['S','M','T','W','T','F','S'],
                days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                table_info = new Object,
                type_info = new Object,
                weekly_info = new Object;
                
                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    var single_week = new Object,
                    total_weekly_active_minutes = 0,
                    daysArrCnt = 0,
                    day_arr = [],
                    day_full_arr = [],
                    count_arr = [],
                    formatted_count_arr = [],
                    dates_arr = [],
                    short_dates_arr = [],
                    table_full_arr = [],
                    week_days_count = 0;
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        n2 = new Date(n2);
                        n2.setDate(n2.getDate() - 1),
                        forMM = (n2.getMonth()+1),
                        forMM = ('0' + forMM).slice(-2),
                        forDD = n2.getDate(),
                        forDD = ('0' + forDD).slice(-2),
                        n2 = n2.getFullYear()+'-'+forMM+'-'+forDD;
                        
                        MemberActiveMinutes.findOne({ created_date: n2, member_id : req.body.member_id }, function(err, memactiveminutes) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            if(memactiveminutes){
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = parseInt(memactiveminutes.active_duration/60);
                                formatted_count_arr[daysArrCnt] = parseInt(memactiveminutes.active_duration/60).toLocaleString();
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+(parseInt(memactiveminutes.active_duration/60).toLocaleString()+' active minutes');
                                total_weekly_active_minutes+= parseInt(memactiveminutes.active_duration/60);
                                week_days_count = (memactiveminutes.active_duration>0) ? (week_days_count+1) : week_days_count;
                            }
                            else {
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = 0;
                                formatted_count_arr[daysArrCnt] = '0';
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+('0 active minutes');
                            }
                            daysArrCnt++;
                            callback_s2();
                        });
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1);
                        var lastMon = (lastDate.getMonth()+1);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }
                        single_week.avg_active_minutes = (total_weekly_active_minutes>0) ? (Math.round(total_weekly_active_minutes/week_days_count,0)).toLocaleString()+' active minutes avg' : '0 active minutes avg';
                        single_week.total_active_minutes = total_weekly_active_minutes.toLocaleString()+' active minutes total';
                        weekObj.days = day_arr.reverse();
                        weekObj.daysFull = day_full_arr.reverse();
                        weekObj.count = count_arr.reverse();
                        weekObj.formatted_count = formatted_count_arr.reverse();
                        weekObj.dates = dates_arr.reverse();
                        weekObj.short_dates = short_dates_arr.reverse();
                        weekObj.table_day_full_str = table_full_arr.reverse();
                        single_week.information = weekObj;
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    weekly_info.table_info = weeksarr;
                    type_info.weekly = weekly_info;
                    resarr.data = type_info;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postValidicCaloriesChartInfo = function(req, res) {
    var resarr = new Object();
    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var request = require('request'),
                async = require('async'),
                moment = require('moment'),
                todayday = new Date(),
                year = todayday.getFullYear(),
                month = (todayday.getMonth()+1),
                day = (todayday.getDate());
                month = ('0' + month).slice(-2);
                day = ('0' + day).slice(-2);
                var todaysFormatDate = year+'-'+month+'-'+day;
                if(req.body.month && req.body.year){
                    year = req.body.year;
                    month = req.body.month;
                }
                var startDate = moment([year, month - 1]),
                endDate = moment(startDate).endOf('month');

                var returndates = _this.postCurrentMonthStartEndDates(req,res,startDate,endDate,month,year);
                var day_arr = [],
                day_full_arr = [],
                count_arr = [],
                formatted_count_arr = [],
                dates_arr = [],
                short_dates_arr = [],
                table_full_arr = [],
                weeksarr = [],
                weeksArrCnt = 0,
                days_arr = ['S','M','T','W','T','F','S'],
                days_full_arr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                table_info = new Object,
                type_info = new Object,
                weekly_info = new Object;
                
                async.forEachSeries(returndates, function(n1, callback_s1) {
                    var resDateRange = _this.postRangeStartEndDates(req,res,n1[0],n1[1]);
                    var single_week = new Object,
                    total_weekly_calories = 0,
                    daysArrCnt = 0,
                    day_arr = [],
                    day_full_arr = [],
                    count_arr = [],
                    formatted_count_arr = [],
                    dates_arr = [],
                    short_dates_arr = [],
                    table_full_arr = [],
                    week_days_count = 0;
                    async.forEachSeries(resDateRange, function(n2, callback_s2) {
                        var origDate = new Date(n2),
                        origDateFormat = n2;
                        n2 = new Date(n2);
                        n2.setDate(n2.getDate() - 1),
                        forMM = (n2.getMonth()+1),
                        forMM = ('0' + forMM).slice(-2),
                        forDD = n2.getDate(),
                        forDD = ('0' + forDD).slice(-2),
                        n2 = n2.getFullYear()+'-'+forMM+'-'+forDD;
                        //var calories_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member[0].validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+n2+"T00:00:00+00:00&end_date="+n2+"T23:59:59+00:00&expanded=0";
                        //request(calories_url, function (error, response, body) {
                        MemberCalories.find({ created_date: n2, member_id : req.body.member_id }, function(err, memcal) {
                            var res_timestamp_str = origDate,
                            res_timestamp_month = (res_timestamp_str.getMonth()+1),
                            tmp_d = res_timestamp_str;
                            if(memcal.length>0){
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = memcal[0].calories_burned;
                                formatted_count_arr[daysArrCnt] = memcal[0].calories_burned.toLocaleString();
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+(memcal[0].calories_burned.toLocaleString()+' calories');
                                total_weekly_calories+= memcal[0].calories_burned;
                                week_days_count = (memcal[0].calories_burned>0) ? (week_days_count+1) : week_days_count;
                            }
                            else {
                                day_arr[daysArrCnt] = days_arr[tmp_d.getDay()];
                                day_full_arr[daysArrCnt] = (todaysFormatDate==origDateFormat) ? 'Today' : days_full_arr[tmp_d.getDay()];
                                count_arr[daysArrCnt] = 0;
                                formatted_count_arr[daysArrCnt] = '0';
                                dates_arr[daysArrCnt] = origDateFormat;
                                short_dates_arr[daysArrCnt] = (res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1));
                                table_full_arr[daysArrCnt] = ((res_timestamp_str.getDate())+' '+(moment.monthsShort(res_timestamp_month - 1)))+' '+('0 calories');
                            }
                            daysArrCnt++;
                            callback_s2();
                        });
                        //});
                    }, function (err) {
                        var weekObj = new Object(),
                        firstDate = new Date(n1[0]),
                        lastDate = new Date(n1[1]),
                        todaysDate = new Date();
                        var firstMon = (firstDate.getMonth()+1);
                        var lastMon = (lastDate.getMonth()+1);
                        if(todaysDate>=firstDate && todaysDate<=lastDate){
                            single_week.week_title = 'This Week';
                        }
                        else {
                            single_week.week_title = (moment.monthsShort(firstMon - 1))+' '+(firstDate.getDate())+' - '+(moment.monthsShort(lastMon - 1))+' '+(lastDate.getDate());
                        }
                        single_week.avg_calories = (total_weekly_calories>0) ? (Math.round(total_weekly_calories/week_days_count,0)).toLocaleString()+' calories avg' : '0 calories avg';
                        single_week.total_calories = total_weekly_calories.toLocaleString()+' calories total';
                        weekObj.days = day_arr.reverse();
                        weekObj.daysFull = day_full_arr.reverse();
                        weekObj.count = count_arr.reverse();
                        weekObj.formatted_count = formatted_count_arr.reverse();
                        weekObj.dates = dates_arr.reverse();
                        weekObj.short_dates = short_dates_arr.reverse();
                        weekObj.table_day_full_str = table_full_arr.reverse();
                        single_week.information = weekObj;
                        weeksarr[weeksArrCnt] = single_week;
                        weeksArrCnt++;
                        callback_s1();
                    });
                }, function (err) {
                    weekly_info.table_info = weeksarr;
                    type_info.weekly = weekly_info;
                    resarr.data = type_info;
                    resarr.msg = 1;
                    res.json(resarr);
                });
            }
            else {
                resarr.msg = 2;
                resarr.mobile_message = 'Member not exist.';
                res.json(resarr);
            }
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

// validic calories chart info
/*exports.postValidicCaloriesChartInfo = function(req, res) {
    var resarr = new Object();

    if(req.body.member_id){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            var type_info = new Object;
            var daily_info = new Object;
            var weekly_info = new Object;
            var table_info = new Object;
            var first_section = new Object;
            first_section.calories_burned = 2590;
            first_section.bpm = 45;
            table_info.first_section = first_section;
            
            var second_section = new Object;
            second_section.duration = '1 hr 24 mins';
            second_section.calories = 707;
            table_info.second_section = second_section;

            var third_section = new Object;
            third_section.peak = '15 minutes';
            third_section.cardio = '11 minutes';
            third_section.fast_burn = '1 hr 10 mins';
            third_section.progressbar = 50;
            table_info.third_section = third_section;

            var chart_info = new Object;
            var day_count = new Object();
            var day_arr = [];
            var count_arr = [];
            var days_arr = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            for (var i = 0; i < days_arr.length; i++) {
                day_arr[i] = days_arr[i];
                count_arr[i] = (i*10);
            }
            chart_info.days = day_arr;
            chart_info.count = count_arr;

            daily_info.chart_info = chart_info;
            daily_info.table_info = table_info;

            var table_info = new Object;
            var weeksarr = [];
            for (var i = 0; i < 4; i++) {
                var single_week = new Object;
                single_week.week_title = 'Week '+(i+1);
                single_week.total_calories = ((i+1)*1000);
                
                var day_arr = [];
                var count_arr = [];

                var weeksnestedarr = new Object();
                for (var j = 0; j < days_arr.length; j++) {
                    day_arr[j] = days_arr[j];
                    count_arr[j] = (j*10);
                };

                weeksnestedarr.days = day_arr;
                weeksnestedarr.count = count_arr;
                single_week.information = weeksnestedarr;
                weeksarr[i] = single_week;
            }
            //weekly_info.chart_info = chart_info;
            weekly_info.table_info = weeksarr;

            //type_info.daily = daily_info;
            type_info.weekly = weekly_info;
            resarr.data = type_info;
            resarr.msg = 1;
            res.json(resarr);
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};*/

// validic dashboard
exports.valenceScoreLabel = function(req, res, current_device_datetime, firstname) {
    var passDate = new Date(current_device_datetime),
    days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    dayName = days[passDate.getDay()],
    hrs = passDate.getHours(),
    mins = passDate.getMinutes();
    if(dayName=='Mon'){
        var labelArr = [];
        if((hrs>=0 && hrs<=4) && (mins>=0 && mins<=59)){
            labelArr[0] = "That was a long Monday "+firstname+" How're you feeling?";
            labelArr[1] = "Hi "+firstname+", good start to the week? Tell me about it...";
            labelArr[2] = "You're still up! What's on your mind...";
        }
        else if((hrs>=5 && hrs<=11) && (mins>=0 && mins<=59)){
            labelArr[0] = "Good Morning "+firstname+"! Happy Monday, How're you feeling?";
            labelArr[1] = "Good Morning "+firstname+"! Happy Monday, Enjoyed your weekend? Tell me about it...";
            labelArr[2] = "Good Morning "+firstname+"! Charged up for the week? What's on your mind...";
        }
        else if((hrs>=12 && hrs<=17) && (mins>=0 && mins<=59)){
            labelArr[0] = "Good start to the week? How're you feeling?";
            labelArr[1] = "Crushed your Monday blues? Tell me about it...";
            labelArr[2] = "Good start to the week? What's on your mind..."; 
        }
        else {
            labelArr[0] = "Hi "+firstname+" Good start to the week? How're you feeling?";
            labelArr[1] = "Hi "+firstname+" Rocked your Monday? Tell me about it...";
            labelArr[2] = "Hi "+firstname+" Feeling good about the week? What's on your mind...";   
        }
        var randomNo = Math.floor(Math.random() * (2 - 0 + 1)) + 0;
        return labelArr[randomNo];
    }
    else if(dayName=='Tue' || dayName=='Wed' || dayName=='Thu'){
        var labelArr = [];
        if((hrs>=0 && hrs<=4) && (mins>=0 && mins<=59)){
            labelArr[0] = "Hi "+firstname+", gathered your thoughts for the day? Tell me about it...";
            labelArr[1] = "You're still up! What's on your mind...";
            labelArr[2] = "That was a long day "+firstname+"! How're you feeling?";
        }
        else if((hrs>=5 && hrs<=11) && (mins>=0 && mins<=59)){
            labelArr[0] = "Good morning "+firstname+"? How're you feeling today?";
            labelArr[1] = "Good morning "+firstname+"! All charged up for today? What's on your mind...";
            labelArr[2] = "Good morning "+firstname+"? Are you having a good week so far? Tell me about it...";
        }
        else if((hrs>=12 && hrs<=17) && (mins>=0 && mins<=59)){
            labelArr[0] = "How goes the day? Feeling good?";
            labelArr[1] = "What's up "+firstname+"! What's on your mind?";
            labelArr[2] = "Good day so far? What's on your mind...";    
        }
        else {
            labelArr[0] = "Hi "+firstname+" Long day? How're you feeling?";
            labelArr[1] = "Hi "+firstname+" Rocked your day? Tell me about it...";
            labelArr[2] = "Hi "+firstname+" Feeling good about the week? What's on your mind...";   
        }
        var randomNo = Math.floor(Math.random() * (2 - 0 + 1)) + 0;
        return labelArr[randomNo];
    }
    else if(dayName=='Fri'){
        var labelArr = [];
        if((hrs>=0 && hrs<=4) && (mins>=0 && mins<=59)){
            labelArr[0] = "You're still up! What's on your mind...";
            labelArr[1] = "Woo "+firstname+"? Up until this late? Tell me about it...";
            labelArr[2] = "Someone's up late, How're you feeling?";
        }
        else if((hrs>=5 && hrs<=11) && (mins>=0 && mins<=59)){
            labelArr[0] = "Good Morning "+firstname+"! Happy Friday, How're you feeling?";
            labelArr[1] = "Good Morning "+firstname+"! Happy Firday, Looking forward to the weekend?";
            labelArr[2] = "Good Morning "+firstname+"! Are you having a good week so far? Tell me about it...";
        }
        else if((hrs>=12 && hrs<=17) && (mins>=0 && mins<=59)){
            labelArr[0] = "What's up "+firstname+"? What's on your mind?";
            labelArr[1] = "Almost to the weekend? What's on your mind...";
            labelArr[2] = "How goes the day? Feeling good?";    
        }
        else {
            labelArr[0] = "Hi "+firstname+", rocking your Friday evening? Tell me about it...";
            labelArr[1] = "Hi "+firstname+", good start to the weekend? How're you geeling?";
            labelArr[2] = "So, how was your week? Tell me about it..."; 
        }
        var randomNo = Math.floor(Math.random() * (2 - 0 + 1)) + 0;
        return labelArr[randomNo];
    }
    else if(dayName=='Sat'){
        var labelArr = [];
        if((hrs>=0 && hrs<=4) && (mins>=0 && mins<=59)){
            labelArr[0] = "You're still up! What's on your mind...";
            labelArr[1] = "Woo "+firstname+"? Up until this late? Tell me about it...";
            labelArr[2] = "Someone's up late, How're you feeling?";
        }
        else if((hrs>=5 && hrs<=11) && (mins>=0 && mins<=59)){
            labelArr[0] = "Good Morning "+firstname+"! Saturday morning, How're you feeling?";
            labelArr[1] = "Good Morning "+firstname+"! Big weekend plans? Tell me about it...";
            labelArr[2] = "Good Morning "+firstname+"! What's on your mind this saturday morning?";
        }
        else if((hrs>=12 && hrs<=17) && (mins>=0 && mins<=59)){
            labelArr[0] = "What's up "+firstname+"? What's on your mind?";
            labelArr[1] = "How goes your Saturday? Feeling good?";
            labelArr[2] = "Hi "+firstname+"! Busy weekend or a relaxed one? Tell me about it...";   
        }
        else {
            labelArr[0] = "Hi "+firstname+", good start to the weekend? How're you feeling?";
            labelArr[1] = "Hi "+firstname+", rocking your weekend? Tell me about it...";
            labelArr[2] = "Good weekend so far? What's on your mind...";    
        }
        var randomNo = Math.floor(Math.random() * (2 - 0 + 1)) + 0;
        return labelArr[randomNo];
    }
    else {
        var labelArr = [];
        if((hrs>=0 && hrs<=4) && (mins>=0 && mins<=59)){
            labelArr[0] = "You're still up! What's on your mind...";
            labelArr[1] = "Woo "+firstname+"? Up until this late? Tell me about it...";
            labelArr[2] = "Someone's up late, How're you feeling?";
        }
        else if((hrs>=5 && hrs<=11) && (mins>=0 && mins<=59)){
            labelArr[0] = "Happy Sunday "+firstname+"! How're you feeling?";
            labelArr[1] = "Good Morning "+firstname+"! What's on your mind this sunday morning?";
            labelArr[2] = "Hi "+firstname+"! Busy weekend or a relaxed one? Tell me about it...";
        }
        else if((hrs>=12 && hrs<=17) && (mins>=0 && mins<=59)){
            labelArr[0] = "What's up "+firstname+"? What's on your mind?";
            labelArr[1] = "How goes your Sunday? Feeling good?";
            labelArr[2] = "Hi "+firstname+" Rocking your weekend? Tell me about it..."; 
        }
        else {
            labelArr[0] = "Hi "+firstname+", did you have a good weekend? How're you feeling?";
            labelArr[1] = "Hi "+firstname+", looking forward to the week? How're you feeling?";
            labelArr[2] = "So how was your weekend? Tell me about it...";   
        }
        var randomNo = Math.floor(Math.random() * (2 - 0 + 1)) + 0;
        return labelArr[randomNo];
    }
};

// for activity image icon
exports.postGetActivityImage = function(req, res, type, firstval, secondval) {
    var fullUrl = req.protocol + '://' + req.get('host');
    var activityIconURL = '';
    if(type=='dashboard'){
        if(firstval>secondval){
            activityIconURL = fullUrl+'/img/activityicons/dashboard_fill_'+firstval+'.png';
        }
        else {
            activityIconURL = fullUrl+'/img/activityicons/dashboard_'+secondval+''+firstval+'.png';
        }
    }
    else if(type=='exercise_slide'){
        if(firstval>secondval){
            activityIconURL = fullUrl+'/img/activityicons/exercise_slide1_fill_'+firstval+'.png';
        }
        else {
            activityIconURL = fullUrl+'/img/activityicons/exercise_slide1_'+secondval+''+firstval+'.png';
        }
    }
    else {
        if(firstval>secondval){
            activityIconURL = fullUrl+'/img/activityicons/exercise_table_fill_'+firstval+'.png';
        }
        else {
            activityIconURL = fullUrl+'/img/activityicons/exercise_table_'+secondval+''+firstval+'.png';
        }
    }
    return activityIconURL;
};

// validic dashboard
exports.postValidicDashboard = function(req, res) {
    var todayday = new Date(),
    year = todayday.getFullYear(),
    month = (todayday.getMonth()+1),
    day = (todayday.getDate());
    month = ('0' + month).slice(-2);
    day = ('0' + day).slice(-2);
    var todaysFormatDate = year+'-'+month+'-'+day;
    var weekRangeDates = _this.postSpecificWeekStartEndDatesPlus1Day(req, res, todaysFormatDate);
    var rangeArr = _this.returnDateRange(req,res,weekRangeDates[0],weekRangeDates[1]);
    var resarr = new Object();
    var typearr = new Object();
    if(req.body.member_id && req.body.current_device_datetime){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            MembersEmotionalAnalytics.findOne({ member_id: req.body.member_id }, function(err, memberbeyondverbal) {
                if(memberbeyondverbal){
                    typearr.valence_score = Math.ceil(memberbeyondverbal.valence_score).toLocaleString();
                }
                else {
                    typearr.valence_score = "0";
                }
                var valence_score_label = _this.valenceScoreLabel(req,res,req.body.current_device_datetime,member[0].firstname);
                typearr.valence_score_label = valence_score_label;
                var request = require('request');
                MemberSleep.findOne({member_id : req.body.member_id,total_sleep:{$ne:0} }, function(err, memSleep) {
                    if(memSleep){
                        MemberSleep.find({member_id : req.body.member_id,created_date: memSleep.created_date}, function(err, memSleepRes) {
                            var totalsleep = 0;
                            for(sl=0;sl<memSleepRes.length;sl++){
                                totalsleep+= memSleepRes[sl].total_sleep;
                            }
                            var x = totalsleep;
                            x = x*1000; 
                            var d = moment.duration(x, 'milliseconds');
                            var hours = Math.floor(d.asHours());
                            totalsleep = hours;
                            var mins = Math.floor(d.asMinutes()) - hours * 60;
                            typearr.sleep = hours+" hr "+mins+" min";
                        });
                    }
                    else {
                        typearr.sleep = "0 hr 0 min";
                    }
                    
                    MemberCalories.findOne({member_id : req.body.member_id, calories_burned:{$ne:0} }, function(err, memCal) {
                        if(memCal){
                            typearr.calories = memCal.calories_burned.toLocaleString();
                        }
                        else {
                            typearr.calories = "0";
                        }

                        MemberSteps.findOne({member_id : req.body.member_id, steps:{$ne:0} }, function(err, memStep) {
                            if(memStep){
                                typearr.steps = memStep.steps.toLocaleString();
                            }
                            else {
                                typearr.steps = "0";        
                            }

                            MemberHeartBitRate.findOne({ member_id : req.body.member_id, resting_heart_rate:{$ne:null} }, function(err, memHeartBit) {
                                if(memHeartBit){
                                    typearr.heartrate = (memHeartBit.resting_heart_rate!=null) ? memHeartBit.resting_heart_rate.toLocaleString() : "0";
                                }
                                else {
                                    typearr.heartrate = "0";
                                }

                                MemberActiveMinutes.findOne({ member_id : req.body.member_id, active_duration:{$ne:0} }, function(err, memActiveMinutes) {
                                    if(memActiveMinutes){
                                        typearr.active_minutes = (memActiveMinutes.active_duration!=null) ? parseInt(memActiveMinutes.active_duration/60).toLocaleString() : "0";
                                    }
                                    else {
                                        typearr.active_minutes = "0";
                                    }
                                    
                                    MemberExercise.find({ "member_id" : req.body.member_id,"created_date" : {$in: rangeArr}}, function(err, memExercise) {
                                        var dayArr = [];
                                        if(memExercise.length>0){
                                            for(ex=0;ex<memExercise.length;ex++){
                                                dayArr.push(memExercise[ex].created_date);
                                            }
                                        }
                                        dayArr = _.uniq(dayArr);
                                        var secondval = 1;
                                        if(member[0].weekly_exercise_goal){
                                            secondval = member[0].weekly_exercise_goal;
                                            if(member[0].weekly_exercise_goal>1){
                                                typearr.weekly_exercise = dayArr.length+" of "+member[0].weekly_exercise_goal+" Days";
                                            }
                                            else {
                                                typearr.weekly_exercise = dayArr.length+" of "+member[0].weekly_exercise_goal+" Day";
                                            }
                                        }
                                        else {
                                            typearr.weekly_exercise = dayArr.length+" of 0 Days";       
                                        }

                                        var activityURL = _this.postGetActivityImage(req,res,'dashboard', dayArr.length, secondval);
                                        typearr.activityIconURL = activityURL;
                                        
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
                                            typearr.aktivo_score = (singleAktivoScore) ? Math.ceil(singleAktivoScore.aktivo_score).toLocaleString() : 0;
                                            MemberCalories.find({member_id:req.body.member_id,platform_type:'validic',created_date: {$in: matchDateArr}}, function(err, memCalories) {
                                                var platTypeArr = ['apple_health_kit','google_fit'];
                                                MemberCalories.find({member_id:req.body.member_id,platform_type: {$in: platTypeArr},created_date: {$in: matchDateArr}}, function(err, memCaloriesAppleGoogle) {
                                                    if(memCalories.length>0 || memCaloriesAppleGoogle.length>0){
                                                        typearr.status = 'available';
                                                        resarr.data = typearr;
                                                        resarr.msg = 1;
                                                        res.json(resarr);
                                                    }
                                                    else {
                                                        typearr.status = 'not_available';
                                                        resarr.data = typearr;
                                                        resarr.msg = 1;
                                                        res.json(resarr);
                                                    }
                                                });
                                            });
                                        }).sort({created_at: 'desc'})
                                    });
                                }).sort({created_date: 'desc', created_time: 'desc'})   
                            }).sort({created_date: 'desc', created_time: 'desc'})
                        }).sort({created_date: 'desc', created_time: 'desc'})
                    }).sort({created_date: 'desc', created_time: 'desc'})
                }).sort({created_date: 'desc', created_time: 'desc'})
            }).sort({created_at:'desc'});
        });
    }
    else {
        resarr.msg = 0;
        resarr.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(resarr);
    }
};

exports.postVerifyUserOrganization = function(req, res) {
    var response = new Object();
    if(req.body.email){
        var email = req.body.email
        Member.find({ email: email }, function(err, member) {
            if(member.length>0){
                if(member[0].status=='Active'){
                    var company_id = member[0].company_id;
                    Company.find({ _id: company_id }, function(err, company) {
                        if(company.length>0){
                            if(company[0].status=='Active'){
                                response.msg = 1;
                                res.json(response);
                            }
                            else {
                                response.msg = 5;
                                response.mobile_message = 'Organization is inactive.';
                                res.json(response);
                            }
                        }
                        else {
                            response.msg = 4;
                            response.mobile_message = 'Your account is not connected with organization.';
                            res.json(response);
                        }
                    });
                }
                else {
                    response.msg = 3;
                    response.mobile_message = 'Your account is inactive, Contact your organization HR.';
                    res.json(response);
                }
            }
            else {
                response.msg = 2;
                response.mobile_message = 'Sorry, your email address not exist.';
                res.json(response);
            }
        });
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

exports.postFetchTokenMarketPlaceURL = function(req, res){
    var response = new Object();
    if(req.body.member_id){
        Member.findOne({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var fullUrl = req.protocol + '://' + req.get('host');
                var photo = (member.photo!='') ? fullUrl+'/member/'+member.photo : fullUrl+'/member/no_image_user.png';
                var marketplaceurl = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+member.validic_access_token;
                response.data = {'member_id' : member._id,'firstname' : member.firstname,'lastname' : member.lastname,'photo' : photo,'validic_uid' : member.validic_uid, 'validic_access_token' : member.validic_access_token, 'marketplaceurl' : marketplaceurl};
                response.msg = 1;
                res.json(response);
            }
            else {
                response.msg = 2;
                response.mobile_message = 'Sorry, member not exist.';
                res.json(response);
            }
        })
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

// verify user
exports.postVerifyUser = function(req, res) {
    var response = new Object();
    if(req.body.email){
        var email = req.body.email;
        var request = require('request');
        Member.findOne({ email: email }, function(err, member) {
            if(member){
                var fullUrl = req.protocol + '://' + req.get('host');
                var photo = (member.photo!='') ? fullUrl+'/member/'+member.photo : fullUrl+'/member/no_image_user.png';
                if(member.status=='Active'){
                    if(member.validic_uid){
                        var auto_refresh_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/refresh_token.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5";
                        request(auto_refresh_url, function (error, responseMarket, body) {
                            var jsonParse = JSON.parse(body);
                            if(jsonParse.code==200){
                                var tokenObj = new Object;
                                tokenObj.validic_access_token = jsonParse.user.authentication_token;
                                tokenObj.validic_access_token_updated_datetime = new Date();
                                Member.findByIdAndUpdate(member._id, tokenObj, function(err, memberres) {
                                    var marketplaceurl = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+tokenObj.validic_access_token;
                                    response.data = {'member_id' : member._id,'firstname' : member.firstname,'lastname' : member.lastname,'photo' : photo,'validic_uid' : member.validic_uid, 'validic_access_token' : tokenObj.validic_access_token, 'marketplaceurl' : marketplaceurl,'badtime' : member.badtime,'wakeup' : member.wakeup};
                                    response.msg = 1;
                                    res.json(response);
                                });             
                            }
                            else {
                                var marketplaceurl = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+member.validic_access_token;
                                response.data = {'member_id' : member._id,'firstname' : member.firstname,'lastname' : member.lastname,'photo' : photo,'validic_uid' : member.validic_uid, 'validic_access_token' : member.validic_access_token, 'marketplaceurl' : marketplaceurl,'badtime' : member.badtime,'wakeup' : member.wakeup};
                                response.msg = 1;
                                res.json(response);
                            }
                        });
                    }
                    else {
                        response.data = {'member_id' : member._id,'firstname' : member.firstname,'lastname' : member.lastname,'photo' : photo,'validic_uid' : '', 'validic_access_token' : '', 'marketplaceurl' : '','badtime' : member.badtime,'wakeup' : member.wakeup};
                        response.msg = 1;
                        res.json(response);
                    }
                }
                else {
                    response.msg = 3;
                    response.mobile_message = 'Your account is inactive, Contact your organization HR.';
                    res.json(response);
                }
            }
            else {
                response.msg = 2;
                response.mobile_message = 'Sorry, your email address not exist.';
                res.json(response);
            }
        });
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

// mail send
exports.postEmailSend = function(req, res) {
    var otp = Math.floor(Math.random()*89999+10000);
    var api_key = 'key-43cf4c016eb85a389fc22df0dd7bf6f4';
    var domain = 'dotzapper.com';
    var mailgun = require('mailgun-js')({apiKey: api_key, domain: domain});
     
    var data = {
      from: 'Aktivolabs <demo1.testing1@gmail.com>',
      to: 'pratik.balochiya@xongolab.com',
      subject: 'Aktivolabs OTP Verification Code',
      html: 'OTP Code : '+otp
    };
     
    mailgun.messages().send(data, function (error, body) {
        console.log(body);
    });
};

exports.sendEmail = function(req,res,toemail,subject,content) {
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

// send otp
exports.postSendOTP = function(req, res) {
    var response = new Object();
    if(req.body.email){
        var email = req.body.email
        Member.find({ email: email }, function(err, member) {
            if(member.length>0){
                if(member[0].status=='Active'){
                    var otp = Math.floor(Math.random()*89999+10000),
                    currentDate = _this.postCurrentDate(req,res),
                    currentTime = _this.postCurrentTime(req,res);

                    var dbObj = {
                        otp : otp,
                        otp_posted : new Date()
                    };

                    Member.findByIdAndUpdate(member[0]._id, dbObj, function(err, member) {});
                    var otpEmailTemplate = "<html xmlns='http://www.w3.org/1999/xhtml'><head><meta name='viewport' content='width=device-width' /><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' /><title>Email</title><style type='text/css'>table tr td a{color:#ffffff!important;text-decoration:none}.reset_password{color:#ffffff!important;text-decoration:none!important}</style></head><body bgcolor='#FFFFFF'><table width='96%' border='0' cellspacing='0' cellpadding='0' bgcolor='#f2f5f7' style='border-right:1px solid #d3d9dd;border-bottom:20px solid #000;padding:1% 2% 2% 2%;margin:0 0 0 2%;border-radius:10px'><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#000' style='border-radius:5px;padding:20px 0'><tr><td align='center' valign='middle'><img src='https://i.imgur.com/n2nLbtv.png' alt='' width='200'></td></tr></table></td></tr><tr><td><table width='100%' border='0' cellspacing='0' cellpadding='0' style='padding:10px 0 0 0'><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:15px 0 10px 10px'><span>Dear #NAME#,</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 10px 10px'><span>Your One-Time Password (OTP) is - #OTP#,</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 10px 10px'><span>This OTP is to be used for activating aktivolabs application to your smartphone as requested on #OTPDATE#, at #OTPTIME# SGT and it is valid for 24 hours only.</span></td></tr><tr><td style='font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#000;padding:10px 0 0 10px'>Thank you,<br>Aktivolabs</td></tr></table></td></tr></table></td></tr></table></body></html>";
                    otpEmailTemplate = otpEmailTemplate.replace("#NAME#", member[0].firstname+' '+member[0].lastname);
                    otpEmailTemplate = otpEmailTemplate.replace("#OTP#", otp);
                    otpEmailTemplate = otpEmailTemplate.replace("#OTPDATE#", moment(Date()).format('MMMM Do YYYY'));
                    otpEmailTemplate = otpEmailTemplate.replace("#OTPTIME#", moment(Date()).format('LT'));
                    
                    _this.sendEmail(req,res,email,'Your One-Time Password Request',otpEmailTemplate);

                    response.data = {'member_id' : member[0]._id};
                    response.msg = 1;
                    res.json(response);
                }
                else {
                    response.msg = 3;
                    response.mobile_message = 'Your account is inactive, Contact your organization HR.';
                    res.json(response);
                }
            }
            else {
                response.msg = 2;
                response.mobile_message = 'Sorry, your email address not exist.';
                res.json(response);
            }
        });
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

// match otp
exports.postMatchOTP = function(req, res) {
    var response = new Object();
    if(req.body.otp && req.body.member_id){
        var otp = req.body.otp
        var member_id = req.body.member_id
        Member.find({ otp: otp,_id : member_id }, function(err, member) {
            if(member.length>0){
                var fullUrl = req.protocol + '://' + req.get('host');
                var photo = (member[0].photo!='') ? fullUrl+'/member/'+member[0].photo : fullUrl+'/member/no_image_user.png';
                var currentDate = new Date(),
                otp_posted = new Date(member[0].otp_posted),
                current = moment(currentDate),
                posted = moment(otp_posted),
                diff = current.diff(posted, 'hours');
                if(diff<24){
                    exports.postProvisioningUser(req, res , member[0]._id, member[0].firstname, member[0].lastname,photo,member[0].validic_uid, member[0].validic_access_token,member[0].badtime,member[0].wakeup);
                }
                else {
                    response.msg = 3;
                    response.mobile_message = 'Sorry, your otp was expired.';
                    res.json(response);
                }
            }
            else {
                response.msg = 2;
                response.mobile_message = 'Sorry, your otp not matched.';
                res.json(response);
            }
        });
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

/*exports.postSaveBeyondVerbal22NOV2017 = function(req, res) {
    var response = new Object();
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    if(req.body.member_id){
        var filearr = ['1.wav','2.wav','3.wav','4.wav','5.wav','6.wav','7.wav','8.wav','9.wav','10.wav','11.wav','12.wav','13.wav','14.wav','15.wav','16.wav','17.wav','18.wav','19.wav','20.wav','21.wav','22.wav','23.wav','24.wav','25.wav','26.wav','27.wav','28.wav','29.wav','30.wav','31.wav','32.wav','33.wav','34.wav','35.wav','36.wav','37.wav','38.wav','39.wav'];

        var min = 0;
        var max = (filearr.length-1);
        var randno = (Math.floor(Math.random()*(max-min+1)+min));
        var frmparam = new Object;
        frmparam.member_id = req.body.member_id;
        frmparam.filename = filearr[randno];
        var created_at = req.body.created_at;
        var created_at_arr = created_at.split(" ");
        frmparam.currentDate = created_at_arr[0];
        frmparam.currentTime = created_at_arr[1];
        var currentDateTime = new Date(req.body.created_at);
        frmparam.created_at = currentDateTime.toISOString();
        
        var fs = require('fs');
        var Analyzer = require('./analyzer-v3');
        var analyzer = new Analyzer('d58a3db3-826a-4bc2-857d-91e91f9c42b7');
        var readfileurl = appDir+'/upload/samplefiles/'+filearr[randno];

        analyzer.analyze(fs.createReadStream(readfileurl),function(err,analysis){
            var analysisObj = new Object;
            if(err || analysis.status=='failure'){
                response.data = frmparam;
                response.msg = 2;
                res.json(response);
            }
            else {
                frmparam.mood = analysis.result.analysisSegments[0].analysis.Mood.Group11.Primary.Phrase;
                frmparam.valence_score = analysis.result.analysisSegments[0].analysis.Valence.Value;
                analysisObj.moodForHighestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Primary.Phrase;   
                analysisObj.moodForLowestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Secondary.Phrase;
                analysisObj.temperValue = analysis.result.analysisSegments[0].analysis.Temper.Value;
                frmparam.moodForHighestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Primary.Phrase;
                frmparam.moodForLowestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Secondary.Phrase;
                frmparam.temperValue = analysis.result.analysisSegments[0].analysis.Temper.Value;
                var mbv = new MembersEmotionalAnalytics(frmparam);
                mbv.save(function(err) {
                    response.data = frmparam;
                    response.msg = 1;
                    res.json(response);
                });
            }
        });
    }
    else {
        response.msg = 0;
        res.json(response); 
    }
};*/

exports.postSaveBeyondVerbal = function(req, res) {
    var response = new Object();
    var path = require('path');
    var appDir = path.dirname(require.main.filename);
    var async = require('async');
    if(req.body.member_id && req.body.created_at){
        var soundFilename = '';
        async.forEachSeries(req.files, function(n1, callback_s1) {
            var length = 10;
            var fileName = Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
            var fileExt = n1.name.split('.').pop();
            soundFilename = fileName+'.'+fileExt;

            n1.mv('./upload/samplefiles/'+soundFilename, function(err) {
                callback_s1();
            });
        }, function (err) {
            var frmparam = new Object;
            frmparam.member_id = req.body.member_id;
            frmparam.filename = soundFilename;
            var created_at = req.body.created_at;
            var created_at_arr = created_at.split(" ");
            frmparam.currentDate = created_at_arr[0];
            frmparam.currentTime = created_at_arr[1];
            var currentDateTime = new Date(req.body.created_at);
            frmparam.created_at = currentDateTime.toISOString();
            
            var fs = require('fs');
            var Analyzer = require('./analyzer-v3');
            var analyzer = new Analyzer('d58a3db3-826a-4bc2-857d-91e91f9c42b7');
            var readfileurl = appDir+'/upload/samplefiles/'+soundFilename;

            analyzer.analyze(fs.createReadStream(readfileurl),function(err,analysis){
                console.log(err); 
                console.log(analysis);
                var analysisObj = new Object;
                if(err || analysis.status=='failure'){
                    response.data = frmparam;
                    response.msg = 2;
                    res.json(response);
                }
                else if(!analysis.result.analysisSegments){
                    response.data = frmparam;
                    response.msg = 3;
                    res.json(response);
                }
                else {
                    frmparam.mood = analysis.result.analysisSegments[0].analysis.Mood.Group11.Primary.Phrase;
                    frmparam.valence_score = analysis.result.analysisSegments[0].analysis.Valence.Value;
                    analysisObj.moodForHighestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Primary.Phrase;   
                    analysisObj.moodForLowestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Secondary.Phrase;
                    analysisObj.temperValue = analysis.result.analysisSegments[0].analysis.Temper.Value;
                    frmparam.moodForHighestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Primary.Phrase;
                    frmparam.moodForLowestPoint = analysis.result.analysisSegments[0].analysis.Mood.Composite.Secondary.Phrase;
                    frmparam.temperValue = analysis.result.analysisSegments[0].analysis.Temper.Value;
                    var mbv = new MembersEmotionalAnalytics(frmparam);
                    mbv.save(function(err) {
                        fs.unlinkSync(readfileurl);
                        response.data = frmparam;
                        response.msg = 1;
                        res.json(response);
                    });
                }
            });
        });
    }
    else {
        response.msg = 0;
        res.json(response); 
    }
};

exports.formatDate = function(req, res, passDate) {
    var d = new Date(passDate),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
};

// list beyond verbal history
exports.postListBeyondVerbalHistory = function(req, res) {
    var response = new Object();
    if(req.body.member_id && req.body.current_device_datetime){
        Member.find({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var current_device_datetime = req.body.current_device_datetime,
                created_at_arr = current_device_datetime.split(" "),
                currentDate = created_at_arr[0],
                today_valence_score_count = 0;

                var filterObj = new Object;
                filterObj.member_id = req.body.member_id;
                if(req.body.month && req.body.year){
                    var firstDay = new Date(req.body.year, req.body.month-1, 1);
                    var lastDay = new Date(req.body.year, req.body.month, 0);
                    firstDay = _this.formatDate(req,res,firstDay);
                    lastDay = _this.formatDate(req,res,lastDay);
                    var rangeArr = _this.returnDateRange(req,res,firstDay,lastDay);
                    filterObj.currentDate = {$in: rangeArr};
                }

                MembersEmotionalAnalytics.find(filterObj, function(err, memberbeyondverbal) {
                    var valence_score_label = _this.valenceScoreLabel(req,res,req.body.current_device_datetime,member[0].firstname);
                    var resarr = [],
                    chartArr = [];
                    for(var i=0;i<memberbeyondverbal.length;i++){
                        if(memberbeyondverbal[i].currentDate==currentDate){
                            today_valence_score_count++;
                        }
                        var innerres = new Object();
                        innerres.filename = memberbeyondverbal[i].filename;
                        innerres.mood = memberbeyondverbal[i].mood;
                        innerres.valence_score = Math.ceil(memberbeyondverbal[i].valence_score);
                        innerres.moodForHighestPoint = memberbeyondverbal[i].moodForHighestPoint;
                        innerres.moodForLowestPoint = memberbeyondverbal[i].moodForLowestPoint;
                        innerres.temperValue = Math.ceil(memberbeyondverbal[i].temperValue);
                        innerres.created_at = moment(memberbeyondverbal[i].created_at).format('DD/MM/YYYY hh:mm A');
                        resarr[i] = innerres;
                        if(i<10){
                            chartArr.push(Math.ceil(memberbeyondverbal[i].valence_score));
                        }
                    }
                    response.data = resarr;
                    response.chartInfo = chartArr.reverse();
                    response.valence_score_label = valence_score_label;
                    response.today_valence_score_count = today_valence_score_count;
                    response.msg = 1;
                    res.json(response); 
                }).sort({created_at:'desc'});
            }
            else {
                response.msg = 0;
                response.mobile_message = 'Member not exist.';
                res.json(response);
            }
        });
    }
    else {
        response.msg = 0;
        response.mobile_message = 'Sorry, you have not passed all parameters.';
        res.json(response);
    }
};

// Provisioning User
exports.postProvisioningUser = function(req, res, member_id, firstname, lastname,photo, validic_uid, validic_access_token, badtime, wakeup) {
    var resarr = new Object();
    var request = require('request');
    var myObj = {
        user: { uid: member_id}, 
        access_token: '1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5'
    }

    request({
        url: "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users.json",
        method: "POST",
        json: true,   // <--Very important!!!
        body: myObj
    }, function (error, response, body){
        //console.log(body);
        if(body.code==201){
            Member.findByIdAndUpdate(member_id, { validic_uid: body.user._id,validic_access_token : body.user.access_token, otp : '', otp_posted : ''}, function(err, member) {});

            var marketplaceurl = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+body.user.access_token;
            resarr.data = {'member_id' : member_id,'firstname' : firstname,'lastname' : lastname,'photo' : photo,'validic_uid' : body.user._id, 'validic_access_token' : body.user.access_token, 'marketplaceurl' : marketplaceurl,'badtime' : badtime,'wakeup' : wakeup};
            resarr.msg = 1;
            res.json(resarr);
        }
        else {
            Member.findByIdAndUpdate(member_id, {otp : '', otp_posted : ''}, function(err, member) {});
            var marketplaceurl = "https://app.validic.com/58eb9ceeff9d9300800000ad/"+validic_access_token;
            resarr.data = {'member_id' : member_id,'firstname' : firstname,'lastname' : lastname,'photo' : photo,'validic_uid' : validic_uid, 'validic_access_token' : validic_access_token, 'marketplaceurl' : marketplaceurl,'badtime' : badtime,'wakeup' : wakeup};
            resarr.msg = 1;
            res.json(resarr);
        }
    });
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

exports.postCurrentTimeNoSec = function(req, res) {
    var currentTime = new Date();
    var hrs = currentTime.getHours();
    hrs = ('0' + hrs).slice(-2);
    var mins = currentTime.getMinutes();
    mins = ('0' + mins).slice(-2);
    var sec = currentTime.getSeconds();
    sec = ('0' + sec).slice(-2);
    return hrs+':'+mins;
};

// save validic calories burned
exports.postSaveValidicCaloriesBurned = function(req, res,member_id,startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var apiRes = new Object();
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

// save validic sleep info
exports.postSaveValidicSleep = function(req, res,member_id,startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var apiRes = new Object();
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

// save validic steps
exports.postSaveValidicSteps = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var apiRes = new Object();
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

// save validic heart rate
exports.postSaveValidicHeartRate = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var apiRes = new Object();
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

exports.postDeleteValidicRecords = function(req, res){
    var async = require('async');
    var currentDate = _this.postCurrentDate(req,res),
    d = new Date(currentDate);
    d.setMonth(d.getMonth() - 3);
    var startdate = _this.formatDate(req,res,d);
    var startDateFormatted = startdate+'T00:00:00.000Z';

    Member.find(function(err, member) {
        if(member.length>0){
            async.forEachSeries(member, function(n1, callback_s1) {
                var whereObj = {
                    member_id:n1._id,
                    timestamp:{$lte:startDateFormatted}
                }
                MemberCalories.remove(whereObj, function(err, recCal) {
                    MemberSleep.remove(whereObj, function(err, recSl) {
                        MemberSteps.remove(whereObj, function(err, recSteps) {
                            MemberHeartBitRate.remove(whereObj, function(err, recHeartRate) {
                                MemberExercise.remove(whereObj, function(err, recExercise) {
                                    MemberActiveMinutes.remove(whereObj, function(err, recActiveMins) {
                                        MemberSleepDetail.remove(whereObj, function(err, recSlDetail) {
                                            callback_s1();
                                        });
                                    });
                                });
                            });
                        });
                    });
                });
            }, function (err) {
                return;
            });
        }
    });
};

exports.postSaveInfo = function(req, res) {
    var apiRes = new Object;
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
                                        apiRes.msg = 1;
                                        res.json(apiRes);   
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
                apiRes.msg = 1;
                res.json(apiRes);
            }, 15000);
        }
        else {
            apiRes.msg = 0;
            res.json(apiRes);       
        }
    }
    else {
        apiRes.msg = 2;
        res.json(apiRes);
    }
};

exports.postSaveInfoCallAtThricePerDay = function(req, res) {
    var async = require('async'),
    currentDate = _this.postCurrentDate(req,res),
    d = new Date(currentDate);
    d.setMonth(d.getMonth() - 3);
    var startdate = _this.formatDate(req,res,d);

    Member.find(function(err, members) {
        async.forEachSeries(members, function(singleRec, callback_singleRec) {
            _this.postSaveValidicSleep(req,res,singleRec._id,startdate);
            _this.postSaveValidicHeartRate(req,res,singleRec._id,startdate);
            _this.postSaveValidicExercise(req,res,singleRec._id,startdate);
            _this.postSaveValidicActiveMinutes(req,res,singleRec._id,startdate);
            _this.postSaveValidicSleepDetails(req,res,singleRec._id,startdate);
            callback_singleRec();
        }, function (err) {
            return;
        });
    });
};

exports.postSaveInfoCallAtEvery5Mins = function(req, res) {
    var async = require('async'),
    currentDate = _this.postCurrentDate(req,res),
    d = new Date(currentDate);
    d.setMonth(d.getMonth() - 3);
    var startdate = _this.formatDate(req,res,d);
    Member.find(function(err, members) {
        async.forEachSeries(members, function(singleRec, callback_singleRec) {
            _this.postSaveValidicCaloriesBurned(req,res,singleRec._id,startdate);
            _this.postSaveValidicSteps(req,res,singleRec._id,startdate);    
            callback_singleRec();
        }, function (err) {
            return;
        });
    });
};

// save sleep details
exports.postSaveValidicSleepDetails = function(req, res, member_id, startDate) {
    var currentDate = _this.postCurrentDate(req,res);
    var currentTime = _this.postCurrentTime(req,res);
    var apiRes = new Object();
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
    var apiRes = new Object();
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
    var apiRes = new Object();
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

// validic sleep
exports.postSaveValidic = function(req, res) {
    var startDate = "2017-04-01";
    var currentDate = _this.postCurrentDate(req,res);
    var apiRes = new Object();
    if(req.body.member_id && req.body.type){
        Member.findOne({ _id: req.body.member_id }, function(err, member) {
            if(member){
                var async = require('async');
                var request = require('request');
                if(req.body.type=='caloriesburned'){
                    var calories_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(calories_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        async.forEachSeries(myjson.routine, function(n1, callback_s1) {
                            var res_timestamp = n1.timestamp;
                            res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                            res_timestamp = res_timestamp.replace('T', ' ');
                            var res_timestamp = new Date(res_timestamp);
                            var match_month = (res_timestamp.getMonth()+1);
                            match_month = ('0' + match_month).slice(-2);
                            var match_day = (res_timestamp.getDate());
                            match_day = ('0' + match_day).slice(-2);
                            var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                            if(n1.calories_burned!=0){
                                var storeCal = new Object;
                                storeCal.member_id = req.body.member_id;
                                storeCal.total_calories = n1.calories_burned;
                                storeCal.created_at = res_timestamp_str;
                                MemberCalories.findOne({ member_id: req.body.member_id,created_at:res_timestamp_str}, function(err, cal) {
                                    if(!cal){
                                        var ca = new MemberCalories(storeCal);
                                        ca.save(function(err) { 
                                            callback_s1();
                                        });     
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
                            apiRes.msg = 1;
                            res.json(apiRes);
                        });
                    });
                }
                else if(req.body.type=='sleep'){
                    var sleep_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/sleep.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(sleep_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        async.forEachSeries(myjson.sleep, function(n2, callback_s2) {
                            var res_timestamp = n2.timestamp;
                            res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                            res_timestamp = res_timestamp.replace('T', ' ');
                            var res_timestamp = new Date(res_timestamp);
                            var match_month = (res_timestamp.getMonth()+1);
                            match_month = ('0' + match_month).slice(-2);
                            var match_day = (res_timestamp.getDate());
                            match_day = ('0' + match_day).slice(-2);
                            var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                            
                            if(n2.total_sleep!=0){
                                var storeSleep = new Object;
                                storeSleep.member_id = req.body.member_id;
                                var x = n2.total_sleep;
                                x = x*1000;
                                var d = moment.duration(x, 'milliseconds');
                                var hours = Math.floor(d.asHours());
                                storeSleep.total_original_sleep = n2.total_sleep;
                                var mins = Math.floor(d.asMinutes()) - hours * 60;
                                storeSleep.total_formatted_sleep = hours+" hr "+mins+" min";
                                storeSleep.total_sleep = hours;
                                storeSleep.created_at = res_timestamp_str;
                                MemberSleep.findOne({ member_id: req.body.member_id,created_at:res_timestamp_str}, function(err, sleep) {
                                    if(!sleep){
                                        var sl = new MemberSleep(storeSleep);
                                        sl.save(function(err) {
                                            callback_s2();
                                        });
                                    }
                                    else {
                                        callback_s2();
                                    }
                                });
                            }
                            else {
                                callback_s2();
                            }
                        }, function (err) {
                            apiRes.msg = 1;
                            res.json(apiRes);
                        });
                    }); 
                }
                else if(req.body.type=='steps'){
                    var steps_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=0";
                    request(steps_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        async.forEachSeries(myjson.routine, function(n3, callback_s3) {
                            var res_timestamp = n3.timestamp;
                            res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                            res_timestamp = res_timestamp.replace('T', ' ');
                            var res_timestamp = new Date(res_timestamp);
                            var match_month = (res_timestamp.getMonth()+1);
                            match_month = ('0' + match_month).slice(-2);
                            var match_day = (res_timestamp.getDate());
                            match_day = ('0' + match_day).slice(-2);
                            var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                            
                            if(n3.steps!=0){
                                var storeStep = new Object;
                                storeStep.member_id = req.body.member_id;
                                storeStep.total_steps = n3.steps;
                                storeStep.created_at = res_timestamp_str;
                                MemberSteps.findOne({ member_id: req.body.member_id,created_at:res_timestamp_str}, function(err, step) {
                                    if(!step){
                                        var st = new MemberSteps(storeStep);
                                        st.save(function(err) { 
                                            callback_s3();
                                        });     
                                    }
                                    else {
                                        callback_s3();
                                    }
                                });
                            }
                            else {
                                callback_s3();
                            }
                        }, function (err) {
                            apiRes.msg = 1;
                            res.json(apiRes);
                        });
                    });
                }
                else if(req.body.type=='heartrate'){
                    var heartrate_url = "https://api.validic.com/v1/organizations/58eb9ceeff9d9300800000ad/users/"+member.validic_uid+"/routine.json?access_token=1cfc38b63c8e0ed5cb8f37d1815ed6e1774f828be3558e75bfe259f2efc671d5&start_date="+startDate+"T00:00:00+00:00&end_date="+currentDate+"T23:59:59+00:00&expanded=1";
                    request(heartrate_url, function (error, response, body) {
                        var myjson = JSON.parse(body);
                        async.forEachSeries(myjson.routine, function(n4, callback_s4) {
                            var res_timestamp = n4.timestamp;
                            res_timestamp = res_timestamp.substring(0, res_timestamp.indexOf("+"));
                            res_timestamp = res_timestamp.replace('T', ' ');
                            var res_timestamp = new Date(res_timestamp);
                            var match_month = (res_timestamp.getMonth()+1);
                            match_month = ('0' + match_month).slice(-2);
                            var match_day = (res_timestamp.getDate());
                            match_day = ('0' + match_day).slice(-2);
                            var res_timestamp_str = res_timestamp.getFullYear()+'-' +match_month+ '-'+match_day;
                            
                            if(n4.resting_heart_rate!=0 && n4.resting_heart_rate!=null){
                                var storeHeart = new Object;
                                storeHeart.member_id = req.body.member_id;
                                storeHeart.total_heartrate = n4.resting_heart_rate;
                                storeHeart.created_at = res_timestamp_str;
                                MemberHeartBitRate.findOne({ member_id: req.body.member_id,created_at:res_timestamp_str}, function(err, heartrate) {
                                    if(!heartrate){
                                        var hr = new MemberHeartBitRate(storeHeart);
                                        hr.save(function(err) { 
                                            callback_s4();
                                        });     
                                    }
                                    else {
                                        callback_s4();
                                    }
                                });
                            }
                            else {
                                callback_s4();
                            }
                        }, function (err) {
                            apiRes.msg = 1;
                            res.json(apiRes);
                        });
                    });
                }
                else {
                    apiRes.msg = 2;
                    res.json(apiRes);
                }
            }   
            else {
                apiRes.msg = 3;
                res.json(apiRes);
            }
        });
    }
    else {
        apiRes.msg = 0;
        res.json(apiRes);
    }
};