<?php
class Table_MerrittExportJob extends Omeka_Db_Table
{
    public function getPending() {
        $pending = $this->findBy(array('status'=>'pending'));
        $pending = array_merge($pending,$this->findBy(array('status'=>'queued')));
        $pending = array_merge($pending,$this->findBy(array('status'=>'consumed')));
        return $pending;
    }

    public function getLog($fields=false,$datestart=false,$dateend=false){
      if(!$fields) $fields = array();
      
      //ignore date range for now
      $jobs = $this->findAll();
      $data = array();

      foreach($jobs as $job) {
	$user = get_record_by_id('User',$job['user_id']);
	if(!$user) $user = new User;
	$item_ids = unserialize($job['items']);
	$time = $job['time'];
	foreach($item_ids as $item_id) {
	  $item = get_record_by_id('Item',$item->id);
	  if(!$item) $item = new Item;

	  foreach($fields as $fieldset => $fieldname)
	    $custom[$fieldname] = metadata($item,array($fieldset,$fieldname));

	  $data[] = array(
			 'item_id'=>$item_id,
			 'title'=>metadata($item,array('Dublin Core','Title')),
			 'user'=>$user->name,
			 'date'=>$time,
			 'description'=>metadata($item,array('Dublin Core','Description')),
			 'ark'=>$this->_getArk($item),
			 'custom'=>$custom
			 );
	}
      }
      return $data;
    }

    private function _getArk($item){
       //find ark, if previously saved to Merritt
            $pid = "";
            $identifiers = metadata($item,array('Dublin Core','Identifier'),'all');
            foreach ($identifiers as $identifier) {
                if(strpos($identifier,'ark')!==FALSE)
                    $pid = $identifier;
            }
	    return $pid
    }

}
?>