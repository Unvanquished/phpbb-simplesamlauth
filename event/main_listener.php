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
	static public function getSubscribedEvents()
	{
		return array(
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
}
