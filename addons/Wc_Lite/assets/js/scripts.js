jQuery(document).ready(function(){

    jQuery(document).on('click', '.show_manage_activations_details a', function(e){
        e.preventDefault();
        jQuery('.manage-activations').removeClass('active');
        jQuery(this).closest('td').find('.manage-activations').toggleClass('active');
    });

    jQuery(document).on('click', '.manage-activations .activations-close-modal', function(e){
        e.preventDefault();
        jQuery(this).closest('td').find('.manage-activations').removeClass("active");
    });

});