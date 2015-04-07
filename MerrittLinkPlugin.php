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
class MerrittLinkPlugin extends Omeka_Plugin_AbstractPlugin
{

    protected $_options = array(
        'default_merritt_collection',
        'merritt_username',
        'merritt_password',
        'merritt_lastcheck'
    );

    protected $_hooks = array(
//        'admin_items_browse',
        'config',
        'config_form',
        'upgrade',
        'install',
        'uninstall',
        'admin_head',
//        'public_head',
        'define_acl',
	'initialize'
    );

    public function hookInitialize() {
      require_once(dirname(__FILE__).'/jobs/ExportJob.php');
    }

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main');

    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        if($oldVersion < '1.5'){
            $db = $this->_db;
            $sql = "ALTER TABLE `$db->MerrittExportJob` ADD `user_id` int(10) unsigned NOT NULL AFTER `id`";
            $db->query($sql);
            $sql = "ALTER TABLE `$db->MerrittExportJob` ADD `status` text";
            $db->query($sql);
            $sql = "ALTER TABLE `$db->MerrittExportJob` ADD `bid` text";
            $db->query($sql);    
        }
    }

    public function hookAdminHead() {
        queue_js_file('MerrittLink');
        queue_css_file('MerrittLink');
    }

    public function hookPublicHead() {
        if(time() - get_option('merritt_lastchecked') > 43200) { //every 12 hours at most
            if($exports = get_db()->getTable("MerrittExportJob")->findBy(array('status'=>'pending'))) {
                foreach($exports as $export) {
                    if($status = $export->checkStatus()){
                        $export->status = $status;
                        $export->save();
                    }
                }               
            }
        }
    }

    public function hookInstall(){
        if(!function_exists('curl_version'))
            throw new Exception("The program libcurl must be installed to use this plugin. Please contact your system administrator.");
        $this->_installOptions();
        set_option('merritt_lastchecked',time());
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
                `user_id` int(10) unsigned NOT NULL,
                `items` text,
                `time` TIMESTAMP,
                `status` text,
                `bid` text,
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
 
}
?>