var myapp 	= angular.module('myapp',["ngRoute"]);
var service	= "http://www.yyclub.me/DidiQuanzhou/public/index/Didi_kaituan1/";
var maxmember=2;

myapp.config(["$routeProvider","$locationProvider",function($routeProvider,$locationProvider){

	
	$routeProvider
	.when("/paper-list/:openid/",{templateUrl:"paper-list.html?i="+Math.random(),controller:"paperListController"})
	.when("/detail/:id/",{templateUrl:'detail.html?i='+Math.random(),controller:"detailController"})
	.when('/result/:id/',{templateUrl:'result.html?i='+Math.random(),controller:'resultController'})
    .when('/sort/:sn/',{templateUrl:'sort.html?i='+Math.random(),controller:'sortController'})
	.otherwise({redirectTo:"paper-list/0"});

}]);


myapp.controller('paperListController',function($scope,$location,$routeParams,$http,$timeout){
    setTitle("我的题库");

	$scope.papers = new Array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);


	/*题目详情页面*/
	$scope.detail = function()
	{


		$location.path('detail/12');
	}
});



myapp.controller("detailController",function($scope,$http,$location,$routeParams,$timeout){

	setTitle("关于19大的最新学习");
    $scope.activeCheckBtn = false;      //是否激活检查按钮
    $scope.showbeginbtn = false;        //显示答题按钮
    $scope.rightNoticeText = "";        //正确答案文字提示
    $scope.checkBtnText = "检查";       //检查按钮文字提示
    $scope.canSelectAnswer    = true;   //是否可以选择答案
    $scope.answerList = new Array();    //答案列表
    $scope.surplusTime = 6;
    $scope.showSurplusTime = "00:00:00";
    $scope.paperId  = $routeParams.id;
    $scope.question = {};
    $scope.finishTitle = "测试完成";
    $scope.paperInfo = {paperId:12,paper_sn:"2015-05-06",surplusTime:60,total_time:72};
    $scope.usedTime = "";//答题所用时间
    $scope.questionList = [

        {
            id:1,
            title:"关于19大的最新学习",
            type:"checkbox",
            isAnswer:0,
            nth:4,
            answers:[
                {
                    sn:"A",
                    content:"快点快点看的开导开导",
                    selected:0,
                    isRight:1,
                    result:3
                },
                {
                    sn:"B",
                    content:"快点快点SSS看的开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                },
                {
                    sn:"C",
                    content:"快点快点看dd的d开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                },
                {
                    sn:"D",
                    content:"快点快点ddssdKkdkd看的开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                }
            ]
        }, {
            id:2,
            isAnswer:0,
            title:"你的名字",
            type:"checkbox",
            answers:[
                {
                    sn:"A",
                    content:"快点快点看的开导开导",
                    selected:0,
                    isRight:1,
                    result:3
                },
                {
                    sn:"B",
                    content:"快点快点SSS看的开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                },
                {
                    sn:"C",
                    content:"快点快点看dd的d开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                },
                {
                    sn:"D",
                    content:"快点快点ddssdKkdkd看的开导开导",
                    selected:0,
                    isRight:0,
                    result:3
                }
            ]
        }
    ];
    


    $scope.init = function(){

        $scope.get_question_list();
        $scope.get_next_question();
        $scope.fresh_time();
    }


    $scope.fresh_time = function(){

            
            $scope.showSurplusTime = $scope.second_to_str($scope.surplusTime);

            console.log($scope.showSurplusTime);

            if($scope.surplusTime == 0)
            { 
                $scope.surplusTime = $scope.surplusTime - 1;
                var time = $scope.paperInfo.total_time - $scope.surplusTime;
                console.log(time);
                $scope.usedTime = $scope.second_to_str(time);

                //向服务器记录完成
                $scope.finishTitle = "测试时间结束";

                return;
            }
            $scope.surplusTime = $scope.surplusTime - 1;
            $timeout(function(){$scope.fresh_time();},1000);
    }

    $scope.second_to_str = function(second){

        var h = parseInt(second/3600);
        var m = parseInt((second%3600)/60);
        var s = second%60;
            h = $scope.getfull_time(h);
            m = $scope.getfull_time(m);
            s = $scope.getfull_time(s);
        var str = h+":"+m+":"+s;

        return str;

    }

    $scope.getfull_time = function(num){

        if(num < 10)
        {
            return "0"+num;
        }else{
            return num;
        }
    }

    //获得题目列表
    $scope.get_question_list = function()
    {



    }

    //获得下一道题
    $scope.get_next_question = function()
    {
        $scope.canSelectAnswer = true;
        $scope.checkBtnText = "检查";
        $scope.rightNoticeText = "";
        $scope.activeCheckBtn = false;
        $scope.answerList   = new Array();

        var isEnd           = true;

        for(var question of $scope.questionList)
        {
            if(question.isAnswer == 0)
            {
                $scope.question = question;
                isEnd = false;
                break;
            }
        }

        if(isEnd == true)
        {
            $location.path('result/0');
        }
    }



	/*开始答题*/
	$scope.begin_answer = function()
	{
		$scope.showbeginbtn = false;

	}


    /*
    * 选择答案
    * */
    $scope.select_answer = function(sn){


        if($scope.canSelectAnswer == false)
        {
            return false;
        }
        //每个答案对于一个是否选择的字段

        $type = $scope.question.type;

        if($scope.question.type == 'radio') {

            //单选题，只能有一个答案 如果选中一个答案 则其他答案变为未选
            for(var ans in $scope.question.answers)
            {

                if(sn == $scope.question.answers[ans].sn) {

                    $scope.question.answers[ans].selected = 1;

                }else{
                    $scope.question.answers[ans].selected = 0;
                }
            }

        }else if($type == 'checkbox'){

            //多选题
            for(var ans of $scope.question.answers)
            {

                if(sn == ans.sn) {

                    if(ans.selected > 1)
                    {
                        ans.selected = 1;
                    }else {
                        ans.selected = ans.selected == 0 ? 1 : 0;    
                    }
                }
            }
        }


        //好的激活按钮的状态
        //多选题
        $scope.activeCheckBtn = false;
        for(var ans of $scope.question.answers)
        {

            if(ans.selected == 1) {

                $scope.activeCheckBtn = true;
                break;
            }
        }
        $scope.activeCheckBtn
    }


    $scope.check_answer = function(){

        //已作答，并且处于冻结状态中
        if($scope.canSelectAnswer == false)
        {

            $scope.get_next_question();
            return false;
        }

    
        //还未作答
        if($scope.activeCheckBtn == false)
        {
            return ;
        }

        //冻结回答状态
        $scope.canSelectAnswer = false;
        $scope.question.isAnswer = 1;

         $timeout(function(){
            $scope.checkBtnText = "下一题";
        },1000);

        console.log($scope.question.answers);

        //检查正确答案


        for(var ans of $scope.question.answers)
        {
            if(ans.isRight == 1 && ans.selected == true)
            {
                ans.result = 1;

            }else if(ans.isRight ==0 && ans.selected == true){

                ans.result = 0;

            }else if(ans.isRight == 1 && ans.selected == false) {

                //ans.result = 0;
            }


            if(ans.isRight == 1)
            {
                //$scope.rightNoticeText = $scope.rightNoticeText + ans.sn+",";
                $scope.answerList.push(ans.sn);
            }
        }



        $scope.rightNoticeText = "正确答案:" + $scope.answerList.join(',');

        //向服务器提交答案
    }


    /*跳到结果页*/
    $scope.page_result = function(){

        $location.path('result/'+$scope.paperId).replace();
    }

    /*跳到排行榜*/
    $scope.page_sort = function()
    {
        $location.path('sort/'+$scope.paperInfo.paper_sn).replace();
    }

    $scope.init();
});


/*答题详情页*/
myapp.controller('resultController',function($scope,$http,$location,$routeParams){





})


/*排行榜*/
myapp.controller('sortController',function($scope,$http,$location,$routeParams){


})

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

