jQuery(document).ready(function() {

    jQuery('#add_merritt_collection_button').click(function(e) {
	e.preventDefault();
	slug=jQuery('#add_merritt_collection').val();
	url=jQuery('#add_merritt_collection_button').attr('title')+encodeURIComponent(slug);
	jQuery.get(url,'',function(data){
	    params = jQuery.parseJSON(data);
	    jQuery('#merritt-collections').append('<li>'+params.slug+'<button  class="merritt-config-button" id="merritt-'+params.id+'-delete">Delete</button></li>');
	    jQuery('#default_merritt_collection').append('<option value='+params.id+'>'+params.slug+'</option>');
	    jQuery('#no-collections').remove();
	});
    });

    jQuery('.merritt-delete-button').click(function(e) {
	e.preventDefault();
	id=jQuery(this).attr('id');
	url=jQuery(this).attr('title')+encodeURIComponent(id);
	jQuery.get(url,'',function(data){
	    params = jQuery.parseJSON(data);
	    jQuery('#collection-'+params.id).remove();
	    jQuery("option[value='"+params.id+"']").remove();
	});
    });

    jQuery('#merritt-search-form > #submit_search_advanced').click(function(e) {
	e.preventDefault();
	url= jQuery('#merritt-search-form').attr('action');

	jQuery('#merritt-items').html('');

	jQuery.post(url,jQuery('#merritt-search-form').serialize(),function(data){
	    jQuery('#merritt-export').show();
	    items = jQuery.parseJSON(data);
	    //console.log(items);
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
		console.log(itemLi);
		itemsUl.append(itemLi);
	    });
	});
    });

});