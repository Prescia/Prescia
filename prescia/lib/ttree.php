<?/*--------------------------------\
  | ttree : Implements a tree structure
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | This tree structure is used also to represent XML files, and have a few functions to ease it's use like that
  | Uses: listFiles
-*/

  class ttree {

    public $data;
    public $branchs = array();
    public $parent = null;

    function __construct($data = false) {
    	if ($data) $this->data = $data;
    }

    # retusn the branch with said id
    public function getbranchById($id) {
    	if ($this->data['id'] == $id) return $this;
    	foreach ($this->branchs as &$branch) {
    		$r = $branch->getbranchById($id);
    		if ($r !== false) return $r;
    	}
    	return false;
    }

	# Gets an array with 'id', 'id_parent' and 'title' and get them into a tree structure
	public function arrayToTree($array,$treestrsep = "\\",$idParField='id_parent',$titleField='treetitle') {
		# First, get all items in a translator ID => actual positio in the array
    	$translator = array();
    	foreach ($array as $id => $content) {
      		$translator[$content['id']] = $id;
      	}
      	# Now, build and check the array for integrity and fills up the text tree
      	$x = 0;
		foreach ($array as $id => $content) {
			$array[$id]['tree'] = "";
			$thisitem = $content;
			if ($thisitem[$idParField] == 0) {
				$array[$id]['tree'] = $array[$id][$titleField];
			} else {
				while ($thisitem[$idParField] != 0 && !is_null($thisitem[$idParField])) {
		      		if (!isset($translator[$thisitem[$idParField]])) {
		      			echo "CKT:arrayToTree item with invalid parent: ".$thisitem['id']." reports parent ".$thisitem[$idParField]."<br/>";
		      			break;
		      		}
		      		$array[$id]['tree'] = $array[$translator[$thisitem[$idParField]]][$titleField].$treestrsep.$array[$id][$titleField];
					$x++;
					if ($x == 1000) die("Overflow at tree builder (infinite loop)");
					$thisitem = $array[$translator[$thisitem[$idParField]]];
		      	}
			}
		}
		return $this->fillme($array,0,0);
	}

	## selectWholeBranch will select all the branch, from root, with "selected" = 1|0
	public function selectWholeBranch($id) {
		$branch = $this->getbranchById($id);
		if ($branch !== false) {
			while ($branch->data['id_parent'] != 0) {
				$branch->data['selected'] = 1;
				$branch = $branch->parent;
			}
			$branch->data['selected'] = 1;
		}
	}

    ## fillme: Gets an array of data, where each item MUST have at least 'id' and 'id_parent' and build the tree. $me is the selected branch of false (none selected)
    private function fillme($array, $rootID = false,$root = true) {
      $outarray = array();
      $used = array();
      if (count($array)==0) return array();
      $rootID = $rootID===false?$array[0]['id_parent']:$rootID;
      if (!isset($this->data)) $this->data = array('id'=>$rootID);

      foreach ($array as $x => $item) {
        if ($item['id_parent'] == $rootID) { // adds this item in my branch
          $this->addbranch($item);
          array_push($used,$item['id']);
        } else { // not in THIS array, process later
          array_push($outarray,$item);
        }
      }
      foreach ($this->branchs as $x => $branch) { // fills up chields
        $newused = $this->branchs[$x]->fillme($outarray,$branch->data['id'],false);
        foreach ($newused as $item) // array merge will ignore keys
        	if (!in_array($item,$used))
        		$used[] = $item;
      }
      // the ones which the parent was not found, add to the root
      if ($root) {
        foreach ($array as $x => $item) {
          if (!in_array($item['id'],$used)) {
            array_push($used,$item['id']);
            $this->addbranch($item);
          }
        }
      }
      return $used;
    } # fillme

	public function getNode($name,$param=array()) { // XML
		# Search on this tree (considering this is a XML) and returns all nodes (with content) with the name (string or array of strings) with
		# the parameters selected (array)
		if ((is_string($name) && $this->data[0] == $name) || (is_array($name) && in_array($this->data[0],$name))) {
			$yes = true;
			foreach ($param as $paramid => $paramdata){
				if (isset($this->data[1][$paramid]))
					$yes = $yes && $this->data[1][$paramid] == $paramdata;
				else
					$yes = false;
				if (!$yes) break;
			}
			if ($yes) return array($this);
		}
		$results = array();
		foreach ($this->branchs as $branch) {
			$t = $branch->getNode($name,$param);
			if ($t !== false) {
				foreach ($t as $innert)
					$results[] = $innert;
			}
		}
		if (count($results)==0) return false;
		else return $results;
	}

    ## getarray: returns the ID in a list, respecting level (parent, chield, grandchield...)
    public function getarray($top_bottom = true) {
      $saida = array();
      if ($top_bottom) {
        for($c=0;$c<$this->total();$c++) {
          array_push($saida,$this->branchs[$c]->data);
          $filhos = $this->branchs[$c]->getarray(true);
          foreach ($filhos as $x => $filho)
            array_push($saida,$filho);
        }
        return $saida;
      } else {
        for($c=$this->total()-1;$c>=0;$c--) {
          array_push($saida,$this->branchs[$c]->data);
          $filhos = $this->branchs[$c]->getarray(false);
          foreach ($filhos as $x => $filho)
            array_push($saida,$filho);
        }
        return $saida;
      }
    } # getarray

    ## addbranch: Adds an item at the tree
    public function addbranch($item) {
      $objitem = new ttree();
      $objitem->data = $item;
      $objitem->parent = &$this;
      array_push($this->branchs,$objitem);
    }

    ## total: return how many items this tree branch has
    public function total() {
      return count($this->branchs);
    }

    ## getbranch: returns one specific item from local array
    public function &getbranch($qual) {
      if ($qual<$this->total())
        return $this->branchs[$qual];
      else
        return array();
    }

    ## lastsibling: returns the last item on the tree
    public function &lastsibling() {
      return $this->branchs[count($this->branchs)-1];
    }

    ## delramo: deletes a branch, note this will move the LAST branch to replace it (thus, this is an unordered tree)
    public function delramo($qual) {
      $this->branchs[$qual]->clear();
      if ($this->total()-1>-1) $this->branchs[$qual] = $this->branchs[$this->total()-1]; // copia aquele ramo inteiro para ca
      array_pop($this->branchs);
    }

    public function clear() {
      foreach ($this->branchs as $x => $branch)
        $x->clear();
      $this->data = false;
      $this->branchs = array();
      //$this->chields = array();
    }
/*
    public function prepchields() {
      $this->chields = array($this->data['id']);
      foreach ($this->branchs as $x => $branch)
        if (is_object($branch)) $this->chields = array_merge($this->chields,$this->branchs[$x]->prepchields());
      return $this->chields;
    }
*/
	# given a path, build a ttree with the folder structure
    public function getFolderTree($path,$includeFiles=false,$selected="",$strparent="",$hidethese=array(),$nextnid=1) {
		$strparent=$strparent == "" ? "/" : $strparent;
		$files = listFiles($path,'@^([^.]*)$@',false,true,false);
		$nid = $nextnid;
		$nidparent = $nid-1;
		$items = 0;
		if ($nextnid==1) { // root
			$this->data = array('id' => "",
								'nid' => 0,
								'checked' => $selected == $strparent?"1":"0" );
		}
		foreach ($files as $file) {
			if (is_dir($path.$file) && !in_array($file,$hidethese)) {
				$this->addbranch(array( "nid" => $nid, "nid_parent" => $nidparent, "id" => $file, "id_parent" => $strparent, "checked" => $strparent.$file == $selected?"1":"0"));
				$l = &$this->branchs[count($this->branchs)-1];
				if ($l->data['checked'] == "1") {
					$this->data['checked'] = 1;
					$papis = &$this->parent;
					while ($papis) {
						$papis->data['checked'] = 1;
						$papis = &$papis->parent;
					}
				}
				$nid++;
				$items++;
				$items_inside = $l->getFolderTree($path.$file."/",$includeFiles,$selected,$strparent.$file."/",$hidethese,$nid);
				$nid += $items_inside;
				$items += $items_inside;
			}
    	}
		if ($includeFiles) {
      		foreach ($files as $file) {
      			if (is_file($path.$file) && !in_array($file,$hidethese)) {
      				$this->addbranch(array("nid" => $nid, "nid_parent" => $nidparent, "id" => $file, "id_parent" => $strparent));
      				$nid++;
      				$items++;
      			}
        	}
    	}


		return $items;
    }

    # Will echo the contents of the tree as HTML (considering it was a (x)HTML loaded into this tree, obviously)
    public function echoHTML($ignoreMyTag=false) {
    	$autoclose = array('img','input','br','meta','link','hr');
    	$output = "";
    	if ($this->data[0] != '' && !$ignoreMyTag) {
    		$tag = strtolower($this->data[0]);
    		$output = "<".$tag;
    		if (is_array($this->data[1])) {
    			foreach ($this->data[1] as $n => $p)
    				$output .= " $n=\"$p\"";
    		} else if ($this->data[1] != '')
    			$output .= " ".$this->data[1];
    		if (in_array($tag,$autoclose)) $output .= "/";
    		$output .= ">";
    	}
    	$output .= $this->data[2];
    	foreach ($this->branchs as $branch) {
    		$output .= $branch->echoHTML();
    	}
    	if (!$ignoreMyTag && $this->data[0] != '' && !in_array($tag,$autoclose) && substr($tag,0,8) != '![cdata[' && substr($tag,0,8) != '![CDATA[')
    		$output .= "</".$tag.">";
    	return $output;
    }

  }

