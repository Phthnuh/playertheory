<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.1.13
 * @build		20120328
 */
 
class Google_maps {
	
	public $default_marker  = array();
	public $default_options = array();
	public $reserved_terms 	= array('', '_min', '_max', '_like');
	
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->config('gmap_config');
	}	
	
	public function center($map_id, $latitude, $longitude, $script = TRUE)
	{
		$latlng = str_replace(';', '', $this->LatLng($latitude, $longitude));
		$js 	= $map_id.'_map.setCenter('.$latlng.');';
		
		return $this->return_js($js, $script);
	}

	public function center_js($map_id, $lat, $lng) {
		$js = '
		var location = new google.maps.LatLng('.$lat.', '.$lng.')
		'.$map_id.'_map.setCenter(location)';

		return $js;
	}
	
	public function directions($origin, $destination, $params = array())
	{
		$this->EE->load->library('directions');
		$this->EE->load->model('gmap_log_model');
		
		$proxy_url = config_item('gmap_directions_proxy_url');

		if($proxy_url)
		{
			$this->EE->directions->base_url = $proxy_url;
		}
		
		$url 	= $this->EE->directions->construct_url(urlencode($origin), urlencode($destination), $params);
		
		$cache 	= $this->EE->gmap_log_model->check_response($url, 'directions', $this->EE->localize->now);
		
		if($cache->num_rows() == 0)
		{
			$directions = $this->EE->directions->query($origin, $destination, $params);
			
			$this->EE->gmap_log_model->cache_response($url, $directions, 'directions');		
		}
		else
		{
			$directions = json_decode($cache->row('response'));
		}
	
		return $directions;
	}
	
	public function infobox($params)
	{
		if(!isset($params['content']) || empty($params['content']))
		{
			return NULL;	
		}
		
		$default_options = array(
			'alignBottom'			 => 'true',
			'boxClass'				 => 'ui-infowindow',
			'boxStyle'				 => '',
			'clearanceX'			 => 1,
			'clearanceY'		 	 => 1,
			'closeBoxMargin'		 => '10px 2px 2px 2px',
			'closeBoxURL'		 	 => 'http://www.google.com/intl/en_us/mapfiles/close.gif',
			'inner_class'		 	 => 'ui-infobox-content',
			'content'				 => $params['content'],
			'disableAutoPan'		 => 'false',
			'enableEventPropagation' => 'false',
			'maxWidth'				 => '0',
			'offsetX'				 => 0,
			'offsetY'				 => 0,
			'isHidden'				 => 'false',
			'pane'					 => 'floatPane',
			'zIndex'				 => 'null',
			'show_one_window'		 => false,
			'open_windows'			 => false,
			'options' 		=> array(
				'location'	=> 'new google.maps.LatLng(0, 0)',
				'content'	=> ''
			),
			'script_tag' 	=> FALSE
		);
		
		$options = array_merge($default_options, $params['options']);
		
		$js = '
			(function () {
				
				var options = {
					alignBottom: '.$options['alignBottom'].',
					boxClass: "'.$options['boxClass'].'",
					boxStyle: {'.$options['boxStyle'].'},
	                closeBoxMargin: "'.$options['closeBoxMargin'].'",
	                closeBoxURL: "'.$options['closeBoxURL'].'",
	                content:  \'{exp:gmap:clean_js}<div class="'.$options['inner_class'].'">'.$this->clean_js($params['content']).'</div>{/exp:gmap:clean_js}\',
	                disableAutoPan: false,
	                enableEventPropagation: '.$options['enableEventPropagation'].',
	                infoBoxClearance: new google.maps.Size('.$options['clearanceX'].', '.$options['clearanceY'].'),
	                isHidden: '.$options['isHidden'].',
	                maxWidth: 0,
	                pane: "'.$options['pane'].'",
	                pixelOffset: new google.maps.Size('.$options['offsetX'].', '.$options['offsetY'].'),
	                zIndex: null
		        };

				var infowindow 	= new InfoBox(options);
					
				var obj	= '.$params['var'].';		
				';
			
				if(isset($params['open_windows']) && $params['open_windows'])
				{
					$js .= 'try { //Try statement is only temporary
					infowindow.open('.$params['id'].'_map, obj); } catch(e) {}';
				}
		
			$js .= '			
				
				'.$params['id'].'_windows.push(infowindow);
								
				google.maps.event.addListener(obj, \'click\', function(e) {
					obj.position = e.latLng;							
					obj.getPosition = function() {
						return e.latLng;
					}';

					if(isset($params['show_one_window']) && $params['show_one_window'])
					{
						$js .= '					
						for(var i = 0; i < '.$params['id'].'_windows.length; i++) {
							'.$params['id'].'_windows[i].close();
						}';					
					}
					
			$js.='
					infowindow.setPosition(e.latLng);
					infowindow.open('.$params['id'].'_map, obj);
					
				});
				
				'.$params['id'].'_window = infowindow;
				
			})();
		';
		
		return $js;
	}
	
	public function infowindow($params)
	{
		$default_params = array(
			'options'		=> array(),
			'script_tag'	=> FALSE
		);
		
		$params = array_merge($default_params, $params);
		
		if(!isset($params['content']) || empty($params['content']))
		{
			return NULL;	
		}
				
		if(!isset($params['inner_class']) || empty($params['inner_class']))
		{
			$params['inner_class'] = 'ui-infowindow-content';
		}
		
		if(!isset($options['content']))
		{
			$options['content'] = '\'{exp:gmap:clean_js}<div class="'.$params['inner_class'].'">'.$this->clean_js($params['content']).'</div>{/exp:gmap:clean_js}\'';
		}
		
		$obj = $this->convert_to_js($options);
		
		$js = '
			(function () {
				var infowindow 	= new google.maps.InfoWindow('.$obj.');	
				var obj			= '.$params['var'].';
				';
			
				if(isset($params['open_windows']) && $params['open_windows'])
				{
					$js .= 'infowindow.open('.$params['id'].'_map, obj);';
				}
		
			$js .= '			
				'.$params['id'].'_windows.push(infowindow);';
				
			$js .= '							
				google.maps.event.addListener(obj, \'click\', function(e) {
					obj.position = e.latLng;';

					if(isset($params['show_one_window']) && $params['show_one_window'])
					{
						$js .= '					
						for(var i = 0; i < '.$params['id'].'_windows.length; i++) {
							'.$params['id'].'_windows[i].close();
						}';					
					}
					
			$js.='
					infowindow.open('.$params['id'].'_map, obj);
					
				});
				
				'.$params['id'].'_window = infowindow;
				
			})();
		';
		
		return $js;
	}
	
	public function init($map_id, $options = FALSE, $args)
	{
		$obj = $this->convert_to_js($options);

		$class = isset($args['plugin']['class']) ? $args['plugin']['class'] : '';
		$style = isset($args['plugin']['style']) ? $args['plugin']['style'] : '';
		
		$js = '		
		<div id="'.$map_id.'" class="'.$class.'" style="'.$style.'"></div>
		
		<script type="text/javascript">

			var '.$map_id.'_options 			= '.$obj.';
			var '.$map_id.'_canvas 				= document.getElementById("'.$map_id.'");
			var '.$map_id.'_map					= new google.maps.Map('.$map_id.'_canvas, '.$map_id.'_options);
			var '.$map_id.'_bounds				= new google.maps.LatLngBounds();
			var '.$map_id.'_markers 			= [];
			var '.$map_id.'_window 				= {};
			var '.$map_id.'_windows 			= [];
			var '.$map_id.'_responses 			= [];
			var '.$map_id.'_html				= [];
			var '.$map_id.'_waypoints 			= [];
			var '.$map_id.'_regions 			= [];
			var '.$map_id.'_geocoder 			= new google.maps.Geocoder();
			var '.$map_id.'_directionsService 	= new google.maps.DirectionsService();
			var '.$map_id.'_directionsDisplay	= new google.maps.DirectionsRenderer({map: '.$map_id.'_map});
			
		</script>
		';
			
		return $js;
	}
	
	public function latlng($latitude, $longitude, $script = FALSE)
	{	
		$js = $this->return_js('new google.maps.LatLng('.$latitude.','.$longitude.');', $script);
	
		return $js;
	}
	
	public function marker($params)
	{
		$default_params = array(
			'options'		=> array(),
			'data'			=> array(),
			'extend_bounds'	=> FALSE,
			'script_tag'	=> TRUE
		);
		
		$params = array_merge($default_params, $params);
		
		$show_one_window = isset($params['infowindow']['show_one_window']) ? $params['infowindow']['show_one_window'] : FALSE;
		$open_windows = isset($params['infowindow']['open_windows']) ? $params['infowindow']['open_windows'] : FALSE;
		
		unset($params['options']['show_one_window']);
		unset($params['options']['open_windows']);
		
		$string_params = array('title', 'icon', 'infowindow');
		
		foreach($string_params as $param)
		{
			if(isset($params['options'][$param])) {
	
				$params['options'][$param] = '"'.str_replace("\"", "\\\"", $params['options'][$param]).'"';
			}			
		}
		
		$js = NULL;
		
		$limit = isset($param['limit']) && $params['limit'] !== FALSE ? (int) $params['limit'] : FALSE;
		
		$offset = isset($param['offset']) ? (int) $params['offset'] : FALSE;

		$data_count = 0;

		foreach($params['data'] as $response)
		{
			foreach($response->results as $data_index => $result)
			{
				// Limit the results
				if($limit === FALSE || $data_count < $limit)
				{
					// Offset the results
					if($data_index >= $offset || $offset === FALSE)
					{
						// Verify that the results are an object
						if(is_object($result))
						{
							$data_count++;

							$options 	= $params['options'];
							$latitude 	= $result->geometry->location->lat;
							$longitude 	= $result->geometry->location->lng;
							$options['map']		 = $params['id'].'_map';
							$options['position'] = rtrim($this->LatLng($latitude, $longitude), ';');

							$icon = isset($options['icon']) ? $options['icon'] : '""';

							if(empty($icon) || $icon == '""')
							{
								if(isset($result->icon))
								{
									$icon = '"'.$result->icon.'"';
								}					
							}

							$options['icon'] = $icon;

							$js .= 'var index = '.$params['id'].'_markers.length;';

							if(isset($params['options']['infowindow'])) {
								$infowindow = $params['options']['infowindow'];
								unset($params['options']['infowindow']);
							}

							$js .= $params['id'].'_markers[index] = new google.maps.Marker('.$this->convert_to_js($options).');';
							
							if($params['extend_bounds'])
							{
								if(isset($params['exclude_single_marker']) && $param['exclude_single_marker'])
								{
									$js .= 
									$params['id'].'_bounds.extend('.$options['position'].');' . 
									$params['id'].'_map.fitBounds('.$params['id'].'_bounds);';
								}
								else
								{	
									$js .=
									$params['id'].'_bounds.extend('.$options['position'].');
									if (index > 0) {' .
										// multiple markers, fit bounds
										$params['id'].'_map.fitBounds('.$params['id'].'_bounds);
									} else {' .
										// single marker, center around marker and set zoom
										$params['id'].'_map.setCenter('.$params['id'].'_bounds.getCenter());' .
										$params['id'].'_map.setZoom('.$params['id'].'_options.zoom);
									}';
								}
							}
							
							if(isset($params['infowindow']) || isset($infowindow) || isset($result->content))
							{
								
								$geocoded_response = $this->parse_geocoder_response(array((object) array('results' => array($result))));

								$geocoded_response = $this->EE->channel_data->utility->add_prefix('marker', $geocoded_response);

								$content = isset($params['infowindow']['content']) ? $params['infowindow']['content'] : NULL;
								$content = $content == NULL && isset($result->content) ? $this->EE->google_maps->clean_js($result->content) : $content;
								
								$content = $this->parse($geocoded_response, $content);
								
								if(isset($params['infobox']) && $params['infobox'])
								{							
									$js .= $this->EE->google_maps->infobox(array(
										'id'			=> $params['id'],
										'content'		=> $content,
										'options'		=> $params['infowindow']['options'],
										'script_tag'	=> FALSE,
										'var'			=> $params['id'].'_markers[index]',
										'show_one_window' 	=> $show_one_window,
										'open_windows'		=> $open_windows
									));
								}
								else
								{
									$js .= $this->EE->google_maps->infowindow(array(
										'id'				=> $params['id'],
										'content'			=> $content, 
										'options'			=> array(),
										'script_tag'		=> FALSE,
										'var'				=> $params['id'].'_markers[index]',
										'show_one_window' 	=> $show_one_window,
										'open_windows'		=> $open_windows
									));
								}
							}
						}
					}
				}
			}
		}

		$js = $this->return_js($js, $params['script_tag']);
		
		return $js;
	}
	
	public function geocode($query, $limit = FALSE, $offset = 0)
	{
		$this->EE->load->library('geocoder');

		$proxy_url = config_item('gmap_geocoder_proxy_url');

		if($proxy_url)
		{
			$this->EE->geocoder->base_url = $proxy_url;
		}
		
		$this->EE->load->model('gmap_log_model');
		
		$query 		= explode('|', $query);
		$response 	= array();
		
		if($this->EE->config->item('gmap_force_http'))
		{
			$this->EE->geocoder->secure = FALSE;
		}

		$this->EE->load->helper('url');
		
		foreach($query as $query)
		{
			$url = $this->EE->geocoder->query($query, $limit, $offset, FALSE, TRUE);

			$cache 	= $this->EE->gmap_log_model->check_response($url, 'geocode', $this->EE->localize->now);
			
			if($cache->num_rows() == 0)
			{			
				$data = $this->EE->geocoder->query($query, $limit, $offset);
				
				$this->EE->gmap_log_model->cache_response($url, $data, 'geocode');
				$response[] = $data;
			}
			else
			{
				$response[] = json_decode($cache->row('response'));
			}
		}
		
		return $response;
	}

	public function geocode_js($map_id, $query, $callback = '')
	{
		$this->EE->load->library('geocoder');

		return $this->EE->geocoder->javascript($map_id, $query, $callback);
	}
	
	public function convert_metric($metric = 'miles')
	{
		$metrics = array(
			'miles' 	 => 1,
			'feet'  	 => 5280,
			'kilometres' => 1.609344,
			'kilometers' => 1.609344,
			'metres'	 => 1609.344,
			'meters'	 => 1609.344
		);	
		
		$metric = strtolower($metric);
		$return = isset($metrics[$metric]) ? $metrics[$metric] : $metrics['miles'];
		
		return isset($metrics[$metric]) ? $metrics[$metric] : $metrics['miles'];
	}
	
	public function parse_geocoder_response($results, $limit = FALSE, $offset = 0, $prefix = '')
	{
		$vars 	= array();
		$count 	= 0;

		foreach($results as $row)
		{
			foreach($row->results as $index => $result)
			{
				//echo $limit .' === FALSE || ' . $index . ' <= ' . $limit . ' && ' . $index . ' >= ' . $offset .'<br>';

				if($limit === FALSE || $count < $limit && $index >= $offset)
				{
					$vars[$count][$prefix.'title'] 				= isset($result->title) ? $result->title : NULL;
					$vars[$count][$prefix.'content']			= isset($result->title) ? $result->content : NULL;
					$vars[$count][$prefix.'address_components'] = isset($result->address_components) ? $this->object_to_array($result->address_components) : array();
					$vars[$count][$prefix.'formatted_address']	= isset($result->formatted_address) ? $result->formatted_address : NULL;
					$vars[$count][$prefix.'latitude']			= $result->geometry->location->lat;
					$vars[$count][$prefix.'longitude']			= $result->geometry->location->lng;
					$vars[$count][$prefix.'location_type']		= isset($result->geometry->location_type) ? $result->geometry->location_type : NULL;
					$vars[$count][$prefix.'types'] 				= isset($result->types) ? implode('|', $result->types) : NULL;
					
					foreach($vars[$count][$prefix.'address_components'] as $component_index => $component_val)
					{
						$vars[$count][$prefix.'address_components'][$component_index]['long_name'] = $component_val['long_name'];
						$vars[$count][$prefix.'address_components'][$component_index]['short_name'] = $component_val['short_name'];
						$vars[$count][$prefix.'address_components'][$component_index]['types'] = implode('|', $component_val['types']);
					}

					$count++;
				}
			}
		
		}
		
		return $vars;
	}

	public function parse_regions($results, $prefix = FALSE)
	{
		$vars  = array();
		$count = 0;

		$regions = array();

		foreach($results->results as $index => $region)
		{
			$regions[$index]['title']		   = $region->title;
			$regions[$index]['content']		   = $region->content;
			$regions[$index]['total_points']   = count($region->coords);
			$regions[$index]['total_coords']   = count($region->coords);
			
			$regions[$index]['strokeColor']    = $region->style->strokeColor;
			$regions[$index]['strokeOpacity']  = $region->style->strokeOpacity;
			$regions[$index]['strokeWeight']   = $region->style->strokeWeight;
			$regions[$index]['fillColor']      = $region->style->fillColor;
			$regions[$index]['fillOpacity']    = $region->style->fillOpacity;
			
			$regions[$index]['stroke_color']   = $region->style->strokeColor;
			$regions[$index]['stroke_opacity'] = $region->style->strokeOpacity;
			$regions[$index]['stroke_weight']  = $region->style->strokeWeight;
			$regions[$index]['fill_color']     = $region->style->fillColor;
			$regions[$index]['fill_opacity']   = $region->style->fillOpacity;

			$coords = array();

			foreach($region->coords as $coord_index => $coord)
			{
				$coords[] = $coord->lat.','.$coord->lng;

				$regions[$index]['coords'][$coord_index] = array(
					'lat' => $coord->lat,
					'lng' => $coord->lng
				);
			}

			$regions[$index]['coord_string'] = implode('|', $coords);

			if($prefix)
			{
				$regions = $this->EE->channel_data->utility->add_prefix($prefix, $regions);
			}
		}

		return $regions;
	}
	
	public function route($params = array()) 
	{
		$default_params = array(
			'options'		=> array(),
			'data'			=> array(),
			'extend_bounds'	=> FALSE,
			'script_tag'	=> TRUE
		);
		
		$params = array_merge($default_params, $params);
		
		$points = array();
		
		foreach($params['data'] as $response)
		{
	
			foreach($response->results as $result)
			{			
				$lat 	= $result->geometry->location->lat;
				$lng 	= $result->geometry->location->lng;
				
				$points[]	=  '"'.$lat . ',' . $lng.'"';
			}
		}
		
		$last_index		= count($points) - 1;
		
		$request = array(
			'origin' 		=> $points[0],
			'destination'	=> $points[$last_index],
			'travelMode'	=> 'google.maps.TravelMode.DRIVING'
		);
		
		unset($points[0]);
		unset($points[$last_index]);	
		
		$waypoints = array();
		
		if(count($points) > 0)
		{
			foreach($points as $point)
			{
				$point =  explode(',', str_replace('"', '', $point));
				$lat = $point[0];
				$lng = $point[1];
				
				$waypoints[] = '{location: '.str_replace(';', '', $this->latlng($lat, $lng)).', stopover: true}';
			}
			
			$request['waypoints'] = '['. implode(',', $waypoints).']';
		}
		
		$request = $this->convert_to_js(array_merge($request, $params['options']));
		
		$preserveViewport = !$params['extend_bounds'] ? $params['id'].'_directionsDisplay.setOptions({preserveViewport: true})' : '';
		
		$js = '
			var request = '.$request.';
			
			'.$preserveViewport.'
			
			'.$params['id'].'_directionsService.route(request, function(response, status) {
				if(status == google.maps.DirectionsStatus.OK) {
					'.$params['id'].'_directionsDisplay.setDirections(response);
				}
			});
		';
		
		return $js;
	}
	
	public function region($params = array())
	{
		$default_params = array(
			'options'			=> array(),
			'data'				=> array(),
			'extend_bounds'		=> FALSE,
			'script_tag'		=> TRUE
		);
		
		$params = array_merge($default_params, $params);
	
		$js = NULL;
		
		foreach($params['data'] as $response)
		{
			foreach($response->results as $result)
			{
				$js .= '
				var paths = '.json_encode($result->coords).';
				
				var region = {
					paths: [],
					strokeColor: "'.$result->style->strokeColor.'",
					strokeOpacity: '.$result->style->strokeOpacity.',
					strokeWeight: '.$result->style->strokeWeight.',
					fillColor: "'.$result->style->fillColor.'",
					fillOpacity: '.$result->style->fillOpacity.'						
				}
				
				for(var x = 0; x < paths.length; x++) {
					var path = new google.maps.LatLng(paths[x].lat, paths[x].lng);
					
					'.$params['id'].'_bounds.extend(path);
					
					region.paths.push(path);
				}';
				
				if($params['extend_bounds'])
				{
					$js  .= '
					'.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);';
				}
				
				$js .= '
				var index = '.$params['id'].'_regions.length;
				
				'.$params['id'].'_regions[index] = new google.maps.Polygon(region);					
				'.$params['id'].'_regions[index].setMap('.$params['id'].'_map);					
			
				//'.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);
				';
				
				if($params['infowindow'])
				{
					if(isset($result->content) && !empty($result->content) || isset($params['infowindow']))
					{
						$content = isset($result->content) && !empty($result->content) ? $result->content : null;
						$content = isset($params['infowindow']['content']) && $params['infowindow']['content'] ? $params['infowindow']['content'] : $content;
						
						if(isset($params['infobox']) && $params['infobox'])
						{
							
							$js .= $this->EE->google_maps->infobox(array(
								'id'				=> $params['id'],
								'content'			=> $content,
								'options'			=> $params['infowindow']['options'],
								'script_tag'		=> FALSE,
								'var'				=> $params['id'].'_regions[index]',
								'open_windows'		=> TRUE
							));
						}
						else
						{						
							$js .= $this->EE->google_maps->infowindow(array(
								'id'			=> $params['id'],
								'content'		=> $content,
								'options'		=> $params['options'],
								'script_tag'	=> FALSE,
								'var'			=> $params['id'].'_regions[index]'
							));
						}
					}
				}
			}
		}
		
		$js = $this->return_js($js, $params['script_tag']);
		
		return $js;
	}
	
	public function zoom($map_id, $zoom, $script = TRUE)
	{
		$js = $map_id.'_map.setZoom('.$zoom.');';
		$js = $this->return_js($js, $script);
		
		return $js;
	}
		
	public function return_js($js, $include_script_tag = TRUE)
	{
		$return = $js;
		
		if($include_script_tag)
			$return = '<script type="text/javascript">'.$js.'</script>';
		else
			$return = $js;
		
		return $return;
	}
	
	public function is_checked_or_selected($post, $item)
	{
		if(is_array($post))
		{
			foreach($post as $post_index => $post_value)
			{											
				if($item == $post_value)
				{
					return TRUE;
				}
			}									
		}
		else
		{
			if($item == $post)
			{	
				return TRUE;								
			}
		}
		
		return FALSE;
	}
	
	
	public function prep_sql_fieldname($field_array, $user_value = FALSE, $to_append = TRUE)
	{	
		$reserved_fields = array('title', 'status', 'expiration_date', 'entry_date', 'author_id');
		$return = FALSE;
		$string = array();
		
		//Converts a single field to an array
		$field_array = is_array($field_array) ? $field_array : array($field_array => '');
		
		//Loops through the field array
		foreach($field_array as $field_name => $field_value)
		{	
			$value = FALSE;
			
			//Fallsback to the post variable if no value is passed
			$value = !empty($field_value) ? $field_value : $user_value;			
			$value = $value ? $value : $this->EE->input->post($field_name);
												
			//Creates the SQL field name by removed the reserved terms
			$sql_field_name = str_replace($this->reserved_terms, '', $field_name);

			//Gets the field data and if the field exists, the sql statement is created
			$field_data = $this->EE->channel_data->get_field_by_name($sql_field_name);
			$in_array   = in_array($sql_field_name, $reserved_fields);

			if($field_data->num_rows() > 0 || $in_array === TRUE)
			{	
				//Validates that a value is not FALSE
				if($value !== FALSE && !empty($value) || $to_append == FALSE)
				{
					//If to_append is TRUE, then the operator is appended
					if($to_append == TRUE)
					{			
						//Converts a value string to a variable
						$values = is_array($value) ? $value : array($value);
						
						//Loops through the values array and creates the SQL conditions
						foreach($values as $value)
						{
							$operator = $this->prep_value($field_name, $value);
		
							if($in_array)
							{
								$string[] = '`'.$sql_field_name.'` '.$operator;
							}
							else
							{
								$string[] = '`field_id_'.$field_data->row('field_id').'` '.$operator;
							}
						}
					}
					else
					{	
						if($in_array)
						{
							$string[] = '`'.$field_name.'` '.$operator;
						}
						else
						{			
							$string[] = '`field_id_'.$field_data->row('field_id').'`';
						}
					}
				}
			}			
		}
		
		return $string;
	}
	
	public function create_id_string($results)
	{		
		$id = NULL;
		
		foreach($results as $row)
			$id .= $row->entry_id . '|';
		
		return rtrim($id, '|');
	}
	
	public function prep_value($field_name, $value)
	{
		if(is_string($value))
		{
			$value = '\''.$value.'\'';
		}
		
		//Preps conditional statement by testing the field_name for keywords
		if(strpos($field_name, '_min'))
			$operator = ' >= '.$value.'';
		else if(strpos($field_name, '_max'))
			$operator = ' <= '.$value.'';
		else if(strpos($field_name, '_like'))
			$operator = ' LIKE \'%'.str_replace('\'', '', $value).'%\'';
		else
			$operator = ' = '.$value.' ';
	
		return $operator;
	}
		
	/**
     *
     * Convert an object to an array
     *
     * @param   object  The object to convert
     * @return	array
     *
     */
    public function object_to_array($object)
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map( array($this, 'object_to_array'), $object );
    }
    
	private function convert_to_js($options = array())
	{
		$obj = NULL;
		
		if(is_array($options))
		{
			foreach($options as $option_index => $option_value)
				$obj .= $option_index . ': '.$option_value.', ';
			
			$obj = '{' . rtrim(trim($obj), ',') . '}';
		}
		else
		{
			$obj = $options;
		}
		
		return $obj;
	}
	
	public function clean_js($str, $escape = TRUE)
	{
		//$this->EE->load->library('template');
		$matches = array();
		
		$str = trim($str);
		$str = preg_replace("/[\n\r\t]/", '', $str);

		if($escape)
		{
			$str = preg_replace('/\'/','\\\'', $str);
			$str = str_replace('\\\\', '\\', $str);
		}

		return $str;
	}
	
	function current_url($uri_segments = TRUE, $get_string = TRUE)
	{
		$return = $this->EE->config->site_url();
		
		if($uri_segments)
		{
			$return .= $this->EE->uri->uri_string();
		}
		
		if($get_string && count($_GET) > 0)
		{
			$get =  array();
		
			foreach($_GET as $key => $val)
			{
				$get[] = $key.'='.$val;
			}
			
			$return .= '?'.implode('&',$get);
		}
		
		return $return;
	}  

	private function parse($vars, $tagdata = FALSE)
	{
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
			
		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}
}