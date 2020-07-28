jQuery(document).ready(function(){  
  
  jQuery("#field_videotype").on('change', function(){
    
    if(jQuery(this).val() == 'live'){
      jQuery('.forlive').show();
      jQuery('.forrecorded').hide();
      jQuery('.foruploaded').hide();
      jQuery('.forrecordedanduploaded').hide();
      jQuery('#field_cacherefreshtime').parent().parent().hide();
    }
    if(jQuery(this).val() == 'recorded'){
      jQuery('.forlive').hide();
      jQuery('.forrecorded').show(); 
      jQuery('.foruploaded').hide();
      jQuery('.forrecordedanduploaded').show();
      jQuery("#field_usecache").change();
    }
    
    if(jQuery(this).val() == 'uploaded'){
      jQuery('.forlive').hide();
      jQuery('.forrecorded').hide(); 
      jQuery('.foruploaded').show();
      jQuery('.forrecordedanduploaded').show();
      jQuery("#field_usecache").change();
    }
    
  }); 

  jQuery("#field_vidsource").on('change', function(){
    
    if(jQuery(this).val() == 'myfbpage'){
      jQuery('#field_pageusername').parent().parent().show();
      jQuery('#field_groupid').parent().parent().hide();
    }
    
    if(jQuery(this).val() == 'myfbgroup'){
      jQuery('#field_pageusername').parent().parent().hide();
      jQuery('#field_groupid').parent().parent().show();
    }
    
    if(jQuery(this).val() == 'me'){
      jQuery('#field_pageusername').parent().parent().hide();
      jQuery('#field_groupid').parent().parent().hide();
    }
    
  }); 

  jQuery("#field_notificationsource").on('change', function(){
    
    if(jQuery(this).val() == 'myfbpage'){
      jQuery('#field_notificationpageusername').parent().parent().show();
      jQuery('#field_notificationgroupid').parent().parent().hide();
    }
    
    if(jQuery(this).val() == 'myfbgroup'){
      jQuery('#field_notificationpageusername').parent().parent().hide();
      jQuery('#field_notificationgroupid').parent().parent().show();
    }
    
    if(jQuery(this).val() == 'me'){
      jQuery('#field_notificationpageusername').parent().parent().hide();
      jQuery('#field_notificationgroupid').parent().parent().hide();
    }
    
  });   
  
  jQuery("#field_alternatebehaviour").on('change', function(){
    if(jQuery(this).val() == 'showmsg'){
      jQuery('#field_alternatemsg').parent().parent().show();
    }
    else
    {
      jQuery('#field_alternatemsg').parent().parent().hide();
    }
  }); 
  
  jQuery("#field_usecache").on('change', function(){
    if(jQuery(this).val() == 'yes'){
      jQuery('#field_cacherefreshtime').parent().parent().show();
    }
    else
    {
      jQuery('#field_cacherefreshtime').parent().parent().hide();
    }
  }); 
  
  jQuery("#fbjk-fblogin").on('click', function(e){
    e.preventDefault();
    logInWithFacebook();
  });
  
  
});

