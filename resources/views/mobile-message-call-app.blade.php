<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<script>
    var browser = {

        versions: function() {

            var u = navigator.userAgent,

                app = navigator.appVersion;

            return {

                trident: u.indexOf('Trident') > -1,                        /*IE内核*/

                presto: u.indexOf('Presto') > -1,          /*opera内核*/

                webKit: u.indexOf('AppleWebKit') > -1, /*苹果、谷歌内核*/

                gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1,        /*火狐内核*/

                mobile: !!u.match(/AppleWebKit.*Mobile.*/),        /*是否为移动终端*/

                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), /*ios终端*/

                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, /*android终端或者uc浏览器*/

                iPhone: u.indexOf('iPhone') > -1,          /*是否为iPhone或者QQHD浏览器*/

                iPad: u.indexOf('iPad') > -1,      /*是否iPad*/

                webApp: u.indexOf('Safari') == -1,          /*是否web应该程序，没有头部与底部*/

                souyue: u.indexOf('souyue') > -1,

                superapp: u.indexOf('superapp') > -1,

                weixin:u.toLowerCase().indexOf('micromessenger') > -1,

                Safari:u.indexOf('Safari') > -1

            };

        }(),

        language: (navigator.browserLanguage || navigator.language).toLowerCase()

    };


    function call_app()
    {
        if (browser.versions.ios) {


            //你的代码
            window.location.href = "taobao://";

            setTimeout(function(){

                //window.location.href = "你的app在商店中的地址";

                //window.location.href = "你的app在商店中的地址";  //为什么要加两遍我下面会说到

            },2000)


        }else if (browser.versions.android){

            //你的代码
            //window.location.href = "taobao://**";

            window.location.href = "taobao://";

            setTimeout(function(){

               // window.location.href = "你的app的下载地址";

            },2000)
        }
    }

    call_app();

</script>


</body>
</html>