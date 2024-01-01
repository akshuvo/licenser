(function($) {
	"use strict";
	$(document).ready(function(){
		
		$(document).on('click', '.show_manage_activations_details a', function(e){
            e.preventDefault();
            jQuery('.manage-activations').removeClass('active');
            $(this).closest('td').find('.manage-activations').toggleClass('active');
        });

        $(document).on('click', '.manage-activations .activations-close-modal', function(e){
            e.preventDefault();
            $(this).closest('td').find('.manage-activations').removeClass("active");
        });


	});

})(jQuery);