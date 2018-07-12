
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
                }else{

                    alert(res.message);

                }
            });
    }
})


var myapp = angular.module('myapp',['ui.router','tm.pagination']);
    myapp.config(function($stateProvider,$urlRouterProvider)
    {
        //$urlRouterProvider.otherwise('/');
        $stateProvider
            .state('question-list',{
                url:'/question-list',
                templateUrl:'question-list.html?t='+Math.random(),
                controller:'questionController'
            })
            .state('question-upload',{
                url:'/question-upload',
                templateUrl:'question-upload.html?t='+Math.random(),
                controller:'questionController'
            })
            .state('user-list',{
                url:'/user-list',
                templateUrl:'user-list.html?t='+Math.random(),
                controller:'userController'
            })
            .state('department',{
                url:"/department",
                templateUrl:'department.html?t='+Math.random(),
                controller:'userController'
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


myapp.controller('questionController',function($scope,$http,$location){

    setTimeout(init_DataTables,1000);
    $scope.excel        = "";
    $scope.questions    = [];
    $scope.hasQuestion  = false;
    $scope.addBtnText   = "若检查无误，点此提交题库";
    $scope.disableAddBtn = false;

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function(){
            $scope.get_question_list($scope.paginationConf.currentPage);
        }
    };


    /*获得题目列表*/
    $scope.get_question_list = function(page)
    {
        if(page == 0) {

            return;
        }
        var url = server + "questions?page=" + page;
            console.log($location);
        $http.get(url).success(function(res)
        {
            var questionData = res.data.question;

            $scope.paginationConf.currentPage   = questionData.current_page;
            $scope.paginationConf.totalItems    = questionData.total;
            $scope.paginationConf.itemsPerPage  = questionData.per_page;
            $scope.questions                    = questionData.data;

            console.log($scope.questions);
        });
    }



    $scope.upload_excel = function(){

        $scope.hasQuestion = false;

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
        }).success(function (res) {

            if(res.code == 200)
            {
                $scope.excel = res.data.filepath;

                $scope.read_excel();
            }


        }).error(function (data) {




        })
    }


    /*读取excel*/
    $scope.read_excel = function(){


        var url = server + "read_question";

        $http.post(url,{filepath:$scope.excel})
            .success(function(res){

                if(res.code == 200)
                {
                    $scope.hasQuestion = true;

                    $scope.questions = res.data.questions;

                    for(var q of $scope.questions)
                    {
                        switch (q.type)
                        {
                            case "radio":   q.type = '单选';break;
                            case "checkbox":q.type = "多选";break;
                            case "judge":   q.type = "判断";break;
                        }
                    }
                }else{

                    alert(res);
                }
            });
    }


    /*添加问题*/
    $scope.add_question = function(){

        if(!confirm('确定导入吗？'))
        {
            return false;
        }

        if($scope.disableAddBtn == true)
        {
            return ;
        }
        $scope.disableAddBtn = true;

        var url = server + "read_question";
        var data= {filepath:$scope.excel,isSave:1};

        $scope.addBtnText = "正在提交，请稍等...";


        $http.post(url,data)
            .success(function(res){

                $scope.disableAddBtn = false;
                $scope.addBtnText = "若检查无误，点此提交题库";

                if(res.code == 200)
                {
                    alert('导入成功');

                }else{
                    alert(res);
                }
            });
    }



    $scope.init = function()
    {
        var path = $location.url();
        switch (path)
        {
            case '/question-list':$scope.get_question_list(1);break;

        }
    }

    $scope.init();

})


myapp.controller('userController',function($scope,$http,$location){


    $scope.users        = [];
    $scope.departments  = [];

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function(){
            $scope.get_user_list($scope.paginationConf.currentPage);
        }
    };


    /*获得用户列表*/
    $scope.get_user_list = function(page)
    {
        if(page  == 0)
        {
            return;
        }
        var url = server + 'users?page='+page;
        $http.get(url).success(function(res){


            if(res.code == 200)
            {
                var users = res.data.users;

                $scope.paginationConf.currentPage   = users.current_page;
                $scope.paginationConf.totalItems    = users.total;
                $scope.paginationConf.itemsPerPage  = users.per_page;
                $scope.users                        = users.data;
            }
        });
    }


    $scope.quit_department = function(userSn,depId)
    {
        if(!confirm('确定移出本部门吗？'))
        {
            return false;
        }

        var url = server + "quit_department";
        var data = {userSn:userSn,depId:depId};

        $http.post(url,data).success(function(res){


            $scope.get_user_list($scope.paginationConf.currentPage);

        });

    }

    /*获取部门列表*/
    $scope.get_department_list = function(){

        var url = server + "departments";
        $http.get(url).success(function(res){


            $scope.departments = res.data.departments;

        });
    }


    /*
    * 改变部门状态
    * */
    $scope.change_pk_status = function(id,status)
    {

        var url = server + "change_pk_status";
        var data = {id:id,status:status};
        $http.post(url,data).success(function(res){


            if(res.code == 200)
            {

                $scope.get_department_list();
            }else{

                alert('设置失败');
            }

        });

    }



    $scope.init = function()
    {
        var path = $location.url();
        switch (path)
        {
            case '/user-list':$scope.get_user_list(1);break;
            case '/department':$scope.get_department_list();break;
        }
    }

    $scope.init();


})








