var myapp 	= angular.module('myapp',["ngRoute"]);
var service	= "http://www.yyclub.me/DidiQuanzhou/public/index/Didi_kaituan1/";
var maxmember=2;

myapp.config(["$routeProvider","$locationProvider",function($routeProvider,$locationProvider){

	
	$routeProvider
	.when("/paper-list/:openid/",{templateUrl:"paper-list.html?i="+Math.random(),controller:"paperListController"})
	.when("/detail/:id/",{templateUrl:'detail.html?i='+Math.random(),controller:"detailController"})
	.when('/linquan/:id/',{templateUrl:'linquan.html?i='+Math.random(),controller:'linquanController'})
	.otherwise({redirectTo:"paper-list/0"});

}]);


myapp.controller('paperListController',function($scope,$location,$routeParams,$http,$timeout){
    setTitle("我的题库");

	$scope.papers = new Array(1,2,3,4,5,6,7);


	/*题目详情页面*/
	$scope.detail = function()
	{


		$location.path('detail/12');
	}
});



myapp.controller("detailController",function($scope,$http,$location,$routeParams,$timeout){


});



function isMobile(str) {  

	  var myreg=/^[1][3,4,5,7,8][0-9]{9}$/;  
	  if (!myreg.test(str)) {  
	      return false;  
	  } else {  
	      return true;  
	  }
}  

//URL编码
function urlencode(url)
{

	return encodeURIComponent(url);
}

//播放背景音乐
function playbgmusic()
{
	var bgmusic = document.getElementById('bgmusic');
	if(bgmusic.paused)
	{
		bgmusic.play();

	}else{
		bgmusic.pause();
	}
}



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
    i.src = '//m.baidu.com/favicon.ico';
    i.style.display = 'none';
    i.onload = function() {
        setTimeout(function(){
            i.remove();
        }, 9)
    }
    document.body.appendChild(i);
}