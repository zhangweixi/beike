    var isDidi = false;
    var wxshareData = {
        url: location.href,
        title: '滴滴校园专属',
        content: '抢超大额暑期出行券包',
        icon: location.origin+location.pathname.replace('index.html',"")+"images/wx.share.png",
        sina_weibo: false,
        qq_appmsg: false,
        qzone: false,
        alipay_appmsg: false,
        ailpay_timeline: false,
        ding_talk: false,
        success: function () {}
    };


    if (isDidi) {
        var wxconfigjs = "//static.udache.com/agility-sdk/1.0.3/aio.js";

    } else {

        var wxconfigjs = "./js/wx.config.js?time="+Math.random();

    }

    var windowloadtime=0;
    if(windowloadtime =0)
    {
        $('<script src=\"'+wxconfigjs+'\"></srcipt>').appendTo('head');    
        windowloadtime = 1;
    }

    

    
    window.onload = function () 
    {

        if (isDidi == false) 
        {
            var url = "https://www.yyclub.me/DidiQuanzhou/public/index/Didi_Tongqin/get_wx_cong?url="
            url += window.encodeURIComponent(location.href);
            $.get(url).success(function (res) 
            {
                
                var data = res.data;
                wx.config({
                    debug: false,
                    appId: data.appId,
                    timestamp: data.timestamp,
                    nonceStr: data.nonceStr,
                    signature: data.signature, // 必填，签名，见附录1
                    jsApiList: ["onMenuShareTimeline", "onMenuShareAppMessage"] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
                });
            })
        }

        //set_wx_share(wxshareData);
    };


    function set_wx_share(sharedata) {

        if (isDidi) 
        {
            D.setShare(sharedata);
            

        } else {

            var title = sharedata.title;
            var desc = sharedata.content;
            var link = sharedata.url;
            var img = sharedata.icon;

            wx.onMenuShareTimeline({
                title: title, // 分享标题
                link: link, // 分享链接
                imgUrl: img, // 分享图标
                success: function () {
                    var url = "//www.yyclub.me/DidiQuanzhou/public/index/Didi_Kaituan/record_share?url=" + urlencode(location.href);
                    $.get(url);
                },
                cancel: function () {
                        // 用户取消分享后执行的回调函数
                }
            });


            wx.onMenuShareAppMessage({
                title: title, // 分享标题
                desc: desc, // 分享描述
                link: link, // 分享链接
                imgUrl: img, // 分享图标
                success: function () {
                    var url = "//www.yyclub.me/DidiQuanzhou/public/index/Didi_Kaituan/record_share?url=" + urlencode(location.href);
                    $.get(url);
                },
                cancel: function () {
                        // 用户取消分享后执行的回调函数
                }
            });
        }
      
    }