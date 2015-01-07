<?php
/**
 * MerrittLink plugin
 *
 * @package     MerrittLink
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * MerrittLink plugin class
 * 
 * @package MerrittLink
 */
class MerrittLinkPlugin extends Omeka_plugin_AbstractPlugin
{

    protected $_options = array('default_merritt_collection','merritt_username','merritt_password');

    protected $_hooks = array(
        'admin_items_browse',
        'config',
        'config_form',
        'install',
        'uninstall',
        'admin_head',
        'define_acl',
        'initialize'
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main');

    
    /**
     * Require the job and helper files
     *
     * @return void
     */
    public function hookInitialize()
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'ExportJob.php';

    }

    public function hookAdminHead() {
        queue_js_file('MerrittLink');
        queue_css_file('MerrittLink');
    }

    public function hookInstall(){
        $this->_installOptions();
        try{
            $sql = "
            CREATE TABLE IF NOT EXISTS `{$this->_db->MerrittCollection}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `slug` text,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $this->_db->query($sql);
            $sql = "
            CREATE TABLE IF NOT EXISTS `{$this->_db->MerrittExportJob}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `items` text,
                `time` TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $this->_db->query($sql);
        }catch(Exception $e) {
            throw $e; 
        }

    }

    public function hookUninstall() {
        $this->_uninstallOptions();
        try{
            $db = get_db();
            $sql = "DROP TABLE IF EXISTS `$db->MerrittCollection` ";
            $db->query($sql);
            $sql = "DROP TABLE IF EXISTS `$db->MerrittExportEvent` ";
            $db->query($sql);
        }catch(Exception $e) {
            throw $e;	
        }
    }

    /**
     * Set the options from the config form input.
     */
    public function hookConfigForm() {
        include dirname(__FILE__) . '/forms/config_form.php';
    }
    
    /**
     * Set the options from the config form input.
     */
    public function hookConfig() {
        if(isset($_REQUEST['default_merritt_collection']))                
            set_option('default_merritt_collection',$_REQUEST['default_merritt_collection']);
        if(isset($_REQUEST['merritt_username']))                
            set_option('merritt_username',$_REQUEST['merritt_username']);
        if(isset($_REQUEST['merritt_password']))                
            set_option('merritt_password',$_REQUEST['merritt_password']);
    }

    public function hookAdminItemsBrowse() {
        /*
        $item = get_record_by_id('Item',131);
        require_once(dirname(__FILE__).'/helpers/manifest_functions.php');
        $mf = new ManifestFactory();
        $manifestUrl = $mf->createManifest($item);
        $this->_pushToMerritt($item,$manifestUrl);
        print_r();
        die('END');
        */
    }

    /**
     * Define the plugin's access control list.
     *
     * @param array $args This array contains a reference to
     * the zend ACL under it's 'acl' key.
     * @return void
     */
    public function hookDefineAcl($args)
    {
        $args['acl']->addResource('MerrittLink_Index');
    }

    /**
     * Add the NuxeoLink link to the admin main navigation.
     * 
     * @param array $nav Array of links for admin nav section
     * @return array $nav Updated array of links for admin nav section
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Merritt Link'),
            'uri' => url('merritt-link'),
            'resource' => 'MerrittLink_Index',
            'privilege' => 'index'
        );
        return $nav;
    }

    private function _pushToMerritt($item,$manifestUrl,$type='single-object-manifest') {
        $query = http_build_query(array(
            'file' => $manifestUrl,
            'type' => $type,
            'profile' => get_option('merritt_profile'),
            'digestType' =>'md5',
            'digestValue' => md5(file_get_contents($manifestUrl)),
            'creater' => metadata($item,array('Dublin Core','Creater')),
            'title' => metadata($item,array('Dublin Core','Title')),
            'date' => metadata($item,array('Dublin Core','Date')),
            'localIdentifier' => metadata($item,array('Dublin Core','Identifier')),
        ));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://merritt.cdlib.org/object/ingest");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, get_option('merritt_username') . ":" . get_option('merritt_password')); 
        $server_output = curl_exec ($ch);

        curl_close ($ch);
    }

}

//UPDATE FILE COMMAND
/*
curl -u $USERNAME:$USERPW -F "file=@$b.checkm" -F "type=container-batch-manifest" -F "profile=uci_lib_diskimage_content" https://merritt.cdlib.org/object/update
*/
//UPDATE METADATA COMMAND
/*
curl -u $USERNAME:$USERPW -F "file=@$b.checkm" -F "type=single-file-batch-manifest" -F "profile=uci_lib_diskimage_content" https://merritt.cdlib.org/object/update
*/
//DELETE COMMAND
/*
curl -u $USERNAME:$USERPW -F "file=@$b.checkm" -F "type=single-file-batch-manifest" -F "profile=uci_lib_diskimage_content" https://merritt.cdlib.org/object/update
*/
/*
//INGEST COMMAND
/*
curl -u $USERNAME:$USERPW -F "file=@$b.checkm" -F "type=container-batch-manifest" -F "profile=uci_lib_diskimage_content" https://merritt.cdlib.org/object/ingest
*/
?>