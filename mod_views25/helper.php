<?php
/*------------------------------------------------------------------------
# mod_views25 - Views for Joomla
# ------------------------------------------------------------------------
# author    Hiro Nozu
# copyright Copyright (C) 2012 Hiro Nozu. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://http://ideas.forjoomla.net
# Technical Support:  Contact - http://ideas.forjoomla.net/contact
-------------------------------------------------------------------------*/

// no direct access
defined('_JEXEC') or die;

jimport('joomla.html.parameter');

class modViewsHelper extends JObject
{
    public static $self = null;
    private static $plugins = null;
    private static $plugins_index = null;

    public static function getInstance()
    {
        if ( ! self::$self) {
            self::$self = new modViewsHelper;
        }
        return self::$self;
    }
    public static function getData(&$params)
    {
        $app = JFactory::getApplication();

        // Set application parameters in model
        // $appParams = $app->getParams();

        // Get an instance of the generic articles model
        // $model = JModel::getInstance('Articles', 'ContentModel', array('ignore_request' => true));
        // $model->setState('params', $appParams);

        // if ( ! class_exists('plgSystemViews25')) {
        if (JPluginHelper::isEnabled('system', 'views25')) {
            $plugin = JPluginHelper::getPlugin('system', 'views25');
            $registry_plugin = new JRegistry;
            $registry_plugin->loadString($plugin->params);
        } else {
            if ( ! defined('VIEWS25_WARN_NO_PLUGIN')) {
                define('VIEWS25_WARN_NO_PLUGIN', true);
                $app->enqueueMessage(JText::_('mod_views25: System Plugin is not installed/enabled!'), 'error');
            }
            return;
        }

        $data = json_decode($params->get('data', '{}'));

        if ( ! $data->vfj_query) $data->vfj_query = 'SELECT \'Parameter query is empty\' AS message';

        $db = JFactory::getDbo();
        $db->setQuery($data->vfj_query);

        // To enable to refer module option in the template
        $registry = new JRegistry;
        $registry->loadString($data->vfj_options, 'INI');
        $vfj_options = $registry;

        $rows = (array) $db->loadObjectList(); // To enable to refer each record in the template

        $vfj_fields = $data->vfj_fields; // To enable to refer each field (which is Array) in the template

        $vfj_php_enabled = ($data->vfj_php_enabled and $registry_plugin->get('custom_php_code'));
        $vfj_php         = $data->vfj_php;

        $vfj_css_enabled = $data->vfj_css_enabled;
        $vfj_css         = $data->vfj_css;

        $vfj_scripts_enabled = $data->vfj_scripts_enabled;
        $vfj_scripts         = $data->vfj_scripts;

        // To enable to refer each field parameter (which is Array) in the template
        $vfj_params = array();
        if (count($data->vfj_params)) {
            foreach ($data->vfj_params as &$tmp_vfj_param) {
                $registry = new JRegistry;
                $registry->loadString($tmp_vfj_param, 'INI');
                $vfj_params[] = $registry;
            }
        }

        $helper = modViewsHelper::getInstance(); // To avoid instancing the helper class in the template

        require JModuleHelper::getLayoutPath('mod_views25', $params->get('layout', 'default'));

        return true;
    }
    public function testQuery() {

        $data = array(
            'error' => 0,
            'message' => '',
            'data' => '',
        );

        $jinput = JFactory::getApplication()->input;

        $query = trim($jinput->get('vfj_query', '', 'string'));

        // To deny executing this method except for Super Administrator just in case
        $user = JFactory::getUser();
        if ($user->id) {
            $result	= $user->authorise('com_admin');
            if ( ! $result) {
                $data = array(
                    'error' => 1,
                    'message' => 'Access Forbidden',
                );
                $query = '';
            }
        } else {
            $data = array(
                'error' => 1,
                'message' => 'Authentication Failed',
            );
            $query = '';
        }

        switch (true) {
            default:

                if ($query == '') break;

                if ( ! preg_match('/^select/i', $query)) {
                    $data = array(
                        'error' => 1,
                        'message' => 'Query has to begin with the word "SELECT"',
                    );
                    break;
                }
                if (preg_match('/(insert|update) /i', $query)) {
                    $data = array(
                        'error' => 1,
                        'message' => 'Query cannot contain the word "UPDATE" or "INSERT"',
                    );
                    break;
                }

                $db = JFactory::getDbo();
                $db->setQuery($query, 0, (strpos(strtolower($query), 'limit') === false) ? 1 : 0);

                // Version 2.5 sets legacy mode for backend so I set it to false.
                // This logic should be gone once newer version stops using legacy mode.
                $legacy = JError::$legacy;
                JError::$legacy = false;
                try {
                    $result = $db->loadAssocList();
                } catch(Exception $e) {
                    $data = array(
                        'error' => 1,
                        'message' => $e->getMessage(),
                    );
                    break;
                }
                JError::$legacy = $legacy;

                if (count($result)) {

                    $fields = '';
                    $values = '';

                    $show_shortened_value = ($jinput->get('full_field_value', '') == '');

                    foreach ($result as $index => &$row) {
                        $values .= '<tr>';
                        if ($index == 0) $fields .= '<tr class="header">';
                        foreach ($row as $key => &$field_value) {
                            if ($index == 0) {
                                $fields .= '<td>'.$key.'</td>';
                            }
                            if ($show_shortened_value) {
                                $field_value = strip_tags($field_value);
                                if (strlen($field_value) > 100) $field_value = substr($field_value, 0, 97).'...';
                            }
                            $values .= '<td>'.strip_tags($field_value).'</td>';
                        }
                        if ($index == 0) $fields .= '</tr>';
                        $values .= '</tr>';
                    }
                    $d = <<<EOH
<table>{$fields}{$values}{$fields}</table>
<input type="button" class="button" onclick="vfjMgr.apply_result()" value="Apply Result" />
EOH;
                }else{
                    $d = '';
                }
                $data['data'] = $d;
        }
        echo json_encode($data);
    }
    public function getFieldList() {

        $data = array(
            'error' => 0,
            'message' => '',
            'data' => '',
        );

        $jinput = JFactory::getApplication()->input;

        $query = trim($jinput->get('vfj_query', '', 'string'));

        // To deny executing this method except for Super Administrator just in case
        $user = JFactory::getUser();
        if ($user->id) {
            $result	= $user->authorise('com_admin');
            if ( ! $result) {
                $data = array(
                    'error' => 1,
                    'message' => 'Access Forbidden',
                );
                $query = '';
            }
        } else {
            $data = array(
                'error' => 1,
                'message' => 'Authentication Failed',
            );
            $query = '';
        }

        switch (true) {
            default:

                if ($query == '') break;

                if ( ! preg_match('/^select/i', $query)) {
                    $data = array(
                        'error' => 1,
                        'message' => 'Query has to begin with the word "SELECT"',
                    );
                    break;
                }
                if (preg_match('/(insert|update) /i', $query)) {
                    $data = array(
                        'error' => 1,
                        'message' => 'Query cannot contain the word "UPDATE" or "INSERT"',
                    );
                    break;
                }

                $db = JFactory::getDbo();
                $db->setQuery($query, 0, (strpos(strtolower($query), 'limit') === false) ? 1 : 0);

                $legacy = JError::$legacy;
                JError::$legacy = false;
                try {
                    $result = $db->loadAssocList();
                } catch(Exception $e) {
                    $data = array(
                        'error' => 1,
                        'message' => $e->getMessage(),
                    );
                    break;
                }
                JError::$legacy = $legacy;

                $fields = array();

                if (count($result)) {
                    foreach ($result[0] as $key => $value) {
                        $fields[] = $key;
                    }
                }else{
                    $data = array(
                        'error' => 1,
                        'message' => 'Sorry, the query has to return one record at least!',
                    );
                    break;
                }
                $data['message'] = 'Fields have been applied.';
                $data['data'] = $fields;
        }
        echo json_encode($data);
    }
    public function getPlugin($name)
    {
        if ($name == '') return null;
        $name = strtolower($name);
        if ($name == 'default') $name = 'views25';
        if (isset(self::$plugins_index[$name])) {
            return self::$plugins_index[$name];
        } else {
            return null;
        }
    }
    public function getPlugins()
    {
        if (self::$plugins !== null) {
            return self::$plugins;
        }

        $user  = JFactory::getUser();
        $cache = JFactory::getCache('mod_views_views25_plugins', '');

        $levels = implode(',', $user->getAuthorisedViewLevels());

        if ( ! self::$plugins = $cache->get($levels)) {

            self::$plugins = JPluginHelper::getPlugin('views25');
            self::$plugins = array_merge(array(JPluginHelper::getPlugin('system', 'views25')), self::$plugins);

            // Apply plugin
            JPluginHelper::importPlugin('views25'); // Load (require_once) enabled plugins

            $dispatcher = JDispatcher::getInstance();
            foreach (self::$plugins as &$plugin) {
                $plugin_name = strtolower($plugin->name);
                $class_name = ($plugin_name == 'views25') ? 'plgSystemViews25' : 'plgViews25'.ucfirst($plugin_name);
                if (class_exists($class_name)) {
                    $object = new $class_name($dispatcher, (array) $plugin);
                    $plugin->object = $object;
                    self::$plugins_index[$plugin_name] = $plugin;
                }
            }

            $cache->store(self::$plugins, $levels);
        }

        return self::$plugins;
    }
    public function getManual($name, $type) {
        $file_path = ($name == 'views25')
                   ? JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'views25'.DS.'manual.php'
                   : JPATH_ROOT.DS.'plugins'.DS.'views25'.DS.$name.DS.'manual.php';
        if ( ! file_exists($file_path)) return '';
        require_once $file_path;
        $function_name = $name.'GetManual';
        if ( ! function_exists($function_name)) return '';
        return $function_name($type);
    }
    public function parse(&$field, &$vfj_param, &$record, &$vfj_options, &$params)
    {
        if (isset($record->$field)) {
            $value = $record->$field;
        }else{
            $value = '';
        }

        $plugins = explode(',', $vfj_param->get('plugin'));

        // Apply default plugin unless it is disabled by the parameter "no_default_plugin"
        if ( ! in_array('default', $plugins) and ! $vfj_param->get('no_default_plugin')) {
            $plugins = array_merge(array('default'), $plugins);
        }

        if (count($plugins)) {

            $this->getPlugins(); // Load all plugins

            foreach ($plugins as &$plugin) {
                $plugin_class = $this->getPlugin($plugin);
                if (method_exists($plugin_class->object, 'onParse')) {
                    $plugin_class->object->onParse($value, $field, $vfj_param, $record, $params, $plugins);
                }
            }
        }

        /*
        $value = preg_replace(
            array(
                 // '/{url}/',
                 '/{value}/',
            ),
            array(
                 // $url,
                 $value,
            ),
            $param_value
        );
        */
        // Parameter value_transformed gets created plgSystemViews25::onParse.
        // The parameter is the return value from the method plgSystemViews25::transformValue.
        // The method replaces {f:field} to the actual value.
        $value = preg_replace('/{value}/', $value, $vfj_param->get('value_transformed'));

        return $value;
    }
}
