jQuery(document).ready(function() {

	jQuery('#billing_transit_branch_number_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	});
	jQuery('#billing_financial_institution_number_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	});
	jQuery('#billing_bankname_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	});
	jQuery('#billing_bank_address1_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	});
	jQuery('#billing_bank_address2_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	});
	jQuery('#billing_bank_address3_field').hide(function(){
		jQuery(this).removeClass("validate-required");
		jQuery(this).removeClass("woocommerce-validated");
	
	});
	jQuery('#billing_country').val("US").trigger('change');


	jQuery('#billing_country').on('change',function(){
		var country = jQuery(this).val();
		if(country == 'CA'){
			jQuery('#billing_transit_branch_number_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#billing_financial_institution_number_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#billing_bankname_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#billing_bank_address1_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#billing_bank_address2_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#billing_bank_address3_field').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			});
			jQuery('#routing_number').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");
			});

		}else if(country == 'US'){
			jQuery('#billing_transit_branch_number_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#billing_financial_institution_number_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#billing_bankname_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#billing_bank_address1_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#billing_bank_address2_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#billing_bank_address3_field').hide(function(){
				jQuery(this).removeClass("validate-required");
				jQuery(this).removeClass("woocommerce-validated");		
			});
			jQuery('#routing_number').show(function(){
				jQuery(this).addClass("validate-required");
				jQuery(this).addClass("woocommerce-validated");
			
			});
		}	
	});


	jQuery("form.woocommerce-checkout").on('submit', function () {
		event.preventdefault();
		jQuery('#billing_transit_branch_number_field').hide(function(){
			jQuery(this).removeClass("validate-required");
			jQuery(this).removeClass("woocommerce-validated");		
		});
	});
	
}); //END JQuery(document).ready(function()]
