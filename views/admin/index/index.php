<?php
$head = array('bodyclass' => 'merritt-link primary', 
              'title' => html_escape(__('MerrittLink | Export items')));
echo head($head);
echo flash();
?>
<h2>Select items to Export to Merritt</h2>
<button id="merritt-toggle-search" style="display:none">Show/Hide search form</button>
<?php echo $this->searchForm; ?>
<div id="merritt-selection-buttons" style="display:none;">
  <button id="merritt-select-all">Select All</button>
  <button id="merritt-select-none">Select None</button>
</div>

<form id="merritt-export" style="display:none;" method="post"><ul id="merritt-items"></ul>
<div class="field"><label for="merritt-collection" class="two columns alpha">Merritt collection to export into</label><select name="merritt_collection" class = "five columns omeka">
<?php foreach($this->collectionOptions as $option => $value) : 
    //echo '<option value="'.$option['id'].'">'.$option['title'].'</option>';?>
    <option value="<?php echo $option; ?>"><?php echo $value; ?></option>
<?php endforeach; ?>
</input>
<input type="submit" name="merritt_export" value="Export Items" />
  <?php echo $this->csrf;?>
</form>
    </div>


<div class="jqmWindow" id="dialog">
  <h2>
    Confirm Duplicate Submission
    </h2>
  <p>
    One or more of the items you have chosen for export seems to already have an identifier in Merritt. New versions of these items will be created in Merritt. Is this ok?
  </p>
		     <a href="#" id="dialogok"><button>OK</button></a> 
		     <a href="#" class="jqmClose"><button>Cancel</button></a>
		     </div>

		     
<?php echo foot();?>
