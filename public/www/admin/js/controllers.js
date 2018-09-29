
var server = location.origin + "/api/admin/";

/*登录控制器*/
mylogin.controller('loginController', function ($scope, $http) {

    $scope.name = "";
    $scope.password = "";


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

            $scope.admin = res.data.admin;

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


myapp.controller('deviceController', function ($scope, $http, $location,$stateParams) {

    setTimeout(init_DataTables, 1000);

    $scope.excel = "";
    $scope.devices = [];
    $scope.hasQuestion = false;
    $scope.addBtnText = "若检查无误，点此提交题库";
    $scope.disableAddBtn = false;

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 10,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            $scope.get_device_list($scope.paginationConf.currentPage);
        }
    };

    $scope.deviceInfo   = {};


    /*获得题目列表*/
    $scope.get_device_list = function (page) {

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


    $scope.init = function () {
        var path = $location.url();

        switch (path)
        {
            case '/device/list':    $scope.get_device_list(1);    break;
        }
    }

    $scope.init();

})


myapp.controller('userController', function ($scope, $http, $location) {


    $scope.users = [];
    $scope.departments = [];
    $scope.userKeyWrods = "";//搜索用户关键字
    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 8000,
        itemsPerPage: 15,
        pagesLength: 15,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function () {
            $scope.get_user_list($scope.paginationConf.currentPage);
        }
    };


    /*获得用户列表*/
    $scope.get_user_list = function (page) {
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

                $scope.paginationConf.currentPage   = users.page;
                $scope.paginationConf.totalItems    = users.total;
                $scope.paginationConf.itemsPerPage  = users.pernumber;
                $scope.users = users.data;
            }
        });
    }


    $scope.down_department = function () {


        var url = server + "down_department";
        $http.get(url).success(function () {

            alert('同步完成');
            $scope.get_department_list();
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


    $scope.init = function () {
        var path = $location.url();
        switch (path) {
            case '/user/list':
                $scope.get_user_list(1);
                break;
            case '/department':
                $scope.get_department_list();
                break;
        }
    }

    $scope.init();


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
        var url = server + "admin_list";

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
        var url = server + "edit_admin";
        $http.post(url, $scope.adminInfo).success(function (res) {

            if (res.code == 200) {
                alert("添加成功");
                $location.path('admin-list');
            }

        });

    }

})


myapp.controller('matchController', function($scope, $http, $location,$stateParams,$timeout){

    $scope.matches      = [];
    $scope.map          = "";
    $scope.court        = {};

    $scope.matchId      = $stateParams.matchId;

    $scope.matchGps     = [];

    $scope.paginationConf = {
        currentPage: 0,
        totalItems: 0,
        itemsPerPage: 0,
        pagesLength: 0,
        perPageOptions: [10, 20, 30, 40, 50],
        onChange: function ()
        {
            $scope.get_match_list($scope.paginationConf.currentPage);
        }
    };

    var getpoint = function(lat,lon)
    {
        return new BMap.Point(lon,lat);
    }

    //比赛列表
    $scope.get_match_list = function(page)
    {
        if(page == 0) return;

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
        var points  = $scope.court.boxs.baiduGps;
        for(var p of points.A_D)
        {
            AD.push(getpoint(p.lat,p.lon));
        }
        //map.setCenter();
        $scope.map.centerAndZoom(AD[0],20);  //初始化地图,设置城市和地图级别。

        $scope.drawline(AD);


        var FE = [];
        for(var p of points.F_E)
        {
            FE.push(getpoint(p.lat,p.lon));
        }
        $scope.drawline(FE);


        var GH = [];
        for(var p of points.AF_DE)
        {
            GH.push(getpoint(p.lat,p.lon));
        }
        $scope.drawline(GH);

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
            $scope.draw_big_data(points);

        });

    }


    //显示大量球场点
    $scope.draw_big_data = function(points)
    {
        var options = {
            size: BMAP_POINT_SIZE_SMALL,
            shape: BMAP_POINT_SHAPE_STAR,
            color: '#d340c3'
        }
        var pointCollection = new BMap.PointCollection(points, options);  // 初始化PointCollection
        $scope.map.addOverlay(pointCollection);  // 添加Overlay
    }


    //地图划线
    $scope.drawline = function(points)
    {
        var polyline = new BMap.Polyline(points, {strokeColor:"green", strokeWeight:2, strokeOpacity:0.5});  //定义折线
        $scope.map.addOverlay(polyline);     //添加折线到地图上
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



    var line = 21;
    var list = 25;

    for(var i=1;i<list;i++)
    {
        $scope.courtTable.line.push(i);
    }


    for(var i=65;i<65+line;i++)
    {
        $scope.courtTable.list.push(String.fromCharCode(i));

    }

    for(var i=0;i<line-1;i++)
    {
        var singline = [];

        for(var j=0;j<list-1;j++)
        {
            singline.push({"b":(Math.random()*10).toFixed(2),"l":(Math.random()*10).toFixed(2)});
        }
        $scope.courtTable.table.push(singline);
    }




    $scope.tdhover = function(line,list)
    {
        $('.current').removeClass('current');
        $('.line-'+line).addClass('current');
        $('.list-'+list).addClass('current');
        $('.td'+list+line).addClass('current');
    }



    $scope.save_court_config = function()
    {
        console.log($scope.courtTable.table);
    }

    /*
    * 获得球场分类的详细
    */
    $scope.get_court_type_detail = function()
    {
        var url = server + "/court/typeDetail?courtTypeId="+$scope.courtTypeId;

    }


    /*
    *球场列表
    */
    $scope.get_court_list = function(page)
    {

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
