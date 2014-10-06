<?/*--------------------------------\
  | Image Handler Bundle: Implements functions for handling images
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

if (!defined('CONS_JPGQUALITY')) define ("CONS_JPGQUALITY",85);

  /* CONDITIONALLY reduces the image to the specified w/h. If the image is SMALLER, does nothing
     However even if the image is smaller, if watermark is present, applies it
     Return codes: 0 - nothing done, 1 - reduced OR applyed masks, 2 - error (had to do something but was unable to)
  */
	function resizeImageCond($imagem,$maxw=500, $maxh=500, $watermarkArray = array(), $bg="FFFFFF",$forceJPG = false) {
	    $ih = @getimagesize($imagem);
	    if ($ih) {
	      $ow = $ih[0]; // original
		  $oh = $ih[1];
	      if (count($watermarkArray)>0 && !is_array($watermarkArray[0]) && $watermarkArray[0][0] == "C" && $ow>$maxw && $oh>=$maxh) {
	      	// will crop, so $w and $h are the max (if original image is larger or equal to the thumb)
	      	$w = $maxw;
	      	$h = $maxh;
	      } else { // either NOT crop, or crop but the image is smaller
		      $w = $ow;
		      $h = $oh;
		      if ($maxw>0) { // width surpassed, limit it
		         if ($w>$maxw) {
		            $w = $maxw;
		            $h = floor(($oh/$ow)*$w);
		         }
		      }
		      if ($maxh>0) { // heiht surpassed, limit it
		         if ($h > $maxh) {
		            $h = $maxh;
		            $w = floor(($ow/$oh)*$h);
		         }
		      }
	      }


	      if (($w != $ow) || ($h != $oh) || count($watermarkArray)>0) {
	      	// if reduce OR watermark present, proceed
	      	$imagemtmp = $imagem."tmp";
	        if (resizeImage($imagem, $imagemtmp, $w, $h,CONS_JPGQUALITY,$watermarkArray,$bg,$forceJPG)) {
	          @unlink ($imagem);
	          locateFile($imagemtmp,$ext); // the resizeImage added .jpg or .png as per required
	          if (!@copy ($imagemtmp,$imagem)) {
	          	@unlink ($imagemtmp);
	          	return 2; // error copying temporary file to destination file
	          }
	          // save ok
	          $temp = umask(0);
	          chmod($imagem,0775);
	          umask ($temp);
	          @unlink ($imagemtmp);
	          return 1; // ok!
	        } else
	          return 2; // error
	      }
	    } else {
	      return 2;
	    }
	    return 0; // no change required
	}
	/*
	 Converts an image (original) to a new image (destination, can be the same as original to replace)
	 It will perform a CROP with the given coordinates regarding the ORIGINAL image where:
	 	$cropposition = array(x,y)
	 	$cropsize = array(x,y)
	 Then, it will perform a CONDITIONAL resize to the specified array(x,y) on resizeto
	 Optionally, it can work out with $watermarkArray on the final image
	 NOTE: will change the extension of the $destination accordinly, will try to preserve PNG transparency unless you set forceJPG
	 */

	function cropImage($original,$destination,$cropposition,$cropsize,$resizeto,$quality =0,$watermarkArray = array(),$bgcolor="FFFFFF",$forceJPG=false) {

		if ($quality == 0) $quality = CONS_JPGQUALITY;
		$thumbext = explode(".",$destination);
		$thumbext = array_pop($thumbext);
		if ($thumbext == 'jpg' || $thumbext == 'png' || $thumbext == 'gif') {
			// removes extension
			$destination = explode(".",$destination);
			array_pop($destination);
			$destination = implode(".",$destination);
		}

		$ih = @getimagesize($original);
		if ($ih[2] == IMAGETYPE_PNG) // png
			$miniatura_id = imagecreatefrompng($original);
		else if ($ih[2] == IMAGETYPE_GIF) // gif
			$miniatura_id = imagecreatefromgif($original);
		else // jpg or bmp
			$miniatura_id = imagecreatefromjpeg($original);

		// create image on the size of the final crop size
		$img_dest = imagecreatetruecolor($cropsize[0],$cropsize[1]);
		// fill the image (jpg) or enable alpha blending (png)
		if ($ih[2] != IMAGETYPE_PNG || $forceJPG) {
			$Hbgcolor = imagecolorallocate($img_dest, hexdec(substr($bgcolor,0,2)), hexdec(substr($bgcolor,2,2)), hexdec(substr($bgcolor,4,2))); // forces a white bg on thumbs
			imagefilledrectangle($img_dest, 0, 0, $cropsize[0],$cropsize[1],$Hbgcolor);
		} else
			imagealphablending($im_dest, ($ih[2] == IMAGETYPE_JPEG || $ih[2] == IMAGETYPE_BMP) || count($watermarkArray)==0);
		// crop/resample
		imagecopyresampled($img_dest,$miniatura_id,0,0,$cropposition[0],$cropposition[1],$cropsize[0],$cropsize[1],$cropsize[0],$cropsize[1]);
		// no need for the original image now
		imagedestroy($miniatura_id);

		// saves the destination image
		if ($ih[2] == IMAGETYPE_PNG && !$forceJPG) {
			$destination .= ".png";
			@imagepng($img_dest, $destination);
		} else {
			$destination .= ".jpg";
			@imagejpeg($img_dest, $destination, $quality);
		}
		// no need for destination image
		imagedestroy($img_dest);

		// guarantees access
		$temp = umask(0);
		chmod($destination,0775);
		umask ($temp);
		// performes conditional reduction w/ watermark (ok if not 2=error on resizeImageCond)
		return resizeImageCond($destination,$resizeto[0],$resizeto[1],$watermarkArray)!=2;


	}

   /*
	  Converts an image (original) to a miniature, at the dimension set.
	  wartermark array is on the same format at the watermark function, and if present will be applyed AFTER size changes
	  however it also accepts "C" (inside the watermark array, as array("C")) to specify the image should be resized AND croped to fit the W,H
	  Note the CROP tag can be followed by the keywords left, top, right, bottom (ex: Ctop left)
	  Note that if you want several effects for the watermark, the crop (C) MUST be the first
      Will preserve PNG and generate a transparent PNG thumbnail unless you force jpg (at which point will use bgcolor where it should be transparent)
	  Returns TRUE|FALSE on sucess

	*/
	function resizeImage($original, $miniatura, $desiredW=100, $desiredH=100, $quality =0 , $watermarkArray = array(), $bgcolor="FFFFFF",$forceJPG=false) {

		$thumbext = explode(".",$miniatura);
		$thumbext = array_pop($thumbext);
		if ($thumbext == 'jpg' || $thumbext == 'png' || $thumbext == 'gif' || $thumbext == 'bmp') {
			// removes extension
			$miniatura = explode(".",$miniatura);
			array_pop($minuatura);
			$miniatura = implode(".",$miniatura);
		}
		// miniatura have no extension from here on
		if ($quality == 0) $quality = CONS_JPGQUALITY;
	    $acceptable = false;
	    $ih = @getimagesize($original);
	    if ($ih) {
			$acceptable = $ih[2] == IMAGETYPE_BMP || $ih[2] == IMAGETYPE_JPEG || $ih[2] == IMAGETYPE_PNG || $ih[2] == IMAGETYPE_GIF;
			if (!$acceptable) {
				return false; // unknown format, abort
			}
			$cropMe = count($watermarkArray) > 0 && !is_array($watermarkArray[0]) && $watermarkArray[0][0] == "C";
			$oW = $ih[0]; # original
			$oH = $ih[1];
			$tW = $oW; # thumb
			$tH = $oH;

			if ($desiredW>0) { // limits width
				if ($tW>$desiredW) { # reduces proportionally to fit
				     $tW = $desiredW;
				     $tH = floor(($oH/$oW)*$tW);
				}
			}
			if ($desiredH>0) { // limits height (if by width was not enough)
			   if ($tH>$desiredH) { # reduces further proportionally
			   		$tH = $desiredH;
			   		$tW = floor(($oW/$oH)*$tH);
			   }
			}

			$willCrop = false;
			# at this point, the image has been reduced to fit inside desired dimensions, but it ignored CropMe
			if ($cropMe) {
				if ($tW<$desiredW) { # enlarges proportionally to MATCH
				     $tW = $desiredW;
				     $tH = floor(($oH/$oW)*$tW);
				     $willCrop = true;
				}
				if ($tH<$desiredH) { # enlarges further proportionally to MATCH
			   		$tH = $desiredH;
			   		$tW = floor(($oW/$oH)*$tH);
			   		$willCrop = true;
			   	}
			   	# at this point, the image is probably larger than the container, so the offset system will cut it
			} # else the image is smaller or equal to container

	    } else {
	      return false; // unable to open as image with GD
	    }

		if ($ih[2] == IMAGETYPE_PNG) // png
			$miniatura_id = imagecreatefrompng($original);
		else if ($ih[2] == IMAGETYPE_GIF) // gif
	      	$miniatura_id = imagecreatefromgif($original);
		else if ($ih[2] == IMAGETYPE_BMP) // bmp
			$miniatura_id = imagecreatefromwbmp($original);
		else // jpg
	        $miniatura_id = imagecreatefromjpeg($original);
	    if ($willCrop) { # needs to crop a part of the image, regardless if the image will be reduced or enlarged
	    	$reductionFactor = $oW/$tW;

	    	if (strpos($watermarkArray[0],'left') !== false)
	    		$offset_x = 0;
	    	else if (strpos($watermarkArray[0],'right') !== false)
	    		$offset_x = ($tW>$desiredW)? $oW - $reductionFactor * $desiredW: 0;
	    	else
	    		$offset_x = ($tW>$desiredW)? floor($oW/2) - floor($reductionFactor * $desiredW/2): 0;

	    	if (strpos($watermarkArray[0],'top') !== false)
	    		$offset_y = 0;
	    	else if (strpos($watermarkArray[0],'bottom') !== false) {
	    		$offset_y = ($tH>$desiredH)? $oH - $reductionFactor * $desiredH: 0;
	    	} else
				$offset_y = ($tH>$desiredH)? floor($oH/2) - floor($reductionFactor * $desiredH/2) : 0;
			$im_dest = imagecreatetruecolor ($desiredW, $desiredH);
			array_shift($watermarkArray); // consumes the crop
	    } else { # either crop don't neet to cut a part of the image, or crop not applied
			$offset_x = 0;
			$offset_y = 0;
			$im_dest = imagecreatetruecolor ($tW, $tH);
			if ($cropMe) array_shift($watermarkArray); // consumes the crop
	    }

	    /*
	    echo "Original: $oW x $oH <br/>";
	    echo "Desired: $desiredW x $desiredH ".($cropMe?"CROP":"normal")."<br/>";
	    echo "Output: $tW x $tH offset $offset_x x $offset_y <br/>";
	    //*/

	    if ($ih[2] != IMAGETYPE_PNG || $forceJPG) {
	    	$Hbgcolor = imagecolorallocate($im_dest, hexdec(substr($bgcolor,0,2)), hexdec(substr($bgcolor,2,2)), hexdec(substr($bgcolor,4,2))); // forces a white bg on thumbs
	    	imagefilledrectangle($im_dest, 0, 0, $tW,$tH,$Hbgcolor);
	    } else {
	    	//imagecolortransparent($im_dest, imagecolorallocatealpha($im_dest, 0, 0, 0, 127)); // <-- if it where a .gif
	    	imagealphablending($im_dest, false);
	    	imagesavealpha($im_dest, true);
	    }
	    imagecopyresampled($im_dest, $miniatura_id, 0, 0, $offset_x, $offset_y, $tW, $tH, $oW, $oH);
	    imagedestroy($miniatura_id);

	    $miniatura_id = $im_dest;

		if ($cropMe) array_shift($watermarkArray);

	    if ($miniatura_id!="") { // might fail on reduction
	      if ($ih[2] == IMAGETYPE_PNG) { // managed to create a png thumbnail
	      	$miniatura .= ".png";
			@imagepng($miniatura_id, $miniatura);
			if (!is_file($miniatura)) {// unknown error while creating temporary PNG
	        	return false;
			}
			@imagedestroy($miniatura_id);
			if (count($watermarkArray)>0)
				$ok = watermark($miniatura,$watermarkArray,$miniatura,$quality,$bgcolor,false);
			else
				$ok = true;
	      } else { // jpg
	      	$miniatura .= ".jpg";
		    imagejpeg($miniatura_id, $miniatura, $quality);
		    if (!is_file($miniatura)) {// unknown error while creating thumb, try lame style (note: if $quality is not a number, weird things WILL happen)
		    	ob_start();
		    	imagejpeg($miniatura_id, NULL, $quality);
		    	$i = ob_get_clean();
		    	if (!cWriteFile($miniatura,$i) || filesize($miniatura)==0) { // no way to save the file ... bummer
		    		@unlink($miniatura);
					return false;
		    	}
		    }
		    @imagedestroy($miniatura_id);
	      	if (count($watermarkArray)>0)
	      		$ok = watermark($miniatura,$watermarkArray,$miniatura,$quality,$bgcolor,true);
	      	else
	      		$ok = true;
	      }
	      if ($ok) {
	      	$temp = umask(0);
	      	chmod($miniatura,0775);
	      	umask ($temp);
	      }
	      return $ok;
	    } else {
	      @imagedestroy($miniatura_id);
	      @imagedestroy($im_dest);
	      return false;
	    }
	}

  // ---------------------------------------------------------
  function watermark($sourcefile, $watermarkfiles, $destination, $quality=0,$bgcolor="FFFFFF",$forcejpg = false) {
  /*

    $sourcefile = Filename of the picture to be watermarked (a.k.a. the main file).
    $watermarkfiles = file array as below
    A file inside watermarkfile: array ( 'filename' => filename,
    				 					 'position' => array(x,y) where to add the image, negative values means from right or bottom
    				 					 'resample' => array(w,h) to resample this image (optional),
    				 					 'isBack' => true|false optional - (will append this to the BACK of the image, not over it - default is FALSE) IGNORES POSITION AND RESAMPLE and resizes to fit original image
    			   					   )
    $destination = jpg or png file to output (jpg if source is jpg, png if source is png). Send none to display rather then save

    Sample:

    $batch = array ( array ( 'filename' => "files/watermark.png",
                             'position' => array(100,0),
                           ),
                    array ( 'filename' => "files/star.png",
                            'position' => array(200,200),
                            'resample' => array(30,30)
                           )
                   );
    watermark("picture.jpg", $batch, "", 90); <-- adds watermark.png on top of star.ong on top of picture.jpg, displays it rather then saving (no output file specified)

  */
  	if ($quality == 0) $quality = CONS_CFP_JPGQUALITY;
	$realwmf = array();
	$ih = getimagesize($sourcefile); // backmost image
	$ispng = $ih[2] == IMAGETYPE_PNG;
	$recurse = false;
	foreach ($watermarkfiles as $index => $wmf) { // test and checks for recursive isBack
		if (!is_array($wmf)) {
			echo "imgHandler>watermark: Invalid watermark array, requires array of arrays!";
			return false;
		}
		if (!isset($wmf['filename'])) {
			echo "imgHandler>watermark: filename not specified";
			return false;
		} else if (!is_file($wmf['filename'])) {
			echo "imgHandler>watermark: file not found:".$wmf['filename'];
			return false;
		}
		if (isset($wmf['isBack']) && $wmf['isBack']===true) {
			array_unshift($realwmf, array('filename' => $sourcefile)); // adds this as the first watermark over this "back" mark
			$ih2 = getimagesize($wmf['filename']);
			if ($ih2[2] == IMAGETYPE_PNG) // png
				$sourceID = imagecreatefrompng($wmf['filename']);
			else if ($ih2[2] == IMAGETYPE_GIF) // gif
		      	$sourceID = imagecreatefromgif($wmf['filename']);
			else // jpg
		        $sourceID = imagecreatefromjpeg($wmf['filename']);
			$im_dest = imagecreatetruecolor ($ih[0], $ih[1]); // same size as original
			imagecopyresampled($im_dest, $sourceID, 0, 0, 0, 0, $ih[0], $ih[1], $ih2[0], $ih2[1]);
    		imagedestroy($sourceID);
			$recurse = imagejpeg($im_dest, $destination, 95); # temporary file, use high quality
			imagedestroy($im_dest);
		} else
			$realwmf[] = $wmf;
	}

	$watermarkfiles = $realwmf;
	if ($recurse)
		return watermark($destination,$watermarkfiles,$destination,$quality,$bgcolor,true);
    if ($ispng) {
      $sourcefile_id = imagecreatefrompng($sourcefile);
      imageAlphaBlending($sourcefile_id,true);
    } else
      $sourcefile_id = imagecreatefromjpeg($sourcefile);

    $sourcefile_width=imageSX($sourcefile_id);
    $sourcefile_height=imageSY($sourcefile_id);
    foreach ($watermarkfiles as $watermarkfile) {
      $ih2 = getimagesize($watermarkfile['filename']);
      // get image
      if ($ih2[2] == IMAGETYPE_PNG) { // png
        $watermarkfile_id = imagecreatefrompng($watermarkfile['filename']);
      } else if ($ih2[2] == IMAGETYPE_GIF) // gif
      	$watermarkfile_id = imagecreatefromgif($watermarkfile['filename']);
      else
        $watermarkfile_id = imagecreatefromjpeg($watermarkfile['filename']);
      // preserve blending
      imageAlphaBlending($watermarkfile_id, false);
	  imageSaveAlpha($watermarkfile_id, true);
      $watermarkfile_width=imageSX($watermarkfile_id);
      $watermarkfile_height=imageSY($watermarkfile_id);

      // resample?
      if (isset($watermarkfile['resample'])) {
        $im_dest = imagecreatetruecolor ($watermarkfile['resample'][0], $watermarkfile['resample'][1]);
        imagealphablending($im_dest, false);
        imagecopyresampled($im_dest, $watermarkfile_id, 0, 0, 0, 0, $watermarkfile['resample'][0], $watermarkfile['resample'][1], $watermarkfile_width, $watermarkfile_height);
        imagesavealpha($im_dest, true);
        imagedestroy($watermarkfile_id);
        $watermarkfile_id = $im_dest;
        $watermarkfile_width = $watermarkfile['resample'][0];
        $watermarkfile_height = $watermarkfile['resample'][1];
      }

      // position ? if none given, centered
      if (isset($watermarkfile['position']))
        list($dest_x,$dest_y) = $watermarkfile['position'];
      else {
        $dest_x = ( $sourcefile_width / 2 ) - ( $watermarkfile_width / 2 ); // centered
        $dest_y = ( $sourcefile_height / 2 ) - ( $watermarkfile_height / 2 ); // centered
      }
	  // negatives?
	  if ($dest_x == "-") $dest_x = "-0";
	  if ($dest_y == "-") $dest_y = "-0";
	  if (strpos($dest_x,"-") !== false ) // so it allows just - or -0 to align to border
	  	$dest_x = $sourcefile_width - $watermarkfile_width + $dest_x;
	  if (strpos($dest_y,"-") !== false)
	    $dest_y = $sourcefile_height - $watermarkfile_height + $dest_y;

	  // apply
      imagecopy($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height);
      imagedestroy($watermarkfile_id);
    }
    if ($ispng && !$forcejpg) {
      $r = imagepng($sourcefile_id,$destination);
      imagedestroy($sourcefile_id);
    } else {
	  if ($ispng) { // was transparent, remove transparency
      	$im_dest = imagecreatetruecolor ($sourcefile_width, $sourcefile_height);
	  	$Hbgcolor = imagecolorallocate($im_dest, hexdec(substr($bgcolor,0,2)), hexdec(substr($bgcolor,2,2)), hexdec(substr($bgcolor,4,2))); // forces a white bg on thumbs
      	imagefilledrectangle($im_dest, 0, 0, $sourcefile_width,$sourcefile_height,$Hbgcolor);
      	imagecopyresampled($im_dest, $sourcefile_id, 0, 0, 0, 0, $sourcefile_width, $sourcefile_height, $sourcefile_width, $sourcefile_height);
	  	imagedestroy($sourcefile_id);
      	$r = imagejpeg($im_dest,$destination,$quality);
      	imagedestroy($im_dest);
	  } else {
	    $r = imagejpeg($sourcefile_id,$destination,$quality);
	    imagedestroy($sourcefile_id);
	  }
    }

    if ($destination != "") return $r;
    else echo("imgHandler>watermark:Unknown error on watermark function");
    return false;
  }

  function getVideoFrame($code,$w=640,$h=480,$class="") {
		if (is_numeric($code)) {
			return '<iframe '.($class!=''?'class="'.$class.'" ':'width="'.$w.'" height="'.$h.'" ').'src="//player.vimeo.com/video/'.$code.'?badge=0" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		} else {
			return '<iframe '.($class!=''?'class="'.$class.'" ':'width="'.$w.'" height="'.$h.'" ').'src="//www.youtube.com/embed/'.$code.'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}
  }

