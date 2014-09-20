<?/*--------------------------------\
  | chmod_all : Sets the chmod of a whole folder structure, possible even using local FTP connection if normal chmod fails (improperly configured linux permissions)
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: listFiles
  | DEPRECATED
-*/



	function chmod_all($path,$mode, $applyOnFolders = false, $tryftp = false, $ftppath = "", $url = "", $login = "", $passwd = "", $reccall = false, $ftp = array ( 'connection' => false )) {
    # Will recursivelly change the chmod of all files in the specified $path to $mode (example: 0777 or 0775)
    # if $applyOnFolders is set, will apply changes to the folder entries themselves
    # if $tryftp is set, in the event php chmod fails (permission issues),will open a FTP with with $url using $login:$passwd and try FTP's chmod to change it.
    #					 $ftppath should be set to the same $path except in the scope of FTP. Example:
    #					 LOCAL: /myfolder/
    #					 FTP: /public_html/myfolder/
    # $reccall and $ftp are internally used and should not be used to call this function
    #
    # Will return an associative array with 'errors' (files/paths that could not have their chmod changed), 'ok' for sucess, 'dir' for directoryes detected and 'ftp' for files with chmod changed using FTP
    #
    # NOTE: will require permission to open FTP connections on php.ini

    $return = array ( 'errors' => 0, 'ok' => 0, 'dir' => 0 , 'ftp' => 0);
    if (!$reccall) $temp = umask(0); // chmod mask
    if (substr($path,strlen($path)-1,1)!="/") $path .= "/"; # make sure last character of $path is "/"
    if ($ftppath != '' && substr($ftppath,strlen($ftppath)-1,1)!="/") $ftppath .= "/"; # make sure last character of $ftppath is "/"
    $itens = listFiles($path); # gets all files from this folder

    foreach($itens as $id => $name) {

      if (is_file($path.$name)) { # file
        if (@chmod($path.$name,intval($mode, 8))) { #try php chmod
          $return['ok'] ++;
        } else if ($tryftp) { # failed, try FTP?
          if (!$ftp['connection']) { # no FTP connection open ...
            $ftp['connection'] = ftp_connect($url); # Open FTP connection
            $ftp['login_result'] = ftp_login($ftp['connection'],$login,$passwd); # Logs into server
          }
          if ($ftp['login_result'] && ftp_site($ftp['connection'], "chmod ".$mode." ".$ftppath.$name)) { # runs FTP chmod command
            $return['ok'] ++;
            $return['ftp'] ++;
          } else
            $return['errors'] ++;
        } else $return['errors'] ++; # unable to change at all

      } else if(is_dir($path.$name)) { # folder

        if ($applyOnFolders) {
          if (@chmod($path.$name,intval($mode, 8)))
            $return['dir'] ++;
          else if ($tryftp) {
            if (!$ftp['connection']) {
              $ftp['connection'] = ftp_connect($url); # Open FTP connection
              $ftp['login_result'] = ftp_login($ftp['connection'],$login,$passwd); # Logs into server
            }
            if ($ftp['login_result'] && ftp_site($ftp['connection'], "chmod ".$mode." ".$ftppath.$name."/")) {
              $return['dir'] ++;
              $return['ftp'] ++;
            } else
              $return['errors'] ++;
          } else $return['errors'] ++;
        }
        $recursereturn = chmod_all($path.$name,$mode, $applyOnFolders, $tryftp, $ftppath.$name, $url, $login, $passwd, true, $ftp);
        $return['ok'] += $recursereturn['ok'];
        $return['errors'] += $recursereturn['errors'];
        $return['dir'] += $recursereturn['dir'];
        $return['ftp'] += $recursereturn['ftp'];

      }
    } # /foreach

    if (!$reccall) {
      umask ($temp);
      if ($tryftp && $ftp['connection']) $temp = ftp_close($ftp['connection']); # base call, disconnect FTP if used
    }
    return $return;
  }

