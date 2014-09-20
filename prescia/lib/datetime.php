<?/*--------------------------------\
  | ADODB_DATE
  | #########################################################
  | ADOdb Date Library, part of the ADOdb abstraction library
  | Download: http://php.weblogs.com/adodb_date_time_library
  | (c) 2003 John Lim and released under BSD-style license except for code by jackbbs,
  | which includes adodb_mktime, adodb_get_gmt_diff, adodb_is_leap_year
  | and originally found at http://www.php.net/manual/en/function.mktime.php
  | ### DOCUMENTATION PORTED TO ADODB-TIME.TXT ###
  | ### TEST FEATURE REMOVED AND PLACED AT ADODB-TIME.TXT ###
  |
  | RESTRICTION: 2 digit years will translate to 19xx and 20xx only
  |
-*/


if (!defined('ADODB_TWODIGITYEAR_OFFSET')) define('ADODB_TWODIGITYEAR_OFFSET',40); # years below this will go 20xx, higher will go to 19xx
if (!defined('ADODB_DATE_VERSION')) define('ADODB_DATE_VERSION',0.15);
if (!defined('ADODB_ALLOW_NEGATIVE_TS')) define('ADODB_NO_NEGATIVE_TS',1);

  function isData($inDate, &$outDate, $usmode = false) {
  	# gets a DATE in DD/MM/YYYY or SQL YYYY-MM-DD and returns true|false if it is a valid date. Fills $$outDate in valid SQL date format
  	if (!$usmode) { # inlt mode (americans like things "their way")
  		if (preg_match("@^(([0]?[1-9])|([1-2][0-9])|([3][0-1]))/(([0]?[1-9])|([1][0-2]))/(([0-9]{2})|([0-9]{4}))$@",$inDate,$pointers)==1) {
	      if ($pointers[8]<100) {
	        if ($pointers[8] < ADODB_TWODIGITYEAR_OFFSET) {
	          $pointers[8] = 2000 + $pointers[8];
	        } else {
	          $pointers[8] = 1900 + $pointers[8];
	        }
	      }
	      $outDate = $pointers[8]."-".( strlen($pointers[5])==1 ? "0".$pointers[5] : $pointers[5] )."-".( strlen($pointers[1])==1 ? "0".$pointers[1] : $pointers[1] );
      	  return true;
  		}
  	} else { # US mode MM/DD/YYYY
    	if (preg_match("@^(([0]?[1-9])|([1][0-2]))/(([0]?[1-9])|([1-2][0-9])|([3][0-1]))/(([0-9]{2})|([0-9]{4}))$@",$inDate,$pointers)==1) {
	      if ($pointers[8]<100) {
	        if ($pointers[8] < ADODB_TWODIGITYEAR_OFFSET) {
	          $pointers[8] = 2000 + $pointers[8];
	        } else {
	          $pointers[8] = 1900 + $pointers[8];
	        }
	      }
	      $outDate = $pointers[8]."-".( strlen($pointers[1])==1 ? "0".$pointers[1] : $pointers[1] )."-".( strlen($pointers[4])==1 ? "0".$pointers[4] : $pointers[4] );
      	  return true;
  		}
  	}
	if (preg_match("@^([0-9]{4})\-([0-9]{2})\-([0-9]{2})$@",$inDate,$pointers)) { # SQL format
	  $outDate = $inDate;
	  return true;
    }
    return false;
  }

  function tomktime($dat1) {
    # Convertes a SQL datetime to adodb_mktime (same as php's mktime, except with the broader year range from ADOdb)
    return adodb_mktime(substr($dat1,11,2),substr($dat1,14,2),substr($dat1,17,2),substr($dat1,5,2),substr($dat1,8,2),substr($dat1,0,4));
  }

  function date_diff_ex($dat1,$dat2) { # _ex because we don't want to replace build-in php functions
  	# Given two SQL dates, return the difference in DAYS (counts leap years)
    # Works best if dat1 > dat2
    $tmp_dat1 = adodb_mktime(0,0,0,substr($dat1,5,2),substr($dat1,8,2),substr($dat1,0,4));
    $tmp_dat2 = adodb_mktime(0,0,0,substr($dat2,5,2),substr($dat2,8,2),substr($dat2,0,4));
    $yeardiff = adodb_date('Y',$tmp_dat1)-adodb_date('Y',$tmp_dat2);
    # leap year exceptions
    $diff = adodb_date('z',$tmp_dat1)-adodb_date('z',$tmp_dat2) + floor($yeardiff /4)*1461;
    for ($yeardiff = $yeardiff % 4; $yeardiff>0; $yeardiff--) {
     $diff += 365 + adodb_date('L', adodb_mktime(0,0,0,1,1,intval(substr((($tmp_dat1>$tmp_dat2) ? $dat1 : $dat2),0,4))-$yeardiff+1));
    }
    return $diff;
  }

  function time_diff($dat1,$dat2) {
  	# Given two SQL datetimes, return the difference in SECONDS. No leap year check (made for hour/days comparisions)
    # Works best if dat1 > dat2
    $tmp_dat1 = adodb_mktime(substr($dat1,11,2),substr($dat1,14,2),substr($dat1,17,2),substr($dat1,5,2),substr($dat1,8,2),substr($dat1,0,4));
    $tmp_dat2 = adodb_mktime(substr($dat2,11,2),substr($dat2,14,2),substr($dat2,17,2),substr($dat2,5,2),substr($dat2,8,2),substr($dat2,0,4));
    return (($tmp_dat1-$tmp_dat2));
  }

  function month_diff($dat1,$dat2) {
  	# Givem two different SQL dates, return the difference in MONTHS
    $tmp_dat1 = adodb_mktime(0,0,0,substr($dat1,5,2),substr($dat1,8,2),substr($dat1,0,4));
    $tmp_dat2 = adodb_mktime(0,0,0,substr($dat2,5,2),substr($dat2,8,2),substr($dat2,0,4));
    $yeardiff = adodb_date('Y',$tmp_dat1)-adodb_date('Y',$tmp_dat2);
    if (substr($dat1,5,2)>substr($dat2,5,2)) { // # final month is larger!?
      return ($yeardiff*12) + (substr($dat1,5,2)-substr($dat2,5,2));
    } else if (substr($dat1,5,2)<substr($dat2,5,2)) { // # final month is smaller!?
      return (($yeardiff-1)*12) + (12+(substr($dat1,5,2)-substr($dat2,5,2)));
    } else # same month
      if (substr($dat1,8,2)>=substr($dat2,8,2)) { # month already passed based on day
        return ($yeardiff*12);
    }  else {
        return ($yeardiff*12)-1; # month did not pass based on day
    }
  }

  function fd($date, $mask="d/m/Y") {
  	# Simple interface to format datetimes with ADOdb.
      if (strlen($date)>0 && $date != "0000-00-00" && $date != "0000-00-00 00:00:00") {
        return adodb_date($mask,tomktime($date));
      }
      return "";
  }

  function datecalc($date,$y=0,$m=0,$d=0,$h=0,$min=0,$seg=0,$usmode=false,$getmysql=true) {
  	# Sums/subtracts year, month, days, hours, minutes, seconds in a date. Returns human-like format unless $getmysql specified
    if (strlen($date) == 10) # this is a SQL date
      $output = adodb_date("Y-m-d",adodb_mktime(2,0,0,substr($date,5,2)+$m,substr($date,8,2)+$d,substr($date,0,4)+$y));
    else if (strlen($date) == 19) # this is a SQL datetime
      $output = adodb_date("Y-m-d H:i:s",adodb_mktime(substr($date,11,2)+$h,substr($date,14,2)+$min,substr($date,17,2)+$seg ,substr($date,5,2)+$m,substr($date,8,2)+$d,substr($date,0,4)+$y));
    else return "ERR"; # invalid input format

    if ($getmysql) return $output; # that's ok, return mysql format

	if ($usmode) {
		if (strlen($date) == 10) # date
	      return substr($output,5,2)."/".substr($output,8,2)."/".substr($output,0,4);
	    else
	      return substr($output,11,8)." ".substr($output,5,2)."/".substr($output,8,2)."/".substr($output,0,4);
	} else {
	    if (strlen($date) == 10) # date
	      return substr($output,8,2)."/".substr($output,5,2)."/".substr($output,0,4);
	    else
	      return substr($output,11,8)." ".substr($output,8,2)."/".substr($output,5,2)."/".substr($output,0,4);
	}
  }

  function datecompare($d1,$d2) {
  	# returns TRUE if $d1 is larger (future) then $d2. Supports date and datetime

    if (strpos($d1,"/") !== false) {
    	if (isData($d1,$d1B))
    		$d1 = $d1B;
    	else
    		$d1 = '';
    }
    if (strpos($d2,"/") !== false) {
    	if (isData($d2,$d2B))
    	$d2 = $d2B;
    	else
    	$d2 = '';
    }
	if ($d1 == '' || empty($d1) || is_null($d1)) return false; // first date is NULL, so it cannot be bigger
    if ($d2 == '' || empty($d2) || is_null($d2)) return true; // second date is NULL, so we are bigger
    if (strlen($d1) == strlen($d2) && strlen($d1) == 10) {
    	$d1 = (int)str_replace("-","",$d1);
    	$d2 = (int)str_replace("-","",$d2);
    	// dates turned into integers ... can't get simpler then that
    	return ($d1>$d2);
    } else if (strlen($d1) == strlen($d2) && strlen($d1) == 19) {
    	$d1 = str_replace("-","",$d1);
    	$d1 = str_replace(" ","",$d1);
    	$d1 = (int)str_replace(":","",$d1);
    	$d2 = str_replace("-","",$d2);
    	$d2 = str_replace(" ","",$d2);
    	$d2 = (int)str_replace(":","",$d2);
    	// dates turned into integers ... can't get simpler then that
    	return ($d1>$d2);
    }
    // dates have values in one char, use preg
    if (preg_match("@([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2}) (([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?@",$d1,$e1) &&
        preg_match("@([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2}) (([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?@",$d2,$e2)) {
    	$d1 = str_pad($e1[1],2,"0",STR_PAD_LEFT).
    		  str_pad($e1[2],2,"0",STR_PAD_LEFT).
    		  str_pad($e1[3],2,"0",STR_PAD_LEFT).
    		  (isset($e1[5]) && $e1[5] != ''?str_pad($e1[5],2,"0",STR_PAD_LEFT):'00').
    		  (isset($e1[6]) && $e1[6] != ''?str_pad($e1[6],2,"0",STR_PAD_LEFT):'00').
    		  (isset($e1[8]) && $e1[8] != ''?str_pad($e1[8],2,"0",STR_PAD_LEFT):'00');
    	$d2 = str_pad($e2[1],2,"0",STR_PAD_LEFT).
		      str_pad($e2[2],2,"0",STR_PAD_LEFT).
		      str_pad($e2[3],2,"0",STR_PAD_LEFT).
		      (isset($e2[5]) && $e2[5] != ''?str_pad($e2[5],2,"0",STR_PAD_LEFT):'00').
		      (isset($e2[6]) && $e2[6] != ''?str_pad($e2[6],2,"0",STR_PAD_LEFT):'00').
		      (isset($e2[8]) && $e2[8] != ''?str_pad($e2[8],2,"0",STR_PAD_LEFT):'00');
     	return (int)$d1>(int)$d2;
    } else
    	return false; // oh well

  }

  /*

	FROM NOW ON, ADOdb code - unchanged

  ADOdb Date Library, part of the ADOdb abstraction library
  Download: http://php.weblogs.com/adodb_date_time_library
  */



  /**
    Returns day of week, 0 = Sunday,... 6=Saturday.
    Algorithm from PEAR::Date_Calc
  */
  function adodb_dow($year, $month, $day) {
  /*
  Pope Gregory removed 10 days - October 5 to October 14 - from the year 1582 and
  proclaimed that from that time onwards 3 days would be dropped from the calendar
  every 400 years.

  Thursday, October 4, 1582 (Julian) was followed immediately by Friday, October 15, 1582 (Gregorian).
  */
    if ($year <= 1582) {
      if ($year < 1582 ||
        ($year == 1582 && ($month < 10 || ($month == 10 && $day < 15)))) $greg_correction = 3;
       else
        $greg_correction = 0;
    } else
      $greg_correction = 0;
    if($month > 2)
        $month -= 2;
    else {
        $month += 10;
        $year--;
    }
    $day =  ( floor((13 * $month - 1) / 5) +
            $day + ($year % 100) +
            floor(($year % 100) / 4) +
            floor(($year / 100) / 4) - 2 *
            floor($year / 100) + 77);
    return (($day - 7 * floor($day / 7))) + $greg_correction;
  }

  // Checks for leap year, returns true if it is. No 2-digit year check. Also
  // handles julian calendar correctly.
  function _adodb_is_leap_year($year) {
    if ($year % 4 != 0) return false;

    if ($year % 400 == 0) {
      return true;
    // if gregorian calendar (>1582), century not-divisible by 400 is not leap
    } else if ($year > 1582 && $year % 100 == 0 ) {
      return false;
    }
    return true;
  }

  // checks for leap year, returns true if it is. Has 2-digit year check
  function adodb_is_leap_year($year) {
    return  _adodb_is_leap_year(adodb_year_digit_check($year));
  }

  // Fix 2-digit years. Works for any century.
  // Assumes that if 2-digit is more than 30 years in future, then previous century.
  function adodb_year_digit_check($y)  {
    if ($y < 100) {
      $yr = (integer) date("Y");
      $century = (integer) ($yr /100);
      if ($yr%100 > 50) {
        $c1 = $century + 1;
        $c0 = $century;
      } else {
        $c1 = $century;
        $c0 = $century - 1;
      }
      $c1 *= 100;
      // if 2-digit year is less than 30 years in future, set it to this century
      // otherwise if more than 30 years in future, then we set 2-digit year to the prev century.
      if (($y + $c1) < $yr+30) $y = $y + $c1;
      else $y = $y + $c0*100;
    }
    return $y;
  }

  // get local time zone offset from GMT
  function adodb_get_gmt_diff() {
  static $TZ;
    if (isset($TZ)) return $TZ;
    $TZ = mktime(0,0,0,1,2,1970) - gmmktime(0,0,0,1,2,1970);
    return $TZ;
  }

  // Returns an array with date info.
  function adodb_getdate($d=false,$fast=false) {
    if ($d === false) return getdate();
    if (!defined('ADODB_TEST_DATES')) {
      if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
        if (!defined('ADODB_NO_NEGATIVE_TS') || $d >= 0) // if windows, must be +ve integer
          return @getdate($d);
      }
    }
    return _adodb_getdate($d);
  }


  //  Low-level function that returns the getdate() array. We have a special
  //  $fast flag, which if set to true, will return fewer array values,
  //  and is much faster as it does not calculate dow, etc.
  function _adodb_getdate($origd=false,$fast=false,$is_gmt=false) {
    $d =  $origd - ($is_gmt ? 0 : adodb_get_gmt_diff());

    $_day_power = 86400;
    $_hour_power = 3600;
    $_min_power = 60;

    if ($d < -12219321600) $d -= 86400*10; // if 15 Oct 1582 or earlier, gregorian correction

    $_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
    $_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);
    $d366 = $_day_power * 366;
    $d365 = $_day_power * 365;
    if ($d < 0) {
      $origd = $d;
      // The valid range of a 32bit signed timestamp is typically from
      // Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 GMT
      for ($a = 1970 ; --$a >= 0;) {
        $lastd = $d;

        if ($leaf = _adodb_is_leap_year($a)) $d += $d366;
        else $d += $d365;

        if ($d >= 0) {
          $year = $a;
          break;
        }
      }

      $secsInYear = 86400 * ($leaf ? 366 : 365) + $lastd;

      $d = $lastd;
      $mtab = ($leaf) ? $_month_table_leaf : $_month_table_normal;
      for ($a = 13 ; --$a > 0;) {
        $lastd = $d;
        $d += $mtab[$a] * $_day_power;
        if ($d >= 0) {
          $month = $a;
          $ndays = $mtab[$a];
          break;
        }
      }

      $d = $lastd;
      $day = $ndays + ceil(($d+1) / ($_day_power));

      $d += ($ndays - $day+1)* $_day_power;
      $hour = floor($d/$_hour_power);

    } else {
      for ($a = 1970 ;; $a++) {
        $lastd = $d;

        if ($leaf = _adodb_is_leap_year($a)) $d -= $d366;
        else $d -= $d365;
        if ($d < 0) {
          $year = $a;
          break;
        }
      }
      $secsInYear = $lastd;
      $d = $lastd;
      $mtab = ($leaf) ? $_month_table_leaf : $_month_table_normal;
      for ($a = 1 ; $a <= 12; $a++) {
        $lastd = $d;
        $d -= $mtab[$a] * $_day_power;
        if ($d < 0) {
          $month = $a;
          $ndays = $mtab[$a];
          break;
        }
      }
      $d = $lastd;
      $day = ceil(($d+1) / $_day_power);
      $d = $d - ($day-1) * $_day_power;
      $hour = floor($d /$_hour_power);
    }

    $d -= $hour * $_hour_power;
    $min = floor($d/$_min_power);
    $secs = $d - $min * $_min_power;
    if ($fast) {
      return array(
      'seconds' => $secs,
      'minutes' => $min,
      'hours' => $hour,
      'mday' => $day,
      'mon' => $month,
      'year' => $year,
      'yday' => floor($secsInYear/$_day_power),
      'leap' => $leaf,
      'ndays' => $ndays
      );
    }


    $dow = adodb_dow($year,$month,$day);

    return array(
      'seconds' => $secs,
      'minutes' => $min,
      'hours' => $hour,
      'mday' => $day,
      'wday' => $dow,
      'mon' => $month,
      'year' => $year,
      'yday' => floor($secsInYear/$_day_power),
      'weekday' => gmdate('l',$_day_power*(3+$dow)),
      'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
      0 => $origd
    );
  }

  function adodb_gmdate($fmt,$d=false) {
    return adodb_date($fmt,$d,true);
  }

  // accepts unix timestamp and iso date format in $d
  function adodb_date2($fmt, $d=false, $is_gmt=false) {
    if ($d !== false) {
      if (!preg_match(
        "|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ -]?(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|",
        ($d), $rr)) return adodb_date($fmt,false,$is_gmt);

      if ($rr[1] <= 100 && $rr[2]<= 1) return adodb_date($fmt,false,$is_gmt);

      // h-m-s-MM-DD-YY
      if (!isset($rr[5])) $d = adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
      else $d = @adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]);
    }

    return adodb_date($fmt,$d,$is_gmt);
  }


  // Return formatted date based on timestamp $d
  function adodb_date($fmt,$d=false,$is_gmt=false) {
  static $daylight;

    if ($d === false) return ($is_gmt)? @gmdate($fmt): @date($fmt);
    if (!defined('ADODB_TEST_DATES')) {
      if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
        if (!defined('ADODB_NO_NEGATIVE_TS') || $d >= 0) // if windows, must be +ve integer
          return ($is_gmt)? @gmdate($fmt,$d): @date($fmt,$d);

      }
    }
    $_day_power = 86400;

    $arr = _adodb_getdate($d,true,$is_gmt);
    if (!isset($daylight)) $daylight = function_exists('adodb_daylight_sv');
    if ($daylight) adodb_daylight_sv($arr, $is_gmt);

    $year = $arr['year'];
    $month = $arr['mon'];
    $day = $arr['mday'];
    $hour = $arr['hours'];
    $min = $arr['minutes'];
    $secs = $arr['seconds'];

    $max = strlen($fmt);
    $dates = '';

    // at this point, we have the following integer vars to manipulate:
    // $year, $month, $day, $hour, $min, $secs
    for ($i=0; $i < $max; $i++) {
      switch($fmt[$i]) {
      case 'T': $dates .= date('T');break;
      // YEAR
      case 'L': $dates .= $arr['leap'] ? '1' : '0'; break;
      case 'r': // Thu, 21 Dec 2000 16:01:07 +0200

        $dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))).', '
          . ($day<10?' '.$day:$day) . ' '.date('M',mktime(0,0,0,$month,2,1971)).' '.$year.' ';

        if ($hour < 10) $dates .= '0'.$hour; else $dates .= $hour;

        if ($min < 10) $dates .= ':0'.$min; else $dates .= ':'.$min;

        if ($secs < 10) $dates .= ':0'.$secs; else $dates .= ':'.$secs;

        $gmt = adodb_get_gmt_diff();
        $dates .= sprintf(' %s%04d',($gmt<0)?'+':'-',abs($gmt)/36); break;

      case 'Y': $dates .= $year; break;
      case 'y': $dates .= substr($year,strlen($year)-2,2); break;
      // MONTH
      case 'm': if ($month<10) $dates .= '0'.$month; else $dates .= $month; break;
      case 'Q': $dates .= ($month+3)>>2; break;
      case 'n': $dates .= $month; break;
      case 'M': $dates .= date('M',mktime(0,0,0,$month,2,1971)); break;
      case 'F': $dates .= date('F',mktime(0,0,0,$month,2,1971)); break;
      // DAY
      case 't': $dates .= $arr['ndays']; break;
      case 'z': $dates .= $arr['yday']; break;
      case 'w': $dates .= adodb_dow($year,$month,$day); break;
      case 'l': $dates .= gmdate('l',$_day_power*(3+adodb_dow($year,$month,$day))); break;
      case 'D': $dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))); break;
      case 'j': $dates .= $day; break;
      case 'd': if ($day<10) $dates .= '0'.$day; else $dates .= $day; break;
      case 'S':
        $d10 = $day % 10;
        if ($d10 == 1) $dates .= 'st';
        else if ($d10 == 2) $dates .= 'nd';
        else if ($d10 == 3) $dates .= 'rd';
        else $dates .= 'th';
        break;

      // HOUR
      case 'Z':
        $dates .= ($is_gmt) ? 0 : -adodb_get_gmt_diff(); break;
      case 'O':
        $gmt = ($is_gmt) ? 0 : adodb_get_gmt_diff();
        $dates .= sprintf('%s%04d',($gmt<0)?'+':'-',abs($gmt)/36); break;

      case 'H':
        if ($hour < 10) $dates .= '0'.$hour;
        else $dates .= $hour;
        break;
      case 'h':
        if ($hour > 12) $hh = $hour - 12;
        else {
          if ($hour == 0) $hh = '12';
          else $hh = $hour;
        }

        if ($hh < 10) $dates .= '0'.$hh;
        else $dates .= $hh;
        break;

      case 'G':
        $dates .= $hour;
        break;

      case 'g':
        if ($hour > 12) $hh = $hour - 12;
        else {
          if ($hour == 0) $hh = '12';
          else $hh = $hour;
        }
        $dates .= $hh;
        break;
      // MINUTES
      case 'i': if ($min < 10) $dates .= '0'.$min; else $dates .= $min; break;
      // SECONDS
      case 'U': $dates .= $d; break;
      case 's': if ($secs < 10) $dates .= '0'.$secs; else $dates .= $secs; break;
      // AM/PM
      // Note 00:00 to 11:59 is AM, while 12:00 to 23:59 is PM
      case 'a':
        if ($hour>=12) $dates .= 'pm';
        else $dates .= 'am';
        break;
      case 'A':
        if ($hour>=12) $dates .= 'PM';
        else $dates .= 'AM';
        break;
      default:
        $dates .= $fmt[$i]; break;
      // ESCAPE
      case "\\":
        $i++;
        if ($i < $max) $dates .= $fmt[$i];
        break;
      }
    }
    return $dates;
  }

  //  Returns a timestamp given a GMT/UTC time.
  //  Note that $is_dst is not implemented and is ignored.
  function adodb_gmmktime($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_dst=false) {
    return adodb_mktime($hr,$min,$sec,$mon,$day,$year,$is_dst,true);
  }

  //  Return a timestamp given a local time. Originally by jackbbs.
  //  Note that $is_dst is not implemented and is ignored.
  //  Not a very fast algorithm - O(n) operation. Could be optimized to O(1).
  function adodb_mktime($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_dst=false,$is_gmt=false) {
    if (!defined('ADODB_TEST_DATES')) {
      // for windows, we don't check 1970 because with timezone differences,
      // 1 Jan 1970 could generate negative timestamp, which is illegal
      if (1971 < $year && $year < 2038
        || $mon === false
        || !defined('ADODB_NO_NEGATIVE_TS') && (1901 < $year && $year < 2038)
        )
          return $is_gmt?
            @gmmktime($hr,$min,$sec,$mon,$day,$year):
            @mktime($hr,$min,$sec,$mon,$day,$year);
    }

    $gmt_different = ($is_gmt) ? 0 : adodb_get_gmt_diff();

    $hr = intval($hr);
    $min = intval($min);
    $sec = intval($sec);
    $mon = intval($mon);
    $day = intval($day);
    $year = intval($year);


    $year = adodb_year_digit_check($year);

    if ($mon > 12) {
      $y = floor($mon / 12);
      $year += $y;
      $mon -= $y*12;
    }

    $_day_power = 86400;
    $_hour_power = 3600;
    $_min_power = 60;

    $_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
    $_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

    $_total_date = 0;
    if ($year >= 1970) {
      for ($a = 1970 ; $a <= $year; $a++) {
        $leaf = _adodb_is_leap_year($a);
        if ($leaf == true) {
          $loop_table = $_month_table_leaf;
          $_add_date = 366;
        } else {
          $loop_table = $_month_table_normal;
          $_add_date = 365;
        }
        if ($a < $year) {
          $_total_date += $_add_date;
        } else {
          for($b=1;$b<$mon;$b++) {
            $_total_date += $loop_table[$b];
          }
        }
      }
      $_total_date +=$day-1;
      $ret = $_total_date * $_day_power + $hr * $_hour_power + $min * $_min_power + $sec + $gmt_different;

    } else {
      for ($a = 1969 ; $a >= $year; $a--) {
        $leaf = _adodb_is_leap_year($a);
        if ($leaf == true) {
          $loop_table = $_month_table_leaf;
          $_add_date = 366;
        } else {
          $loop_table = $_month_table_normal;
          $_add_date = 365;
        }
        if ($a > $year) { $_total_date += $_add_date;
        } else {
          for($b=12;$b>$mon;$b--) {
            $_total_date += $loop_table[$b];
          }
        }
      }
      $_total_date += $loop_table[$mon] - $day;

      $_day_time = $hr * $_hour_power + $min * $_min_power + $sec;
      $_day_time = $_day_power - $_day_time;
      $ret = -( $_total_date * $_day_power + $_day_time - $gmt_different);
      if ($ret < -12220185600) $ret += 10*86400; // if earlier than 5 Oct 1582 - gregorian correction
      else if ($ret < -12219321600) $ret = -12219321600; // if in limbo, reset to 15 Oct 1582.
    }
    //print " dmy=$day/$mon/$year $hr:$min:$sec => " .$ret;
    return $ret;
  }


