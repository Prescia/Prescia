<?	# -------------------------------- SIMPLE POP-in automato
	# USAGE:
	# <POPIN>divname,page[,width[,height]]]</POPIN>
	# You need to leave a {popin} tag in the frame/page so it knows where to insert the divs
	#	width is optional, default is 50% of screen.
	#	height is optional, default is 75% of screen.
	#
	# The divname will be used in the function name (startPopin('[divname]')) you should add when you want the popin to start
	# The divname will also be used in the div that contains the page (div id="popin[divname]")
	# You can add multiple popins on the same page
	# Note that this is meant to be simple popin, and thus they are *loaded* with the page to make it faster. Avoid big popin htmls
	#
	# To open/close the popin: startPopin('[divname]',[true|false]) where the second parameters is to show or hide the popin

class auto_popin extends CautomatedModule  {

	private $popins = array();
	private $jscodeadded = false;
	private $popincode = "
	var affpopin_lock = false;
	function startPopin(pane,showme) {
		if (showme) {
			if (affpopin_lock) return false;
			wD = windowDimensions();
			if (!$('popin_fader')) return;
			$('popin'+pane).style.display = ''; // show so it HAS width/height
			el = componentDimensions('popin'+pane);
			$('popin' + pane).style.left = Math.floor(wD[0]/2 - el[0]/2) + 'px';
			$('popin' + pane).style.top = Math.floor(wD[1]/2 - el[1]/2) + 'px';
			$('popin'+pane).style.display = 'none'; // hide back
			affpopin_lock = true;
			if (prototypeAvail) {
				try {
					$('popin_fader').appear({
							duration: 0.5, from: 0, to: 0.6
						});
					$('popin' + pane).appear({
						duration: 0.5, from: 0, to: 0.95
					});
				} catch(ee) {
					$('popin_fader').style.display ='';
					$('popin'+pane).style.display = '';
					try {
						Element.setOpacity('popin_fader',0.6);
						Element.setOpacity('popin'+pane,0.95);
					} catch(ee) {
					}
				}
			} else {
				$('popin'+pane).style.display = '';
			}
		} else {
			if (!affpopin_lock) return false;
			affpopin_lock = false;
			if (prototypeAvail) {
				$('popin_fader').fade({
						duration: 0.5, from: 0.5, to: 0
					});
				$('popin' + pane).fade({
					duration: 0.5, from: 0.9, to: 0
				});
			} else {
				$('popin'+pane).style.display = 'none';
			}
		}
		return false;
	}
	";

	function loadSettings() {
		$this->name = "popin";
		$this->sorting_weight = 1;
		$this->accepts_multiple = true;
		$this->jscodeadded = false;
	}


	function onCheckActions($multiDef) {
		if ($this->parent->layout > 1) return; // no popin in ajax
		foreach ($multiDef as $definitions) {
			$definitions = explode(",",$definitions[CONS_XMLPS_DEF]);
			$width = "50%";
			$height = "50%";
			if (count($definitions)>2) {
				$width = $definitions[2];
				if (count($definitions)>3) {
					$height = $definitions[3];
				}
			}
			$definitions[1] = explode(".",$definitions[1]);
			$definitions[1] = array_shift($definitions[1]); // remove extension
			$this->popins[$definitions[0]] = array($definitions[1],$width,$height);
		}
	}

	function onRender($multiDef) {

		if ($this->parent->layout >1) return; // no popin in ajax
		if ($this->parent->template->get("bi_popin") === false) {
			$this->parent->errorControl->raise(406,'','popin');
		} else {
			if (!$this->parent->addLink("common.js")) $this->parent->errorControl->raise(402,'autoformautomato',"","common.js failed to be linked");
			$newTP = new CKTemplate($this->parent->template);
			$newTP->append("<div id=\"popin_fader\" style=\"display:none;background:#ffffff;width:100%;height:100%;position:fixed;z-index:5000;top:0px;left:0px;\"></div>\n");
			foreach ($this->popins as $pname => $popin) {
				$newTP->append("<div id=\"popin".$pname."\" style=\"width:".$popin[1].";height:".$popin[2].";position:fixed;z-index:5001;display:none;\">\n");
				$fileTP = new CKTemplate($this->parent->template);
				$fileTP->fetch(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$popin[0].".html");
				$newTP->append($fileTP);
				unset($fileTP);
				$newTP->append("\n</div>\n");
			}
			if (!$this->jscodeadded) {
				$newTP->append("<script type=\"text/javascript\">\n".$this->popincode."\n</script>");
				$this->jscodeadded = true; // makes sure the javascript is added only once
			}
			$this->parent->template->assign("bi_popin",$newTP);
		}

	}


}

