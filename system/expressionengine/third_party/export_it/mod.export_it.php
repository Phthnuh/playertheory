<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Export It
 *
 * @package		mithra62:Export_it
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2012, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/export-it/
 * @version		1.0
 * @filesource 	./system/expressionengine/third_party/export_it/
 */
 
 /**
 * Export It - Module Class
 *
 * The external module class used for Actions (ACT) and template tags
 *
 * @package 	mithra62:Export_it
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/export_it/mod.export_it.php
 */
class Export_it {

	public $return_data	= '';
	
	public function __construct()
	{
		//ob_start();

		//ob_end_clean();
		$this->EE =& get_instance();
		$this->EE->load->model('export_it_settings_model', 'export_it_settings');
		$this->EE->load->library('export_it_lib');
		$this->settings = $this->EE->export_it_lib->get_settings();
		$this->EE->load->library('member_data');
		$this->EE->load->library('channel_data');
		$this->EE->load->library('mailinglist_data');
		$this->EE->load->library('comment_data');
		$this->EE->load->library('encrypt');
		$this->EE->load->library('json_ordering');
		$this->EE->load->library('Export_data/export_data');
		$this->EE->lang->loadfile('export_it');	
		
		if(isset($this->EE->TMPL))
		{
			$this->export_format = $this->EE->TMPL->fetch_param('format', 'xls');
			$this->export_fields = $this->EE->TMPL->fetch_param('export_fields', FALSE);
			if($this->export_fields)
			{
				$parts = explode(',', $this->export_fields);
				if(count($parts) >= '1')
				{
					$this->export_fields = array_map('trim', $parts);
				}
			}
		}
	}
	
	public function members()
	{
		$group_id = $this->EE->TMPL->fetch_param('group_id', FALSE);
		$include_custom_fields = $this->EE->TMPL->fetch_param('include_custom_fields', TRUE);
		$complete_select = $this->EE->TMPL->fetch_param('complete_select', FALSE);		
		$date_range = ($this->EE->TMPL->fetch_param('date_range', FALSE) && $this->EE->TMPL->fetch_param('date_range', FALSE) != '') ? $this->EE->TMPL->fetch_param('date_range', FALSE) : FALSE;
		$keywords = ($this->EE->TMPL->fetch_param('keywords') && $this->EE->TMPL->fetch_param('keywords') != '') ? $this->EE->TMPL->fetch_param('keywords') : FALSE;

		$where = array();
		if($group_id)
		{
			$where['members.group_id'] = $group_id;
		}
		
		if($keywords)
		{
			$where['search'] = $keywords;
		}
		
		if($date_range)
		{
			$where['date_range'] = $date_range;
		}
		
		$data = $this->clean_export_data($this->EE->member_data->get_members($where, $include_custom_fields, $complete_select, FALSE, 0, FALSE));
		$this->EE->export_data->export_members($data, $this->export_format);
		exit;		
	}
	
	public function channel_entries()
	{
		$keywords = $this->EE->TMPL->fetch_param('keywords', FALSE);
		$complete = $this->EE->TMPL->fetch_param('complete_select', FALSE);
		$channel_id = $this->EE->TMPL->fetch_param('channel_id', FALSE);
		$status = $this->EE->TMPL->fetch_param('status', FALSE);
		$date_range = $this->EE->TMPL->fetch_param('date_range', FALSE);
		$category = $this->EE->TMPL->fetch_param('category', FALSE);
		
		$where = array();
		if($channel_id)
		{
			$where['ct.channel_id'] = $channel_id;
		}
		else 
		{
			return 'channel_id is REQUIRED :(';
		}
		
		if($keywords)
		{
			$where['search'] = $keywords;
		}
		
		if($status)
		{
			$where['status'] = $status;
		}
		
		if($category)
		{
			$cat_where = array('cat_id' => $category);
			$entry_ids = $this->EE->channel_data->get_category_posts($cat_where);
			$ids = array();
			foreach($entry_ids AS $entry_id)
			{
				$ids[] = $entry_id['entry_id'];
			}
		
			$where['ct.entry_id'] = $ids;
		}
		
		if($date_range)
		{
			$where['date_range'] = $date_range;
		}
		
		if($complete && $complete != '')
		{
			$this->EE->channel_data->complete_select = TRUE;
		}
		
		$data = $this->clean_export_data($this->EE->channel_data->get_entries($where));
		$this->EE->export_data->export_channel_entries($data, $this->export_format);	
		exit;
	}
	
	public function mailinglist()
	{
		$exclude_duplicates = $this->EE->TMPL->fetch_param('exclude_duplicates');
		$keywords = $this->EE->TMPL->fetch_param('keywords', FALSE);
		$list_id = $this->EE->TMPL->fetch_param('list_id', FALSE);
		$where = array();
		if($list_id)
		{
			$where['mailing_list.list_id'] = $list_id;
		}
		
		if($keywords)
		{
			$where['search'] = $keywords;
		}
		
		$data = $this->clean_export_data($this->EE->mailinglist_data->get_list_emails($where));
		$this->EE->export_data->export_mailing_list($data, $this->export_format);
		exit;		
	}
	
	public function comments()
	{
		$date_range = $this->EE->TMPL->fetch_param('date_range' , FALSE);
		$status = $this->EE->TMPL->fetch_param('status', FALSE);
		$channel_id = $this->EE->TMPL->fetch_param('channel_id', FALSE);
		$keywords = $this->EE->TMPL->fetch_param('keywords', FALSE);
		$where = array();
		if($channel_id)
		{
			$where['comments.channel_id'] = $channel_id;
		}
			
		if($keywords)
		{
			$where['search'] = $keywords;
		}
		
		if($status)
		{
			$where['comments.status'] = $status;
		}
		
		if($date_range)
		{
			$where['date_range'] = $date_range;
		}
		
		$data = $this->EE->comment_data->get_comments($where);
		$comments = array();
		$channel_data = array();
		foreach($data AS $comment)
		{
			if(!isset($channel_data[$comment['channel_id']]))
			{
				$channel_data[$comment['channel_id']] = $this->EE->channel_model->get_channel_info($comment['channel_id'])->row();
			}
				
			$comments[$comment['entry_id']]['title'] = $comment['title'];
			$comments[$comment['entry_id']]['entry_title'] = $comment['title'];
			$comments[$comment['entry_id']]['entry_date'] = $comment['entry_date'];
			$comments[$comment['entry_id']]['comment_url'] = $channel_data[$comment['channel_id']]->comment_url;
			$comments[$comment['entry_id']]['channel_url'] = $channel_data[$comment['channel_id']]->channel_url;
			$comments[$comment['entry_id']]['url_title'] = $comment['url_title'];
			$comments[$comment['entry_id']]['entry_url_title'] = $comment['url_title'];
			$comments[$comment['entry_id']]['channel_id'] = $comment['channel_id'];
			$comments[$comment['entry_id']]['channel_title'] = $comment['channel_title'];
			$comments[$comment['entry_id']]['channel_name'] = $comment['channel_name'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['comment_id'] = $comment['comment_id'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['name'] = $comment['name'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['email'] = $comment['email'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['status'] = $comment['status'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['url'] = $comment['url'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['location'] = $comment['location'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['ip_address'] = $comment['ip_address'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['comment_date'] = $comment['comment_date'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['edit_date'] = $comment['edit_date'];
			$comments[$comment['entry_id']]['comments'][$comment['comment_id']]['comment'] = $comment['comment'];
		}
			
		$this->EE->export_data->export_comments($comments, $this->export_format);
		exit;		
	}
	
	public function matrix()
	{
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', FALSE);
		$field_id = $this->EE->TMPL->fetch_param('field_id', FALSE);
		if(!$field_id)
		{
			return 'Missing field_id';
		}
				
		$data = $this->EE->channel_data->clean_matrix_data($entry_id, $field_id);
		$return = array();
		foreach($data AS $key => $value)
		{
			$return[$key] = $data[$key]['matrix'][$key];
		}

		$data = $this->clean_export_data($return);
		$this->EE->export_data->export_channel_entries($data, $this->export_format, 'matrix');
		exit;	
	}
	
	public function channel_files_dl_log()
	{
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', FALSE);
		$file_id = $this->EE->TMPL->fetch_param('file_id', FALSE);

		$this->EE->db->select("cf.filename, cfdl.*, m.username, m.email, ct.title AS entry_title");
		$this->EE->db->from('channel_files cf');
		$this->EE->db->join('channel_files_download_log cfdl', 'cfdl.file_id = cf.file_id');
		$this->EE->db->join('members m', 'm.member_id = cfdl.member_id', 'left');
		$this->EE->db->join('channel_titles ct', 'ct.entry_id = cf.entry_id', 'left');

		if($file_id)
		{
			$this->EE->db->where('cf.file_id', $file_id);
		}
		
		if($entry_id)
		{
			$this->EE->db->where('cf.entry_id', $entry_id);
		}		
		
		//$this->EE->db->limit(50);
		$data = $this->EE->db->get();
		$arr = $data->result_array();
		if(count($arr) >= '1')
		{
			$i = 0;
			foreach($arr AS $key => $file)
			{
				$arr[$key]['date'] = m62_convert_timestamp($arr[$key]['date']);
				$arr[$key]['ip_address'] = long2ip($arr[$key]['ip_address']);
			}
		}		

		$data = $this->clean_export_data($arr);
		$this->EE->export_data->export_channel_entries($data, $this->export_format, 'channel_files_dl_log');
		exit;
	}

	public function channel_files()
	{
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', FALSE);
		$file_id = $this->EE->TMPL->fetch_param('file_id', FALSE);
	
		$this->EE->db->select("cf.*, m.username, m.email, ct.title AS entry_title");
		$this->EE->db->from('channel_files cf');
		$this->EE->db->join('members m', 'm.member_id = cf.member_id', 'left');
		$this->EE->db->join('channel_titles ct', 'ct.entry_id = cf.entry_id', 'left');
	
		if($file_id)
		{
			$this->EE->db->where('cf.file_id', $file_id);
		}
	
		if($entry_id)
		{
			$this->EE->db->where('cf.entry_id', $entry_id);
		}
	
		//$this->EE->db->limit(50);
		$data = $this->EE->db->get();
		$arr = $data->result_array();
		if(count($arr) >= '1')
		{
			$i = 0;
			foreach($arr AS $key => $file)
			{
				$arr[$key]['date'] = m62_convert_timestamp($arr[$key]['date']);
			}
		}
	
		$data = $this->clean_export_data($arr);
		$this->EE->export_data->export_channel_entries($data, $this->export_format, 'channel_files');
		exit;
	}	
	
	public function clean_export_data(array $data)
	{
		if(is_array($this->export_fields) && count($this->export_fields) >= '1')
		{
			$return = array();
			foreach($data AS $key => $value)
			{
				foreach($this->export_fields AS $index => $field)
				{
					if(isset($value[$field]))
					{
						$return[$key][$field] = $value[$field];
					}
					else
					{
						$return[$key][$field] = '';
					}
				}
				unset($data[$key]);
			}
			
			return $return;
		}
		
		return $data;
	}
	
	public function api()
	{
		$this->EE->load->library('Api_server/api_server');
		$this->EE->api_server->run();
		exit;
	} 	
}