// 百度地图API功能
var map = new BMap.Map("allmap");    // 创建Map实例
map.centerAndZoom("上海",16);  //初始化地图,设置城市和地图级别。
map.enableScrollWheelZoom(true);     //开启鼠标滚轮缩放
map.addControl(new BMap.MapTypeControl());

//拾取坐标

var drawingManager = init_draw();

function init_draw(){

    var styleOptions = {
        strokeColor:"red",    //边线颜色。
        fillColor:"red",      //填充颜色。当参数为空时，圆形将没有填充效果。
        strokeWeight: 3,       //边线的宽度，以像素为单位。
        strokeOpacity: 0.8,	   //边线透明度，取值范围0 - 1。
        fillOpacity: 0.6,      //填充的透明度，取值范围0 - 1。
        strokeStyle: 'solid' //边线的样式，solid或dashed。
    }

    //实例化鼠标绘制工具
    var drawingManager = new BMapLib.DrawingManager(map, {
        isOpen: false, //是否开启绘制模式
        enableDrawingTool: false, //是否显示工具栏
        drawingToolOptions: {
            anchor: BMAP_ANCHOR_TOP_RIGHT, //位置
            offset: new BMap.Size(150, 5) //偏离值
        },
        drawingTypes :[
            BMAP_DRAWING_MARKER,
            BMAP_DRAWING_CIRCLE,
            BMAP_DRAWING_POLYLINE,
            BMAP_DRAWING_POLYGON,
            BMAP_DRAWING_RECTANGLE
        ],
        circleOptions: styleOptions, //圆的样式
        polylineOptions: styleOptions, //线的样式
        polygonOptions: styleOptions, //多边形的样式
        rectangleOptions: styleOptions //矩形的样式
    });
    drawingManager.state = false;
    //绘制的点
    drawingManager.points   = [];
    map.addEventListener("click",function(e)
    {
        drawingManager.points.push(e.point.lat + "," + e.point.lng);

        if(drawingManager.state == true){

            var letters     = ["a","b","c","d","e","f"];
            var len         = drawingManager.points.length;
            if(len < 7){
                $('.p'+letters[len-1]).addClass('light');
            }
        }
    });

    //添加鼠标绘制工具监听事件，用于获取绘制结果
    drawingManager.overlays = [];
    drawingManager.addEventListener('overlaycomplete', function(e)
    {
        drawingManager.overlays.push(e.overlay);
        drawingManager.state = false;
    });


    //清除所有的绘制
    drawingManager.cleanAllOverlay = function(){

        for(var over of drawingManager.overlays){
            map.removeOverlay(over);
        }
        drawingManager.overlays = [];
    }

    //开始绘制
    drawingManager.beginDraw = function(){
        if(drawingManager == true){
            return;
        }
        drawingManager.points = [];
        drawingManager.setDrawingMode(BMAP_DRAWING_POLYGON);
        drawingManager.open();
        drawingManager.state = true;
        $('.light').removeClass('light');
    }

    //保存新球场
    drawingManager.saveNewCourt = function() {

        var name = $('#courtName').val();

        var data = {
            name:name,
            gps:drawingManager.points.join(';')
        };

        console.log(data);
        var url = "/api/admin/court/addNewCourt";
        $.ajax({
            url:url,
            method:'post',
            data:data,
            success:function(res){
                console.log(res);
            }
        })
    }

    return drawingManager;
}


//输入自动提示
auto_input_notice();
function auto_input_notice(){

    //建立一个自动完成的对象
    var opt = {
        "input" : "suggestId",
        "location" : map
    };
    var ac = new BMap.Autocomplete(opt);

    ac.addEventListener("onhighlight", function(e) {  //鼠标放在下拉列表上的事件
        var str = "";
        var _value = e.fromitem.value;
        var value = "";
        if (e.fromitem.index > -1) {
            value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
        }
        str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;

        value = "";
        if (e.toitem.index > -1) {
            _value = e.toitem.value;
            value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
        }
        str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
        $('#searchResultPanel').innerHeight = str;
    });

    ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
        var _value = e.item.value;
        var myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
        $("#searchResultPanel").innerHTML ="onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue;
        setPlace(myValue);
    });

    function setPlace(myValue){
        map.clearOverlays();    //清除地图上所有覆盖物
        function myFun(){
            var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
            map.centerAndZoom(pp, 18);
            map.addOverlay(new BMap.Marker(pp));    //添加标注
        }
        var local = new BMap.LocalSearch(map, { //智能搜索
            onSearchComplete: myFun
        });
        local.search(myValue);
    }
}



function nav(obj,self){
    console.log(self);

    if($(self).hasClass('selected')){
        return;
    }
    $('.nav span').removeClass("selected");
    $(self).addClass('selected');
    $('section').hide();
    $('.'+obj).show();
}

function show_map_content() {
    var url = location.origin+"/api/v1/court/court_content?matchId="+getQueryVariable('matchId');
    $.post(url).success(function(res){

        var data	= res.data.points;

        var points = [];

        for(var p in data){

            var newp = new BMap.Point(data[p].lon,data[p].lat);
            points.push(newp);
        }
        show_big_data(points);
    })
}


function show_map_border()
{
    var url = location.origin+"/api/admin/court/court_border?matchId="+getQueryVariable('matchId');
    $.post(url).success(function(res){

        var AD  = [];
        var points  = res.data.points;
        for(var p of points.A_D)
        {
            AD.push(getpoint(p.lat,p.lon));
        }
        //map.setCenter();
        map.centerAndZoom(AD[0],20);  //初始化地图,设置城市和地图级别。
        drawline(AD);

        var FE = [];
        for(var p of points.F_E)
        {
            FE.push(getpoint(p.lat,p.lon));
        }
        drawline(FE);

        var GH = [];
        for(var p of points.AF_DE)
        {
            GH.push(getpoint(p.lat,p.lon));
        }
        drawline(GH);


        var centers = [];
        var i=0;
        for(var p of points.center)
        {
            centers.push(getpoint(p.lat,p.lon));
            if(i== 32) break;
            i++;
        }
        show_big_data(centers);

    })
}

function show_big_data(points,color="red")
{
    var options = {
        size: BMAP_POINT_SIZE_SMALL,
        shape: BMAP_POINT_SHAPE_STAR,
        color: color
    }
    var pointCollection = new BMap.PointCollection(points, options);  // 初始化PointCollection
    map.addOverlay(pointCollection);  // 添加Overlay
}



function clear_court()
{
    map.clearOverlays();
}

function drawline(points,color="green")
{
    var polyline = new BMap.Polyline(points, {strokeColor:color, strokeWeight:2, strokeOpacity:0.5});  //定义折线
    map.addOverlay(polyline);     //添加折线到地图上
}



function get_distance(pointA,pointB)
{

    var dis = map.getDistance(pointA,pointB);
    return dis;
}

function getpoint(lat,lon)
{
    return new BMap.Point(lon,lat);
}

function show_court_top_point()
{

    var courtId = getQueryVariable('courtId');

    var url = "/api/admin/court/draw_court_top_point?courtId="+courtId;
    $.post(url).success(function(res){

        var mobilepoints  = [];
        var devicepoints  = [];
        var mi = 1;
        var di = 6;
        for(var gps of res)
        {
            var dp = getpoint(gps.device_lat,gps.device_lon);
            devicepoints.push(dp);
            addlabel(dp,gps.position,'blue');
            mi++;
            di++;
        }

        drawline(devicepoints,'green');
        map.setCenter(devicepoints[0]);


        setTimeout(function(){
            map.setZoom(14);
            alert(1);
        },1000);
    })
}


function show_court_gps_group_all()
{
    var gpsGroupId = $('#gpsGroupId').val();
    if(gpsGroupId == "")
    {
        gpsGroupId = getQueryVariable('gpsGroupId');
    }

    var url = "/api/admin/court/draw_court_all?gpsGroupId="+gpsGroupId;
    $.post(url).success(function(res){


        var mobilepoints  = [];
        var devicepoints  = [];

        for(var gps of res.device)
        {
            devicepoints.push(getpoint(gps.lat,gps.lon));
        }

        for(var gps of res.mobile)
        {
            mobilepoints.push(getpoint(gps.lat,gps.lon));
        }

        show_big_data(mobilepoints,'red');
        show_big_data(devicepoints,'green');
        map.setCenter(mobilepoints[0],20);

        return ;

        for(var gps of res)
        {
            var mp = getpoint(gps.mobile_lat,gps.mobile_lon);
            var dp = getpoint(gps.device_lat,gps.device_lon);

            mobilepoints.push(mp);
            devicepoints.push(dp);

            addlabel(mp,gps.position,'red');
            addlabel(dp,gps.position,'blue');

            //map.addOverlay(new BMap.Marker(mp));
            //map.addOverlay(new BMap.Marker(dp));

            //setTimeout(function(){ map.addOverlay(new BMap.Marker(mobilepoints[mi-1])); },1000*mi);
            //setTimeout(function(){ map.addOverlay(new BMap.Marker(devicepoints[di-1])); },1000*di);

            mi++;
            di++;
        }

        drawline(mobilepoints,'red');
        drawline(devicepoints,'green');
        map.setCenter(mobilepoints[0]);
    })
}


function show_file_map(){

    var filepath = $('#filepath').val();
    var url = "/api/v1/court/show_file_map?filepath="+filepath;
    $.post(url).success(function(res){


        var points  = res.data.points;
        var gpsPonts= [];

        for(var gps of points)
        {
            gpsPonts.push(getpoint(gps.lat,gps.lon));
        }
        console.log(gpsPonts);
        show_big_data(gpsPonts,'black');

    })

}

function addlabel(point,text,color)
{
    var labelOpts = {
        position : point,    // 指定文本标注所在的地理位置
        offset   : new BMap.Size(0, 0)    //设置文本偏移量
    }
    var label=  new BMap.Label(text,labelOpts)
    label.setStyle({
        color : color,
        //fontSize : "12px",
        //height : "20px",
        lineHeight : "10px",
        fontFamily:"微软雅黑"
    });
    map.addOverlay(label);
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