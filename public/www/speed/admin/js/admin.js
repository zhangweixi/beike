
function setCookie(c_name,value,expiredays)
{
    var exdate=new Date()
    exdate.setDate(exdate.getDate()+expiredays)
    document.cookie=c_name+ "=" +escape(value)+
        ((expiredays==null) ? "" : ";expires="+exdate.toGMTString())
}

function getCookie(c_name)
{
    if (document.cookie.length>0)
    {
        c_start=document.cookie.indexOf(c_name + "=")
        if (c_start!=-1)
        {
            c_start=c_start + c_name.length+1
            c_end=document.cookie.indexOf(";",c_start)
            if (c_end==-1) c_end=document.cookie.length
            return unescape(document.cookie.substring(c_start,c_end))
        }
    }
    return ""
}

function getQueryVariable(variable)
{
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if(pair[0] == variable){return pair[1];}
    }
    return(false);
}


function setTitle(t) {

    document.title = t;
    var i = document.createElement('iframe');
    i.src = '//m.baidu.com/favicon.ico?time='+Math.random();
    i.style.display = 'none';
    i.onload = function() {
        setTimeout(function(){
            i.remove();
        }, 9)
    }
    document.body.appendChild(i);
}



var server = location.origin + "/api/speed/admin/";

var mylogin = angular.module('mylogin',[]);

/*登录控制器*/
mylogin.controller('loginController',function($scope,$http){

    $scope.name = "";
    $scope.password = "";

    $scope.login = function(){

        var url = server + "login";

        $http.post(url,{name:$scope.name,password:$scope.password})
            .success(function(res){

                if(res.code == 200)
                {
                    setCookie('adminToken',res.data.adminToken);
                    //缓存登录信息
                    location.replace("./index.html");
                }
            });
    }
})


var myapp = angular.module('myapp',['ui.router']);
    myapp.config(function($stateProvider,$urlRouterProvider)
    {
        //$urlRouterProvider.otherwise('/');
        $stateProvider
            .state('fruit',{
                url:'/fruit',
                templateUrl:'fruit.html'
            })
            .state('question-list',{
                url:'/question-list',
                templateUrl:'question-list.html',
                controller:'questionController'
            })
            .state('question-upload',{
                url:'/question-upload',
                templateUrl:'question-upload.html',
                controller:'questionController'
            })
            .state('index1',{
                url:'/index1',
                template:'<h1>index2</h1>'
            })
            .state('fruit.orange',{
                url:'/orange',
                templateUrl:'orange.html',
            });
    });



myapp.controller('indexController',function($scope,$location){

    $scope.admin = {name:'zhangweixi'};

    //alert();
    $scope.tiao = function()
    {
        $location.path('index1');
    }


});


myapp.controller('questionController',function($scope,$http){

    setTimeout(init_DataTables,1000);

    $scope.upload_excel = function(){

        var form = new FormData();
        var file = document.getElementById("excel").files[0];
        //var user =JSON.stringify($scope.user);

        form.append('file', file);

        //传递参数
        //form.append('user',user);
        //var url = server + "upload_excel";
        var url = "/service/upload";

        $http({
            method: 'POST',
            url: url,
            data: form,
            headers: {'Content-Type': undefined},
            transformRequest: angular.identity
        }).success(function (data) {
            console.log('operation success');

        }).error(function (data) {

            console.log('operation fail');

        })


    }


})









