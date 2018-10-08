
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
    //设备
        .state('device/list', {
            url: '/device/list',
            templateUrl: 'device-list.html?t=' + Math.random(),
            controller: 'deviceController'
        })
        .state('device/edit/:deviceId', {
            url: '/device/edit/:deviceId',
            templateUrl: 'device-add.html?t=' + Math.random(),
            controller: 'deviceController'
        })
        .state('question-upload', {
            url: '/question-upload',
            templateUrl: 'question-upload.html?t=' + Math.random(),
            controller: 'questionController'
        })
        //用户
        .state('user/list', {
            url: '/user/list',
            templateUrl: 'user-list.html?t=' + Math.random(),
            controller: 'userController'
        })

        //比赛
        .state('match/list/:page', {
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
        //球场
        .state('court/angle-setting/:courtTypeId',{
            url:"/court/angle-setting/:courtTypeId",
            templateUrl:'court-angle-setting.html?t='+Math.random(),
            controller:'courtController'
        })
        .state('court/type',{
            url:'/court/type',
            templateUrl:'court-type.html?t='+Math.random(),
            controller:'courtController'
        })

        .state('court/list', {
            url: "/court/list",
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
        });


});
