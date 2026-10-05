<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright (c) 2014 Robet Heim
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use robertheim\topictags\permissions;

/**
* Event listener
*/
class acp_listener implements EventSubscriberInterface
{

	public static function getSubscribedEvents()
	{
		return array(
			'core.permissions'                         => 'add_permissions',
			'core.acp_manage_forums_initialise_data'   => 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'      => 'acp_manage_forums_display_form',
			'core.acp_manage_forums_validate_data'     => 'acp_manage_forums_validate_data',
			'core.acp_manage_forums_update_data_after' => 'acp_manage_forums_update_data_after',
		);
	}

	/** @var \phpbb\request\request_interface */
	private $request;

	/** @var \phpbb\user */
	private $user;

	/** @var \robertheim\topictags\service\tags_manager */
	private $tags_manager;

	/**
	 * Constructor
	 */
	public function __construct(
		\phpbb\request\request_interface $request,
		\phpbb\user $user,
		\robertheim\topictags\service\tags_manager $tags_manager
	)
	{
		$this->request = $request;
		$this->user = $user;
		$this->tags_manager = $tags_manager;
	}

	/**
	 * Event: core.permissions
	 * phpBB 3.2+ only lists a permission in the ACP if the extension registers it here.
	 */
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions[permissions::USE_TAGS] = array(
			'lang'	=> 'ACL_U_RHTOPICTAGS_USE_TAGS',
			'cat'	=> 'misc',
		);
		$permissions[permissions::ADMIN_EDIT_TAGS] = array(
			'lang'	=> 'ACL_A_RHTOPICTAGS_EDIT_TAGS',
			'cat'	=> 'misc',
		);
		$permissions[permissions::MOD_EDIT_TAGS] = array(
			'lang'	=> 'ACL_M_RHTOPICTAGS_EDIT_TAGS',
			'cat'	=> 'misc',
		);
		$event['permissions'] = $permissions;
	}

	public function acp_manage_forums_initialise_data($event)
	{
		$this->user->add_lang_ext('robertheim/topictags', 'topictags_acp');
	}

	public function acp_manage_forums_display_form($event)
	{
		$data = $event->get_data();

		$data['template_data']['S_RH_TOPICTAGS_ENABLED'] = $data['forum_data']['rh_topictags_enabled'];
		$prune = $this->request->variable('rh_topictags_prune', 0);
		$data['template_data']['S_RH_TOPICTAGS_PRUNE'] = $prune;

		$event->set_data($data);
	}

	public function acp_manage_forums_validate_data($event)
	{
		$data = $event->get_data();

		$status = $this->request->variable('rh_topictags_enabled', 0);
		// ensure 0 or 1
		$status = ($status ? 1 : 0);
		data['forum_data']['rh_topictags_enabled'] = $status;

		// pruning requires the tagging to be disabled for this forum to prevent accidental deletion of tags
		$prune = $this->request->variable('rh_topictags_prune', 0);
		if ($prune && $status)
		{
			$this->user->add_lang_ext('robertheim/topictags', 'topictags_acp');
			$data['errors'][] = $this->user->lang('ACP_RH_TOPICTAGS_PRUNING_REQUIRES_TAGGING_DISABLED');
		}

		$event->set_data($data);
	}

	public function acp_manage_forums_update_data_after($event)
	{
		$status = $this->request->variable('rh_topictags_enabled', 0);
		$prune = $this->request->variable('rh_topictags_prune', 0);
		if (!$status && $prune)
		{
			$data = $event->get_data();
			$forum_id = (int) $data['forum_data']['forum_id'];
			$this->tags_manager->delete_tags_from_tagdisabled_forums(array($forum_id));
			$this->tags_manager->delete_unused_tags();
		}
		$this->tags_manager->calc_count_tags();
	}
}
