<?php

/**
* @copyright	Copyright (C) 2013 Function90.
* @license		GNU/GPL, see LICENSE.php
* @contact		dev.function90+ contact@gmail.com
* @author		Function90
*/

defined( '_JEXEC' ) or die( 'Restricted access' );


JFormHelper::loadFieldClass('list');

/** 
 * Joomla Usertype Field
 */
class LFAFormFieldUsertype extends JFormFieldList
{
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$usertypes = $this->_getUsertypes();

		$options[] = JHtml::_('select.option', 0, "All");
		foreach ($usertypes as $usertype){
			$options[] = JHtml::_('select.option', $usertype->id, $usertype->title);
		}
		
		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
	
	protected function _getUsertypes()
	{
		$db	 =  JFactory::getDBO();
		$sql = ' SELECT `title`, `id` FROM '.$db->quoteName('#__usergroups')
				.' WHERE '.$db->quoteName('title').' NOT LIKE "%Public%"';
		$db->setQuery($sql);
		return $db->loadObjectList();
	}
}
