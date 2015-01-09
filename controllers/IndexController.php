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

  private function _getItemsFromPost() {
      return array(); //TODO
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

      $job = new MerrittExportJob();

      if(isset($_POST['bulkAdd'])){
          $items = $this->_getItemsFromPost();
          $job->items=serialize($items);
      }else {
          $job->items=serialize($_POST['export_items']);
      }
      $job->save();
      $url = $this->_getSiteBase().public_url('merritt-link/manifest/batch/job/'.$job->id);//TODO url of batch manifest
      return $this->_submitBatchToMerritt($url,$collection->slug);
      /*
      require_once(dirname(dirname(__FILE__)).'/jobs/ExportJob.php');
      $options = array(
          'collection'=>$collection->slug,
          'fileurl'=>absolute_url('files/')
      );
      $file = get_record_by_id('file',73);
      die(file_display_url($file));
      if(isset($_POST['bulkAdd'])){
          $options['bulk']=true;
          $exporter->setBulk(true);
          $options['post'] = serialize($_POST);
      }else if(isset($_POST['merritt_export'])) {
          $options['bulk']=false;
          $options['items']= serialize($_POST['export_items']);
      }
      
      try{
          $dispacher = Zend_Registry::get('job_dispatcher');
          $dispacher->sendLongRunning('MerrittLink_ExportJob',$options);
      } catch (Exception $e) {
          throw($e);
      }
      */
  }

  private function _getCollectionOptions() {
     
      return $collectionTable;
  }

  private function _submitBatchToMerritt($batchManifestUrl,$collection,$url='https://merritt.cdlib.org/object/ingest') {

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
      if($status == 'ERROR' || $status == 'FAILURE')
         return 'Failure exporting to merritt.'; 
      return "Successfully submitted Omeka items to Merritt with batch identifier ".$state->{'bat:batchID'}.". Items are now ".strtolower($status)."." ;

/*

    $post = http_build_query($postFields);
//      $post = "file=@/storage/omeka/files/merritt_IjdCWL&".http_build_query($postFields);

	$headers = array("Content-Type:multipart/form-data");	
	$filesize = (string) filesize("/storage/omeka/files/merritt_IjdCWL");

      $ch = curl_init();
//      curl_setopt($ch, CURLOPT_URL,$url); 
      curl_setopt($ch, CURLOPT_URL,'ec2-54-67-110-244.us-west-1.compute.amazonaws.com/merritt-link/manifest/test');
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch,CURLOPT_INFILESIZE,$filesize);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
      curl_setopt($ch, CURLOPT_USERPWD, get_option('merritt_username') . ":" . get_option('merritt_password') );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $server_output = curl_exec ($ch);
      curl_close ($ch);
      
      unlink($tmpfname);
      
//      return($server_output."\n<pre>".json_encode($postFields)."</pre>");
      return($server_output."\n".$post);
*/
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
