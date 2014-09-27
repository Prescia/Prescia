<?	# -------------------------------- Gallery plugin
	/* NOTE: this version has been optimized to work as a plugin to the bi_blog
	 * 	     Single-alone controlers have not been made as of yet 
	 */

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_gallery','Gallery module requires database');

class mod_bi_gallery extends CscriptedModule  {

	var $name = "bi_gallery";
	var $pagename = "gallery"; // captures this page as the gallery page
	var $grouperField = ""; // which field groups images (if any, for instance a category)
	
	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->loadSettings();
	}

	function loadSettings() {
		//$this->name = ""; 
		$this->moduleRelation = "gallery"; // this is the name of the metadata module with the data on the gallery. Change if necessary
		//$this->parent->onMeta[] = $this->name;
		//$this->parent->onActionCheck[] = $this->name;
		//$this->parent->onRender[] = $this->name;
		//$this->parent->on404[] = $this->name;
		//$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
	}
	
	function onMeta() {
	}

	function onCheckActions() {
	}
	
	
	function on404($action, $context = "") {
	}
	
	function onShow(){
	}

	function onEcho(&$PAGE){
	}
	#-
	function embedGallery($id){
		$mod = $this->parent->loaded($this->moduleRelation);
		$myTemplate = new CKTemplate($this->parent->template);
		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."template/gallery.html"))
			$myTemplate->fetch(CONS_PATH_PAGES.$_SESSION['CODE']."template/gallery.html");
		else
			$myTemplate->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/index.html");
		$sql = $mod->get_base_sql("gallery.".$this->grouperField."='$id'","gallery.date ASC");		
		$n = $mod->runContent($myTemplate,$sql,"_images",false,"bloggallery$id");
		if ($n==0) return "";
		return $myTemplate->techo();
		
	}

		
}