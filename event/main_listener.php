<?php
/**
*
* @package phpBB Extension - SimpleSAMLphp Auth
* @copyright (c) 2015 Unvanquished Development
* @license http://opensource.org/licenses/LGPL-2.1 (LGPL-2.1)
*
*/


namespace unvanquished\simplesamlphpauth\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	protected $config;
	protected $template;
	protected $user;
	protected $saml;
	protected $phpEx;

	public function __construct(\phpbb\config\config $config,
			\phpbb\template\template $template,
			\phpbb\user $user,
			$phpEx)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->saml = new \SimpleSAML_Auth_Simple($config['saml_sp']);
		$this->phpEx = $phpEx;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.page_header_after'				=> 'override_login_logout_link',
			'core.user_setup'						=> 'load_language_on_setup',
		);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'unvanquished/simplesamlphpauth',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function override_login_logout_link($event)
	{
		// Generate logged in/logged out status
		if ($this->user->data['user_id'] != ANONYMOUS)
		{
			$u_login_logout = append_sid("{$phpbb_root_path}ucp.{$this->phpEx}", 'mode=logout', true,
					$this->user->session_id);
		}
		else
		{
			$returnTo = generate_board_url() . '/';
			$returnTo .= append_sid("{$phpbb_root_path}ucp.{$this->phpEx}", 'mode=login');
			$u_login_logout = $this->saml->getLoginURL($returnTo);
		}
		$this->template->assign_vars(array(
			'U_LOGIN_LOGOUT' => $u_login_logout,
		));
	}
}
