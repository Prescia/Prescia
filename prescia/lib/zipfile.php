<?
/*
  $zipfile = new zipfile($arquivo_zip,false);  // nome apenas necessario se for enviar na hora, ai coloque o nome e true, e depois echo no $zipfile->file();
  $zipfile->addFileAndRead ($arquivo,$arquivo_path_in_zip);   // para cada arquivo
  $arquivo_zipado = $zipfile->file();
*/

class zipfile {  
  var $datasec       = array();  
  var $ctrl_dir      = array();  
  var $eof_ctrl_dir  = "\x50\x4b\x05\x06\x00\x00\x00\x00";  
  var $old_offset    = 0;  
  
  function zipfile ($output_filename = 'archive.zip', $output_headers = false) {  
    if ($output_headers) {
      header('Content-Type: application/x-zip');  
      header('Content-Disposition: inline; filename="' . $output_filename . '"');  
      header('Expires: 0');  
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');  
      header('Pragma: public');  
    }
  }
  function __construct($output_filename = 'archive.zip', $output_headers = false) {
  		$this->zipfile($output_filename, $output_headers);
  }
  
  function read_File ($file) {  
    if (is_file($file)) {  
      $fp = fopen ($file, 'rb');  
      $content = fread ($fp, filesize($file));  
      fclose ($fp);  
      return $content;  
    }  
  }  
  
  function addFileAndRead ($file,$name) {  
    if (is_file($file)) $this->addFile($this->read_File($file), $name);  
  }  
  
  function unix2DosTime($unixtime = 0) {  
    $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);  
    if ($timearray['year'] < 1980) {  
      $timearray['year']    = 1980;  
      $timearray['mon']     = 1;  
      $timearray['mday']    = 1;  
      $timearray['hours']   = 0;  
      $timearray['minutes'] = 0;  
      $timearray['seconds'] = 0;  
    }  
    return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);  
  }  
  
  function addFile($data, $name, $time = 0) {  
    $name     = str_replace('\\', '/', $name);  
    $dtime    = dechex($this->unix2DosTime($time));  
    $hexdtime = '\x' . $dtime[6] . $dtime[7]  
              . '\x' . $dtime[4] . $dtime[5]  
              . '\x' . $dtime[2] . $dtime[3]  
              . '\x' . $dtime[0] . $dtime[1];  
    eval('$hexdtime = "' . $hexdtime . '";');  
    $fr   = "\x50\x4b\x03\x04";  
    $fr   .= "\x14\x00";  
    $fr   .= "\x00\x00";    
    $fr   .= "\x08\x00";    
    $fr   .= $hexdtime;  
    $unc_len = strlen($data);  
    $crc     = crc32($data);  
    $zdata   = gzcompress($data);  
    $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug  
    $c_len   = strlen($zdata);  
    $fr      .= pack('V', $crc);            
    $fr      .= pack('V', $c_len);  
    $fr      .= pack('V', $unc_len);    
    $fr      .= pack('v', strlen($name));  
    $fr      .= pack('v', 0);  
    $fr      .= $name;  
    $fr .= $zdata;  
    $fr .= pack('V', $crc);                
    $fr .= pack('V', $c_len);    
    $fr .= pack('V', $unc_len);  
    $this -> datasec[] = $fr;  
    $new_offset  = strlen(implode('', $this->datasec));  
    $cdrec  = "\x50\x4b\x01\x02";  
    $cdrec .= "\x00\x00";              
    $cdrec .= "\x14\x00";          
    $cdrec .= "\x00\x00";            
    $cdrec .= "\x08\x00";                 
    $cdrec .= $hexdtime;              
    $cdrec .= pack('V', $crc);         
    $cdrec .= pack('V', $c_len);        
    $cdrec .= pack('V', $unc_len);       
    $cdrec .= pack('v', strlen($name) );  
    $cdrec .= pack('v', 0 );  
    $cdrec .= pack('v', 0 );    
    $cdrec .= pack('v', 0 );          
    $cdrec .= pack('v', 0 );      
    $cdrec .= pack('V', 32 );  
    $cdrec .= pack('V', $this -> old_offset );  
    $this -> old_offset = $new_offset;  
    $cdrec .= $name;  
    $this -> ctrl_dir[] = $cdrec;  
  }  
  
  function file() {  
    $data    = implode(NULL, $this -> datasec);  
    $ctrldir = implode(NULL, $this -> ctrl_dir);  
    return $data . $ctrldir .$this -> eof_ctrl_dir . pack('v', sizeof($this -> ctrl_dir)) . pack('v', sizeof($this -> ctrl_dir)) .  pack('V', strlen($ctrldir)) .  pack('V', strlen($data)) . "\x00\x00";                           
  }  
  
}  

