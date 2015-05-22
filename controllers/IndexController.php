<?php
/**
 * MerrittLink
 *
 * @package MerrittLink
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The MerrittLink index controller class.
 *
 * @package MerrittLink
 */
class MerrittLink_IndexController extends Omeka_Controller_AbstractActionController
{    

  /**
   * The default action to display the export form and process it.
   *
   * This action runs before loading the main export form. It 
   * processes the form output if there is any, and populates
   * some variables used by the form.
   *
   * @param void
   * @return void
   */

  public function indexAction()
  {
      //initialize flash messenger for success or fail messages
      $flashMessenger = $this->_helper->FlashMessenger;
      if(!function_exists('curl_version'))
          $flashMessenger->addMessage("The program lib-curl must be installed to use this plugin. Please contact your system administrator.",'error');
      if ($this->getRequest()->isPost()) {
          try{
              if( $successMessage = $this->_processPost())
                  $flashMessenger->addMessage($successMessage,'success');
              else 
                  $flashMessenger->addMessage('There was a problem exporting your documents to Merritt. '.$successMessage,'error');
          } catch (Exception $e){
              $flashMessenger->addMessage($e->getMessage(),'error');
          }
      }
      $this->view->collectionOptions = get_db()->getTable('MerrittCollection')->findPairsForSelectForm();
      $this->view->searchForm = items_search_form(array('id'=>'merritt-search-form'),'#','View Items');   
  }

  private function _getSiteBase() {
      $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
      return $protocol.$_SERVER['HTTP_HOST'];
  }

  private function _processPost() {

      if(isset($_POST['search'])) 
          $this->_returnSearchResults();

      $collection_id = $_POST['merritt_collection'];
      $collection = get_db()->getTable('MerrittCollection')->find($collection_id);

      $user = current_user();

      include_once(dirname(dirname(__FILE__)).'/models/MerrittExportJob.php');
      $job = new MerrittExportJob();
      $job->status = "not submitted";
      $job->user_id=$user->id;
      $job->items=serialize($_POST['export_items']);
      $job->save();

      $url = $this->_getSiteBase().public_url('merritt-link/manifest/batch/job/'.$job->id);

      $rval =  $this->_submitBatchToMerritt($url,$collection->slug,$job);

      return $rval;
  }

  private function _getCollectionOptions() {
      return $collectionTable;
  }

  private function _submitBatchToMerritt($batchManifestUrl,$collection,$job=false) {

      $url='https://merritt.cdlib.org/object/ingest';
      $file = file_get_contents($batchManifestUrl);
      $tmpfname = tempnam(sys_get_temp_dir(), "merritt_");
      $handle = fopen($tmpfname, "w");	 
      fwrite($handle,$file);
      fclose($handle);	

      $curlCommand = 'curl -u '.get_option('merritt_username') . ":" . get_option('merritt_password');

      $curlCommand .= ' -F "file=@'.$tmpfname.'"';
      $curlCommand .= ' -F "type=container-batch-manifest"';
      $curlCommand .= ' -F "responseForm=json"';
      $curlCommand .= ' -F "profile='.$collection.'"';
      $curlCommand .= ' https://merritt.cdlib.org/object/ingest';
      
      $postFields = array(
          'type' => 'container-batch-manifest',
          'responseForm' => 'xml',
          'profile' => $collection,
          'file' => '@'.$tmpfname
      );
  
      exec($curlCommand,$output);

      unlink($tmpfname);
      
      $json = implode("\n",$output);
      $data = json_decode($json);
      $state = $data->{'bat:batchState'};
      $status = $state->{'bat:batchStatus'};
      $bid = $state->{'bat:batchID'};

      //start job to check complete for the next 2 hours

      if($status == 'ERROR' || $status == 'FAILED') {
          if($job){
              $job->status = "failed";
              $job->save();
          }
         return 'Failure exporting to merritt.'; 
      }
      if(!$job) {
          $job = new MerrittExportJob();
      }
      $job->status = strtolower($status);
      $job->bid = $bid;
      $job->save();

      fire_plugin_hook('export',array('records'=>unserialize($job->items),'service'=>'Merritt'));
      
      return "Successfully submitted Omeka items to Merritt with batch identifier ".$state->{'bat:batchID'}.". Items are now ".strtolower($status)."." ;
  }

  private function _returnSearchResults() {
      
      $selectItems = true;
      $itemTable = get_db()->getTable('Item');
      $select = $itemTable->getSelect();
      $itemTable->applySearchFilters($select,$_POST);
      $items=$itemTable->fetchObjects($select);

      if(count($items) > 200) {
          $selectItems = false;
          $items = array_slice($items,0,20);
      }
      $return = array();
      foreach($items as $item) {

          $description = metadata($item,array("Dublin Core","Description"),array('snippet'=>'250'));
          if(!$description)
              $description = "No description available";
          $title = metadata($item,array("Dublin Core","Title"));
          if(!$title)
              $title = "Untitled";
          $thumb = item_image('square_thumbnail',array(),0,$item);
          if(!$thumb)
              $thumb = '<img alt="No Image Available" src="xxx" style="border:1px solid black; width: 100px;"/>';

          $return[]=array(
              'id'=>$item->id,
              'title'=> $title,
              'description'=> $description,
              'thumb'=> $thumb,
          );
      }
      die(json_encode($return));
  }
}
