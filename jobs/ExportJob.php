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
    private $_items;

    private $_bulk;

    private $_collection;

    private $_postArray;

    private $_username;

    private $_password;

    private $_fileUrl;

    public function setFileurl($url){
        $this->_fileUrl = $url;
    }

    public function setPost($post){
        $this->_postArray = unserialize($post); 
    }

    public function setItems($items){
        $this->_items = unserialize($items); 
    }

    public function setBulk($bulk){
        $this->_bulk = $bulk; 
    }

    public function setCollection($collection){
        $this->_collection = $collection; 
    }

    public function perform() {
        Zend_Registry::get('bootstrap')->bootstrap('Acl');

        $this->_username = get_option('merritt_username');
        $this->_password = get_option('merritt_password');

        if(!isset($this->_collection))
            $this->_collection = get_option('default_merritt_collection');

        if($this->_bulk) {
            //get items from search form
            //import them all
        } else {
            $objectManifests = array();
            foreach($this->_items as $id => $value) {
                $item = get_record_by_id('Item',$id);
                $manifest = $this->_createObjectManifest($item);
                $objectManifests[$manifest] = $item;
            }
            $batchManifest = $this->_createBatchManifest($objectManifests);
            $response = $this->_submitBatchToMerritt($batchManifest,$this->_collection);
            echo $batchManifest;
            //handle response
        }

    }

    private function _createBatchManifest($objectManifests) {
        ob_start();
        echo('#%checkm_0.7
#%profile | http://uc3.cdlib.org/registry/ingest/manifest/mrt-batch-manifest
#%prefix | mrt: | http://merritt.cdlib.org/terms#
#%prefix | nfo: | http://www.semanticdesktop.org/ontologies/2007/03/22/nfo#
#%fields | nfo:fileUrl | nfo:fileName | mrt:localIdentifier | mrt:creator | mrt:title | mrt:date
');
        foreach($objectManifests as $manifest => $item) {
            echo($manifest.' | '.$file->original_filename);

            $title = metadata($item,array("Dublin Core","Title"));
            $creator = metadata($item,array("Dublin Core","Creator"));
            $date = metadata($item,array("Dublin Core","Date"));
            $localId = metadata($item,array("Dublin Core","Identifier"));

            echo(' | '.$localId);
            echo(' | '.$creator);
            echo(' | '.$title);
            echo(' | '.$date);
            echo("\n");
        }
        $manifest =  ob_get_clean();
        $manifestDir = $this->_getMerrittDir(); 
        $manifestFilename = tempnam($manifestDir,'manifest_');       
        $manifestFile = fopen($manifestFilename,'w');
        fwrite($manifestFile,$manifest);
        fclose($manifestFile);
        
        return($this->_fileUrl.pathinfo($manifestFilename,PATHINFO_FILENAME));
    } 

    private function _submitBatchToMerritt($batchManifestUrl,$collection,$url='https: //merritt-stage.cdlib.org/object/ingest') {
        $postFields = array(
            'type' => 'container-batch-manifest',
            'responseForm' => 'json',
            'profile' => $collection,
            'file' => $batchManifestUrl
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/plain'
        ));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //$server_output = curl_exec ($ch);
        $server_output = 'poo';
        curl_close ($ch);

        return($server_output);
    }

    private function _createObjectManifest($item) {
        if(plugin_is_active('MetsExport'))      
            $metsline = $this->_getMetsLine($item);
        
        ob_start();
        echo('#%checkm_0.7
#%profile | http://uc3.cdlib.org/registry/ingest/manifest/mrt-ingest-manifest
#%prefix | nfo: | http://www.semanticdesktop.org/ontologies/2007/03/22/nfo#
#%fields | nfo:fileUrl | nfo:hashAlgorithm | nfo:hashValue  | nfo:fileName
');
        echo(isset($metsline) ? $metsline."\n" : '');
        $files = $item->getFiles();
        foreach($files as $file) {
            //$fileUrl = absolute_url('files/fullsize/'.$file->filename).$file->filename;
            //die('id: '.$file->id.' url: '.$this->_fileUrl.$file->filename);
            $hashvalue = md5(file_get_contents($this->_fileUrl.'fullsize/'.$file->filename));
            echo($this->_fileUrl.'fullsize/'.$file->filename.' | md5 | '.$hashvalue.' | '.$file->original_filename."\n");
        }
        $manifest =  ob_get_clean();
        
        $itemDir = $this->_getItemDir($item);
        $manifestFilename = 'Item_'.$item->id.'.checkm';
        $manifestFile = fopen($itemDir.'/'.$manifestFilename,'w');
        fwrite($manifestFile,$manifest);
        fclose($manifestFile);
        return $this->_getSiteBase().public_url('files/Merritt/'.$item->id.'/'.$manifestFilename);
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

        $itemDir = $this->_getItemDir($item);

        $metsFilename = 'Item_'.$item->id.'_mets.xml';
        $metsFile = fopen($itemDir.'/'.$metsFilename,'w');
        fwrite($metsFile,$metsXml);
        fclose($metsFile);
        
        return( $this->_getSiteBase().public_url('files/Merritt/'.$item->id.'/'.$metsFilename) .' | md5 | '.md5($metsXml).' | '.$metsFilename);
    }

    private function _getMerrittDir() {
//        $merrittDir =  dirname(dirname(dirname(dirname(__FILE__)))).'/files/Merritt';
        $merrittDir = '/var/www/html/omeka/files/Merritt';
        if(!file_exists($merrittDir))
            mkdir($merrittDir) or die('error creating merrit dir');
        return $merrittDir;
    }


    private function _getItemDir($item,$merrittDir=null) {
        if(!$merrittDir)
            $merrittDir = $this->_getMerrittDir();

        $itemDir = $merrittDir.'/'.$item->id;
        if(!file_exists($itemDir))
            mkdir($itemDir) or die('error creating item dir');

        return($itemDir);
    }



}