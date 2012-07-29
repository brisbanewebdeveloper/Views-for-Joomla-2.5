<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


$doc = JFactory::getDocument();

$url_prefix = JURI::root().'modules/mod_views25/tmpl/nivo-slider-3.0.1/';

$doc->addStyleSheet($url_prefix.'themes/default/default.css');
$doc->addStyleSheet($url_prefix.'nivo-slider.css');
// $doc->addStyleSheet($url_prefix.'demo/style.css');

$doc->addScript($url_prefix.'jquery.nivo.slider.pack.js');

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
// $vfj_fields[0...9] - Field Value (id, title, modified_by etc depending on the SQL query).
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
<div class="slider-wrapper theme-default">
    <div id="slider" class="nivoSlider">
<?php

$aCaption = array();

foreach($rows as $index_row => $record) {
    // foreach($vfj_fields as $index_col => &$field) {

        $index_col = 0;
        $field     = $vfj_fields[0];

        $value = $record->$field; // Raw Value

        // Get caption data (Default wrapper tag is H2 but it is amendable via field parameter "caption_tag" e.g. caption_tag=h3)
        $caption_tag = $vfj_params[$index_col]->get('caption_tag', 'h2');
        preg_match('#<'.$caption_tag.'[^>]*>(.*)</'.$caption_tag.'>#Umi', $value, $matches);
        $aCaption[$index_row] = isset($matches[1]) ? $matches[1] : '';

        // Info: This way avoids you to have to set this at backend
        $vfj_params[$index_col]->set('strip_tags', '1');
        $vfj_params[$index_col]->set('strip_tags_excl', 'img,a');

        $value = $helper->parse($field, $vfj_params[$index_col], $record, $vfj_options, $params);

        if ($value) {
            // Nivo Slider does not seem to like IMG tag to have any unexpected attributes.
            // Therefore, I get rid of them here.
            $output = '';
            preg_match_all('#(src="[^"]+")|(data-thumb="[^"]+")|(data-transition="[^"]+")#Umi', $value, $matches);
            if (isset($matches[0])) {
                // Map Caption
                if ($aCaption[$index_row]) {
                    $matches[0][] = 'title="#nivo-htmlcaption-'.$index_row.'"';
                }
                $output = '<img ' . implode(' ', $matches[0]) . ' />';
            }
            // Add link if exists (check if it is wrapping IMG tag because Caption can have link as well)
            preg_match('#(<a [^>]+>).*<img#Umi', $value, $matches);
            if (isset($matches[1])) {
                $output = $matches[1] . $output . '</a>';
            }
            echo $output;
        }

    // }
}
?>
    </div>
<?php
foreach ($aCaption as $index => $value) {
    if ($value) {
?>
    <div id="nivo-htmlcaption-<?php echo $index; ?>" class="nivo-html-caption">
        <?php echo $value; ?>
    </div>
<?php
    }
}
?>
</div>
