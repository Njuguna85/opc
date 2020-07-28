jQuery(document).ready(function(){  
  
  
  window.fbAsyncInit = function() {
      FB.init({
        appId      : fbjk_fbinit_localize.appid,
        xfbml      : true,
        version    : 'v2.10'
      });

      // Get Embedded Video Player API Instance
      var my_video_player;
      FB.Event.subscribe('xfbml.ready', function(msg) {
        if (msg.type === 'video') {
          my_video_player = msg.instance;
          my_video_player.unmute();
        }
      });
    };
  
  (function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0];
          if (d.getElementById(id)) return;
          js = d.createElement(s); js.id = id;
          js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.10&appId="+fbjk_fbinit_localize.appid;
          fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));        

      var removebg = window.setInterval(function(){               
        var counter = 0;        
        jQuery(".fb-video iframe").each(function(i, obj){
          if(jQuery(this).css("visibility") == "visible")
          {
            jQuery(this).parent().parent().css("background-image","none");
            counter++;
          }
        });
        if(counter == jQuery(".fb-video iframe").length)
          clearInterval(removebg);
      }, 3000);
  
});