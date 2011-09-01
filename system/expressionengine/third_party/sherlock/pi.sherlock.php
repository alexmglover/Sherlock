<?php

$plugin_info = array(
  'pi_name' 			=> 'Sherlock',
  'pi_version' 		=> '1.0',
  'pi_author' 			=> 'Thomas Brewer',
  'pi_author_url' 	=> 'http://th3mu1cman.me',
  'pi_description' 	=> 'Inspects URLs',
  'pi_usage' 			=> Sherlock::usage()
);

/**
 * Sherlock - A EE2 plugin inspects urls
 *
 * @package Sherlock
 * @author Thomas Brewer
 **/
class Sherlock {
	
	public $return_data = '';
	
	protected $segments = array();
	
	protected $entry_title_segments = array();
	
	protected $category_title_segments = array();
	
	protected $page_types = array(
		'pagination' 		=> FALSE,
		'category' 			=> FALSE,
		'archive' 			=> FALSE,
	);
	
	protected $current_type = '';
	
	/**
	 * __construct
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function Sherlock() 
	{
		$this->EE =& get_instance();
	}
	
	/**
	 * page_type
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function page_type() 
	{		
		/*
			TODO This needs a big old refactor and cleanup....
		*/
		
		$this->category_title_segments = array_filter(
			explode('|', $this->EE->TMPL->fetch_param('category_title_segments', "2"))
		);
		
		$this->entry_title_segments = array_filter(
			explode('|', $this->EE->TMPL->fetch_param('entry_title_segments', "2"))
		);
		
		$this->tagdata = $this->EE->TMPL->tagdata;

		$this->category_trigger = $this->EE->config->item("reserved_category_word");
		
		$this->segments = $this->EE->uri->segments;
		
		foreach ($this->segments as $segment) 
		{
			$this->_test_segment($segment);
		}
		
		if (count($this->segments) > 0) 
		{
			$category_query = $this->EE->db->select('cat_id')
				->from('categories')
				->where_in('cat_url_title', $this->segments)
				->limit(1)
				->get();
				
				if ($category_query->num_rows() == 1)
				{
					$this->page_types['category'] = TRUE;
					$this->current_type = 'category';
				}
		}
		
		$conditionals = array();
		
		if ($this->_is_listing())
		{
			$conditionals['is_listing'] = TRUE;
			$conditionals['is_not_listing'] = FALSE;
			$conditionals['is_entry'] = FALSE;
			$conditionals['is_not_entry'] = TRUE;
		}
		else
		{
			$conditionals['is_listing'] = FALSE;
			$conditionals['is_not_listing'] = TRUE;
			$conditionals['is_entry'] = TRUE;
			$conditionals['is_not_entry'] = FALSE;
		}
		
		foreach ($this->page_types as $page_type => $cond) 
		{
			$conditionals['is_'.$page_type] = $cond;
			$conditionals['is_not_'.$page_type] = !$cond;
		}	
			
		$conditionals['page_type'] = $this->current_type;
	
		$this->tagdata = $this->EE->functions->prep_conditionals($this->tagdata, $conditionals);

		$this->return_data = str_replace('{page_type}', $this->current_type, $this->tagdata);

		return $this->return_data;
	}
	
	/**
	 * _is_listing
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function _is_listing() 
	{	
		
		if ($this->page_types['category'] || $this->page_types['pagination'] || $this->page_types['archive'])
		{
			
			if ($this->page_types['category'] AND $this->page_types['pagination'] === FALSE)
			{	
				//this might not be an listing page... let check
				foreach (array_diff($this->entry_title_segments, $this->category_title_segments) as $segment) 
				{
					$segment = (int) $segment;
					if (
							array_key_exists($segment, $this->segments) &&
							!empty($this->segments[$segment])
						)
					{
						return FALSE;
					}
					
				}
			}
			else
			{
				return TRUE;
			}
		}
		else
		{	
			//this might not be an entry page... let check
			foreach ($this->entry_title_segments as $segment) 
			{
				$segment = (int) $segment;

				if (array_key_exists($segment, $this->segments) && !empty($this->segments[$segment]))
				{
					return FALSE;
				}
				else 
				{
					return TRUE;
				}
			}
		}
		
		
		return TRUE;
	}
	
	/**
	 * _test_segment
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function _test_segment($segment) 
	{
		if (preg_match('/^[P][0-9]+$/i', $segment))
		{
			$this->page_types['pagination'] = TRUE;
			$this->current_type = 'pagination';
			return TRUE;
		}
		else if (preg_match("/$this->category_trigger/", $segment))
		{
			$this->page_types['category'] = TRUE;
			$this->current_type = 'category';
			return TRUE;
		}
		else if (preg_match("/^\d{4}$/", $segment))
		{
			$this->page_types['archive'] = TRUE;
			$this->current_type = 'archive';
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * category_id
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function category_id() 
	{
		$category_url_title = $this->EE->TMPL->fetch_param('category_url_title', NULL);
		
		$category_group = $this->EE->TMPL->fetch_param('category_group', NULL);
		
		$cat_id = 0;
		
		if ($category_url_title !== NULL && !empty($category_url_title))
		{
			$this->EE->db->select('cat_id')
				->from('categories')
				->where('cat_url_title', $category_url_title);
								
			if ($category_group) 
			{
			  $this->EE->db->where('group_id', $category_group);
			}
			
      $category_query = $this->EE->db->limit(1)->get();
				
				if ($category_query->num_rows() == 1)
				{
					$cat = $category_query->row_array();
					$cat_id = $cat['cat_id'];
				}
		}
		
		$tagdata = $this->EE->TMPL->tagdata;
		
		if (empty($tagdata))
		{
			return $cat_id;
		}
		else
		{
			return $this->EE->TMPL->parse_variables_row($tagdata, array('category_id' => $cat_id));
		}
	}
	
	
	/**
	 * entry_id
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function entry_meta() 
	{
		$url_title = $this->EE->TMPL->fetch_param('url_title', NULL);
		
		$status = $this->EE->TMPL->fetch_param('status', 'open');
		
		if ($url_title) 
		{
			
			$entry_query = $this->EE->db->select('entry_id, title, channel_id, author_id, status')
				->from('channel_titles')
				->where('status', $status)
				->where('url_title', $url_title)
				->limit(1)
				->get();
				
			if ($entry_query->num_rows() == 1) 
			{	
				return $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $entry_query->row_array());
			}	
			else
			{
				return $this->EE->TMPL->no_results();
			}
		}
		else
		{
			return $this->EE->TMPL->no_results();
		}
		
	}
	
	/**
	 * get
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function get() 
	{
		$data = array();
		
		foreach (array_keys($_GET) as $key) 
		{
			$data['get:'.$key] = $this->EE->input->get($key, TRUE);
		}
		
		$this->return_data = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $data);

		//remove all the {get:****} vars
		$this->return_data = preg_replace('/{get:.*}/Usi', '', $this->return_data);
		
		return $this->return_data;
	}
	
	/**
	 * parts
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function parts() 
	{					
		$uri = (isset($_SERVER["PATH_INFO"])) ? $_SERVER["PATH_INFO"] : '';
		
		$protocal = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
		
		$data = array(
			'host' 		=> (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '',
			'port' 		=> (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : '',
			'uri'			=> $uri,
			'protocal' 	=> $protocal,
		);
	
		foreach (array_filter(explode('/', $uri)) as $count => $url_part) 
		{
			$data['uri_part_'.$count] = $url_part;
		}
		
		return $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $data);
	}
	
	/**
	 * url_decode
	 *
	 * @access public
	 * @param  void	
	 * @return void
	 * 
	 **/
	public function url_decode() 
	{
		return urldecode($this->EE->TMPL->fetch_param('string', ''));
	}
	
	
	/**
	 * usage
	 *
	 * @access public
	 * @param  void	
	 * @return string
	 * 
	 **/
	public static function usage() 
	{
		return <<< HTML
		{exp:sherlock:page_type}

			{if is_pagination}
				<p>There are a lot of these.</p>
			{/if}

			{if page_type == 'category'}
				<p>Lets filter these entries a little.</p>
			{/if}

		{/exp:sherlock:page_type}

		{exp:sherlock:category_id category_url_title="{segment_4}"}

			{exp:weblog:entries weblog="news" category="{category_id}"}
				<ul>
					<li><a href="{url_title_path="news"}">{title}</a></li>
				</ul>
			{/exp:weblog:entries}

		{/exp:sherlock:category_id}


		{exp:sherlock:get}

			<p>{get:name} - {get:gender}</p>

		{/exp:sherlock:get}	


		{exp:sherlock:parts}

			<p>{protocal}://{host}:{port}{uri}</p>

			{if uri_part_1 == 'home'}
				<p>Home at last!</p>
			{/if}

		{/exp:sherlock:parts}


		{exp:sherlock:url_decode string="Cheese%20is%20awesome!"}	
HTML;
		
	}
	
}
