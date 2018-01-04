<?php

function LinkPicker($tbl, $pickername='') {
	global $tablearr, $recz;
	# Array ( [pdf] => [location] => [product] => fMms [manufacturer] => [type] => CcZ7 ) 
	# SELECT li.link_id, ty.name AS tyname, ty.z AS tyz FROM store_links li LEFT JOIN store_type ty ON ty.z='CcZ7' LIMIT 0,1
	# create query to get names of all records
	#foreach($tablearr as $t => $a) {
	#	if($t != $tbl) {
	#		if(isset($recz[$t]) && $recz[$t] != '') {
	#			$p = substr($t,0,2); 
	#			$j1[] = "LEFT JOIN $t $p ON {$p}.z='{$recz[$t]}'"; 
	#			$j2[] = "{$p}.name AS {$p}name, {$p}.z AS {$p}z";
	#		}
	#	}
	#}
	#if(isset($j1)) {
	foreach($recz as $tbl => $z) {
		if($z) {
			$someopen = true; break;
		}
	}
	if($someopen) {
		$bf[] = '<select name="'.($pickername==''?$t:$pickername).'_picker">';
		$bf[] = '<option value="0">Link to</option>';
		foreach($recz as $tbl => $z) {
			if($z) {
				$mrow = GetSQLRecordi($sql="SELECT name, z FROM $tbl WHERE z='{$z}'");
				if($mrow) {
					$bf[] = '<option value="'.$z.':'.$tbl.'">'.ProperAreaName($tbl).': '.$mrow["name"].'</option>';
				}
			}
		}
		$bf[] = '</select>';
		$bf[] = '<select name="'.($pickername==''?$t:$pickername).'_direction">';
		$bf[] = '<option value="0">This is the</option>';
		$bf[] = '<option value="parent">Parent</option>';
		$bf[] = '<option value="child">Child</option>';
		$bf[] = '</select> ';
		return implode($bf, "\n");
	}
	return "Cannot link to/from other areas, none open.<br />";
}

function ProperAreaName($param) {
	$label = after($param, '_');
	return ucfirst($label);
}

function ParentPicker($t, $pickername='', $defaultz='') {
	$bf[] = '<select name="'.($pickername==''?$t:$pickername).'_picker">';
	$bf[] = '<option value="0">Choose ...</option>';
	$result = ExecuteSQLi($sql="SELECT * FROM $t ORDER BY name, birthdate");
	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$temp = '<option value="'.$row["z"].'"';
			if(mb_strlen($defaultz)==4 && $defaultz == $row['z']) $temp .= ' selected';
			$temp .= '>'.$row['name'].'</option>';
			$bf[] = $temp;
		}
	}
	$bf[] = '</select>';
	return implode($bf, "\n");
}

function ParentPicker1($prodrow, $idfld='manufacturer_id', $foreignidfld='manufacturer_id', $tbl='store_manufacturer', $controlname='', $firstitem='Choose', $disabled=false, $showmarker=true) {
	global $login, $locationname, $manufacturername, $typename;
	if($controlname) $cname=$controlname;
	else $cname=$idfld;
	$bf[] = '<select name="'.$cname.'"'.($disabled?' disabled="disabled"':'').'>';
	$bf[] = '<option value="0">'.$firstitem.'</option>';
	if($tbl=='store_location') {
		foreach($locationname as $id => $nm) $bf[] = '<option value="'.$id.'"'.($prodrow[$foreignidfld] == $id?' selected="selected"':'').'>'.$nm.'</option>';
	} elseif($tbl=='store_manufacturer') {
		#print_r($prodrow);
		#echo "$foreignidfld<br />";
		foreach($manufacturername as $id => $nm) {
			# if disabled just have this one choice
			if(!$disabled || ($disabled && $prodrow[$foreignidfld] == $id)) $bf[] = '<option value="'.$id.'"'.($prodrow[$foreignidfld] == $id?' selected="selected"':'').'>'.$nm.'</option>';
			#echo "$id, $nm<br />";
		}
		#die;
	} else { # type
		foreach($typename as $id => $nm) {
			if(!$disabled || ($disabled && $prodrow[$foreignidfld] == $id)) $bf[] = '<option value="'.$id.'"'.($prodrow[$foreignidfld] == $id?' selected="selected"':'').'>'.$nm.'</option>';
		}
	}
	$bf[] = '</select>';
	if($showmarker && !$disabled) $bf[] = '<input type="hidden" value="1" name="'.$cname.'*" />';
	return implode($bf, "\n");
}

function ValidArea($tbl) { # whitelist ability to select area
	global $tablearr;
	foreach($tablearr as $t => $a) {
		if($t == $tbl) return $t;
	}
	return false;
}
function ZExists($temp) {
	# do not use qrcode is already used in z or filename columns
	global $tablearr, $login;
	if($temp=='') return true;
	foreach($tablearr as $t => $a) {
		$row = GetSQLRecordi($sql="SELECT count(*) FROM $t WHERE account_id=".$login->AcctID()." AND (z='{$temp}' OR filename RLIKE '{$temp}')");
		if($row["count(*)"] > 0) return true;
	}
	return false;
}
function GetIDFromZ($z, $t) {
	global $login, $tablearr;
	$tbl = ValidArea($t);
	if($tbl !== false) {
		$row = GetSQLRecordi($sql="SELECT ".$tablearr[$tbl]['idfield']." FROM {$t} WHERE z='".FilterZ($z, 4)."' AND account_id=".$login->AcctID());
		echo $sql;
		if($row) return $row[$tablearr[$tbl]['idfield']];
	}
	return false;
}
function InsertLinkRecord($t1, $id1, $t2, $id2) {
	global $login;
	ExecuteSQLi($sql="INSERT INTO store_links SET table1='{$t1}', id1={$id1}, table2='{$t2}', id2={$id2}, moddate=now(), birthdate=now(), account_id=".$login->AcctID());
	return $sql;
}
function InsertRecord($tbl, $z4='', $nm='', $morefields='') {
	global $login, $store;
	if($nm) $temp = $nm;
	else $temp = 'Untitled '.$tbl;
	$pdo = NEW myPDO(true);
	$f[] = 'account_id'; $v[] = $login->AcctID();
	$f[] = 'store_id'; $v[] = $store;
	$f[] = 'name'; $v[] = $temp;
	$x=0;
	$z=$z4;
	if(!FilterZ($z,4)) $z=MakeQRCode(4);
	while(ZExists($z) && ++$x<100) {
		$z = MakeQRCode(4);
	}
	if(ZExists($z)) echo 'Error: Could not generate unique code.';
	$f[] = 'z'; $v[] = $z;
	if($morefields) {
		foreach($morefields as $fld => $vl) {
			$f[] = $fld; $v[] = $vl;
		}
	}
	$f[] = 'moddate=now(), birthdate=now()';
	$pdo->SetFields($f);
	$pdo->SetValues($v);
	if($pdo->Prepare('INSERT', '*', $tbl)) {
		if($pdo->Execute()) {
			$id = $pdo->GetResult();
			# echo $pdo->SQL();
			return array($id, $z);
		}
	}
	return false;
}
function MakeQRCode($numchars = 4) {
	$letters='abcdefghijkmnopqrstuvwxyz';
	$temp='';
	for ($x=0; $x < $numchars; $x++) {
		mt_srand ((double) microtime() * 1000000);
		$temp .= substr($letters,mt_rand(0,strlen($letters)-1),1);
	}
	return $temp;
}
function GetARecord($tbl, $vl, $fld='z') {
	global $login;
	$pdo = NEW myPDO(true);
	$f[] = $fld; $v[] = $vl;
	$f[] = 'account_id'; $v[] = $login->AcctID();
	$pdo->WhereFields($f);
	$pdo->WhereValues($v);
	if($pdo->Prepare('SELECT', '*', $tbl)) {
		if($pdo->Execute()) { #returns false or the rowcount
			$STH = $pdo->GetResult();
			$row = $STH->fetch(PDO::FETCH_ASSOC); 
			#list($id) = $row[$fld];
			#$rec[$tbl] = $vl;
			return $row;
		}
	}
	return false;
}

# Translate
function xl($en, $es='') {
	if($_SESSION['lang']=='ES' && $es) return $es;
	return $en;
}

?>
