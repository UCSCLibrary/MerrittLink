<?php
class Table_MerrittCollection extends Omeka_Db_Table
{
    protected function _getColumnPairs() 
    {
        $alias = $this->getTableAlias();
        return(array($alias.'.id',$alias.'.slug'));
    }
}
?>