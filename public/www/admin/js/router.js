
mylogin = angular.module('mylogin', [])
    .config(function($httpProvider){
        var  token = getCookie("csrftoken");
        $httpProvider.defaults.headers.post = {"Content-Type":"application/x-www-form-urlencoded",'X-CSRFToken':token};
    });


myapp = angular.module('myapp', ['ui.router', 'tm.pagination'])
    .config(function($httpProvider){
        var  token = getCookie("csrftoken");
        $httpProvider.defaults.headers.post = {"Content-Type":"application/x-www-form-urlencoded",'X-CSRFToken':token};
    });


myapp.config(function ($stateProvider, $urlRouterProvider) {
    //$urlRouterProvider.otherwise('/');
    $stateProvider
        .state('device/list', { //设备
            url: '/device/list/:page/:keywords',
            templateUrl: 'device-list.html?t=' + Math.random(),
            controller: 'deviceController'
        })
        .state('device/edit', {
            url: '/device/edit/:deviceId',
            templateUrl: 'device-add.html?t=' + Math.random(),
            controller: 'deviceController'
        })
        .state('device/device-qr',{
            url:"/device/device-qr",
            templateUrl:'device-qr.html?t=' + Math.random(),
            controller:'deviceController'
        })
        .state('device/device-code/:page', {
            url: '/device/device-code/:page',
            templateUrl: 'device-code.html?t=' + Math.random(),
            controller: 'deviceController'
        })
        .state('user/list', { //用户
            url: '/user/list/:page/:keywords',
            templateUrl: 'user-list.html?t=' + Math.random(),
            controller: 'userController'
        })
        .state("user/suggestions/:page",{ //用户反馈
            url:"/user/suggestions/:page",
            templateUrl:"user-suggestion.html?t=" + Math.random(),
            controller:"userController"
        })
        .state('match/list/:page', {//比赛
            url: "/match/list/:page",
            templateUrl: 'match-list.html?t=' + Math.random(),
            controller: 'matchController'
        })
        .state('match/court/:matchId',{
            url: "/match/court/:matchId",
            templateUrl: "match-court.html?t=" + Math.random(),
            controller: 'matchController'
        })
        .state('match/result/:matchId',{
            url: "/match/result/:matchId",
            templateUrl: "match-result.html?t=" + Math.random(),
            controller: 'matchController'
        })
        .state('match/files/:matchId',{
            url: "/match/files/:matchId",
            templateUrl: "match-files.html?t=" + Math.random(),
            controller: 'matchController'
        })
        .state('match/compass-map/:file',{
            url:"/match/compass-map/:file",
            templateUrl:"match-compass-map.html?t="+Math.random(),
            controller:'matchController'
        })
        .state('match/run/:matchId',{
            url:"/match/run/:matchId",
            templateUrl:"match-run.html?t="+Math.random(),
            controller:'matchController'
        })
        .state('court/angle-setting/:courtTypeId',{//球场
            url:"/court/angle-setting/:courtTypeId",
            templateUrl:'court-angle-setting.html?t='+Math.random(),
            controller:'courtController'
        })
        .state('court/type',{
            url:'/court/type',
            templateUrl:'court-type.html?t='+Math.random(),
            controller:'courtController'
        })
        .state('court/list/:page', {
            url: "/court/list/:page",
            templateUrl: 'court-list.html?t=' + Math.random(),
            controller: 'courtController'
        })
        .state('count-user', {
            url: '/count-user',
            templateUrl: 'count-user.html?t=' + Math.random(),
            controller: 'countController'
        })
        .state('admin-list', {
            url: '/admin-list',
            templateUrl: 'admin-list.html?m=' + Math.random(),
            controller: 'adminController'
        })
        .state('admin-add/:id', {
            url: "/admin-add/:id",
            templateUrl: 'admin-add.html?t=' + Math.random(),
            controller: 'adminController'
        })
        .state('sqmatch/list/:page',{
            url:'/sqmatch/list/:page',
            templateUrl:'sqmatch-list.html?t=' + Math.random(),
            controller:'sqmatchController'
        })
        .state('sqmatch/users/:matchId',{
            url:'/sqmatch/users/:matchId',
            templateUrl:'sqmatch-users.html?t=' + Math.random(),
            controller:'sqmatchController'
        })
        .state('system/logs',{
            url:'/system/logs',
            templateUrl:'system-logs.html?t='+Math.random(),
            controller:'systemController'
        });


});
