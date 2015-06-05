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
class MerrittLink_CollectionController extends Omeka_Controller_AbstractActionController
{
  /**
   * Add a new merritt collection.
   *
   * @param void
   * @return void
   */
  public function addAction()
  {
    //verify csrf
      if(version_compare(OMEKA_VERSION,'2.2.1') >= 0)
          $this->_validateCsrf();

    $slug = $this->getParam('slug');
    $collection = new MerrittCollection();
    $collection->slug = $slug;
    $collection->save();
    $return = array(
		    'id'=>$collection->id,
		    'slug'=> $slug,
		    'title' => absolute_url('merritt-link/collection/delete/id/'),
		    );
    $this->view->data = $return;
  }
  /**
   * Delete a merritt collection.
   *
   * @param void
   * @return void
   */
  public function deleteAction()
  {
    $this->_validateCsrf();

    $id = $this->getParam('id');
    $collection = get_db()->getTable('MerrittCollection')->find($id);
    //$collection = get_record_by_id("MerrittCollection",$id);
    $collection->delete();
    $return = array(
		    'id'=>$id,
		    );
    $this->view->data = $return;
  }

  private function _validateCsrf(){
    $csrf = new Omeka_Form_SessionCsrf;
    if (!$csrf->isValid($_POST))
      die('ERROR');

    return $csrf;
  }
}