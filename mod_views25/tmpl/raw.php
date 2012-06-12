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
if ($vfj_php_enabled) {
    eval($vfj_php);
}
// Custom CSS
if ($vfj_css_enabled) {
    $doc->addStyleDeclaration($vfj_css);
}
// Custom Javascript logic
if ($vfj_scripts_enabled) {
    $doc->addScriptDeclaration($vfj_scripts);
}

foreach($rows as $index => $record) {
    foreach($vfj_fields as $index => &$field) {
        $value = $helper->parse($field, $vfj_params[$index], $record, $vfj_options, $params);
        if ($value) echo $value;
    }
}
