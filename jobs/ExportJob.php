<?php
/**
 * MerrittLink export job
 *
 * @package MerrittLink
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The MerrittLink export job class.
 *
 * @package MerrittLink
 */
class MerrittLink_ExportJob extends Omeka_Job_AbstractJob
{
    private $_bid;

   public function setBid($bid){
        $this->_bid = $bid;
    }

   public function perform() {
       for($i=0;$i<96;$i++){
	 
           $exports = get_db()->getTable('MerrittExportJob')->findBy(array('bid'=>$this->_bid));

           foreach($exports as $export) {

               if($status = $export->checkStatus()){
                   $export->status = $status;
                   $export->save();
		   if($status==='completed' || $status==="failed")
		     die();
               }
           }               

           sleep(900);
       }
   }
}
