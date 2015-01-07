<?php
/**
 * A Merritt Export Job Event
 * 
 * @package MerrittLink
 *
 */
class MerrittExportJob extends Omeka_Record_AbstractRecord
{
    /*
     *@var int The record ID
     */
    public $id; 

    /*
     *@var string The items  
     */
    public $items;

    /*
     *@var date The time  
     */
    public $time;
}
?>