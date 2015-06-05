function registerDeleteButtons() {

    jQuery('.merritt-delete-button').click(function(e) {
	e.preventDefault();
	id=jQuery(this).attr('id');
	url=jQuery(this).attr('title')+encodeURIComponent(id);
	jQuery.post(url,{csrf_token:csrf_token},function(data){
	    params = jQuery.parseJSON(data);
	    jQuery('#collection-'+params.id).remove();
	    jQuery("option[value='"+params.id+"']").remove();
	});
    });
}

function registerExportButton() {
    merritt_resubmission_flag = false;    
    jQuery('.merritt-link input#merritt_export').click(function(e) {    
	    if(!merritt_submission_flag)
		return;
	    e.preventDefault();
	    alert('One or more of the items you have chosen for export seems to already have an identifier in Merritt. New versions of these items will be created. Is this ok?');
	    jQuery(this).submit();
	});
}

jQuery(document).ready(function() {
     registerDeleteButtons();

    jQuery('#add_merritt_collection_button').click(function(e) {
	e.preventDefault();
	slug=jQuery('#add_merritt_collection').val();
	url=jQuery('#add_merritt_collection_button').attr('title')+encodeURIComponent(slug);
	jQuery.post(url,{csrf_token:csrf_token},function(data){
	    params = jQuery.parseJSON(data);
	    jQuery('#merritt-collections').append('<li id="collection-'+params.id+'">'+params.slug+'<button  class="merritt-delete-button" id="'+params.id+'" title="'+params.title+'">Delete</button></li>');
	    jQuery('#default_merritt_collection').append('<option value='+params.id+'>'+params.slug+'</option>');
	    jQuery('#no-collections').remove();
	    registerDeleteButtons();
	});
    });

    jQuery('#merritt-select-all').click(function(){
	jQuery('#merritt-items > li > input').prop('checked',true);
    });
    jQuery('#merritt-select-none').click(function(){
	jQuery('#merritt-items > li > input').prop('checked',false);
    });

    jQuery('#merritt-search-form > #submit_search_advanced').click(function(e) {
	e.preventDefault();
	url= jQuery('#merritt-search-form').attr('action');

	jQuery('#merritt-selection-buttons').show();

	jQuery('#merritt-items').html('');

	jQuery.post(url,jQuery('#merritt-search-form').serialize(),function(rdata){
	    jQuery('#merritt-export').show();
	    data = jQuery.parseJSON(rdata);
	    items = data.items;
	    merritt_resubmission_flag = data.flag;
	    checkboxes = true;
	    if(items.length > 200) {
		jQuery('#merritt-export-form').prepend('<input type="hidden" name="bulkAdd" value="true" />');
		checkboxes = false;
	    }
	    itemsUl = jQuery('#merritt-items');
	    jQuery.each(items,function(i,item){
		
		itemLi  = '<li id="merritt-item-div-'+item.id+'">';
		if(checkboxes) 
		    itemLi += '<input type="checkbox" name="export_items['+item.id+']" />';
		itemLi += item.thumb+'<div><h3>'+item.title+'</h3><p>'+item.description+'</p></div></li>';
		itemsUl.append(itemLi);
	    });
	});
    });

});
