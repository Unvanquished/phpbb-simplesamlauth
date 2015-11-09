<?php
/**
*
* @package phpBB Extension - SimpleSAMLphp Auth
* @copyright (c) 2015 Unvanquished Development
* @license http://opensource.org/licenses/LGPL-2.1 (LGPL-2.1)
*
*/
/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}
$lang = array_merge($lang, array(
	'SAML_NOT_IMPLEMENTED' => 'This functionality is not implemented (yet).',
	'SAML_PATH' => 'SimpleSAMLphp path',
	'SAML_PATH_EXPLAIN' => 'Absolute or relative path to your simpleSAMLphp library.',
	'SAML_SP' => 'SP name for the board.',
	'SAML_SP_EXPLAIN' => 'SAML Service Provider name associated to this board.',
	'SAML_UID' => 'SAML username attribute.',
	'SAML_UID_EXPLAIN' => 'Such as uid, sn, cn, username...',
	'SAML_MAIL' => 'SAML user email attribute.',
	'SAML_MAIL_EXPLAIN' => 'Will fill the user email address upon first login.',
	'SAML_NOT_DIRECTORY' => 'The path you specified is not a directory.',
	'SAML_CANNOT_INCLUDE' => 'Unable to load SimpleSAMLphp library. Did you specify the right directory?',
	'SAML_INVALID_SP' => 'The given SP name is invalid.',
));
