
<div class="field">
    <div id="merrit-username-label" class="two columns alpha">
        <label for="merritt_username"><?php echo __('Username'); ?></label>
    </div>
    <div class="inputs five columns omega">
<?php echo get_view()->formText('merritt_username',get_option('merritt_username'),array()); ?>
        <p class="explanation"><?php echo __('The username of your CDL account'); ?></p>
    </div>
</div>

<div class="field">
    <div id="merrit-password-label" class="two columns alpha">
        <label for="merritt_password"><?php echo __('Password'); ?></label>
    </div>
    <div class="inputs five columns omega">
<?php echo get_view()->formText('merritt_password',get_option('merritt_password'),array()); ?>
        <p class="explanation"><?php echo __('The password of your CDL account'); ?></p>
    </div>
</div>

<?php
$options = get_db()->getTable('MerrittCollection')->findPairsForSelectForm();
//$options = array();
?>
<div class="field">
<div id="default-merritt-collection-label" class="two columns alpha">
     <label for="default_merritt_collection"><?php echo __('Default Merritt Collection'); ?></label>
</div>
<div class="inputs five columns omega">
<?php
     echo get_view()->formSelect('default_merritt_collection',get_option('default_merritt_collection'),array(),$options);
?>
<p class="explanation"><?php echo __('Choose the default collection in Merritt to push Omeka items into.');?></p>
</div>
</div>
<div class="field">
<div id="add-merritt-collection-label" class="two columns alpha">
     <label for="add_merritt_collection"><?php echo __('New Merritt Collection'); ?></label>
</div>
<div class="inputs five columns omega">
<?php
// echo get_view()->formText('add_merritt_collection','',array());
?>
<input type="text" id="add_merritt_collection" name="add_merritt_collection" />
<button id="add_merritt_collection_button" title="<?php echo absolute_url('merritt-link/collection/add/slug/');?>">Add Collection</button>
     <p class="explanation"><?php echo __('Add a new Merritt Collection'); ?></p>
</div>
</div>
<h3>Merritt Collections</h3>
<ul id="merritt-collections" class="merritt-config-button">
<?php
     $collectionsTable = get_db()->getTable('MerrittCollection');
//$collections=array();
$collections=array();
try{
    $collections = $collectionsTable->findAll();
}catch(Exception $e) {
}
if(empty($collections))
    echo '<li id="no-collections">No collections</li>';
foreach($collections as $collection) {
    echo '<li id="collection-'.$collection['id'].'">'.$collection['slug'].'<button class="merritt-delete-button" id="'.$collection->id.'" title="'.absolute_url('merritt-link/collection/delete/id/').'">Delete</button></li>';
}
?>
</ul>
