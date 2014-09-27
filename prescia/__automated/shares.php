<?	# -------------------------------- Implemente SHARE THIS PAGE standard fields
	# USAGE:
	# <SHARES>
	#	<TAG>where to put the shares, the name of the template tag. MANDATORY</TAG>
	#	<ORDER>GFTA</ORDER> accepts G (google plus), F (Facebook like), T (twitter) and A (Add This), default is GFT
	#	<ALIGN>right|left|none</ALIGN> either to float each component left or right, default is right. None will stack them
	#	<WIDTH>80px</WIDTH> width of each item, default is 80px (AddThis size might be limited by image)
	#	<HEIGHT></HEIGHT> height of each item, default is not set
	#	<ADDTHISID>?</ADDTHISID> which ADDThis ID to use, default is the public id "xa-50818b704da8e1bb"
	#	<ADDTHISIMG></ADDTHISIMG> full path (accepts template) to image to use on the button instead of the addthis default one
	#	<ADDTHISTXT></ADDTHISTXT> Text to add inside the "a" tag, default is nothing
	#	<TWEETTEXT></TWEETTEXT> what is to be added to the tweet if you click it (other than the URL), default is nothing
	#	<TWEETCOUNT>none|horizontal|vertical</TWEETCOUNT> twitter API count value (default horizontal)
	#	<FBLAYOUT>box_count|button_count</FBLAYOUT> Facebook API layout value (default box_count)
	#	<PADDING>#</PADDING> padding on each div, default is 0
	# </SHARES>



class auto_shares extends CautomatedModule  {

	var $addThisCode = "<a tabindex=\"-1\" class=\"addthis_button\" href=\"http://www.addthis.com/bookmark.php?v=300&amp;pubid={ID}\">{IMG}</a>
<script type=\"text/javascript\" src=\"http://s7.addthis.com/js/300/addthis_widget.js#pubid={ID}\"></script>";
	var $addThisImg = "<img src=\"http://s7.addthis.com/static/btn/v2/lg-share-en.gif\" width=\"125\" height=\"16\" alt=\"Bookmark and Share\" style=\"border:0\"/>";
	var $facebookCode = "<div id=\"fb-root\"></div>
<script type=\"text/javascript\">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = \"//connect.facebook.net/en_GB/all.js#xfbml=1\";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>";

	function loadSettings() {
		$this->name = "shares";
		$this->nested_folders = true; // add everywhere
		$this->nested_files = true; // add everywhere
		$this->virtual_folders = true; // add everywhere
		$this->accepts_multiple = false;
		$this->sorting_weight = 1;
	}


	function onShow($definitions) {
		$definitions = $definitions[CONS_XMLPS_DEF];
		if (!isset($definitions['tag'])) $this->parent->errorControl->raise(407,'sharesautomato',"","TAG not specified");
		$tag = $definitions['tag'];
		$order = isset($definitions['order'])?$definitions['order']:"GFT";
		$align = isset($definitions['align'])?$definitions['align']:"right";
		$width = isset($definitions['width'])?$definitions['width']:"80px";
		$height = isset($definitions['height'])?$definitions['height']:"";
		$widthnum = str_replace("px","",$width);
		$addthisid = isset($definitions['addthisid'])?$definitions['addthisid']:"xa-50818b704da8e1bb";
		$addthisimg = isset($definitions['addthisimg'])?$definitions['addthisimg']:"";
		$addthistxt = isset($definitions['addthistxt'])?$definitions['addthistxt']:"";
		$tweet = isset($definitions['tweettext'])?$definitions['tweettext']:"";
		$tweetc = isset($definitions['tweetcount'])?$definitions['tweetcount']:"horizontal";
		$fblayout = isset($definitions['fblayout'])?$definitions['fblayout']:"button_count";
		$padding = isset($definitions['padding'])?$definitions['padding']:"0";
		$canonical = "http://".$this->parent->domain.$this->parent->context_str.$this->parent->action.".html";
		if (isset($_REQUEST['id'])) $canonical .= "?id=".$_REQUEST['id'];

		$aligndiv = $align == 'none' ? '' : ";float: $align";
		$heigthdiv = $height == '' ? '' : ";height: $height;line-height: $height";

		$output = "";
		$sizeorder = strlen($order);
		for ($c=0;$c<$sizeorder;$c++) {
			switch (strtolower($order[$c])) {
				case "g":
					$output = "<script type=\"text/javascript\" src=\"https://apis.google.com/js/plusone.js\">\n".
							  "{lang: '".$_SESSION[CONS_SESSION_LANG]."'}\n".
							  "</script>\n".$output;
					$output .= "<div style=\"padding:".$padding."px;width:".$width.$heigthdiv.$aligndiv."; margin-top:0px;\"><g:plusone size=\"medium\"></g:plusone></div>\n";
					break;
				case "t":
					$output .= "<div style=\"padding:".$padding."px;width:".$width.$heigthdiv.$aligndiv."\"><a tabindex=\"-1\" href=\"http://twitter.com/share\" class=\"twitter-share-button\" data-url=\"".$canonical."\" data-count=\"".$tweetc."\" data-text=\"".$tweet."\">Tweet</a><script type=\"text/javascript\" src=\"http://platform.twitter.com/widgets.js\"></script></div>\n";
					break;
				case "f":
					$output = $this->facebookCode."\n".$output;
					$output .= "<div style=\"padding:".$padding."px;width:".$width.$heigthdiv.$aligndiv."\" class=\"fb-like\" data-href=\"".$canonical."\" data-send=\"false\" data-layout=\"".$fblayout."\" data-width=\"".$widthnum."\" data-show-faces=\"false\"></div>\n";
					break;
				case "a":
					if ($addthisimg=='') $addthisimg = $this->addThisImg;
					else {
						$nTP = new CKTemplate($this->parent->template);
						$nTP->tbreak("<img src=\"".$addthisimg."\" alt=\"Share\"/>");
						$addthisimg = $nTP->techo();
					}
					$output .= "<div style=\"padding:".$padding."px;width:".$width.$heigthdiv.$aligndiv."\">".str_replace("{IMG}",$addthisimg.$addthistxt,str_replace("{ID}",$addthisid,$this->addThisCode))."</div>\n";
					break;
			}
		}
		$this->parent->template->assign($tag,$output);
	}

}