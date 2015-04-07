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
            
            $manifestUrl = $this->_getManifestUrl($item);
            $hash = '';
            $hashAlg = '';
            $filesize = '';
            $modified = '';
            $filename = $item->id.'.checkm';
            $pid = '';
            $title = metadata($item,array("Dublin Core","Title"));
            $creator = metadata($item,array("Dublin Core","Creator"));
            $date = metadata($item,array("Dublin Core","Date"));
            //       $localId = metadata($item,array("Dublin Core","Identifier"));
            $localId = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]:$item->id";
            
            $title = preg_replace('/[^A-Za-z0-9",.;:@#!%&_ \'\/\-\t\$\+\^\*\\\?\(\)]/', '', $title);
            $creator = preg_replace('/[^A-Za-z0-9",.:@ \'\/\-\t\(\)]/', '', $creator);
            $date = preg_replace('/[^A-Za-z0-9: \/\-]/', '', $date);
            $localId = preg_replace('/[^A-Za-z0-9",.;:@#!%&_ \'\/\-\t\$\+\^\*\\\?\(\)]/', '', $localId);
            
            echo $manifestUrl;
            echo ' | '.$hashAlg;
            echo ' | '.$hash;
            echo ' | '.$filesize;
            echo ' | '.$modified;
            echo ' | '.$filename;
            echo ' | '.$pid;
            echo ' | '.$localId;
            echo ' | '.$creator;
            echo ' | '.$title;
            echo ' | '.$date;
            //            echo("$manifestUrl | $hashAlg | $hash | $filesize | $modified | $filename | $pid | ".urlencode($localId).' | '.urlencode($creator).' | '.urlencode($title).' | '.urlencode($date));
            //	    echo($item->id.'.checkm');
            
            //	    echo(' | ');
            /*
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
        if(plugin_is_active('MetsExport')){
        require_once(dirname(dirname(dirname(__FILE__)))."/MetsExport/helpers/MetsExporter.php");
        $exporter = new MetsExporter();
        ob_start();
        $exporter->exportItem($item->id);
        $metsXml = ob_get_clean();
        $metsUrl = $this->_getSiteBase().public_url('items/show/'.$item->id.'?output=METS');
        $metsFilename = 'Item_'.$item->id.'_mets.xml';
        return( $metsUrl .' | | | | | '.$metsFilename);
        } else {
            return( $this->getSiteBase().public_url('items/show/'.$item->id.'?output=xml').' | | | | | Item_'.$item->id.'.xml');
        }
    }

}
