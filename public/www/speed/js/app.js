var service = location.origin + "/api/speed/index/";
var myapp 	= angular.module('myapp',["ngRoute"]);

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
    $scope.page     = 1;
    $scope.userSn   = getQueryVariable('userSn');


	$scope.papers = new Array();


    $scope.init = function(){

        $scope.get_papers();
    }


	/*题目详情页面*/
	$scope.detail = function(paperId,canAnswer)
	{

	    if(canAnswer == 1)
        {
            $location.path('detail/'+paperId);
        }else{

            $location.path('result/'+paperId);
        }
	}

    $scope.page_sort = function(sn)
    {
        $location.path('sort/'+sn);
    }


    $scope.get_papers = function(){

        var url = service + "papers";
        $http.post(url,{userSn:$scope.userSn,page:$scope.page})
        .success(function(res){

            if(res.data.papers)
            {
                $scope.papers = res.data.papers.data;
            }
        })
    }


    $scope.init();
});



myapp.controller("detailController",function($scope,$http,$location,$routeParams,$timeout){

    $scope.activeCheckBtn = false;      //是否激活检查按钮
    $scope.showbeginbtn = true;        //显示答题按钮
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
    $scope.questionList = new Array();
    $scope.isStop   = false;
    
    $scope.userSn = getQueryVariable('userSn');
 

    


    $scope.init = function(){

        $scope.get_question_list();

    }


    $scope.fresh_time = function(){

            if($scope.isStop == true) return;
            $scope.showSurplusTime = second_to_str($scope.surplusTime);

            if($scope.surplusTime == 0)
            { 
                $scope.surplusTime = $scope.surplusTime - 1;
                //向服务器记录完成
                $scope.finishTitle = "测试时间结束";
                $scope.finish_exam();

                return;
            }
            $scope.surplusTime = $scope.surplusTime - 1;
            var url = $location.url();
                url = url.substring(0,7)
                console.log(url);

            if(url == '/detail')
            {
                $timeout(function(){$scope.fresh_time();},1000);    
            }
            
    }

  

  

    //获得题目列表
    $scope.get_question_list = function()
    {

        
        var url = service + "paper_detail";
        $http.post(url,{paperId:$scope.paperId})
        .success(function(res){

            for(var quest of res.data.questions)
            {

                for(var ans of quest.answers)
                {
                    ans.result = 2;
                }
            }

            $scope.paperInfo =  res.data.paperInfo;
            var paperInfo  =   res.data.paperInfo;

            

            $scope.questionList = res.data.questions;

            

            $scope.surplusTime = paperInfo.total_time - paperInfo.used_time;

            $scope.showSurplusTime = second_to_str($scope.surplusTime);
            

            setTitle(paperInfo.title+"测试");

            $scope.get_next_question();
           
            //$scope.fresh_time();
        })

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
            if(question.answer == null)
            {
                $scope.question = question;
                isEnd = false;
                break;
            }
        }

        if(isEnd == true)
        {

            //结束答题
            $scope.finishTitle = "恭喜，已完成答题";
            $scope.finish_exam();
        }
    }


    $scope.finish_exam = function()
    {
        $scope.isStop = true;
        var url = service + "finish_exam?paperId=" + $scope.paperId;
        var data = {paperId:$scope.paperId,userSn:$scope.userSn};

        $http.post(url,data)
        .success(function(res)
        {
            $scope.paperInfo = res.data.paperInfo;
            $scope.usedTime = second_to_str($scope.paperInfo.used_time);

        });
    }


	/*开始答题*/
	$scope.begin_answer = function()
	{
		$scope.showbeginbtn = false;
        
        //刷新前端时间
        $scope.fresh_time();

        //定时更新耗费时间
        $timeout($scope.save_used_time,5000);
	}

    /*更新使用的时间*/
    $scope.save_used_time = function()
    {
        
        if($scope.isStop == true) return;

        var url = service + "fresh_exam_time?paperId="+$scope.paperId;
        $http.get(url);
        if($scope.surplusTime > 0)
        {

            $timeout($scope.save_used_time,5000);
        }

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
        

         $timeout(function(){
            $scope.checkBtnText = "下一题";
        },1000);

        console.log($scope.question.answers);

        //检查正确答案

        var userAnwers = [];

        for(var ans of $scope.question.answers)
        {
            if(ans.is_right == 1 && ans.selected == true)
            {
                ans.result = 1;

            }else if(ans.is_right ==0 && ans.selected == true){

                ans.result = 0;

            }else if(ans.is_right == 1 && ans.selected == false) {

                //ans.result = 0;
            }

            if(ans.selected == true)
            {
                userAnwers.push(ans.sn);
            }
            

            if(ans.is_right == 1)
            {
                //$scope.rightNoticeText = $scope.rightNoticeText + ans.sn+",";
                $scope.answerList.push(ans.sn);
            }
        }

        $scope.question.answer = $scope.answerList;
        $scope.rightNoticeText = "正确答案:" + $scope.answerList.join(',');


        //向服务器提交答案

        var url = service + "save_answer";
        var data = {
            answers:userAnwers.join(','),
            paperId:$scope.paperId,
            paperQuestionId:$scope.question.paper_question_id
        };

        $http.post(url,data)
        .success(function(){



        });
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


    $scope.usedTime     = "00:00:00";

    $scope.paperInfo    = {};
    $scope.paperId      = $routeParams.id;
    $scope.questions    = [];


    $scope.get_papger_info = function(){

        var url = service + "paper_detail?paperId="+$scope.paperId;

        $http.get(url)
        .success(function(res){


            $scope.paperInfo = res.data.paperInfo;
            $scope.usedTime  = second_to_str($scope.paperInfo.used_time);

            $scope.questions = res.data.questions;

        });
    }


    $scope.get_papger_info();


    /*前往排行榜*/
    $scope.to_sort = function(){

        $location.path('sort/'+$scope.paperInfo.paper_sn);

    }


})


/*排行榜*/
myapp.controller('sortController',function($scope,$http,$location,$routeParams){


    setTitle('排行榜');
    $scope.papers   = new Array();
    $scope.paperSn  = $routeParams.sn;

    $scope.get_papers = function(){

        var url = service + "same_paper_sort?paperSn=" + $scope.paperSn;


        $http.get(url)
        .success(function(res){

            $scope.papers = res.data.papers;

            for(var paper of $scope.papers)
            {
                var str = "用时";
                var usedTime = paper.used_time;

                var h   = parseInt(usedTime/3600);
                if(h > 0)
                {
                    usedTime = usedTime%3600;
                    str = str + h + "小时";
                }

                var m = parseInt(usedTime/60);
                var s = usedTime%60;

                str = str + m + "分钟" + s + "秒";
                paper.usedTime = str;
                
            }

        });
    }

    $scope.get_papers();


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
    i.src = '//m.baidu.com/favicon.ico?time='+Math.random();
    i.style.display = 'none';
    i.onload = function() {
        setTimeout(function(){
            i.remove();
        }, 9)
    }
    document.body.appendChild(i);
}

function second_to_str(second){

    var h = parseInt(second/3600);
    var m = parseInt((second%3600)/60);
    var s = second%60;
        h = getfull_time(h);
        m = getfull_time(m);
        s = getfull_time(s);
    var str = h+":"+m+":"+s;

    return str;
}

function getfull_time(num){

    if(num < 10)
    {
        return "0"+num;
    }else{
        return num;
    }
}