<?php
/**
 *
 * @package phpBB Extension - RH Topic Tags
 * @copyright (c) 2014 Robet Heim
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace robertheim\topictags\service;

use robertheim\topictags\tables;

/**
 * Holds the active tag-search filter and finds tags that can still narrow it.
 */
class tag_filter
{
	/** @var \robertheim\topictags\service\tags_manager */
	protected $tags_manager;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $table_prefix;

	/** @var array */
	protected $search_tags = array();

	/** @var string */
	protected $search_mode = 'AND';

	public function __construct(tags_manager $tags_manager, \phpbb\db\driver\driver_interface $db, $table_prefix)
	{
		$this->tags_manager = $tags_manager;
		$this->db = $db;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * @param array $tags
	 * @param string $mode AND or OR
	 */
	public function set_search_context(array $tags, $mode = 'AND')
	{
		$this->search_tags = array_values($tags);
		$this->search_mode = ($mode == 'OR' ? 'OR' : 'AND');
	}

	/**
	 * @return array
	 */
	public function get_search_tags()
	{
		return $this->search_tags;
	}

	/**
	 * @return string
	 */
	public function get_search_mode()
	{
		return $this->search_mode;
	}

	/**
	 * Whether $tag is already in the active filter (case-insensitive).
	 *
	 * @param string $tag
	 * @return bool
	 */
	public function is_selected($tag)
	{
		$needle = utf8_strtolower($tag);
		foreach ($this->search_tags as $current)
		{
			if (utf8_strtolower($current) === $needle)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Tags on the current result set that are not already selected.
	 *
	 * @param array $tags
	 * @param string $mode
	 * @param bool $casesensitive
	 * @return array list of array('tag' => string, 'count' => int)
	 */
	public function get_cooccurring_tags(array $tags, $mode = 'AND', $casesensitive = false)
	{
		if (empty($tags))
		{
			return array();
		}

		$topics_sql = $this->tags_manager->get_topics_build_query($tags, $mode, $casesensitive);
		$sql = 'SELECT t2.tag, COUNT(DISTINCT tt2.topic_id) AS tag_count
			FROM (' . $topics_sql . ') topics
			JOIN ' . $this->table_prefix . tables::TOPICTAGS . ' tt2 ON tt2.topic_id = topics.topic_id
			JOIN ' . $this->table_prefix . tables::TAGS . ' t2 ON t2.id = tt2.tag_id
			WHERE ' . $this->db->sql_in_set('t2.tag', $tags, true, true) . '
			GROUP BY t2.tag
			ORDER BY tag_count DESC, t2.tag ASC';

		$result = $this->db->sql_query($sql);
		$out = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[] = array(
				'tag'	=> $row['tag'],
				'count'	=> (int) $row['tag_count'],
			);
		}
		$this->db->sql_freeresult($result);
		return $out;
	}

	/**
	 * Tag list with $tag added or removed from the current filter.
	 *
	 * @param string $tag
	 * @return array
	 */
	public function toggle_tag($tag)
	{
		$out = array();
		if ($this->is_selected($tag))
		{
			$needle = utf8_strtolower($tag);
			foreach ($this->search_tags as $current)
			{
				if (utf8_strtolower($current) !== $needle)
				{
					$out[] = $current;
				}
			}
			return $out;
		}

		$out = $this->search_tags;
		$out[] = $tag;
		return $out;
	}
}
