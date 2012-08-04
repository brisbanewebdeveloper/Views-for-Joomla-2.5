<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


$doc = JFactory::getDocument();

/*
$doc->addScriptDeclaration("
window.addEvent('domready', function() {
});
// $.noConflict();
// jQuery(document).ready(function($) {
// });
");
*/

// $db - Return value from JFactory::getDbo()
// $helper - Instance of modViewsHelper. The method "parse" returns the value after applying some parameters if specified.
// $rows[0...X] - Return value from $db->loadObjectList().
// $vfj_fields[0...9] - Field (id, title, modified_by etc depending on the SQL query).
// $vfj_params[0...9] - JRegistry: Parameter for Field (e.g value={value}).
// $vfj_options - JRegistry: Options for the module.
// $vfj_php_enabled - Whether it is ticked "Enable Custom HTML"
// $vfj_php - Whether it is ticked "Enable Custom HTML"

// $vfj_css_enabled
// $vfj_css

// $vfj_scripts_enabled
// $vfj_scripts

// $params: Module parameter. Required for the method parse for $helper (helper class).

// Example use of $vfj_options
// If you put "debug=1" for this module at backend, this logic will be executed
if ($vfj_options->get('debug')) {
    var_dump($rows, $vfj_fields, $vfj_params, $vfj_options);
}

// Custom PHP logic
/*
// I disable the logic here as default to reduce the security risk.
// $vfj_php_enabled gets FALSE if the system plugin has "No" at "Custom PHP Feature" option
// regardless of the module settings.
// Use this parameter at your own risk!
if ($vfj_php_enabled) {
    eval($vfj_php);
}
*/
// Custom CSS
if ($vfj_css_enabled) {
    $doc->addStyleDeclaration($vfj_css);
}
// Custom Javascript logic
if ($vfj_scripts_enabled) {
    $doc->addScriptDeclaration($vfj_scripts);
}
?>
<div class="box-1">
<?php
foreach($rows as $index_row => $record) {
?>
    <div class="box-2 <?php echo ($index_row % 2 == 0) ? 'even' : 'odd'; ?>">
<?php
    foreach($vfj_fields as $index_col => &$field) {

        // Example of Getting Raw Field Value
        // $value = $record->$field;

        // Example of Getting Raw Field Value after checking if this column is to display something
        // if ($field) $value = $record->$field;

        $value = $helper->parse($field, $vfj_params[$index_col], $record, $vfj_options, $params);

        if ($value) {
?>
    <div class="box-3 value-<?php echo $index_col; ?>"><?php echo $value; ?></div>
<?php
        }
    }
?>
    </div>
<?php
}
?>
</div>
