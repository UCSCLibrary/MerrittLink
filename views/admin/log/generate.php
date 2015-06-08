<?php

$head = array('bodyclass' => 'merritt-link-log primary', 
              'title' => html_escape(__('Merritt Link | Generate Log')));
echo head($head);
?>
<?php echo flash(); ?>
<?php echo $form; ?>
<?php echo foot(); ?>