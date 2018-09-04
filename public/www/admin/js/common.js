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
