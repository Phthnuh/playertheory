<ul class="tab_menu" id="tab_menu_tabs">
	<li class="content_tab <?=($method == '') ? ' current': ''?>"><a href="<?=$base_url?>"><?=lang('tagger:tags')?></a></li>
	<li class="content_tab <?=(($method == 'groups') OR ($method == 'add_group')) ? ' current': ''?>"><a href="<?=$base_url?>&method=groups"><?=lang('tagger:groups')?></a></li>
	<li class="content_tab"> <a rel="external" href="<?=$this->cp->masked_url('http://www.devdemon.com/docs/')?>"><?=lang('tagger:docs')?></a>&nbsp;</li>
</ul>
<br /><br />