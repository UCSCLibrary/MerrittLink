<?php

$head = array('bodyclass' => 'merritt-link primary', 
              'title' => html_escape(__('MerrittLink | Export items')));
echo head($head);
echo flash();
echo("<h2>Select items to Export to Merritt</h2>"); 
echo('<button id="merritt-toggle-search" style="display:none">Show/Hide search form</button>');
echo $this->searchForm;
echo '
<div id="merritt-selection-buttons" style="display:none;">
  <button id="merritt-select-all">Select All</button>
  <button id="merritt-select-none">Select None</button>
</div>
';
echo '<form id="merritt-export" style="display:none;" method="post"><ul id="merritt-items"></ul>';
echo '<div class="field"><label for="merritt-collection" class="two columns alpha">Merritt collection to export into</label><select name="merritt_collection" class = "five columns omeka">';
foreach($this->collectionOptions as $option => $value) {
    //echo '<option value="'.$option['id'].'">'.$option['title'].'</option>';
    echo '<option value="'.$option.'">'.$value.'</option>';
}
echo '</input>';
echo '<input type="submit" name="merritt_export" value="Export Items" /></form>';
echo(foot());
?>