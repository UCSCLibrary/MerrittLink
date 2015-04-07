<?php
class Table_MerrittExportJob extends Omeka_Db_Table
{
    public function getPending() {
        $pending = $this->findBy(array('status'=>'pending'));
        $pending = array_merge($pending,$this->findBy(array('status'=>'queued')));
        $pending = array_merge($pending,$this->findBy(array('status'=>'consumed')));
        return $pending;
    }
}
?>