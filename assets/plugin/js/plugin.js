jQuery(document).ready(function($) {
	//select all
	$("#cb-select-all-item").click(function(){
		var checked=$(this).prop("checked");		
		$(".check-item").each(function(){			
				$(this).prop("checked",checked);			
		});
	});
	
    //edit data
    jQuery('a.cf7sh-edit-value').click(function(event) {		
        var rid = jQuery(this).data('rid');		
        jQuery('form#cf7sh-modal-form-edit-value input[name="rid"]').attr('value', rid);

        jQuery('form#cf7sh-modal-form-edit-value input[class^="field-"]').attr('value', 'Loading...');
        jQuery.ajax({
            url: ajaxurl + '?action=cf7sh_edit_value',
            type: 'POST',
            data: {'rid': rid},
        })
        .done(function(data) {	
            var json = jQuery.parseJSON(data);			
            jQuery.each(json, function(index, el) {				
				$(".li-field-"+index).show();
                jQuery('form#cf7sh-modal-form-edit-value .field-' + index).attr('value', el);
            });
            jQuery('#cf7sh-modal-form-edit-value').removeClass('loading');
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            console.log("complete");
        });
        
    });  

    //search data
    jQuery('#cf7sh-search-btn').click(function(event) {		
        var $this = jQuery(this);
        var form = jQuery('#cf7sh-admin-action-frm');  
		var q_startdate = jQuery('#cf7sh-startdate-q').val();
		var q_enddate = jQuery('#cf7sh-enddate-q').val();	      
		var txtSearch=jQuery('#cf7sh-search-q').val();
		var q_search ="";
		if(txtSearch!=undefined){
			//history page
			q_search=jQuery('#cf7sh-search-q').val();
		}else{
			//analytics
			if(q_startdate==""){
				alert("start date is required.")
				return false;
			}
			if(q_enddate==""){
				alert("end date is required.")
				return false;
			}
		}
		
		var fid = jQuery('input[name="fid"]', form).val();
    	var url = $this.data('url');
    	location.replace(url + '&fid=' + fid +'&search='+ q_search+ '&startdate=' + q_startdate+ '&enddate=' + q_enddate);		
    });

});
