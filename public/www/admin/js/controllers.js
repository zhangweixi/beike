
var server = location.origin + "/api/admin/";

/*登录控制器*/
mylogin.controller('loginController', function ($scope, $http) {

    $scope.name = "";
    $scope.password = "";

    var loginCookie = getCookie('adminToken');

    if(loginCookie){

        location.href = "index.html";

        return;
    }

    $scope.login = function () {

        var url = server + "admin/login";
        var data = {name: $scope.name, password: $scope.password};
            data = http_query(data);

            $http.post(url, data).success(function (res) {

                if (res.code == 200) {

                    setCookie('adminToken', res.data.adminInfo.token);
                    //缓存登录信息
                    location.replace("./index.html");
                } else {

                    alert(res.msg);

                }
            });
    }
})



myapp.controller('indexController', function ($scope, $location, $http) {

    $scope.admin = {};

    //检查是否登录
    var token = getCookie('adminToken');
    if (!token)
    {
        location.href = "./login.html";
        return;
    }


    $scope.admin_info = function () {
        var url = server + "admin/get_admin_info_by_token";
        var data = http_query({token: token});
        $http.post(url, data).success(function (res) {

            if(res.code == 200){

                $scope.admin = res.data.admin;

            }else{
                setCookie("adminToken","",-10);
                location.href = "login.html";
                
            }
        });
    }


    //退出
    $scope.login_out = function () {

        var token = getCookie('adminToken');
        var url = server + "login_out";

        $http.post(url, {token: token}).success(function (res) {

            setCookie('adminToken', '', 0);
            location.href = "./login.html";

        });
    }

    $scope.admin_info();
});


myapp.controller('deviceController', function ($scope, $http, $location,$stateParams,$location) {

    setTimeout(init_DataTables, 1000);

    $scope.excel = "";
    $scope.devices = [];
    $scope.hasQuestion = false;
    $scope.addBtnText = "若检查无误，点此提交题库";
    $scope.disableAddBtn = false;
    $scope.showAddDeviceQr = false;
    $scope.newQrData = {
        prefix:"",
        num:1000,
        length:4,
        addnum:true
    };

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 10,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            if($scope.paginationConf.currentPage > 0)
            {
                $location.path('/device/list/'+$scope.paginationConf.currentPage);
            }
        }
    };

    $scope.deviceInfo   = {};

    $scope.deviceQrs    = {};


    /*获得题目列表*/
    $scope.get_device_list = function () {

        var page = $stateParams.page;

        if (page == 0) {

            return;
        }

        var url = server + "device/devices?page=" + page;
        console.log($location);
        $http.get(url).success(function (res) {

            var devices = res.data.devices;

            $scope.paginationConf.currentPage = devices.current_page;
            $scope.paginationConf.totalItems = devices.total;
            $scope.paginationConf.itemsPerPage = devices.per_page;
            $scope.devices = devices.data;

        });
    }



    /*获取设备信息*/
    $scope.get_device_info = function()
    {
        var deviceId    = $stateParams.deviceId;


        if(deviceId == 0){

            $scope.deviceInfo = {
                "device_id":$stateParams.deviceId,
                "device_sn":'',
                "bluetooth_r":'',
                "bluetooth_l":'',
                "pin":'',
            };
            return false;
        }

        var url = server + "device/get_device_info?deviceId="+deviceId;

        $http.get(url).success(function(res)
        {

            $scope.deviceInfo = res.data.deviceInfo;

        })
    }



    //编辑设备
    $scope.edit_device  = function()
    {
        var url = server + "device/edit_device";
        var data = http_query($scope.deviceInfo);
        $http.post(url,data).success(function(res)
        {
            alert(res.message);
        })
    }


    //删除设备
    $scope.delete_device = function(deviceId)
    {

        if(!confirm('确定删除吗')){

            return false;
        }

        var url =server + "device/delete_device?deviceId="+deviceId;

        $http.get(url).success(function(){

            $scope.get_device_list($scope.paginationConf.currentPage);
        })
    }

    $scope.delete_qrs = function(){

        alert('待开发');
    }

    $scope.download_qr = function(prefix){

    var url = server + "device/download_qr?prefix="+prefix;

    
    const xhr = new XMLHttpRequest();

    xhr.open('POST', url, true);        // 定义请求方式

    xhr.setRequestHeader('X-CSRF-TOKEN',$('meta[name="csrf-token"]').attr('content'));  // 添加 csrf 令牌

    xhr.responseType = "blob";    // 返回类型blob

    // 定义请求完成的处理函数，请求前也可以增加加载框/禁用下载按钮逻辑

    xhr.onload = function () {};

    // 发送ajax请求

    xhr.send()

    return;

        $http.get(url,{},{responseType: "blob"}).success(function(res){

        });

    }
    //添加新批次的二维码
    $scope.add_new_qrs = function(){

        if(!confirm('确定添加吗')){

            return false;
        }

        var url = server + "device/create_device_qr";
        var data = http_query($scope.newQrData);

        $http.post(url,data).success(function(res)
        {
            alert('已完成');
            $scope.get_device_qr();
            $scope.triggle_show_qr_from();
        });

    }

    $scope.triggle_show_qr_from = function(){

        $scope.showAddDeviceQr = !$scope.showAddDeviceQr;
    }

   

    $scope.get_device_qr = function(){

        var url = server + "device/get_device_qrs";

        $http.get(url).success(function(res){

            $scope.deviceQrs = res.data.qrs.data;

        });
    }

     //解绑设备
    $scope.unbind_device = function(deviceId){

        if(!confirm("确定解除绑定吗")){

            return false;
        }

        var url = server + "device/unbind_device?deviceId="+deviceId;

        $http.post(url).success(function(res)
        {
            if(res.code == 200){
                alert('已解绑');
                $scope.get_device_list();
            }
        });
    }

    $scope.upload_excel = function () {

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

            if (res.code == 200) {
                $scope.excel = res.data.filepath;

                $scope.read_excel();
            }


        }).error(function (data) {


        })
    }


    /*读取excel*/
    $scope.read_excel = function () {


        var url = server + "read_question";

        $http.post(url, {filepath: $scope.excel})
            .success(function (res) {

                if (res.code == 200) {
                    $scope.hasQuestion = true;

                    $scope.questions = res.data.questions;

                    for (var q of $scope.questions) {
                        switch (q.type) {
                            case "radio":
                                q.type = '单选';
                                break;
                            case "checkbox":
                                q.type = "多选";
                                break;
                            case "judge":
                                q.type = "判断";
                                break;
                        }
                    }
                } else {

                    alert(res);
                }
            });
    }


    /*添加问题*/
    $scope.add_question = function () {

        if (!confirm('确定导入吗？')) {
            return false;
        }

        if ($scope.disableAddBtn == true) {
            return;
        }
        $scope.disableAddBtn = true;

        var url = server + "read_question";
        var data = {filepath: $scope.excel, isSave: 1};

        $scope.addBtnText = "正在提交，请稍等...";


        $http.post(url, data)
            .success(function (res) {

                $scope.disableAddBtn = false;
                $scope.addBtnText = "若检查无误，点此提交题库";

                if (res.code == 200) {
                    alert('导入成功');

                } else {
                    alert(res);
                }
            });
    }
})


myapp.controller('userController', function ($scope, $http, $location,$stateParams) {


    $scope.users        = [];
    $scope.departments  = [];
    $scope.userKeyWrods = "";//搜索用户关键字
    $scope.suggestions  = [];

    // 用户列表分页配置
    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function () {
            if($scope.paginationConf.currentPage > 0)
            {
                $location.path('user/list/'+$scope.paginationConf.currentPage);
            }
        }
    };

    // 用户反馈分页配置
    $scope.suggestionConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function () {
            if($scope.suggestionConf.currentPage > 0)
            {
                $location.path('user/suggestions/'+$scope.suggestionConf.currentPage);
            }
        }
    };


    /*获得用户列表*/
    $scope.get_user_list = function () {
        var page = $stateParams.page;

        if (page == 0) {
            return;
        }

        var data = {page:page,keywords:$scope.userKeyWrods};
            data = http_query(data);

        var url = server + 'user/users';

        $http.post(url,data).success(function (res) {
            if (res.code == 200)
            {
                var users = res.data.users;

                $scope.paginationConf.currentPage   = users.current_page;
                $scope.paginationConf.totalItems    = users.total;
                $scope.paginationConf.itemsPerPage  = users.per_page;
                $scope.users = users.data;
            }
        });
    }


    $scope.get_suggestions = function () {

        var url = server + "user/suggestions?page=" + $stateParams.page;

        $http.post(url).success(function(res){

            var suggestion = res.data.suggestions;

            $scope.suggestions                  = suggestion.data;
            $scope.suggestionConf.currentPage   = suggestion.current_page;
            $scope.suggestionConf.totalItems    = suggestion.total;
            $scope.suggestionConf.itemsPerPage  = suggestion.per_page;
        })
    }

    $scope.down_user = function () {

        var url = server + "down_all_users";

        $http.get(url).success(function () {
            alert('同步成功');
            $scope.get_user_list(0);
        })
    }

    $scope.quit_department = function (userSn, depId) {
        if (!confirm('确定移出本部门吗？')) {
            return false;
        }

        var url = server + "quit_department";
        var data = {userSn: userSn, depId: depId};

        $http.post(url, data).success(function (res) {


            $scope.get_user_list($scope.paginationConf.currentPage);

        });

    }

    /*获取部门列表*/
    $scope.get_department_list = function () {

        var url = server + "departments";
        $http.get(url).success(function (res) {


            $scope.departments = res.data.departments;

        });
    }


    /*
    * 改变部门状态
    * */
    $scope.change_pk_status = function (id, status) {

        var url = server + "change_pk_status";
        var data = {id: id, status: status};
        $http.post(url, data).success(function (res) {


            if (res.code == 200) {

                $scope.get_department_list();
            } else {

                alert('设置失败');
            }

        });

    }
})

myapp.controller('countController', function ($scope, $http, $location) {

    $scope.beginDate = new Date()
    $scope.endDate = new Date();

    $scope.avgChart = {};
    $scope.percentChart = {};
    $scope.departments = new Array();

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function () {

            $scope.get_user_data($scope.paginationConf.currentPage);

        }
    };


    /*获取部门数据*/
    $scope.get_department_data = function () {
        //myChart.title = '世界人口总量 - 条形图';
        var url = server + "count_department";
        var data = {
            beginDate: GMTToStr($scope.beginDate, 'date'),
            endDate: GMTToStr($scope.endDate, 'date')
        };

        $http.post(url, data).success(function (res) {

            var departNames = new Array();
            var avgData = new Array();
            var percentData = new Array();
            $scope.departments = res.data.departments;
            for (var depart of res.data.departments) {
                departNames.push(depart.name);
                avgData.push(depart.avgGrade);
                percentData.push(depart.percent);
            }
            $scope.set_department_avg(departNames, avgData, percentData);
        })
    }


    $scope.set_department_avg = function (yAxisData, avgData, percentData) {

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
                data: ['平均分', '完成率']
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
                    data: avgData
                },
                {
                    name: '完成率',
                    type: 'bar',
                    data: percentData
                }
            ]
        };
        $scope.avgChart.setOption(option);
    }


    /*获取用户数据*/
    $scope.get_user_data = function (page) {
        if (page == 0) return;
        var url = server + "count_user";
        var beginDate = GMTToStr($scope.beginDate, 'date');
        var endDate = GMTToStr($scope.endDate, 'date');
        var data = {beginDate: beginDate, endDate: endDate, page: page};
        $http.post(url, data).success(function (res) {
            var data = res.data.users;
            $scope.paginationConf.currentPage = data.current_page;
            $scope.paginationConf.itemsPerPage = data.per_page;
            $scope.paginationConf.totalItems = data.total;
            $scope.users = res.data.users.data;

        })
    }


    $scope.init = function () {
        var path = $location.url();
        if (path == "/count-department") {

            $scope.avgChart = echarts.init(document.getElementById('avggrade'));
            $scope.get_department_data();

        } else if (path == "/count-user") {

            $scope.get_user_data(1);
        }
    }
    $scope.init();

})

myapp.controller('adminController', function ($scope, $http, $location, $stateParams) {

    $scope.admins = new Array();
    $scope.adminId = $stateParams.id;
    $scope.adminInfo = {

        admin_id: 0,
        name: "",
        password: ''
    };

    console.log($stateParams);

    $scope.admin_list = function () {
        var url = server + "admin/admin_list";

        $http.get(url).success(function (res) {

            $scope.admins = res.data.admins;

        });
    }

    $scope.delete_admin = function (adminId) {
        if (!confirm('确定删除吗')) return false;
        var url = server + "delete_admin?adminId=" + adminId;
        $http.get(url).success(function (res) {

            $scope.admin_list();

        });
    }

    $scope.get_admin_info = function () {


        var url = server + "get_admin_info?adminId=" + $scope.adminId;
        if ($scope.adminId == 0) return false;
        $http.post(url, $scope.adminInfo).success(function (res) {

            if (res.data.adminInfo) {
                $scope.adminInfo = res.data.adminInfo;
                $scope.adminInfo.password = "";
            }
        });
    }

    $scope.edit_admin = function () {
        var url = server + "admin/edit_admin";
        var data = http_query($scope.adminInfo);
        $http.post(url, data).success(function (res) {

            if (res.code == 200) {
                alert("添加成功");
                $location.path('admin-list');
            }

        });

    }

})


myapp.controller('matchController', function($scope, $http, $location,$stateParams,$timeout,$interval){

    $scope.matches      = [];   //比赛列表
    $scope.map          = "";
    $scope.court        = {};

    $scope.matchId      = $stateParams.matchId;

    $scope.matchGps     = [];

    $scope.matchResult  = {};   //比赛结果

    $scope.matchFiles   = [];   //比赛文件


    $scope.paginationConf = {
        currentPage: $stateParams.page,
        totalItems: 0,
        itemsPerPage: 0,
        pagesLength: 0,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            if($scope.paginationConf.currentPage > 0 )
            {
                $location.path('match/list/'+$scope.paginationConf.currentPage);
            }
            //$scope.get_match_list($scope.paginationConf.currentPage);
        }
    };

    var getpoint = function(lat,lon)
    {
        return new BMap.Point(lon,lat);
    }

    //比赛列表
    $scope.get_match_list = function()
    {
        page = $stateParams.page;
        
        if(page == 0) 
        {
            return;
        }

        var url     = server + "match/matches";
        var data    = {page:page};
            data    = http_query(data);

            $http.post(url,data).success(function(res)
            {
                var matches = res.data.matches;

                $scope.paginationConf.page          = matches.current_page;
                $scope.paginationConf.totalItems    = matches.total;
                $scope.paginationConf.itemsPerPage  = matches.per_page;

                $scope.matches = matches.data;
            });
    }

    //解析数据
    $scope.parse_data = function(matchId){

        var url = server + "match/parse_data?matchId="+matchId;

        $http.get(url).success(function(res)
        {
            if(res.code == 200){

                alert('已开始解析');
            }
        });
    }

    //调用算法计算
    $scope.caculate_data = function(matchId){

        var url = server + "match/caculate_match?matchId="+matchId;

        $http.get(url).success(function(res)
        {   
            if(res.code == 200){

                alert('一开始运算');
            }
        })
    }

    //比赛结果
    $scope.get_match_result = function(){

        $scope.init_map();

        var url         = server + "match/match_result?matchId=" + $scope.matchId;

        $http.get(url).success(function(res)
        {
            $scope.matchResult = res.data.matchResult;

            //显示热点图
            var width = $('.map-box').css('width');
                width = parseInt(width);
                width = (width-40)/2;
            $('.map').css('width',width+"px");
            $('.map div').css('height',width/2 + "px");

            console.log($scope.matchResult);

            $scope.draw_hot_map("map-run-all",width,$scope.matchResult.map_gps_run);
            $scope.draw_hot_map("map-run-high",width,$scope.matchResult.map_speed_high);
            $scope.draw_hot_map("map-run-middle",width,$scope.matchResult.map_speed_middle);
            $scope.draw_hot_map("map-run-low",width,$scope.matchResult.map_speed_low);
            $scope.draw_hot_map("map-run-static",width,$scope.matchResult.map_speed_static);
            //$scope.draw_hot_map("map-shoot",width,$scope.matchResult.map_shoot);
            //$scope.draw_hot_map("map-pass-long",width,$scope.matchResult.map_pass_long);
            //$scope.draw_hot_map("map-pass-short",width,$scope.matchResult.map_pass_short);
            //$scope.draw_hot_map("map-touchball",width,$scope.matchResult.map_touchball);
        
        
        })
    }

    $scope.get_single_result = function(type,color){

        var url     = server + "match/get_match_single_result";
        var data    = {matchId:$scope.matchId,type:type};
            data    = http_query(data);

            $http.post(url,data).success(function(res){

                var gps     = res.data.gps;
                var points  = [];

                for(var g of gps){

                    points.push(getpoint(g.lat,g.lon));
                }

                bdmap.draw_big_data($scope.map,points,color);
            })
    }
    /**
     * 显示热点图
     */
    $scope.draw_hot_map = function(eleId,width,data)
    {
        data = JSON.parse(data);
        var heatmap1 = h337.create({

                container: document.querySelector('#'+eleId)

            });

        var data2   = [];
        var max     = 0;
        var scale   = width / data[0].length;   //x为20分
        
            for(var y in data)
            {
              for(var x in data[y] )
              {

                  data2.push({"x":parseInt(x*scale),"y":parseInt(y*scale),"value":data[y][x]});
                  max = Math.max(max,data[y][x]);
              }
            }

            if(max < 100 && max > 0 ){

                max = max*20;



                for(var data of data2){

                    if(data.value > 0){

                        data.value = data.value * 20;
                    }
                }
            } else if(max == 0){

                max = 100;
            }
            data3 = {"max":max,"data":data2};
            heatmap1.setData(data3);
    }

    //比赛文件
    $scope.get_match_files = function()
    {
        var url = server + "match/match_files?matchId="+$scope.matchId;

        $http.get(url).success(function(res){

            $scope.matchFiles.sourceFile = res.data.matchFiles;
            $scope.matchFiles.resultFile = res.data.resultFiles;
            
            for(var f of $scope.matchFiles.resultFile)
            {
                f.url1 = btoa(f.url);

            }
        });
    }

    //获得比赛球场
    $scope.get_match_court = function()
    {
        var url     = server + "match/match_court";
        var params  = {matchId:$scope.matchId};
            params      = http_query(params);

        $http.post(url,params).success(function(res)
        {
            $scope.court = res.data.court;
        });
    }


    //描绘球场边
    $scope.draw_court_border = function()
    {
        var AD  = [];
        var points  = $scope.court.boxs;
        for(var p of points.A_D)
        {
            AD.push(getpoint(p.lat,p.lon));
        }

        $scope.map.centerAndZoom(AD[0],20);  //初始化地图,设置城市和地图级别。

        bdmap.drawline($scope.map,AD);


        var FE = [];
        for(var p of points.F_E)
        {
            FE.push(getpoint(p.lat,p.lon));
        }
        bdmap.drawline($scope.map,FE);


        var GH = [];
        for(var p of points.AF_DE)
        {
            GH.push(getpoint(p.lat,p.lon));
        }
        bdmap.drawline($scope.map,GH);
        
        return;

        var centers = [];
        var i=0;
        for(var p of $scope.court.boxs.center)
        {
            centers.push(getpoint(p.lat,p.lon));
            if(i== 32) break;
            i++;
        }
        //$scope.show_big_data(centers);
    }


    /*
    * 显示用户的实际GPS
    * */
    $scope.draw_match_gps = function()
    {

        var url = server + "match/match_gps?matchId=" + $scope.matchId;
        $http.post(url).success(function(res)
        {
            if(res.code != 200)
            {
                alert(res.message);
                return;
            }

            var points = [];

            for(var p in res.data.points){

                var newp = new BMap.Point(res.data.points[p].lon,res.data.points[p].lat);
                points.push(newp);
            }

            bdmap.draw_big_data($scope.map,points);
        });

    }

    //显示球场格子中心点
    $scope.draw_court_center = function(){

        var center = [];
        var points  = $scope.court.boxs;

        for(var p of points.center)
        {
            for(var p1 of p){

                center.push(getpoint(p1.lat,p1.lon));    
            }
        }

        $scope.interval = $interval(function(){

            if(center.length > 0){
                
                var p = center.splice(0,10);

                bdmap.draw_big_data($scope.map,p);                    
                

            }else{

                $interval.cancel($scope.interval);

            }

        },100);

        
    }

    /**
     * 当GPS球场无效的时候，利用实际的点模拟出一个球场
     * 模拟的标准是4个点的最大点
     */
    $scope.draw_visual_court = function()
    {
        //var url = server + "match/get_visual_match_court?matchId="+$scope.matchId;
        var url = server + "match/build_new_court_coordinate?matchId="+$scope.matchId;

        

        $http.get(url).success(function(res)
        {
        
            var points  = [];
            var data    = res.data.points; 

            for(var p in data){

                var newp = new BMap.Point(data[p].lon,data[p].lat);

                points.push(newp);
            }

            bdmap.draw_big_data($scope.map,points);
            //bdmap.draw_shape($scope.map,points);


        })
    }

    $scope.cut_court_to_line = function(){
        

        var url = server + "match/get_visual_match_court?matchId="+$scope.matchId;

        $http.get(url).success(function(res)
        {
        
            var points  = [];
            var data    = res; 

            for(var line in data){

                
                var points  = [];
                    points.push(new BMap.Point(data[line][0].lon,data[line][0].lat));
                    points.push(new BMap.Point(data[line][1].lon,data[line][1].lat));

                bdmap.draw_shape($scope.map,points);
                bdmap.draw_big_data($scope.map,points);

            }

        })


    }

    $scope.clean_overlay = function()
    {
        $scope.map.clearOverlays();
    }


    $scope.init_map = function()
    {
        $timeout(function(){

            // 百度地图API功能
            var map = new BMap.Map("map");    // 创建Map实例
            map.centerAndZoom("上海",16);  //初始化地图,设置城市和地图级别。
            map.enableScrollWheelZoom(true);     //开启鼠标滚轮缩放
            $scope.map = map;
        },1000);


        $scope.get_match_court();
    }

    $scope.compass = [];
    $scope.get_compass_data = function()
    {
        var file = $stateParams.file;
            file = atob(file);

        var url = server + "match/get_compass_data?file="+file;
        $http.get(url).success(function(res){

            var data = res.data.compass;
            var angles = [];
            angles.x = [];
            angles.y = [];
            


            var i = 0;
            for(var angle of data)
            {
                angles.y.push(angle[0]);
                angles.x.push(i++);
            }

            var option = {
                xAxis: {
                    type: 'category',
                    data: angles.x
                },
                yAxis: {
                    type: 'value',
                    minInterval:90,
                    maxInterval:90,
                },
                series: [{
                    data: angles.y,
                    type: 'line'
                }]
            };

            var myChart = echarts.init(document.getElementById('main'));
            myChart.setOption(option);
        });
    }


    $scope.get_match_run_data = function(){


        var url = server + "match/get_match_run_data?matchId="+$stateParams.matchId;
        $http.get(url).success(function(res){

            var gpsList = res.data.gpsList;
            var gpsData = [];
            var courtData= res.data.courtInfo;

            //找出经度最大，最小值，维度最大，最小值

            var maxlat = 0;
            var maxlon = 0;
            var minlat = 100000000;
            var minlon = 100000000;

            for(var gps of gpsList)
            {
                if(gps[0] == 0 || gps[1] == 0)
                {
                    continue;
                }
                gps[0] = gps[0] * 1000;
                gps[1] = gps[1] * 1000;

                gpsData.push([gps[1],gps[0]]);

                maxlat = Math.max(maxlat,gps[0]);
                maxlon = Math.max(maxlon,gps[1]);
                minlat = Math.min(minlat,gps[0]);
                minlon = Math.min(minlon,gps[1]);
            }

           

            var court = [];
            for(var gps of courtData)
            {
                if(gps[0] == 0 || gps[1] == 0)
                {
                    continue;
                }else{
                    gps[0] = gps[0] * 1000;
                    gps[1] = gps[1] * 1000;
                }
                court.push([gps[1],gps[0]]);
                maxlat = Math.max(maxlat,gps[0]);
                maxlon = Math.max(maxlon,gps[1]);
                minlat = Math.min(minlat,gps[0]);
                minlon = Math.min(minlon,gps[1]);
            }

            var height  = maxlat - minlat;
            var width   = maxlon - minlon;

            if(width < height)
            {
                height = 700 * (height/width);
                width  = 700;

            }else{

                console.log(width/height);

                width = 700 * (width / height );
                height = 700;
            }


            var option = {
                xAxis: {
                    scale: true,
                    //minInterval:1,
                    //maxInterval:1,
                },
                yAxis: {
                    scale: true,
                    //minInterval:1,
                    //maxInterval:1,
                },
                series: [
                {
                    type: 'effectScatter',
                    symbolSize: 5,
                    data: court
                }, {
                    type: 'scatter',
                    data: gpsData,
                    symbolSize:5
                }
                ]
            };

            $('#main').css({'width':width+"px",'height':height+"px"});
            var myChart = echarts.init(document.getElementById('main'));
            myChart.setOption(option);

        });
    }


   
    /**
     * 开启备注的编辑状态
     */
    $scope.enable_remark = function(matchId){

        for(var match of $scope.matches)
        {
            if(matchId == match.match_id)
            {
                match.isEdit = 1;
                $scope.editMatchId = matchId;
                break;
            }
        }
    }

    /**
     * 确定编辑是否生效
     */
    $scope.sure_edit_remark = function()
    {
    
        var data    = {matchId:$scope.editMatchId};

        for(var match of $scope.matches)
        {   
            match.isEdit = 0;
            if($scope.editMatchId == match.match_id)
            {
                data.admin_remark = match.admin_remark;    
            }
        }

        var data    = http_query(data);
        var url     = server + "match/update_match";

            $http.post(url,data).success(function(res)
            {
                console.log(res);
            })
    }


    

})


myapp.controller('courtController', function($scope, $http, $location, $stateParams,$timeout){

    $scope.courtTypeId  = $stateParams.courtTypeId;

    //应该是line[list]
    $scope.courtTable = {line:[],list:[],table:[]};
    $scope.courtTypes = [{"court_type_id":1,"num":11,"length":10,"width":20}];




    $scope.courtList    = [];   //球场列表
    $scope.courtListPaginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 10,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            $scope.get_court_list($scope.courtListPaginationConf.currentPage);
        }
    };


   
    $scope.init_court_type = function(){

        //初始化球场配置
        var line = 42;
        var list = 26;

        for(var i=1;i<list;i++)
        {
            $scope.courtTable.line.push(i);
        }

        var mid = 65+ line/2 -1;
        for(var i=65;i<65+line;i++){

            if(i < mid){
                
                $scope.courtTable.list.push(String.fromCharCode(i));    

            }else{

                $scope.courtTable.list.push(String.fromCharCode(mid-(i-(mid-1))));    
            }
        }

        for(var i=0;i<line-1;i++){

            var singline = [];

            for(var j=0;j<list-1;j++)
            {
                singline.push({"b":(Math.random()*10).toFixed(2),"l":(Math.random()*10).toFixed(2)});
            }
            $scope.courtTable.table.push(singline);
        }


        $scope.get_court_type_detail();
    }


    $scope.tdhover = function(line,list)
    {
        $('.current').removeClass('current');
        $('.line-'+line).addClass('current');
        $('.list-'+list).addClass('current');
        $('.td'+list+line).addClass('current');
    }


    /*保存球场角度配置信息*/
    $scope.save_court_config = function()
    {
        var angles  = $scope.courtTable.table;

        var angleArr= [];

        for(var line in angles){

            angleArr[line] = [];

            for(var p of angles[line]){

                angleArr[line].push(p.angle);
            }
        }

        var url = server + "court/edit_court_config";

        var data = {
            courtTypeId:$scope.courtTypeId,
            angles: JSON.stringify(angleArr)
        };

        data = http_query(data);
        $http.post(url,data).success(function(res){


            alert('ok');

        })
    }

    /*
    * 获得球场分类的详细
    */
    $scope.get_court_type_detail = function()
    {

        var url = server + "court/typeDetail?courtTypeId="+$scope.courtTypeId;

        $http.post(url).success(function(res){

            //把角度信息存入到球场数据中
            var angles = res.data.configInfo.angles;
            for(var line in angles){

                for(var td of angles[line]){
                    td.angle = td.type + td.angle;
                }
            }

            $scope.courtTable.table = angles;
        });

    }


    /*
    *球场列表
    */
    $scope.get_court_list = function(page){

        var url = server + "court/court_list?page=" + page;

        $http.post(url).success(function(res)
        {
            var courtData = res.data.courtList;

            $scope.courtList = courtData.data;
            $scope.courtListPaginationConf.currentPage = courtData.current_page;
            $scope.courtListPaginationConf.totalItems = courtData.total;
            $scope.courtListPaginationConf.itemsPerPage = courtData.per_page;
        })
    }
})

myapp.controller('sqmatchController',function($scope,$http,$location,$stateParams){


    $scope.matches  = [];
    $scope.sqPaginationConf = {
        currentPage: $stateParams.page,
        totalItems: 0,
        itemsPerPage: 0,
        pagesLength: 0,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            if($scope.sqPaginationConf.currentPage > 0 )
            {
                $location.path('sqmatch/list/'+$scope.sqPaginationConf.currentPage);
            }
        }
    };


    //获取社区列表
    $scope.get_sqmatch_list = function()
    {
        var page = $stateParams.page;
        var url = server + "sqmatch/matches?page="+page;
        $http.get(url).success(function(res){

            var match      = res.data.matches;
            $scope.matches = match.data;
            $scope.sqPaginationConf.currentPage = page;
            $scope.sqPaginationConf.totalItems = match.total;
            $scope.sqPaginationConf.itemsPerPage = match.per_page;

        });
    }

    $scope.matchUsers = [];
    $scope.get_sqmatch_users = function()
    {
        var url = server + "sqmatch/match_users?matchId="+$stateParams.matchId;
        $http.get(url).success(function(res){

            $scope.matchUsers = res.data.matchUsers;

        });
    }



})


myapp.controller('systemController',function($scope,$http,$location){

    $scope.serviceLogs = [];
    /*获得系统服务日至*/
    $scope.get_system_logs = function()
    {
        var url = server + "system/service_logs";
        $http.get(url).success(function(res){

            $scope.serviceLogs = res.data.logs;

        })
    }


})