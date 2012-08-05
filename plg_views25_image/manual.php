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

function imageGetManual($type) {
    switch ($type) {
        case 'below_query_settings':

            $text = <<<EOH

<tr><th colspan="2">Image</th></tr>
<tr><th class="mark">Markup</th><th class="details">details</th></tr>

<tr>
    <td>
        Example 1
    </td>
    <td>
        <p>
            Display a static image
        </p>
        <p>
            This always displays a same image regardless the query.
        </p>
        <p>
            value=&lt;img src={value} /&gt;<br />
            image=images/sampledata/parks/animals/800px_wobbegong.jpg<br />
            resized_width=100<br />
            plugin=image
        </p>
    </td>
</tr>

<tr>
    <td>
        Example 2
    </td>
    <td>
        <p>
            Display re-sized image(s)
        </p>
        <p>
            This re-sizes the image(s) in the field. Useful when field is storing a content with big images.
        </p>
        <p>
            value={value}<br />
            resized_width=200<br />
            plugin=image
        </p>
    </td>
</tr>

<tr>
    <td>
        Example 3
    </td>
    <td>
        <p>
            Display re-sized image with URI in which is dynamically generated
        </p>
        <p>
            This is an example of using the extension FieldsAttach.<br />
            FieldsAttach actually has the image resizing feature,<br />
            but this example give you the idea of how you can generate the URI.
        </p>
        <p>
            value=&lt;img src={value} /&gt;<br />
            image=images/documents/{f:id}/{f:image}<br />
            plugin=image<br />
            resized_width=100
        </p>
    </td>
</tr>

EOH;
            break;
        default:
    }
    return $text;
}
