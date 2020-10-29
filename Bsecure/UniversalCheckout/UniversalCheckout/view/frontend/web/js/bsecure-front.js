jQuery(function(){
	jQuery(document).on('click', '.bsecure-checkout-button', function(){
		var btn = jQuery(this);		
		var msgArea = jQuery(".page.messages");		
		jQuery('.checkout').trigger('processStart');

		jQuery.post(baseUrl+'bsecure/index/bsecureajax',{"action":"bsecure_send"},function(res){ 
		},"json").done(function(res) {
		
		if(res.status){

			if(res.redirect){

				document.location = res.redirect;

			}else{
				scrollToMessageArea();
				msgArea.html(printHtmlMsg(res.msg,'error'));
			}
			
		}else{
			scrollToMessageArea()
			msgArea.html(printHtmlMsg(res.msg,'error'));
		
		}
		})
		.fail(function(res) {
			scrollToMessageArea();
			msgArea.html(printHtmlMsg('An error occurred while sending your request. Please try again','error'));

		
		})
		.always(function(res) {
			jQuery('.checkout').trigger('processStop');		
		});
	})
				
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