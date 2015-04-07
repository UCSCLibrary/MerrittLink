<?php
/**
 * A Merritt Export Job
 * 
 * @package MerrittLink
 *
 */
class MerrittExportJob extends Omeka_Record_AbstractRecord
{
    /**
     *@var int The record ID
     */
    public $id; 

    /**
     *@var int The user ID of the creator of this job
     */
    public $user_id;

    /**
     *@var string The items  
     */
    public $items;

    /**
     *@var date The time  
     */
    public $time;

    /**
     *@var string The status of the request ("not submitted","queued", "failed", "success")  
     */
    public $status;

    /**
     *@var The batch identified defined when the ingest is submitted to Merritt
     */
    public $bid;

    public function checkStatus() {

        $pollurl = "https://merritt.cdlib.org/istatus/bid/";
        $pollurl .= $this->bid."/jobfull";
        
        $c = curl_init($pollurl);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, 'login='.get_option('merritt_username').'&password='.get_option('merritt_password'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $json = curl_exec($c);
        curl_close($c);        

        if(!isset($json) || !$json || ! $statusInfo = json_decode($json) || count($statusInfo['rows']) < 1 )
            return false;

        $status = "completed";
        foreach($statusInfo['rows'] as $job) {
            $jid = $job['id'];
            if ($job['value'] === "FAILED"){
                $status = "failed";
                $this->fail($job);
            }
            if ($job['value'] === "CONSUMED")
                $status = "consumed";
            if ($job['value'] != "COMPLETED" && $status == "completed")
                $status = "pending";
            if(isset($job['doc']['jobState']['primaryID'])){
                $item_id = end(explode(':',$job['doc']['jobState']['localID']));
                $ark = $job['doc']['jobState']['primaryID'];
                $arkElement = get_db()->getTable('Element')->findByElementSetNameAndElementName('Item Type Metadata','ARK');
                $arkMeta = new ElementText();
                $elementText->recordType = 'Item';
                $elementText->record_id = $item_id;
                $elementText->element_id = $arkElement->id;
                $elementText->html = 0;
                $elementText->text = $ark;
                $elementText->save();
            }
        }
        return $status;
    }

    public function fail($job) {
        $user = get_record_by_id('User',$this->user_id);
        mail('$user->email','Merritt Link Failure','Sorry to report that the transfer from Omeka to Merritt that you scheduled has failed. The failed job ID is: '.$job['id'].'. If this happens only once, it might be a Merritt glitch. Hope you can sort out the problem!');        
    }

    
    protected function beforeSave($args)
    {
        $oldItem = get_record_by_id('MerrittExportJob',$this->id);
        if($oldItem->status==="not submitted" && isset($this->bid)) {
            //start polling job
            $options = array('bid'=>$this->bid);
            include_once(dirname(dirname(__FILE__)).'/jobs/ExportJob.php');
            $dispacher = Zend_Registry::get('job_dispatcher');
            $dispacher->sendLongRunning('MerrittLink_ExportJob',$options);
            $this->status="newly submitted";
        }
    }
}
?>