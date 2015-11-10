<?php
/**
*
* @package phpBB Extension - SimpleSAMLphp Auth
* @copyright (c) 2015 Unvanquished Development
* @license hhttp://opensource.org/licenses/gpl-2.0.php GPLv2.0
*
*/

namespace unvanquished\simplesamlphpauth\auth\provider;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}


/**
* SimpleSAMLphp authentication provider for phpBB3
*
* This is for authentication via SimpleSAMLphp
*
* @package simplesamlauth
*/
class simplesamlphpauth extends \phpbb\auth\provider\base
{
	protected $db;
	protected $config;
	protected $user;
	protected $saml;

	public function __construct(\phpbb\db\driver\driver_interface $db,
			\phpbb\config\config $config,
			\phpbb\user $user)
	{
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;

		if (!array_key_exists('saml_path',  $config) || empty($config['saml_path']))
		{
			return;
		}

		if (!is_dir($config['saml_path']))
		{
			return;
		}
		if (!(include_once($config['saml_path'] . '/lib/_autoload.php')))
		{
			return;
		}
		if (!is_string($config['saml_sp']) || empty($config['saml_sp']))
		{
			return;
		}

		$saml = new SimpleSAML_Auth_Simple($config['saml_sp']);
	}

	public function init()
	{
		if (!array_key_exists('saml_path',  $config) || empty($config['saml_path']))
		{
			return $this->user->lang['SAML_CANNOT_INCLUDE'];
		}

		if (!is_dir($config['saml_path']))
		{
			return $this->user->lang['SAML_NOT_DIRECTORY'];
		}
		if (!(include_once($config['saml_path'] . '/lib/_autoload.php')))
		{
			return $this->user->lang['SAML_CANNOT_INCLUDE'];
		}
		if (!is_string($config['saml_sp']) || empty($config['saml_sp']))
		{
			return $this->user->lang['SAML_INVALID_SP'];
		}

		return false;
	}

	/**
	* {@inheritdoc}
	*/
	public function login($username, $password)
	{
		auth_or_redirect();
		if ($this->saml->isAuthenticated())
		{
			$username = get_attribute($this->config['saml_uid']);
			$user_row = get_user_row($username);

			if (empty($user_row))
			{
				// User unknown... We create his/her profile.
				$usermail = '';
				if (!empty($this->config['saml_mail']))
				{
					$usermail = utf8_htmlspecialchars(saml_attribute($this->config['saml_mail']));
				}

				// retrieve default group id
				$sql = 'SELECT group_id
						FROM ' . GROUPS_TABLE . "
						WHERE group_name = '" . $this->db->sql_escape('REGISTERED') . "'
						AND group_type = " . GROUP_SPECIAL;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				if (!$row)
				{
					trigger_error('NO_GROUP');
				}

				$user_row = array(
					'username' => $username,
					'user_password' => phpbb_hash($usermail . rand() . $username),
					'user_email' => $usermail,
					'user_type'  => USER_NORMAL,
					'group_id'   => (int) $row['group_id'],
					'user_ip'    => $this->user->ip,
					'user_new'   => ($this->config['new_member_post_limit']) ? 1 : 0,
				);

				return array(
					'status' => LOGIN_SUCCESS_CREATE_PROFILE,
					'error_msg' => false,
					'user_row' => $user_row,
				);
			}
			else
			{
				// Known user, we just log him in.
				if ($user_row['user_type'] == USER_INACTIVE ||
						$user_row['user_type'] == USER_IGNORE)
				{
					return array(
						'status' => LOGIN_ERROR_ACTIVE,
						'error_msg' => 'ACTIVE_ERROR',
						'user_row' => $user_row,
					);
				}

				return array(
					'status' => LOGIN_SUCCESS,
					'error_msg' => false,
					'user_row' => $user_row,
				);
			}
		}

	}

	/**
	* {@inheritdoc}
	*/
	public function logout($data, $new_session)
	{
		$this->saml->logout(generate_board_url());
	}

	/**
	* {@inheritdoc}
	*/
	public function validate_session($user)
	{
		return $this->saml->isAuthenticated();
	}

	/**
	 * {@inheritdoc}
	 */
	public function acp()
	{
		return array('saml_path', 'saml_sp', 'saml_uid', 'saml_mail');
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_acp_template($new_config)
	{
		return array(
			'TEMPLATE_FILE'	=> 'auth_provider_simplesamlphp.html',
			'TEMPLATE_VARS' => array(),
			'BLOCK_VAR_NAME' => 'options',
			'BLOCK_VARS' => array(
				array(
					'NAME' => 'saml_path',
					'SHORT_DESC' => $this->user->lang['SAML_PATH'],
					'EXPLAIN' => $this->user->lang['SAML_PATH_EXPLAIN'],
					'VALUE' => $new_config['saml_path']
				),
				array(
					'NAME' => 'saml_sp',
					'SHORT_DESC' => $this->user->lang['SAML_SP'],
					'EXPLAIN' => $this->user->lang['SAML_SP_EXPLAIN'],
					'VALUE' => $new_config['saml_sp']
				),
				array(
					'NAME' => 'saml_uid',
					'SHORT_DESC' => $this->user->lang['SAML_UID'],
					'EXPLAIN' => $this->user->lang['SAML_UID_EXPLAIN'],
					'VALUE' => $new_config['saml_uid']
				),
				array(
					'NAME' => 'saml_mail',
					'SHORT_DESC' => $this->user->lang['SAML_MAIL'],
					'EXPLAIN' => $this->user->lang['SAML_MAIL_EXPLAIN'],
					'VALUE' => $new_config['saml_mail']
				),
			),
		);
	}

	/** Reads a SAML attribute.
	*
	*  @param string $attr Attribute name
	*
	*  @return string The attribute value.
	*/
	private function get_attribute($attr)
	{
		$attributes = $this->saml->getAttributes();
		return $attributes[$attr][0];
	}

	/** Reads SAML username attribute.
	*
	*  Reads the SAML username attribute using the saml_uid configuration property.
	*
	*  @return string The username.
	*/
	private function get_username()
	{
		return get_attribute($this->config['saml_uid']);
	}

	/** Get the user row.
	*
	*  Reads the user row from the database. If none is found, then returns the $default_row.
	*
	*  @param string $username Username.
	*  @param array $default_row The default row in case no user is found.
	*  @param bool $select_all Whether to retrieve all fields or just a specific subset.
	*
	*  @return array The user row or $default_row if the user does not exists in phpBB.
	*/
	private function get_user_row($username, $default_row = array(), $select_all = true)
	{
		$user_row = $default_row;
		$sql = 'SELECT';
		if ($select_all)
			$sql .= ' *';
		$sql .= ' FROM ' . USERS_TABLE . " WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($username)) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
			$user_row = $row;

		return $user_row;
	}

	private function auth_or_redirect()
	{
		$returnTo = generate_board_url() . '/';
		$returnTo .= request_var('redirect', $this->user->page['page']);
		$saml->requireAuth(array(
			'ReturnTo' => $returnTo,
		));
	}
}
