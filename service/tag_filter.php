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

	/** @var bool */
	protected $casesensitive = false;

	public function __construct(tags_manager $tags_manager, \phpbb\db\driver\driver_interface $db, $table_prefix)
	{
		$this->tags_manager = $tags_manager;
		$this->db = $db;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * @param array $tags
	 * @param string $mode AND or OR
	 * @param bool $casesensitive
	 */
	public function set_search_context(array $tags, $mode = 'AND', $casesensitive = false)
	{
		$this->search_tags = array_values($tags);
		$this->search_mode = ($mode == 'OR' ? 'OR' : 'AND');
		$this->casesensitive = (bool) $casesensitive;
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
	 * @return bool
	 */
	public function is_casesensitive()
	{
		return $this->casesensitive;
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
	 * @param int $limit
	 * @return array list of array('tag' => string, 'count' => int)
	 */
	public function get_cooccurring_tags(array $tags, $mode = 'AND', $casesensitive = false, $limit = 30)
	{
		if (empty($tags))
		{
			return array();
		}

		$topics_sql = $this->tags_manager->get_topics_build_query($tags, $mode, $casesensitive);

		if ($casesensitive)
		{
			$exclude_sql = $this->db->sql_in_set('t2.tag', $tags, true, true);
		}
		else
		{
			$lower = $tags;
			for ($i = 0, $count = sizeof($lower); $i < $count; $i++)
			{
				$lower[$i] = utf8_strtolower($lower[$i]);
			}
			$exclude_sql = $this->db->sql_in_set('t2.tag_lowercase', $lower, true, true);
		}

		$sql = 'SELECT t2.tag, COUNT(DISTINCT tt2.topic_id) AS tag_count
			FROM (' . $topics_sql . ') topics
			JOIN ' . $this->table_prefix . tables::TOPICTAGS . ' tt2 ON tt2.topic_id = topics.topic_id
			JOIN ' . $this->table_prefix . tables::TAGS . ' t2 ON t2.id = tt2.tag_id
			WHERE ' . $exclude_sql . '
			GROUP BY t2.tag
			ORDER BY t2.tag ASC';

		$result = $this->db->sql_query_limit($sql, (int) $limit);
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
