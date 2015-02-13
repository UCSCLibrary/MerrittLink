<?php
/**
 * MerrittLink
 *
 * @package MerrittLink
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The MerrittLink Manifest controller class.
 *
 * @package MerrittLink
 */
class MerrittLink_ManifestController extends Omeka_Controller_AbstractActionController
{    
    public function objectAction() {

      $this->getResponse()->setHeader('Content-Type', 'text/plain');

        $item = get_record_by_id('Item',$this->getParam('item'));

        if(plugin_is_active('MetsExport'))      
            $metsline = $this->_getMetsLine($item);

        ob_start();
        echo('#%checkm_0.7
#%profile | http://uc3.cdlib.org/registry/ingest/manifest/mrt-ingest-manifest
#%prefix | nfo: | http://www.semanticdesktop.org/ontologies/2007/03/22/nfo#
#%fields | nfo:fileUrl | nfo:hashAlgorithm | nfo:hashValue | nfo:fileSize | nfo:fileLastModified | nfo:fileName | mrt:mimeType
');
        echo(isset($metsline) ? $metsline."\n" : '');
        $files = $item->getFiles();
        foreach($files as $file) {
            $fileUrl = file_display_url($file);
	    $filename = pathinfo($file->original_filename,PATHINFO_BASENAME);
            $hashvalue = md5(file_get_contents($fileUrl));
            echo($fileUrl.' | | | | | '.$filename."\n");
	    //            echo($fileUrl.' | md5 | '.$hashvalue.' |  |  | '.$file->original_filename."\n");
        }
        echo "#%eof";
        $this->view->manifest =  ob_get_clean();
    }

    private function _getManifestUrl($item) {
        return $this->_getSiteBase().public_url('merritt-link/manifest/object/item/'.$item->id);        
    }

  public function testAction() {
    echo("post:<pre>\n");
    print_r($_POST);
    echo("\n\nfiles:\n");
    print_r($_FILES);
    echo("</pre>");
    die('d');
  } 



    public function batchAction() {
      $this->getResponse()->setHeader('Content-Type', 'text/plain');

        $job_id = $this->getParam('job');
        $job = get_db()->getTable('MerrittExportJob')->find($job_id);
        $items = unserialize($job->items);

        ob_start();
        echo('#%checkm_0.7
#%profile | http://uc3.cdlib.org/registry/ingest/manifest/mrt-batch-manifest
#%prefix | mrt: | http://merritt.cdlib.org/terms#
#%prefix | nfo: | http://www.semanticdesktop.org/ontologies/2007/03/22/nfo#
#%fields | nfo:fileUrl | nfo:hashAlgorithm | nfo:hashValue | nfo:fileSize | nfo:fileLastModified | nfo:fileName | mrt:primaryIdentifier | mrt:localIdentifier | mrt:creator | mrt:title | mrt:date
');
        foreach($items as $item_id => $val) {

            $item = get_record_by_id('item',$item_id);
            
            echo($this->_getManifestUrl($item));
            echo(' | | | | | ');
	    echo($item->id.'.checkm');

	    echo(' | ');
	    /*
            $title = metadata($item,array("Dublin Core","Title"));
            $creator = metadata($item,array("Dublin Core","Creator"));
            $date = metadata($item,array("Dublin Core","Date"));
            $localId = metadata($item,array("Dublin Core","Identifier"));

            echo(' | | '.$localId);
            echo(' | '.$creator);
            echo(' | '.$title);
            echo(' | '.$date);
	    */
            echo("\n");
        }    
        echo "#%eof";
        $this->view->manifest =  ob_get_clean();               
    }

    private function _getSiteBase() {
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
        return $protocol.$_SERVER['HTTP_HOST'];
    }

    private function _getMetsLine($item) {
        require_once(dirname(dirname(dirname(__FILE__)))."/MetsExport/helpers/MetsExporter.php");
        $exporter = new MetsExporter();
        ob_start();
        $exporter->exportItem($item->id);
        $metsXml = ob_get_clean();
        $metsUrl = $this->_getSiteBase().public_url('items/show/'.$item->id.'?output=METS');
        $metsFilename = 'Item_'.$item->id.'_mets.xml';
        /*
        $itemDir = $this->_getItemDir($item);
        $metsFilename = 'Item_'.$item->id.'_mets.xml';
        $metsFile = fopen($itemDir.'/'.$metsFilename,'w');
        fwrite($metsFile,$metsXml);
        fclose($metsFile);
        */
        //return( $this->_getSiteBase().public_url('files/Merritt/'.$item->id.'/'.$metsFilename) .' | md5 | '.md5($metsXml).' | '.$metsFilename);
        return( $metsUrl .' | md5 | '.md5($metsXml).' | | | '.$metsFilename);
    }

}
