var bsecureWindow = "";
require([
  'jquery',
  'jquery/ui',
], function($){

	if(document.location.hash == '#bsecure-auto-checkout'){

		jQuery("body").addClass("bsecure-popup-handle-loader");
		jQuery(".bsecure-popup-loader").show();
	}
	jQuery(document).on('click', '.bsecure-checkout-button', function(){

		openBsecureWindow(BASE_URL+"checkout/cart/?hosted=1#bsecure-auto-checkout");		
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


	jQuery(document).on('change',"[name='country_id']",function(){
		
	 	//for country
	 	var country_id = jQuery(this).val();		 	
	    jQuery("input[name='telephone']").intlTelInput("setCountry", country_id.toLowerCase());

	});		


	jQuery(document).on("click","#checkout .new-address-popup button",function(){
           
		jQuery(".modal-inner-wrap .form-shipping-address").find("input[name='telephone']").intlTelInput("setCountry","pk");

	});

	if(jQuery(".bsecure-btn-checkout-pg").length > 0){

		jQuery("body .page-wrapper").addClass("bSecure-Wrapper");
	}

	 //wait until the last element (.payment-method) being rendered
    var existCondition = setInterval(function() {
		if (jQuery('.checkout-shipping-method').length) { 
		    clearInterval(existCondition);
			// Handle bSecure checkout button at checkout pg
			if(jQuery(".bsecure-checkout-button-wrapper").length > 0 ){
				var bsecure_checkout_button_wrapper = jQuery(".bsecure-checkout-button-wrapper").html();
			    if(jQuery(".bsecure-checkout-button-wrapper-aside").length < 1){

			    	//if(detectMob()){
			    		jQuery(".opc-wrapper").prepend('<div class="bsecure-checkout-button-wrapper-aside">' + bsecure_checkout_button_wrapper + '</div>');
			    	//}
			        jQuery(".opc-summary-wrapper").prepend('<div class="bsecure-checkout-button-wrapper-aside">' + bsecure_checkout_button_wrapper + '</div>');
			        jQuery(".bsecure-checkout-button-wrapper").remove();
			    }
			}

			setTimeout(function(){
				jQuery(".table-checkout-shipping-method").find("#label_method_bsecureshipping_bsecureshipping").parents("tr").remove();
			},100);
		}
		
	},200);


	var existCondition = setInterval(function() {
		if (jQuery('#label_method_bsecureshipping_bsecureshipping').length) { 
		    clearInterval(existCondition);
		    jQuery(".table-checkout-shipping-method").find("#label_method_bsecureshipping_bsecureshipping").parents("tr").remove();
		}
		
	},200);


	var existCondition = setInterval(function() {
		if (jQuery("input[name='telephone']").length > 0 ) {
			if (jQuery("input[name='telephone']").val().includes("undefined")) { 
			    clearInterval(existCondition);
			    var country_id = jQuery("select[name='country_id']").val().toLowerCase();		 	
	            
	            jQuery("input[name='telephone']").val(jQuery("input[name='telephone']").val().replace("undefined", jQuery("li[data-country-code="+country_id+"]").data("dial-code")));
	            jQuery("input[name='telephone']").intlTelInput("setCountry", country_id.toLowerCase());
	        }	
        }	
	},200);


	jQuery(document).on("click", ".opc-progress-bar-item", function(){
		var country_id = jQuery("select[name='country_id']").val().toLowerCase();
		jQuery("input[name='telephone']").val(jQuery("input[name='telephone']").val().replace("undefined", jQuery("li[data-country-code="+country_id+"]").data("dial-code")));
		jQuery("input[name='telephone']").intlTelInput("setCountry", country_id.toLowerCase());
	});


	jQuery(document).ready(function($) {

	  if (window.history && window.history.pushState) {

	    //window.history.pushState('forward', null, './#forward');

	    $(window).on('popstate', function() {
	      
	    	var existCondition = setInterval(function() {
	    	if (jQuery("input[name='telephone']").length > 0 ) {
				if (jQuery("input[name='telephone']").val().includes("undefined")) { 
					    clearInterval(existCondition);
					    var country_id = jQuery("select[name='country_id']").val().toLowerCase();
			            jQuery("input[name='telephone']").val(jQuery("input[name='telephone']").val().replace("undefined", jQuery("li[data-country-code="+country_id+"]").data("dial-code")));
			            jQuery("input[name='telephone']").intlTelInput("setCountry", country_id.toLowerCase());
			        }
			    }		
			},200);

	    });

	  }
	});


	jQuery(document).on("click", ".bsecure-login-button", function(e){
    	e.preventDefault();
	    var loginUrl = jQuery(this).attr("href");    
	    openBsecureWindow(loginUrl+"?hosted=1#bsecure-auto-checkout");
	});
	
				
});


function detectMob() {
    const toMatch = [
        /Android/i,
        /webOS/i,
        /iPhone/i,
        /iPad/i,
        /iPod/i,
        /BlackBerry/i,
        /Windows Phone/i
    ];

    return toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
    });
}

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
	
	//console.log('event.origin In:', event.origin, 'event.data:', event.data);

    if (event.origin == "https://order-dev.bsecure.app" || 
    	event.origin == "https://checkout-stage.bsecure.app" || 
    	event.origin == "https://order.bsecure.pk" || 
    	event.origin == "https://login-dev.bsecure.app" || 
    	event.origin == "https://login-stage.bsecure.app" || 
    	event.origin == "https://login.bsecure.pk" ){
    	//console.log('event.origin In:', event.origin, 'event.data:', event.data);
    		bsecureWindow.close();
		   	if(typeof event.data.hrf !== 'undefined'){
		   		if(jQuery(".checkout").length > 0) {
		   			jQuery('.checkout').trigger('processStart');
		   		} else {
		   			jQuery('body').trigger('processStart');
		   		}
		   		
		   		window.location.href=event.data.hrf;
		   }

    }

    return; 
	      
	  
}); 

function focusBsecureWindow() {  	
  	bsecureWindow.focus();
}




