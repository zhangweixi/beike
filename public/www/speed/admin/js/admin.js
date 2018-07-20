
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
            })
            .state('count-department',{
                url:"/count-department",
                templateUrl:'count-department.html?t='+Math.random(),
                controller:'countController'
            })
            .state('count-user',{
                url:'/count-user',
                templateUrl:'count-user.html?t='+Math.random(),
                controller:'countController'
            })
            .state('admin-list',{
                url:'/admin-list',
                templateUrl:'admin-list.html?m='+Math.random(),
                controller:'adminController'
            })
            .state('admin-add/:id',{
                url:"/admin-add/:id",
                templateUrl:'admin-add.html?t=' + Math.random(),
                controller:'adminController'
            })
            .state('paper-setting',{
                url:"/paper-setting",
                templateUrl:'paper-setting.html?t='+Math.random(),
                controller:'paperController'
            })
            .state('paper-create',{
                url:"/paper-create",
                templateUrl:'paper-create.html?t='+Math.random(),
                controller:'paperController'
            })
            .state('paper-list',{
                url:'/paper-list',
                templateUrl:'paper-list.html?t='+Math.random(),
                controller:'paperController'
            });
    });



myapp.controller('indexController',function($scope,$location,$http){

    $scope.admin = {};

    //检查是否登录
    var token   = getCookie('adminToken');
    if(!token)
    {
        location.href = "./login.html";

        return ;
    }


    $scope.admin_info = function()
    {
        var url = server + "get_admin_info_by_token";

        $http.post(url,{token:token}).success(function(res){

            $scope.admin = res.data.adminInfo;

        });
    }



    //退出
    $scope.login_out = function(){

        var token   = getCookie('adminToken');
        var url     = server + "login_out";

        $http.post(url,{token:token}).success(function(res){

            setCookie('adminToken','',0);
            location.href = "./login.html";

        });
    }

    $scope.admin_info();
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
        pagesLength: 10,
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
    $scope.userKeyWrods = "";//搜索用户关键字
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
        var url = server + 'users?page=' + page + "&keywords="+$scope.userKeyWrods;

        $http.get(url).success(function(res)
        {
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


    $scope.down_department = function(){


        var url = server + "down_department";
        $http.get(url).success(function(){

            alert('同步完成');
            $scope.get_department_list();
        })

    }

    $scope.down_user = function()
    {

        var url = server + "down_all_users";

        $http.get(url).success(function(){
            alert('同步成功');
            $scope.get_user_list(0);
        })
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

myapp.controller('countController',function($scope,$http,$location){

    $scope.beginDate    = new Date()
    $scope.endDate      = new Date();

    $scope.avgChart = {};
    $scope.percentChart = {};
    $scope.departments = new Array();
    $scope.departments1 = new Array();


    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function(){

            $scope.get_user_data($scope.paginationConf.currentPage);

        }
    };



    /*获取部门数据*/
    $scope.get_department_data = function()
    {
        //myChart.title = '世界人口总量 - 条形图';
        var url = server + "count_department";
        var data = {
            beginDate: GMTToStr($scope.beginDate,'date'),
            endDate:GMTToStr($scope.endDate,'date')
        };

        $http.post(url,data).success(function(res){

            var departNames = new Array();
            var avgData     = new Array();
            var percentData = new Array();
            $scope.departments = res.data.departments;
            
            $scope.departments1 = new Array();
            for(var depart of res.data.departments)
            {
                departNames.push(depart.name);
                avgData.push(depart.avgGrade);
                percentData.push(depart.percent);
                $scope.departments1.splice(0,0,depart);
            }
            $scope.set_department_avg(departNames,avgData,percentData);
        })
    }


    $scope.set_department_avg = function(yAxisData,avgData,percentData){

        var option = {
            title: {
                text: '部门答题统计',
                subtext: '数据来自后台统计'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                data: ['平均分','完成率']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'value',
                boundaryGap: [0, 0.01]
            },
            yAxis: {
                type: 'category',
                data: yAxisData
            },
            series: [
                {
                    name: '平均分',
                    type: 'bar',
                    label:{ 
                        normal:{ 
                            show: true, 
                            position: "right"
                        } 
                    },
                    data: avgData
                },
                {
                    name: '完成率',
                    type: 'bar',
                    label:{ 
                        normal:{ 
                            show: true, 
                            position: "right"
                        } 
                    },
                    data: percentData
                }
            ]
        };
        $scope.avgChart.setOption(option);
    }


    /*获取用户数据*/
    $scope.get_user_data = function(page)
    {
        if(page == 0) return;
        var url         = server + "count_user";
        var beginDate   = GMTToStr($scope.beginDate,'date');
        var endDate     = GMTToStr($scope.endDate,'date');
        var data = {beginDate:beginDate,endDate:endDate,page:page};
        $http.post(url,data).success(function(res)
        {
            var data    = res.data.users;
            $scope.paginationConf.currentPage = data.current_page;
            $scope.paginationConf.itemsPerPage= data.per_page;
            $scope.paginationConf.totalItems  = data.total;
            $scope.users = res.data.users.data;

        })
    }


    $scope.init = function()
    {
        var path = $location.url();
        if(path == "/count-department"){

            $scope.avgChart     = echarts.init(document.getElementById('avggrade'));
            $scope.get_department_data();

        }else if(path == "/count-user") {

            $scope.get_user_data(1);
        }
    }
    $scope.init();

})

myapp.controller('adminController',function($scope,$http,$location,$stateParams){

    $scope.admins = new Array();
    $scope.adminId= $stateParams.id;
    $scope.adminInfo = {

        admin_id:0,
        name:"",
        password:''
    };

    console.log($stateParams);

    $scope.admin_list = function()
    {
        var url = server + "admin_list";

        $http.get(url).success(function(res){

            $scope.admins = res.data.admins;

        });
    }

    $scope.delete_admin = function(adminId)
    {
        if(!confirm('确定删除吗')) return false;
        var url = server + "delete_admin?adminId="+adminId;
        $http.get(url).success(function(res){

            $scope.admin_list();

        });
    }

    $scope.get_admin_info = function(){


        var url = server + "get_admin_info?adminId="+$scope.adminId;
        if($scope.adminId  == 0) return false;
        $http.post(url,$scope.adminInfo).success(function(res){

            if(res.data.adminInfo)
            {
                $scope.adminInfo            = res.data.adminInfo;
                $scope.adminInfo.password   = "";    
            }
        });
    }

    $scope.edit_admin = function()
    {
        var url = server + "edit_admin";
        $http.post(url,$scope.adminInfo).success(function(res){

            if(res.code == 200)
            {
                alert("添加成功");
                $location.path('admin-list');
            }

        });

    }

})


myapp.controller('paperController',function($scope,$http,$location){

    $scope.variables        = {};
    $scope.questionNumber   = 0;
    $scope.beginDate        = new Date();
    $scope.paperNumber      = 1;
    $scope.papers           = new Array();
    $scope.isFenfa          = false;

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 10,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function(){
            $scope.get_paper_list($scope.paginationConf.currentPage);
        }
    };

    $scope.get_variable = function()
    {
        var url = server + "get_variable";

        $http.get(url).success(function(res){


            $scope.variables = res.data.variables;

        });
    }

    /*修改信息*/
    $scope.update_variable = function()
    {

        var data = {};

        for(var obj in $scope.variables)
        {
            data[obj] = $scope.variables[obj].value;
        }

        var url = server + "update_variable";
        $http.post(url,data).success(function(res){

            alert(res.message);

        });
    }

    //获取剩余数量
    $scope.get_question_number = function()
    {
        var url = server + "get_surplus_question";
        $http.get(url).success(function(res){

            $scope.questionNumber = res.data.questionNumber;

        });
    }


    //创建试卷
    $scope.create_paper = function()
    {
        var data = {
            'paperNumber':$scope.paperNumber,
            'beginDate':GMTToStr($scope.beginDate)
        };

        var url = server + "create_paper";
        $scope.isFenfa = true;
        $http.post(url,data).success(function(res){
            
            $scope.isFenfa = false;
            alert(res.message);
            if(res.code == 200)
            {
                $scope.papers = res.data.papers;
            }

        });
    }

    $scope.get_paper_list = function(page)
    {

        if(page == 0) return;
        var url = server + "get_paper_list?page="+page;
        $http.get(url).success(function(res){

            var paperData = res.data.papers;

            $scope.paginationConf.currentPage   = paperData.current_page;
            $scope.paginationConf.totalItems    = paperData.total;
            $scope.paginationConf.itemsPerPage  = paperData.per_page;
            $scope.papers                       = paperData.data;

        });
    }

    $scope.init = function()
    {
        var path = $location.url();
        if(path == "/paper-setting"){

            $scope.get_variable();

        }else if(path == "/paper-create") {

            $scope.get_question_number();

        }else if(path == '/paper-list'){

            $scope.get_paper_list(1);
        }
    }




    $scope.init();


})
function GMTToStr(time,type){
    var date    = new Date(time)

    var year    = getfull_time(date.getFullYear());
    var month   = getfull_time(date.getMonth()+1);
    var day     = getfull_time(date.getDate());
    var hour    = getfull_time(date.getHours());
    var min     = getfull_time(date.getMinutes());
    var sen     = getfull_time(date.getSeconds());

        date    = year + "-" + month + "-" + day;
        time    = hour + ":" + min   + ":" + sen;

        if(type == 'date'){

            return date;
        }

        if(type == 'time')
        {
            return time;
        }

        return date + " " + time;
}


function getfull_time(num){

    if(num < 10)
    {
        return "0"+num;
    }else{
        return num;
    }
}
