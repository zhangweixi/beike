angular.module('myapp',['ui.router'])
    .config(function($stateProvider,$urlRouterProvider){
        $urlRouterProvider.otherwise('/index');

        $stateProvider
            .state('fruit',{
                url:'/fruit',
                templateUrl:'fruit.html'
            })
            .state('vegetable',{
                url:'/vegetable',
                templateUrl:'vegetable.html'
            })
            .state('index',{
                url:'/index',
                template:'<h2>这是首页</h2>'
            })
            .state('fruit.orange',{
                url:'/orange',
                templateUrl:'orange.html',
            })
            .state('fruit.apple',{
                url:'/apple',
                templateUrl:'apple.html'
            })
            .state('fruit.banana',{
                url:'/banana',
                templateUrl:'banana.html'
            });

    });



