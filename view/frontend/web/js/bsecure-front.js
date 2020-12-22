var bsecureWindow = "";
require([
  'jquery',
  'jquery/ui',
], function($){
	if(document.location.hash == '#bsecure-auto-checkout'){

		jQuery(".bsecure-popup-loader").show();
	}
	jQuery(document).on('click', '.bsecure-checkout-button', function(){

		openBsecureWindow(BASE_URL+"/checkout/cart/#bsecure-auto-checkout");
		var btn = jQuery(this);		
		var msgArea = jQuery(".page.messages");		
		jQuery('.checkout').trigger('processStart');

		jQuery.post(BASE_URL+'bsecure/index/bsecureajax',{"action":"bsecure_send"},function(res){ 
		},"json").done(function(res) {
		
		if(res.status){

			if(res.redirect){

				bsecureWindow.location.href = res.redirect;

				//document.location = res.redirect;

			}else{
				closeBsecureWindow();
				scrollToMessageArea();
				msgArea.html(printHtmlMsg(res.msg,'error'));
			}
			
		}else{
			closeBsecureWindow();
			scrollToMessageArea()
			msgArea.html(printHtmlMsg(res.msg,'error'));
		
		}
		})
		.fail(function(res) {
			closeBsecureWindow();
			scrollToMessageArea();
			msgArea.html(printHtmlMsg('An error occurred while sending your request. Please try again','error'));

		
		})
		.always(function(res) {
			jQuery('.checkout').trigger('processStop');		
		});
	})


	jQuery(document).on('click', '.bsecure-popup-overlay', function(){

		if(bsecureWindow.closed) {        
	        jQuery(".bsecure-popup-overlay").hide();       
	    }

	});		
				
});




function scrollToMessageArea(){

	jQuery('html, body').animate({
        scrollTop: jQuery("#contentarea").offset().top
    }, 1000);
}



function printHtmlMsg(msg,typ){

	var htmlText = '<div data-bind="scope: \'messages\'"><div role="alert" data-bind="" class="messages"><div data-bind="" class="message-'+typ+' '+typ+' message" data-ui-id="message-'+typ+'"><div data-bind="">'+msg+'</div></div></div></div>';

	return htmlText;

}




function openBsecureWindow(url) {

	jQuery(".bsecure-popup-overlay").show();
	var h = 700;
	var w = 400;
	var left = (screen.width/2)-(w/2);
  	var top = (screen.height/2)-(h/2);  	

  	bsecureWindow = window.open(url, "_blank", 'toolbar=no,scrollbars=yes,resizable=yes,width='+w+', height='+h+', top='+top+', left='+left);   	

  	 var timer = setInterval(function() { 
	    if(!bsecureWindow || bsecureWindow.closed || typeof bsecureWindow.closed == 'undefined') {
	        clearInterval(timer);
	        isPopupBlocked(true);
	        jQuery(".bsecure-popup-overlay").hide();       
	    }

	}, 500);
  	
}


function closeBsecureWindow(){
	bsecureWindow.close();
	jQuery(".bsecure-popup-overlay").hide();
}


function isPopupBlocked(isBlocked){

	if(isBlocked){

		//console.log('isBlocked: ', isBlocked);
	}
}

// Receive message from bsecure server //
window.addEventListener("message", (event)=>{	
	
    if (event.origin == "https://order-dev.bsecure.app" || event.origin == "https://checkout-stage.bsecure.app" || event.origin == "https://order.bsecure.pk" ){

    		bsecureWindow.close();

		   	if(typeof event.data.hrf !== 'undefined'){
		   		jQuery('.checkout').trigger('processStart');
		   		window.location.href=event.data.hrf;
		   }

    }

    return; 
	      
	  
}); 

function focusBsecureWindow() {  	
  	bsecureWindow.focus();
}


