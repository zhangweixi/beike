function setCookie(c_name, value, expiredays) {
    var exdate = new Date()
    exdate.setDate(exdate.getDate() + expiredays)
    document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString())
}

function getCookie(c_name) {
    if (document.cookie.length > 0) {
        c_start = document.cookie.indexOf(c_name + "=")
        if (c_start != -1) {
            c_start = c_start + c_name.length + 1
            c_end = document.cookie.indexOf(";", c_start)
            if (c_end == -1) c_end = document.cookie.length
            return unescape(document.cookie.substring(c_start, c_end))
        }
    }
    return ""
}

function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }
    return (false);
}


function setTitle(t) {

    document.title = t;
    var i = document.createElement('iframe');
    i.src = '//m.baidu.com/favicon.ico?time=' + Math.random();
    i.style.display = 'none';
    i.onload = function () {
        setTimeout(function () {
            i.remove();
        }, 9)
    }
    document.body.appendChild(i);
}


//去掉空格
function Trim(str, is_global) {

    var result = str.replace(/(^\s+)|(\s+$)/g, "");

    if (is_global.toLowerCase() == "g") {

        result = result.replace(/\s/g, "");

    }

    return result;
}


//json转字符串格式的参数
function http_query(data) {
    var str = "";
    for (var key in data) {
        str += key + "=" + data[key] + "&";
    }
    str = str.replace(/(^\&+)|(\&+$)/g, "");
    return str;
}

function GMTToStr(time, type) {
    var date = new Date(time)

    var year = getfull_time(date.getFullYear());
    var month = getfull_time(date.getMonth() + 1);
    var day = getfull_time(date.getDate());
    var hour = getfull_time(date.getHours());
    var min = getfull_time(date.getMinutes());
    var sen = getfull_time(date.getSeconds());

    date = year + "-" + month + "-" + day;
    time = hour + ":" + min + ":" + sen;

    if (type == 'date') {

        return date;
    }

    if (type == 'time') {
        return time;
    }

    return date + " " + time;
}


function getfull_time(num) {

    if (num < 10) {
        return "0" + num;
    } else {
        return num;
    }
}



//地图划线
var bdmap = {

    drawline : function(map,points)
    {
        var polyline = new BMap.Polyline(points, {strokeColor:"green", strokeWeight:2, strokeOpacity:0.5});  //定义折线
        map.addOverlay(polyline);     //添加折线到地图上
    },
    //显示大量球场点
    draw_big_data : function(map,points)
    {
        if(arguments.length > 2){

            var color = arguments[2];

        }else{
            var color = "#d340c3";
        }

        var options = {
            size: BMAP_POINT_SIZE_SMALL,
            shape: BMAP_POINT_SHAPE_STAR,
            color: color
        }
        var pointCollection = new BMap.PointCollection(points, options);  // 初始化PointCollection
        if(points.length > 0 && arguments.length == 1)
        {
            map.centerAndZoom(points[0],20);
        }
        map.addOverlay(pointCollection);  // 添加Overlay
    },
     /**
     * 绘制直线图
     */
    draw_shape : function(map,points)
    {
        var points1 = [
            new BMap.Point(116.387112,39.920977),
            new BMap.Point(116.385243,39.913063),
            new BMap.Point(116.394226,39.917988),
            new BMap.Point(116.401772,39.921364),
            new BMap.Point(116.41248,39.927893)
        ];

        var option = {strokeColor:"blue", strokeWeight:2, strokeOpacity:0.5};

        var polygon = new BMap.Polygon(points,option);  //创建多边形

        map.addOverlay(polygon);   //增加多边形
    }
}

