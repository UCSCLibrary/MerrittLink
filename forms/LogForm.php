<?php
/**
 * MerrittLinkImport Form for defining import parameters
 *
 * @package     MerrittLinkImport
 * @copyright   2014 UCSC Library Digital Initiatives
 * @license     
 */

/**
 * MerrittLinkImport form class
 */
class MerrittLink_Form_Log extends Omeka_Form
{

    /**
     * Construct the import form.
     *
     *@return void
     */
    public function init()
    {
        parent::init();
        $this->_registerElements();
        $this->setAction(url('merritt-link/log/download'));
    }

    /**
     * Define the form elements.
     *
     *@return void
     */
    private function _registerElements()
    {
      $this->addElement('select', 'fields[]', array(
							'label'         => __('Add Field'),
							'description'   => __('Choose extra metadata fields to include in the log files'),
							'size'=>10,
							'order'         => 7,
							'multiple' =>  'multiple',
							'multiOptions'       => $this->_getElementOptions()
							)
			);

        
        if(version_compare(OMEKA_VERSION,'2.2.1') >= 0)
            $this->addElement('hash','MerrittLink_token');

        // Submit:
        $this->addElement('submit', 'merritt-link-log-submit', array(
            'label' => __('Export Log')
        ));

        $this->addDisplayGroup(
            array(
                'MerrittLink_token',
                'date-start',
                'date-end',
                'fields[]'
            ),
            'main-fields'
        );
        
        $this->addDisplayGroup(
            array(
                'merritt-link-log-submit'
            ), 
            'submit_buttons',
            array(
                'style'=>'clear:left;'
            )
        );
          
    }


    /**
     * Get an array to be used in html select input
 containing all elements.
     * 
     * @return array $elementOptions Array of options for a dropdown
     * menu containing all elements applicable to records of type Item
     */
    private function _getElementOptions()
    {
        $db = get_db();
        $sql = "
        SELECT es.name AS element_set_name, e.id AS element_id, 
        e.name AS element_name, it.name AS item_type_name
        FROM {$db->ElementSet} es 
        JOIN {$db->Element} e ON es.id = e.element_set_id 
        LEFT JOIN {$db->ItemTypesElements} ite ON e.id = ite.element_id 
        LEFT JOIN {$db->ItemType} it ON ite.item_type_id = it.id 
         WHERE es.record_type IS NULL OR es.record_type = 'Item' 
        ORDER BY es.name, it.name, e.name";
        $elements = $db->fetchAll($sql);
        $options = array();
//        $options = array('' => __('Select Below'));
        foreach ($elements as $element) {
            if($element['element_set_name'] == 'Dublin Core' && $element['element_name'] == 'Title')
                continue;
            if($element['element_set_name'] == 'Dublin Core' && $element['element_name'] == 'Description')
                continue;
            $optGroup = $element['item_type_name'] 
                      ? __('Item Type') . ': ' . __($element['item_type_name']) 
                      : __($element['element_set_name']);
            $value = __($element['element_name']);
            
            $options[$optGroup][$element['element_id']] = $value;
        }
        return $options;
    }

    public function processPost(){
        $fields = array();
        if(isset($_POST['fields'])) {
            foreach($_POST['fields'] as $field){
                $element = get_record_by_id('Element',$field);
                $elementSet = get_record_by_id('ElementSet',$element->element_set_id);
                if(!is_object($element))
                    continue;
                $fields[] = array('name'=>$element->name,'fieldset'=>$elementSet->name);
            }
        }
        return $fields;
    }
}
