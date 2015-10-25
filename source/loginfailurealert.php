<?php
/**
* @copyright	Copyright (C) 2013 Function90.
* @license		GNU/GPL, see LICENSE.php
* @contact		dev.function90+contact@gmail.com
* @author		Function90
*/

defined( '_JEXEC' ) or die( 'Restricted access' );


/**
 * Login Failure Alert System Plugin
 */
require_once __DIR__.'/usertype.php';
	
class plgSystemLoginfailurealert extends JPlugin
{	
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	
	public function onUserLoginFailure($response)
	{
		$plg_application = $this->params->get('application', 2);
		$application	 = JFactory::getApplication();
		
		if ($plg_application == 0 && $application->isAdmin() == false ){
			return true;
		}
		
		if ($plg_application == 1 && $application->isSite() == false ){
			return true;
		}
		
		if (!is_array($response)){
			return true;
		}
		
		$username = isset($response['username']) ? $response['username'] : '';
		
		$user = $this->_getUser($username, $option);

		// 	If _getUser returned an error, then pass it back.
		if ($user instanceof Exception)
		{
			return false;
		}
		
		$usertype_result = $this->_checkUsertype($user);
		
		if ($usertype_result == true){
			$this->_sendAlert($user);
		}
		
		return true;
	}
	
	/*
	 * Get user instance from username 
	 */
	protected function _getUser($username, $option = array())
	{
		$instance = JUser::getInstance();
		$id = (int) JUserHelper::getUserId($username);
		if ($id)
		{
			$instance->load($id);
			return $instance;
		}
	}
	
	/*
	 * send alert 
	 */
	protected function _sendAlert($user)
	{
		$config	= JFactory::getConfig();
		
		$mail_to_admin= $this->params->get('mail_to_admin', 1);
		$mail_content = $this->params->get('mail_content', '');
		$mail_subject = $this->params->get('mail_subject', '');
		$alert_user	  = $this->params->get('alert_user', 0);
		
		$recipient = array($mail_to_admin);
		if ($alert_user){
			$recipient[] = $user->email;
		}
		
		$mail = JFactory::getMailer()
							->setSender(
										array(
												$config->get('mailfrom'),
												$config->get('fromname')
											)
										)
							->addRecipient($recipient)
							->setSubject($this->replaceToken($mail_subject, $user))
							->setBody($this->replaceToken($mail_content, $user))
							->isHtml(true);
							
		if (!$mail->Send()) {
			
		}
		
		return true;
	}
	
	protected function _checkUsertype($user)
	{
		$plg_usertypes = $this->params->get('usertype', 0);
		
		if (in_array(0, $plg_usertypes)){
			return true;
		}
		
		$usergroup = JUserHelper::getUserGroups($user->id);
		
		$intersect = array_intersect($plg_usertypes, $usergroup);
		if (count($intersect)>0){
			return true;
		}
		
		return false;
	}
	
	/*
	 * Replace tokens with user values
	 */
	function replaceToken($content, $user)
	{
		$app = (JFactory::getApplication()->isAdmin()) ? 'Administrator' : 'Site';

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('group_concat(title)')
				->from('#__usergroups as gp')
				->where('gp.id IN ('.implode(',', $user->groups).')');
		
		$db->setQuery($query);
		$usergroups = $db->loadRow();		
		$usergroups = implode(',', array_values($usergroups));

		$tokens = array('{LFA_USERID}'=>$user->id, '{LFA_EMAIL}'=>$user->email, '{LFA_USERNAME}'=>$user->username, '{LFA_NAME}'=>$user->name,'{LFA_LOCATION}'=>$app, '{LFA_USERGROUP}'=>$usergroups);
		foreach($tokens as $key => $value){
			$content =  str_replace($key, $value, $content);
		}
		
		return $content;
	}
}

