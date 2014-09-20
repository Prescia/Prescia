<?	# -------------------------------- Blog Plugin

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_blog','Blog module requires database');

class mod_bi_blog extends CscriptedModule  {

	var $blogfolder = "/blog/"; # add / on both sides. If the blog works at the root, just leave "". Supports multiple folders, for that separate them by a comma (ex "/prescia/,/blog/")
	var $folderfilters = ""; # only usefull if you have more than one blog. Comma separated 'WHERE' statements to define how to filter each blog according to $blogfolder
	var $blogTitle = "blog"; # will add this as title of the inner page (will use i18n translation tag)
 	var $ignoreTagsSmallerThen = 3; # tags smaller than this number of characters are ignored
	var $blogsPerPage = 5;
	var $hideAbout = false; # if true, the about that goes on top of the page is hidden (note that this is always hidden when not at the index)
	var $galleryObject = false; # this will call a function that should return an ARRAY to be filled on each blog with the gallery for that blog.
							    # the function name will be "embedGallery" with the gallery id
	var $customareaname = "";

	// --
	private $contextfriendlyfolderlist = array();
	private $bloginuse = 0;

	function loadSettings() {
		$this->name = "bi_blog";
		//$this->parent->onMeta[] = $this->name;
		$this->moduleRelation = "blog"; // this is the name of the metadata module with the data on the blog. Change if necessary
		$this->parent->onActionCheck[] = $this->name;
		//$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
	}

	function onCheckActions() {
		// explode the lists into arrays, checks for / at the end and beginning of folders
		$this->blogfolder = explode(",",$this->blogfolder);
		for ($c=0;$c<count($this->blogfolder);$c++) {
			$this->blogfolder[$c] = trim($this->blogfolder[$c]," /");
			$this->contextfriendlyfolderlist[] = ($this->blogfolder[$c]!=''?"/":"").$this->blogfolder[$c]."/";
		}
		$this->folderfilters = explode(",",$this->folderfilters);
	}

	function on404($action, $context = "") { // if we do not copy the blog index.html, use the default
		if (in_array($this->parent->context_str,$this->contextfriendlyfolderlist) && $this->parent->action == "index") {
			return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/index.html"; // no index available, use the default
		}
		return false;
	}

	function onShow(){
		if (in_array($this->parent->context_str,$this->contextfriendlyfolderlist) && $this->parent->action == "index") {
			// we are at a blog page, build it
			$core = &$this->parent; // php 5.4 namespaces could come in handy now -_-
			$filter = "";
			if (count($this->contextfriendlyfolderlist)>1 && count($this->contextfriendlyfolderlist)==count($this->folderfilters)) {
				for ($c=0;$c<count($this->contextfriendlyfolderlist);$c++) {
					if ($this->parent->context_str == $this->contextfriendlyfolderlist[$c]) {
						$this->bloginuse = $c;
						$filter = $this->folderfilters[$c];
						break;
					}
				}
			}
			$filter .= ($filter != "" ? " AND " : "")."(blog.publish_after < NOW() || blog.publish_after IS NULL)";
			$langfilter = "lang=\"".$_SESSION[CONS_SESSION_LANG]."\"";
			$filter .= " AND blog.".$langfilter;

			include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/index.php";
		}
	}

	function getTags($filter="") { # Filter is an SQL where statement
		# tag sizes 0 ~ 4
		$TAGS = array();
		$maxTAG = 1;
		$mod = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT ".$mod->name.".tags FROM ".$mod->dbname." as ".$mod->name." WHERE ".$mod->name.".tags<>''".($filter != ""?" AND ".$filter:"");
		$this->parent->dbo->query($sql,$r,$n);
		for ($c=0;$c<$n;$c++) {
			list($ttags) = $this->parent->dbo->fetch_row($r);
			$ttags = explode(" ",strtolower($ttags));
			foreach ($ttags as $tag) {
				if (strlen($tag)>=$this->ignoreTagsSmallerThen) {
					if (isset($TAGS[$tag]))
						$TAGS[$tag]++;
					else
						$TAGS[$tag] = 1;
					if ($TAGS[$tag]>$maxTAG) $maxTAG =$TAGS[$tag];
				}
			}
		}
		foreach ($TAGS as $tag => $count) {
			$TAGS[$tag] = ($count<$maxTAG/5)?0:(($count<2*$maxTAG/5)?1:(($count<3*$maxTAG/5)?2:(($count<4*$maxTAG/5)?3:4)));
		}

		return $TAGS;
	}

	function getArchieveDates($filter="") { # Filter is an SQL where statement
		$mod = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT ".$mod->name.".date FROM ".$mod->dbname." as ".$mod->name." ".($filter!=""?"WHERE $filter":"")." ORDER BY ".$mod->name.".date DESC";
		$result = array();
		$this->parent->dbo->query($sql,$r,$n);
		for($c=0;$c<$n;$c++) {
			list($date) = $this->parent->dbo->fetch_row($r);
			$YM = substr($date,0,4).substr($date,5,2);
			if (!isset($result[$YM]))
				$result[$YM] = array('month' => substr($date,5,2), 'year' => substr($date,0,4), 'monthname' => 'month'.substr($date,5,2));
		}
		return $result;
	}

}

