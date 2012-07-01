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

class plgSystemViews25 extends JPlugin
{
    public $type = 'default';

    /*
    protected function isToBeParsed($plugins)
    {
        // The values of the array $plugins should be converted to all lower case
        // at modules/mod_views25/helper.php before this method is called.
        return in_array(strtolower($this->type), $plugins);
    }
    */
    public function onParse(&$value, &$field, &$vfj_param, &$record, &$params, &$plugins) {

        // -> This is obsolete
        // The helper function parsing the value calls all the available plugins
        // regardless if it needs to be processed. Therefore, all the plugin classes have to check
        // if your plugin has to be processed as follows:
        // if ( ! $this->isToBeParsed($plugins)) return;

        // Each plugin is to be implemented the logic for this event by overriding this method
        // $value = 'To manipulate this value is basically what your plugin does';


        // Format Date
        if ($vfj_param->get('date_format')) {
            $value = JHtml::_('date', $value, $vfj_param->get('date_format'), ($vfj_param->get('user_timezone') == 1));
        }

        // Strip Tags
        if ($vfj_param->get('strip_tags')) {
            if ($vfj_param->get('strip_tags_excl')) {
                $strip_tags_excl = explode(',', $vfj_param->get('strip_tags_excl'));
                foreach ($strip_tags_excl as &$tag) $tag = '<'.$tag.'>';
                $value = strip_tags($value, implode('', $strip_tags_excl));
            }else{
                $value = strip_tags($value);
            }
        }

        // $url = $vfj_param->get('url') ? JRoute::_($vfj_param->get('url')) : '';

        // Replace some parts in the value to the field(s)
        $param_value = $vfj_param->get('value');
        preg_match_all('/{f:([^}]+)}/', $param_value, $matches);
        if (isset($matches[1][0])) {
            foreach($matches[1] as $index => $match) {
                $param_value = preg_replace('/'.$matches[0][$index].'/', $record->$match, $param_value);
            }
            $vfj_param->set('value', $param_value);
        }

        // Limit the length of the value
        if ($vfj_param->get('limit')) {
            $value = substr($value, 0, $vfj_param->get('limit'));
        }

        // Apply content plugin
        $dispatcher = JDispatcher::getInstance();
        JPluginHelper::importPlugin('content');
        $offset = 0;
        $params = new JRegistry;
        $item = new stdClass;
        $item->text = $value;
        $dispatcher->trigger('onContentPrepare', array ('mod_views25.article', &$item, &$params, $offset));
        $value = $item->text;
    }
    /**
     *
     * Joomla pre-defined events
     *
     *
     *
     *
     */
    public function onAfterRoute()
    {
        $app = JFactory::getApplication();

        /*
         * This logic let you avoid having to develop an extension as component.
         *
         * If your extension needs to check if the request is for component for module,
         * checking if JRequest::getCmd('option') is 'mod_views25' let you figure it out.
         * Sample Module Logic: modules/mod_views25/mod_views25.php
         *
         * Example query: index.php?option=mod_views25&module=mod_mymodule&task=mytask
         */
        $jinput = $app->input;
        if ($jinput->get('option') != 'mod_views25') return true;

        $module = $jinput->get('module');
        $id     = $jinput->get('id');

        // Check if the request "module" is sent
        if ( ! preg_match('/^mod_[a-z0-9]+/', $module)) {
            die('Invalid parameter module. You need to send the request "module" to use mod_views25 feature');
            return true;
        }

        $path = JPATH_ROOT.DS.'modules'.DS.$module.DS.$module.'.php';

        if (file_exists($path)) {
            if ($id) {
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
                $query->select('m.id, m.title, m.module, m.position, m.content, m.showtitle, m.params, mm.menuid');
                $query->from('#__modules AS m');
                $query->join('LEFT', '#__modules_menu AS mm ON mm.moduleid = m.id');
                $query->where('m.published = 1');
                $query->join('LEFT', '#__extensions AS e ON e.element = m.module AND e.client_id = m.client_id');
                $query->where('e.enabled = 1');
                $db->setQuery($query);
                $modules = $db->loadObjectList();
            }else{
                $modules = new stdClass;
                $modules->id = 0;
                $modules->title = null;
                $modules->module = null;
                $modules->position = null;
                $modules->content = '';
                $modules->showtitle = false;
                $modules->params = new JParameter;
                $modules->menuid = -1;
            }
            $params = new JParameter($modules->params);
            // You can refer $modules and $params if the parameter id is sent
            // Don't forget to exit the process in the module logic.
            jimport('joomla.application.module.helper'); // To enable to use JModuleHelper class
            include $path;
        }else{
            $app->enqueueMessage('File '.$path.' not found.', 'error');
        }
    }
    public function onAfterDispatch()
    {
        $app = JFactory::getApplication();

        if ( ! $app->isAdmin()) return true;

        require_once JPATH_ROOT.DS.'modules'.DS.'mod_views25'.DS.'helper.php';
        $helper = modViewsHelper::getInstance();

        $jinput = $app->input;
        if ($jinput->get('option') != 'com_modules') return true;
        if ($jinput->get('view') != 'module') return true;
        if ($jinput->get('layout') != 'edit') return true;

        $db  = JFactory::getDbo();
        $doc = JFactory::getDocument();

        $custom_php_code = $this->params->get('custom_php_code');

        // Layout fields
        $doc->addScriptDeclaration("
// UserVoice integration
var uvOptions = {};
(function() {
    var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
    uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/b3HBN20oV6IvmtmlwDxFxQ.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
})();

// Custom code for Views for Joomla
var vfjMgr = {
    set_setting: function(options) {

        var vfj_params = new Array();
        var vfj_fields = new Array();
        var vfj_php_enabled = {$custom_php_code};
        var j = 0;

        $$('#vfjForm .vfj_fields').each(function(item) {
            vfj_fields.push(item.value);
        });
        $$('#vfjForm .vfj_params').each(function(item) {
            vfj_params.push(item.value);
        });

        $('jform_params_data').value = JSON.encode({
            'vfj_query': $('vfj_query').value,
            'vfj_fields': vfj_fields,
            'vfj_params': vfj_params,
            'vfj_options': $('vfj_options').value,
            'vfj_php_enabled': $('vfj_php_enabled').checked ? 1 : 0,
            'vfj_php': $('vfj_php').value,
            'vfj_css_enabled': $('vfj_css_enabled').checked ? 1 : 0,
            'vfj_css': $('vfj_css').value,
            'vfj_scripts_enabled': $('vfj_scripts_enabled').checked ? 1 : 0,
            'vfj_scripts': $('vfj_scripts').value
        });
    },
    parse_setting: function(options) {

        if ( ! JSON.validate($('jform_params_data').value)) {
            alert('Validation failed: Module parameter Data is invalid');
            // $('vfj_query').value = $('jform_params_data').value;
            return false;
        }
        var data = JSON.decode($('jform_params_data').value);

        if (data.vfj_query) $('vfj_query').value = data.vfj_query;
        if (data.vfj_php_enabled) $('vfj_php_enabled').checked = (data.vfj_php_enabled == 1);
        if (data.vfj_php) $('vfj_php').value = data.vfj_php;
        if (data.vfj_css_enabled) $('vfj_css_enabled').checked = (data.vfj_css_enabled == 1);
        if (data.vfj_css) $('vfj_css').value = data.vfj_css;
        if (data.vfj_scripts_enabled) $('vfj_scripts_enabled').checked = (data.vfj_scripts_enabled == 1);
        if (data.vfj_scripts) $('vfj_scripts').value = data.vfj_scripts;
        if (data.vfj_options) $('vfj_options').value = data.vfj_options;

        vfjMgr.apply_result({
            not_ask: true,
            onComplete: function() {
                $$('#vfjForm .vfj_fields').each(function(item, index) {
                    item.value = data.vfj_fields[index];
                });
                $$('#vfjForm .vfj_params').each(function(item, index) {
                    item.value = data.vfj_params[index];
                });
            }
        });
    },
    set_parameter: function(options) {
        var index = options.element.getProperty('index');
        var element = $$('#vfjForm .vfj_params')[index];
        if (element.value != '') {
            if (element.value == 'value={value}') return false;
            // if ( ! confirm('This overwrites the current setting. Proceed?')) return false;
        }
        // element.value = 'value={value}';
        vfjMgr.set_setting();
    },
    apply_result: function(options) {

        if ( ! options) options = {};

        if ( ! $defined(options.not_ask)) {
            if ( ! confirm('Are you sure you want to apply the result?')) return false;
        }

        vfjMgr.test_query();

        new Request({
            url: 'index.php?option=mod_views25&module=mod_views25&task=getFieldList',
            data: $('vfjForm'),
            onProgress: function() {
                $('vfj-message').set('html', 'Applying the fields...');
            },
            onComplete: function(data) {
                data = JSON.decode(data);
                $('vfj-message').set('html', data.message);
                if (data.error == 0) {
                    var box = $$('.vfj_field_box');
                    var box_selected = '';
                    var j = box.length;
                    for (var i = 0; i < j; i++) {
                        // Remember the current values in dropdown list
                        box_selected = $(box[i]).getElement('select') ? $(box[i]).getElement('select').value : '';
                        // Apply new dropdown list
                        box[i].empty();
                        var selectElement = new Element('select', {
                            'id': 'vfj_field_' + i,
                            'name': 'vfj_field_' + i,
                            'class': 'vfj_fields',
                            'events': {
                                'change': function() { vfjMgr.set_parameter({element: this}); }
                            }
                        }).inject(box[i]);
                        selectElement.setProperty('index', i);
                        new Element('option', {
                            'value': '',
                            'text': '- N/A -'
                        }).inject(selectElement);
                        var data_count = data.data.length;
                        for (var i_data = 0; i_data < data_count; i_data++) {
                            new Element('option', {
                                'value': data.data[i_data],
                                'text': data.data[i_data]
                            }).inject(selectElement);
                            if (box_selected == data.data[i_data]) selectElement.value = box_selected;
                        }
                    }
                    if ($defined(options.onComplete)) options.onComplete();
                }
            }
        }).send();
    },
    set_query: function(options) {
        switch (options.type) {
            case 'single_article':
                if ($('_id').value) {
                    if ($('vfj_query').value) {
                        if ( ! confirm('Query will be updated. Are you sure?')) return false;
                    }
                    $('vfj_query').value = 'SELECT * FROM #__content WHERE state = 1 AND id = ' + $('_id').value;
                    vfjMgr.test_query();
                }
                break;
            case 'articles':
                if ($('vfj_query_articles_category').value) {
                    if ($('vfj_query').value) {
                        if ( ! confirm('Query will be updated. Are you sure?')) return false;
                    }
                    $('vfj_query').value = 'SELECT * FROM #__content WHERE state = 1 AND catid = ' + $('vfj_query_articles_category').value;
                    if ($('vfj_query_articles_order').value)
                        $('vfj_query').value += ' ORDER BY ' + $('vfj_query_articles_order').value + ' ' + $('vfj_query_articles_order_dir').value;
                    if ($('vfj_query_articles_limit').value)
                        $('vfj_query').value += ' LIMIT ' + $('vfj_query_articles_limit').value;
                    vfjMgr.test_query();
                }
                break;
            case 'article':
                break;
            default:
                alert('Invalid type');
        }
    },
    switch_field: function(source, destination) {
        var s = $('vfj_field_' + source);
        var d = $('vfj_field_' + destination);
        var t = {
            vfj_field: '',
            vfj_params: ''
        };
        if (s.getElement('select')) t.vfj_field = s.getElement('select').value;
        if ($$('.vfj_params')[source].value) t.vfj_params = $$('.vfj_params')[source].value;
        if (d.getElement('select')) {
            s.getElement('select').value = d.getElement('select').value;
        } else {
            s.getElement('select').value = '';
        }
        if ($$('.vfj_params')[destination]) {
            $$('.vfj_params')[source].value = $$('.vfj_params')[destination].value;
        } else {
            $$('.vfj_params')[source].value = '';
        }
        d.getElement('select').value = t.vfj_field;
        $$('.vfj_params')[destination].value = t.vfj_params;
        vfjMgr.set_setting();
    },
    test_query: function() {
        new Request({
            url: 'index.php?option=mod_views25&module=mod_views25&task=testQuery',
            data: $('vfjForm'),
            onProgress: function() {
                $('vfj-result').set('html', 'Checking the query...');
            },
            onComplete: function(data) {
                data = JSON.decode(data);
                if (data.error == 1) {
                    $('vfj-result').set('html', data.message);
                }else{
                    if ( ! data.data) data.data = 'No Results Found';
                    $('vfj-result').set('html', data.data);
                }
            }
        }).send();
    },
    preview: function() {
alert('To be implemented');
    }
};
window.addEvent('domready', function() {

    $('vfj_table_list_wrapper').hide();
    $('vfj_table_list').addEvent('click', function() {
        $('vfj_table_list_wrapper').toggle();
    });

    $('vfj_query_builder_wrapper').hide();
    $('vfj_query_builder').addEvent('click', function() {
        $('vfj_query_builder_wrapper').toggle();
    });
    $('vfj_query_single_article_button').addEvent('click', function() {
        vfjMgr.set_query({'type': 'single_article'});
    });

    $('vfj_query_articles_button').addEvent('click', function() {
        vfjMgr.set_query({'type': 'articles'});
    });

    $('vfj_query').addEvents({
        'keyup': function() { if ($('live_update').checked) vfjMgr.test_query(); },
        'blur': function() { vfjMgr.set_setting(); }
    });

    $$('.vfj .left').each(function(item, index) {
        item.getElements('.vfj_switch a').addEvent('click', function(e) {
            var p = this.getParent();
            e = new Event();
            e.stop();
            if (p.hasClass('vfj_switch_vertical_top')) {
                vfjMgr.switch_field(index, index + 2);
            }
            if (p.hasClass('vfj_switch_vertical_bottom')) {
                vfjMgr.switch_field(index, index - 2);
            }
            if (p.hasClass('vfj_switch_horizontal_left')) {
                vfjMgr.switch_field(index, index + 1);
            }
            if (p.hasClass('vfj_switch_horizontal_right')) {
                vfjMgr.switch_field(index, index - 1);
            }
        });
    });

    $$('.vfj_params, #vfj_options, #vfj_php, #vfj_css, #vfj_scripts').addEvent('blur', function() { vfjMgr.set_setting(); });
    $$('#vfj_php_enabled, #vfj_css_enabled, #vfj_scripts_enabled').addEvent('change', function() { vfjMgr.set_setting(); });

    $('vfj_layout').addEvent('change', function() { vfjMgr.preview(); });
    $('vfj_layout_button').addEvent('click', function() { vfjMgr.preview(); });

    vfjMgr.parse_setting();
});
");

        $doc->addStyleDeclaration("
#vfjForm {
}
    #vfjForm .button {
        width: 200px;
        padding: 10px 20px;
        cursor: button;
    }
    div.current .vfj {
    }
        #vfj_query {
            width: 80%;
            height: 200px;
            margin-bottom: 13px;
        }
        div.current .vfj .checkbox {
            margin-top: 8px;
        }
        #vfj-result {
            width: 800px;
            overflow: auto;
            padding: 10px 0;
        }
            #vfj-result table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
                #vfj-result table th,
                #vfj-result table td {
                    white-space: nowrap;
                    padding: 4px;
                    border: 1px solid #000;
                }
                #vfj-result table tr.header td {
                    font-weight: bold;
                }
            #vfj-result .button {
            }
            #vfj-message {
                margin: 5px 0 10px 140px;
            }
        div.current .vfj .left,
        div.current .vfj .right {
            float: left;
        }
        div.current .vfj .left {
            margin-left: 13px;
            width: 13%
        }
            div.current .vfj .vfj_field_box {
                width: 100%;
                overflow: hidden;
            }
            div.current .vfj .vfj_switch_vertical {
            }
                div.current .vfj .vfj_switch a {
                    display: block;
                    padding-left: 20px;
                    line-height: 20px;
                }
                div.current .vfj .vfj_switch_vertical_top a {
                    background: transparent url(../plugins/system/views25/assets/images/arrow_down.png) no-repeat left center;
                }
                div.current .vfj .vfj_switch_vertical_bottom a {
                    background: transparent url(../plugins/system/views25/assets/images/arrow_up.png) no-repeat left center;
                }
                div.current .vfj .vfj_switch_horizontal_left a {
                    background: transparent url(../plugins/system/views25/assets/images/arrow_right.png) no-repeat left center;
                }
                div.current .vfj .vfj_switch_horizontal_right a {
                    background: transparent url(../plugins/system/views25/assets/images/arrow_left.png) no-repeat left center;
                }
        div.current .vfj .right {
            width: 35%
        }
            div.current .vfj label {
                clear: none;
            }
            div.current .vfj textarea.vfj_params {
                width: 100%;
                height: 200px;
            }
        div.current .vfj_box_1 {
            clear: both;
            width: 100%;
            overflow: hidden;
            padding-bottom: 8px;
        }
            #vfj_query_builder,
            #vfj_table_list {
                cursor: pointer;
            }
            div.current .vfj_box_1 a.table-list {
                display: block;
                float: left;
                width: 30%;
                padding: 6px;
                margin-right: 8px;
                text-decoration: none !important;
                cursor: pointer;
            }
            div.current .vfj_box_1 a.table-list:hover {
                background: #dfdfdf !important;
            }
            div.current .vfj_box_1 .fltlft {
                margin: 4px 10px 0 0;
            }
            div.current .vfj_box_1 .button2-left {
                margin-top: 4px;
            }
            div.current .vfj_box_1 select {
                margin: 7px 10px 0 0;
            }
            div.current .vfj_box_1 .button {
                padding: 0 5px !important;
                width: auto !important;
                height: 24px;
            }
        label.vfj_full_width {
            width: 80%;
        }
        fieldset.adminform textarea.vfj_full_width {
            width: 80%;
            height: 300px;
        }
    div.current .vfj_feedback {
        padding-top: 32px;
    }
        div.current .vfj_feedback p {
            font-size: 24px;
            margin-bottom: 24px;
        }
        div.current .vfj_feedback .button {
            display: block;
            border: 1px solid #ccc;
            text-decoration: none;
            width: 460px !important;
        }
        ");

        $buffer = $doc->getBuffer();
        $buffer = array_pop($buffer['component']);

        $contents = '';

        $contents .= JHtml::_('tabs.start', 'view-settings-tabs');

        $contents .= JHtml::_('tabs.panel', 'Query / Layout', 'view-details');

        $contents .= '<fieldset class="vfj">';

        $lang_article = JFactory::getLanguage();
        $lang_article->load('com_content');

        require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_content'.DS.'models'.DS.'fields'.DS.'modal'.DS.'article.php';
        $modal_article = new JFormFieldModal_Article;
        $options_article = $modal_article->input;
        unset($modal_article);

        $select_category  = JText::_('JOPTION_SELECT_CATEGORY');
        $options_category = JHtml::_('select.options', JHtml::_('category.options', 'com_content'), 'value', 'text');

        $contents .= '<label style="margin-bottom: 10px" id="vfj_query_builder">Query Builder (Show/Hide) :</label>';
        $contents .= <<<EOH
<div id="vfj_query_builder_wrapper">
<div class="vfj_box vfj_box_1">
    <label style="min-width: 110px">Display article</label>
    {$options_article}
    <input type="button" class="button" id="vfj_query_single_article_button" name="vfj_query_single_article_button" value="Generate Query" />
</div>
<div class="vfj_box vfj_box_1">
    <label style="min-width: 110px">Display article(s) in</label>
    <select id="vfj_query_articles_category" name="vfj_query_articles_category" class="inputbox">
        <option value="">{$select_category}</option>
        {$options_category}
    </select>
    <select id="vfj_query_articles_order" name="vfj_query_articles_order" class="inputbox">
        <option value="">- Select Order Field -</option>
        <option value="ordering">Ordering</option>
        <option value="modified">Modified Date</option>
        <option value="created">Created Date</option>
        <option value="title">Title</option>
    </select>
    <select id="vfj_query_articles_order_dir" name="vfj_query_articles_order_dir" class="inputbox">
        <option value="">- Select Order -</option>
        <option value="ASC">Ascending</option>
        <option value="DESC">Descending</option>
    </select>
    <label style="min-width: 30px" for="vfj_query_articles_limit">Limit</label>
    <input type="text" id="vfj_query_articles_limit" name="vfj_query_articles_limit" class="inputbox" value="" />
    <input type="button" class="button" id="vfj_query_articles_button" name="vfj_query_articles_button" value="Generate Query" />
</div>
</div>
EOH;
        // @TODO Display VirtueMart Query Builder if detected the related table(s)
        // @TODO Display Query Builder for extensions if detected the table(s)
        $contents .= '<div class="clr"></div>';

        $prefix = $db->getPrefix();
        $table_list = $db->getTableList();
        foreach ($table_list as &$value) {
            $table = preg_replace('/'.preg_quote($prefix, '/').'/', '#__', $value);
            $value = '<a class="modal table-list" rel="{handler: \'ajax\', ajaxOptions: {\'method\': \'post\'}}" href="index.php?option=mod_views25&module=mod_views25&task=getFieldList&table='.$table.'">'.$table.'</a>';
        }
        $table_list = implode('', $table_list);

        $contents .= '<label id="vfj_table_list" class="hasTip" title="Info::Click one of the tables put the table name into the clipboard to let you just paste it into Query field">Table List (Show/Hide) :</label>';
        $contents .= <<<EOH
<div id="vfj_table_list_wrapper" class="vfj_box vfj_box_1">
    {$table_list}
</div>
<div style="width: 100%; height: 20px"></div>
EOH;
        $contents .= '<div class="clr"></div>';

        $contents .= '<label for="vfj_query" class="hasTip" title="Put SQL statement here or set one from Template">Query:</label>';
        $contents .= '<textarea id="vfj_query" name="vfj_query"></textarea>';
        $contents .= '<div class="clr"></div>';

        $contents .= '<label for="live_update" class="hasTip" title="Query will be sent every time you type something in Query.">Live Update:</label>';
        $contents .= '<input type="checkbox" style="margin-right: 10px" class="checkbox" id="live_update" name="live_update" value="1" />';
        $contents .= '<div class="clr"></div>';

        $contents .= '<label for="full_field_value" class="hasTip" title="Without ticking this the value over 97 characters gets cut off. Tick this if you need to see full value. HTML tags are stripped out regardless of this setting (which does not happen at frontend).">See Full Value:</label>';
        $contents .= '<input type="checkbox" style="margin-right: 10px" class="checkbox" id="full_field_value" name="full_field_value" value="1" onclick="vfjMgr.test_query();" />';
        $contents .= '<input type="button" class="button" value="Query" onclick="vfjMgr.test_query();" />';
        $contents .= '<div class="clr"></div>';

        $contents .= '<div id="vfj-message"></div>';

        $contents .= '<label class="hasTip" title="LIMIT 1 will be added if the query does not have LIMIT statement">Result:</label>';
        $contents .= '<div id="vfj-result"></div>';
        $contents .= '</fieldset>';

        $contents .= '<fieldset class="vfj">';

        for ($i = 0; $i < 5; $i++) {

            $no = ($i * 2) + 1;

            $aClass = array();

            if ($i == 4) {
                $aClass[] = 'vfj_switch_vertical_bottom';
            } else {
                if ($i) {
                    $aClass[] = 'vfj_switch_vertical_bottom';
                }
                $aClass[] = 'vfj_switch_vertical_top';
            }

            $contents .= '<div class="left">';
            $contents .= '<label>Field '.$no.':</label>';
            $contents .= '<div id="vfj_field_'.($i * 2).'" class="vfj_field_box"></div>';
            foreach ($aClass as $class) {
                $contents .= '<div class="vfj_switch '.$class.'"><a href="#">Move</a></div>';
            }
            $contents .= '<div class="vfj_switch vfj_switch_horizontal_left"><a href="#">Move</a></div>';
            $contents .= '</div>';
            $contents .= '<div class="right">';
            $contents .= '<textarea class="vfj_params" name="vfj_params[]">'.'</textarea>';
            $contents .= '</div>';

            $contents .= '<div class="left">';
            $contents .= '<label>Field '.($no + 1).':</label>';
            $contents .= '<div id="vfj_field_'.($i * 2 + 1).'" class="vfj_field_box"></div>';
            foreach ($aClass as $class) {
                $contents .= '<div class="vfj_switch '.$class.'"><a href="#">Move</a></div>';
            }
            $contents .= '<div class="vfj_switch vfj_switch_horizontal_right"><a href="#">Move</a></div>';
            $contents .= '</div>';
            $contents .= '<div class="right">';
            $contents .= '<textarea class="vfj_params" name="vfj_params[]">'.'</textarea>';
            $contents .= '</div>';

            $contents .= '<div class="clr"></div>';
        }

        // Manual
        $plugins = $helper->getPlugins();

        $contents .= '<div class="info">';
        $contents .= '<h3>About Parameters for field</h3>';
        $contents .= '<table id="vfj-markup">';
        foreach ($plugins as $plg) {
            $contents .= $helper->getManual($plg->name, 'below_query_settings');
        }
        $contents .= '</table>';
        $contents .= '</div>';

        $contents .= '</fieldset>';




        $contents .= JHtml::_('tabs.panel', 'Options', 'view-details');

        $contents .= '<fieldset class="vfj">';
        $contents .= '<label for="vfj_options">Options:</label>';
        $contents .= '<textarea id="vfj_options" name="vfj_options" class="vfj_full_width">'
                   . "example1=value1\nexample2=value2"
                   . '</textarea>';
        $contents .= '</fieldset>';




        if ($custom_php_code) {

            $contents .= JHtml::_('tabs.panel', 'Custom PHP', 'view-details');

            $contents .= '<fieldset class="vfj">';
            $contents .= '<label for="vfj_php" class="vfj_full_width">The value here can be applied as PHP code:</label>';
            $contents .= '<div class="clr"></div>';
            $contents .= '<label for="vfj_php_enabled">Enable Custom PHP:</label>';
            $contents .= '<input type="checkbox" style="margin-right: 10px" class="checkbox" id="vfj_php_enabled" name="vfj_php_enabled" value="1" />';
            $contents .= '<div class="clr"></div>';
            $contents .= '<textarea id="vfj_php" name="vfj_php" class="vfj_full_width">'
                       . "// PHP code here\n// var_dump('Test');\n"
                       . '</textarea>';
            $contents .= '</fieldset>';
        } else {
            $contents .= '<input type="hidden" id="vfj_php_enabled" name="vfj_php_enabled" value="0" />';
            $contents .= '<input type="hidden" id="vfj_php" name="vfj_php" value="" />';
        }




        $contents .= JHtml::_('tabs.panel', 'CSS', 'view-details');

        $contents .= '<fieldset class="vfj">';
        $contents .= '<label for="vfj_css" class="vfj_full_width">CSS:</label>';
        $contents .= '<div class="clr"></div>';
        $contents .= '<label for="vfj_css_enabled">Enable Custom CSS:</label>';
        $contents .= '<input type="checkbox" style="margin-right: 10px" class="checkbox" id="vfj_css_enabled" name="vfj_css_enabled" value="1" />';
        $contents .= '<div class="clr"></div>';
        $contents .= '<textarea id="vfj_css" name="vfj_css" class="vfj_full_width">'
                   . ".moduletable xxx \n{\n    margin: 10px;\n}\n"
                   . '</textarea>';
        $contents .= '</fieldset>';




        $contents .= JHtml::_('tabs.panel', 'JavaScript', 'view-details');

        $contents .= '<fieldset class="vfj">';
        $contents .= '<label for="vfj_scripts">JavaScript:</label>';
        $contents .= '<div class="clr"></div>';
        $contents .= '<label for="vfj_scripts_enabled">Enable Custom JavaScript:</label>';
        $contents .= '<input type="checkbox" style="margin-right: 10px" class="checkbox" id="vfj_scripts_enabled" name="vfj_scripts_enabled" value="1" />';
        $contents .= '<div class="clr"></div>';
        $contents .= '<textarea id="vfj_scripts" name="vfj_scripts" class="vfj_full_width">'
                   . "window.addEvent('domready', function() {\n    // Logic here\n}\n"
                   . '</textarea>';
        $contents .= '</fieldset>';




        $contents .= JHtml::_('tabs.panel', 'Preview', 'view-details');

        preg_match('/<select id="jform_params_layout".+<\/select>/smU', $buffer, $matches);

        $contents .= '<fieldset class="vfj">';
        if (isset($matches[0])) {
            $contents .= '<label class="hasTip" title="LIMIT 3 will be added if the query does not have the statement">Layout:</label>';
            $contents .= preg_replace(
                array(
                     '/id="[^"]+"/',
                     '/name="[^"]+"/',
                ),
                array(
                     'id="vfj_layout"',
                     'name="vfj_layout"',
                ),
                $matches[0]
            );
            $contents .= '<input type="button" style="display: block; clear: both" class="button" id="vfj_layout_button" value="Preview" />';
        }
        $contents .= '</fieldset>';




        $contents .= JHtml::_('tabs.panel', 'Feedback / Share', 'view-details');

        $contents .= '<fieldset class="vfj vfj_feedback">';
        $contents .= '<p><a class="button" target="_blank" href="http://forjoomla.uservoice.com/knowledgebase">Knowledge Base</a></p>';
        /*
        $contents .= '<p><a class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 700}}" href="http://ideas.forjoomla.net/contact?tmpl=component">Send Feedback, Feature Request etc</a></p>';
        $contents .= '<p><a class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 700}}" href="http://ideas.forjoomla.net/share-your-views-for-joomla-setting?tmpl=component">Share your settings to let our lives easier</a></p>';
        */
        // $contents .= '<p><a class="button" target="_blank" href="http://ideas.forjoomla.net/contact">Send Feedback, Feature Request etc</a></p>';
        $contents .= '<p><a class="button" title="Open feedback & support dialog (powered by UserVoice)" href="javascript:UserVoice.showPopupWidget();">Feedback &amp; Support</a></p>';
        $contents .= '<p><a class="button" target="_blank" href="http://ideas.forjoomla.net/contact">Send Feedback, Feature Request etc at Ideas for Joomla website</a></p>';
        $contents .= '<p><a class="button" target="_blank" href="http://ideas.forjoomla.net/share-your-views-for-joomla-setting">Share your settings to let our lives easier</a></p>';
        $contents .= '<p><a class="button" target="_blank" href="http://twitter.com/hironozu">Follow the author on Twitter</a></p>';
        $contents .= '</fieldset>';

        $contents .= JHtml::_('tabs.end');




        $token = JHtml::_('form.token');

        $buffer .= <<<EOH
<form id="vfjForm" method="post" action="index.php">
<fieldset class="adminform">

<legend>View Settings</legend>

<fieldset class="adminform" style="display: none">
    <input style="width: 500px" class="button" type="button" value="Apply Setting to Module (Don't forget to click Save/Apply button after clicking this!)" onclick="vfjMgr.set_setting();" />
</fieldset>

{$contents}
{$token}
</fieldset>
</form>

EOH;
        $doc->setBuffer($buffer, 'component');
    }
}
