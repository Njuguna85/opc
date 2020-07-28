
function fbjkcreateCookie(name,value,mins) {
    if (mins) {
        var date = new Date();
        date.setTime(date.getTime()+(mins*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function fbjkreadCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function fbjkeraseCookie(name) {
    fbjkcreateCookie(name,"",-1);
}

function fbjkshowNotification(){
  if(jQuery("#fbjk-bar").length)
  {
    return;
  }
  if(fbjkreadCookie('fbjk_closenotification') == null)
  {
    var stickycss = '';
    if(fbjk_notificationbar_localize.sticky == 'yes')
    {
      stickycss = 'position:fixed;';
    }
    jQuery('body').prepend('<div id="fbjk-bar" style="'+stickycss+' background-color:#'+
            fbjk_notificationbar_localize.barbg+'"><a class="fbjk-fullwidth" href="'+fbjk_notificationbar_localize.link+'"><span style="color:#'+
            fbjk_notificationbar_localize.textcolor+'">'+
            fbjk_notificationbar_localize.msg+'</span></a><a style="color:#'+
            fbjk_notificationbar_localize.textcolor+'" href="#" class="fbjk-close"></a></div>');

    if(fbjk_notificationbar_localize.animated == 'yes')
    {
      function fbjk_loop() {
            //jQuery('#fbjk-bar').css({opacity:1});
            if(jQuery('#fbjk-bar').css('opacity') == 1)
              var val = '0.5';
            else
              var val = '1';
            jQuery('#fbjk-bar').animate ({
                opacity: val,
            }, 1500, 'linear', function() {
                fbjk_loop();
            });
        }
        fbjk_loop();
    }
    var pt = jQuery('body').css('padding-top');
    jQuery('body').css('padding-top', (parseFloat(pt) + jQuery('#fbjk-bar').height()) + 'px');
    
    var fixedels = jQuery('*').filter(function () { 
        return jQuery(this).css('position') == 'fixed';
    });
                   //console.log(fixedels);
    fixedels.each(function(){  //move all the fixed elements below notification bar
      if(jQuery(this).attr('id') == 'fbjk-bar')
        return;
      var topdist = jQuery(this).css('top');
      jQuery(this).css('top', (parseFloat(topdist) + jQuery('#fbjk-bar').height()) + 'px');
    });
    
    jQuery('#fbjk-bar a.fbjk-close').on('click', function(e){
      e.preventDefault();
      var barheight = jQuery('#fbjk-bar').height();
      var fixedels = jQuery('*').filter(function () { 
        return jQuery(this).css('position') == 'fixed';
      });
      fixedels.each(function(){ //get back all the fixed elements to their original positions
        var topdist = jQuery(this).css('top');
        jQuery(this).css('top', (parseFloat(topdist) - barheight) + 'px');
      });
      
      jQuery('body').css('padding-top', parseFloat(pt) + 'px');
      jQuery('#fbjk-bar').remove();
      fbjkcreateCookie('fbjk_closenotification','yes','15');
      
    });
  }
}

function fbjkhideNotification(){
  jQuery("#fbjk-bar").remove();
}
function fbjkcheckNotificationbarpage(){
    jQuery.ajax({
            type:'GET',
            url: fbjk_notificationbar_localize.ajaxurl,
            success: function(value) {
              value = JSON.parse(value);
              if(value.livestatus == "live")
              {
                fbjkshowNotification();
              }
              else
              {
                fbjkhideNotification();
              }
              setTimeout( fbjkcheckNotificationbarpage, 25000 );
            }
          });
}
jQuery(document).ready(function(){  
	
        fbjkcheckNotificationbarpage();
  
});
