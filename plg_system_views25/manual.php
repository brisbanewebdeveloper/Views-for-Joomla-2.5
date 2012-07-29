<?php
/*------------------------------------------------------------------------
# views25 - Views for Joomla
# ------------------------------------------------------------------------
# author    Hiro Nozu
# copyright Copyright (C) 2012 Hiro Nozu. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://http://ideas.forjoomla.net
# Technical Support:  Contact - http://ideas.forjoomla.net/contact
-------------------------------------------------------------------------*/

// no direct access
defined('_JEXEC') or die;

function views25GetManual($type) {
    switch ($type) {
        case 'below_query_settings':
            $text = <<<EOH
<tr><th colspan="2">General</th></tr>
<tr><th class="mark">Markup</th><th class="details">details</th></tr>
<tr><td valign="top">value={value}</td><td>The field value. If you need a block element wrapping the value, just put like value=&lt;h4 class="sample"&gt;{value}&lt;/h4&gt;</td></tr>
<tr><td valign="top">value={f:field}</td><td>The field value. Useful if you want to display multiple fields with main field.<br />e.g. value=&lt;a href="index.php?option=com_content&view=article&id={value}&Itemid=123"&gt;readmore about {f:title}&lt;/a&gt;</td></tr>
<!--
<tr><td valign="top">{url}</td><td>This parameter is used if the field is link. The value of this parameter will be embedded where {url} in value parameter.<br />e.g.<br />value=((a href="{url}"))readmore((/a))<br />
url=index.php?option=com_content&view=article&id={value}</td></tr>
-->
<tr><td valign="top">date_format=text</td><td>JHtml::_('date') is applied to {value}. e.g. date_format=Y/m/d <a target="_blank" href="http://www.php.net/manual/en/function.date.php">See Available Format</a></td></tr>
<tr><td valign="top">user_timezone=1</td><td>Use user's timezone when date_format is applied.</td></tr>
<tr><td valign="top">strip_tags=1</td><td>Strips HTML tags from the field value.</td></tr>
<tr><td valign="top">strip_tags_excl=tags</td><td>Exceptions for strip_tag parameter. Separate by comma. e.g. strip_tags_excl=br,p,strong</td></tr>
<tr><td valign="top">limit=n</td><td>First n characters of field value is displayed. If the value contains HTML, you probably have to use strip_tags parameter as well.</td></tr>

EOH;
            break;
        default:
    }
    return $text;
}
