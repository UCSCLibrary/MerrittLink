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
class MerrittLink_LogController extends Omeka_Controller_AbstractActionController
{    

  /**
   * The default action to display the log creation form and process it.
   *
   * This action runs before loading the main log  form. It 
   * processes the form output if there is any, and populates
   * some variables used by the form.
   *
   * @param void
   * @return void
   */

  public function generateAction()
  {
    include_once(dirname(dirname(__FILE__))."/forms/LogForm.php");
    $form = new MerrittLink_Form_Log();
/*
    //initialize flash messenger for success or fail messages
    $flashMessenger = $this->_helper->FlashMessenger;

    try{
    if ($this->getRequest()->isPost()){
            if($form->isValid($this->getRequest()->getPost())) {
//                $this->_helper->redirector('download');
                $fields = $form->processPost();
                $this->_download($fields);
            }else{ 
                $flashMessenger->addMessage('Error generating log form.');
            }
        }
    } catch (Exception $e){
        $flashMessenger->addMessage($e->getMessage(),'error');
    }
*/
    $this->view->form = $form;
    
  }

  public function downloadAction() {

      $this->getResponse()
           ->setHeader('Content-Disposition', 'attachment; filename=merritt-export-log.csv')
           ->setHeader('Content-Type', 'text/csv');
      
    $flashMessenger = $this->_helper->FlashMessenger;

    include_once(dirname(dirname(__FILE__))."/forms/LogForm.php");
    $form = new MerrittLink_Form_Log();

    $fields = array();
      if($form->isValid($this->getRequest()->getPost())) {
          $fields = $form->processPost();
      }else{ 
          $flashMessenger->addMessage('Error generating log form.');
      }

    $logs = get_db()->getTable('MerrittExportJob')->getLog($fields);

    $header = array('Item ID','Title','Exporting User','Date Exported','Description','ARK');

    foreach($fields as $field)
        $header[] = $field['name'];

    foreach($logs as $log) {
        
        $newExport = array(
            $log['item_id'],
            $log['title'],
            $log['user'],
            $log['date'],
            $log['description'],
            $log['ark']
        );

        if( isset($log['custom']) && is_array($log['custom']) )
            foreach($log['custom'] as $fieldname => $value)
                $newExport[] = $value;        
        $exports[] = $newExport;
    }
    ob_start();
    $this->_outputCSV($exports,$header);
    $this->view->log = ob_get_clean();
  }

  private function _outputCSV($data,$header) {
   
    $outstream = fopen("php://output", 'w');
    if($header)
      fputcsv($outstream, $header);
    function __outputCSV(&$vals, $key, $filehandler) {
      fputcsv($filehandler, $vals);
    }
    array_walk($data, '__outputCSV', $outstream);
    fclose($outstream);
  }

}
