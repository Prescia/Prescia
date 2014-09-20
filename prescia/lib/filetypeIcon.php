<?
	function filetypeIcon($ext) { #converts to a standardized icon
	    switch($ext) {
	      case "jpg":case "jpeg":case "gif":case "qif":case "png":case "tif":case "ico":case "bmp": return "jpg"; # picture
	      case "html":case "htm": case "xml": return "htm"; # markup
	      case "ttf":case "fon": return "ttf"; # font
	      case "wav":case "mp3":case "acc":case "ogg":case "flac":case "au":case "wma":case "wmf": return "wav"; # audio
	      case "avi":case "ogm":case "mp4":case "mpg":case "mpeg":case "qt":case "mkv": case "mov": case "asf": case "wmv": case "rm": case "rmvb": return "avi"; # video
	      case "php": return "php"; #script
	      case "ps": case "ai": case "pdf": return "pdf"; # advanced text type
	      case "swf": case "flv": case "fla": return "swf"; # flash
	      default: return "exe"; # generic
	    }
	} 
