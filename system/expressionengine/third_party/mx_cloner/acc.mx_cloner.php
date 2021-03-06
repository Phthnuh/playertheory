<?php
/**
 * MX Cloner Accessory
 *
 * @package		ExpressionEngine
 * @category	Accessory
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2010 Max Lazar (http://eec.ms)
 * @license   http://creativecommons.org/licenses/MIT/  MIT License
 * @version 1.1.3
 */

class mx_cloner_acc 
{
	var $name	 		= 'MX Cloner';
	var $id	 			= 'mx_cloner';
	var $version	 	= '1.1.3';
	var $description	= 'Adds the Ability to Clone Entries';
	var $sections	 	= array();

	/**
	* Set Sections
	*
	* Set content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function set_sections()
	{
		$EE =& get_instance();
		
		$out = '<script type="text/javascript" charset="utf-8">$("#accessoryTabs a.mx_cloner").parent().remove();';
		
		if  ($EE->input->get('C') == 'content_edit') {
		$out .='
	
		$("#entries_form table tbody tr").live("mouseenter", function() {
			_prev =  $(this).children("td:first").html();
			_prev_id = $(this).children("td:first");
			_href = $(this).children("td:first").next().children("a:first").attr("href")+"&clone=y&use_autosave=n";	
			_clone_link = " <a onclick=\'javascript:mx_cloner(\""+_href+"\");\'  class=\'clone_btn\'><img src=\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAMAAACecocUAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBNYWNpbnRvc2giIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RUI1NzRDNjUzQzc2MTFFMTk2NDFEQzIyNjdDMTBGOTAiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RUI1NzRDNjYzQzc2MTFFMTk2NDFEQzIyNjdDMTBGOTAiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpFQjU3NEM2MzNDNzYxMUUxOTY0MURDMjI2N0MxMEY5MCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpFQjU3NEM2NDNDNzYxMUUxOTY0MURDMjI2N0MxMEY5MCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pi/eewoAAAC3UExURQoKCg4ODhERERgYGCwsLCIiIhUVFSYmJubm5uXl5dLS0kBAQBwcHJCQkDY2Nh4eHh8fH2VlZQcHBykpKTs7O8DAwC0tLXZ2dlRUVI6OjkJCQtfX11VVVUZGRktLS+vr63R0dOHh4d3d3S4uLqGhoZ2dnVlZWScnJ4+PjwMDAzAwMIaGhjk5OU5OTt7e3r29vRsbGy8vL4mJieTk5BkZGa2trWRkZMvLyzU1NWZmZgsLC9HR0f///9ZQkzsAAAA9dFJOU////////////////////////////////////////////////////////////////////////////////wAJL6xfAAAAdUlEQVR42mJQ09LhkxG1AQEGOWFhFnE+fTBblpWVRU+aA8zm5uFRkNeQNOLltGEQYWPjVRRjYdGVMGawYGRU4VBnZze1FGQwFBJS5RTg55fiVmbQFjQzVzJhBgJWBpABXEwgYABmWzOAABtEXJORkdFKACDAAMyMDeP1g+KEAAAAAElFTkSuQmCC\' style=\'cursor:hand;cursor: pointer;padding:0px;margin:0px\' alt=\'Clone it\' title=\'Clone it\'></a>";
			$(this).children("td:first").html(_clone_link);

		});
	
		function mx_cloner(link){
			location.href= link;
			return true;
		}

		$("#entries_form table tbody tr").live("mouseleave", function() {
				$(_prev_id).html(_prev);
		});
		';
		};

		$EE->lang->loadfile('mx_cloner');


		if  ($EE->input->get('clone') == 'y') {
			$out .= '$("input[name=\'entry_id\']").val("");
						EE.publish.which="new";
						$(".contents .heading").html("<h2>" + "'.$EE->lang->line('clone_entry').'" + "</h2>");		
			if (typeof EE.publish.autosave != "undefined" ) {
				delete EE.publish.autosave;
			};
			
			$("input[name=\'nsm_better_meta__meta[0][id]\']").val("");	

			
			';
		};
		
		$out .= '
		</script>';
		$this->sections[]  = $out;
	}
	
}