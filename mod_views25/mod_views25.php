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
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT.DS.'modules'.DS.'mod_views25'.DS.'helper.php';

$jinput = JFactory::getApplication()->input;
if ($jinput->get('option') == 'mod_views25') { // The request is the one via onAfterRoute event

    // Check for request forgeries (Version 2.5.2 says JRequest is deprecated so I am gonna have to amend this part later)
    JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

    // $app = JFactory::getApplication(); // Not to create one here because $app has been already defined in plugins/system/views25/views25.php::onAfterRoute
    $task = $jinput->get('task');
    $helper = modViewsHelper::getInstance();
    if (method_exists($helper, $task)) {
        modViewsHelper::$task();
    }

    $app->close();

} else { // The request is general one

    $cacheparams = new stdClass;
    $cacheparams->cachemode = 'safeuri';
    $cacheparams->class = 'modViewsHelper';
    $cacheparams->method = 'getData';
    $cacheparams->methodparams = $params;
    $cacheparams->modeparams = array('id'=>'int', 'Itemid'=>'int');

    $items = JModuleHelper::ModuleCache($module, $params, $cacheparams);

}
