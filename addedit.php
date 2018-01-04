<?php
# no need to require outside login header. Will start session in init-qrcode, login.php will check its $_SESSION['username'] to see if we are logged in.
/*
Craig Charlesworth <ccharlesworth@greenbarn.com>, Larry McIntosh <larry@plainsmanclays.com>, sales@vipotterysupply.com, "Tim Lerner-Plainsman Clays Ltd." <plainsman@telus.net>, David Stalwick <david.stalwick@plainsmanclays.com>
I am getting close to roll-out and will need on-going feed back. Tim will work with it first and we will simplify the user interface as much as possible. At the beginning the system you use your login to know which store you are, you will see all products details of others (except their prices).

The initial objective is to create a comprehensive database of all products sold at all locations and cooperatively manage it. The initial use will be to print a price list. Later, you will add location information for products (e.g. shelf number) and your local webstore will go live. A base list of over a thousand products sold at Plainsman Medicine Hat is in the system (most have pictures). I am fine-tuning it so that pictures are as light-weight as possible (so the site is fast).

Individual products in the base list can be shared across locations because each can specify unique values for product price, option, unit-of-measure, qty, location and NFS (not-for-sale). To start you will traverse the products already there (editing price, option, u/m) for the ones you sell and then add entries for additional products you sell. Stores will be able to adopt products of others (by entering a price), but only the store creating a product will be able to change certain details (e.g. product name, description, manufacturer, type, qrcode).

You can upload pictures of your products (which you find online or take with your cellphone). The site has automated ways to add more than one product at a time.

Products have auto-assigned QRCodes, they are four lower case letters (e.g. dxyg) so they are easily typed on a cellphone keyboard. The system ensures that no codes are duplicated. You will be able to put codes (with labels) on products or shelves in your sales area (so customers can scan these for product info or find a product on the sales floor that they have already found on their phone).


*/

$thisphpfile = 'addedit.php';
session_start();
define('START', microtime()); # in other files start with if(!defined('START')) die;
include('../inc/init-plainsman.php'); # starts session
include('inc/functions.php');
include('../inc/login.php'); # this handles logout
$login = New Login;
include(VARWWW."/mastercopies/fieldgetput.php");
# Edmonton: 1f5v2j7n4i, Vancouver: 9d7b2j5k1v, Vancouver Island: 3k7d826d3n, Medicine Hat: 4h8d9w2k1v
# $_SESSION['z']='dasgezodab';
if(!$login->LoggedIn()) die($login->LogInMsg());
if(!$login->ProductEditor()) die('Logged in but without crendentials to edit products for a store.');
$store=$login->Store();
#$result = ExecuteSQLi($sql="SELECT p.product_id, p.name AS pname, m.name AS mname FROM store_product p LEFT JOIN store_manufacturer m ON m.manufacturer_id=p.manufacturer_id WHERE m.name='Kemper Tools' ORDER BY p.name");
#if($result->num_rows > 0) {
#	while($row = $result->fetch_assoc()) {
#		echo $row['pname'].', '.Kemper($row['pname']).'<br />';
#	}
#}
#$arr = array('store_product', 'store_manufacturer', 'store_location', 'type');
#foreach($arr as $tbl) {
#	$result = ExecuteSQLi($sql="SELECT * FROM $tbl");
#	if($result->num_rows > 0) {
#		while($row = $result->fetch_assoc()) {
#			$temp = MakeQRCode(4); while(ZExists($temp)) $temp = MakeQRCode(4);
#			ExecuteSQLi("UPDATE $tbl SET z='{$temp}' WHERE product_id={$row['product_id']}");
#			# SaveFileSize($row['filename'], $tbl, $row['z']);
#		}
#	}
#}

# set $recz array to remember records for all areas (needs to come before setting area)
foreach($tablearr as $t => $a) {
	if(isset($_POST["{$a['abbr']}z"])) { $recz[$t] = FilterZ($_POST["{$a['abbr']}z"], 4); }
	if(isset($_GET["{$a['abbr']}z"])) { $recz[$t] = FilterZ($_GET["{$a['abbr']}z"], 4); }
	if(!isset($recz[$t])) $recz[$t]='';
}
if(isset($_GET["haspictures"])) { $haspictures = $_GET["haspictures"]; }
if(isset($_POST["haspictures"])) { $haspictures = $_POST["haspictures"]; }
if(!isset($haspictures)) $haspictures='both';
if(isset($_GET["haslocation1"])) { $haslocation1 = $_GET["haslocation1"]; }
if(isset($_POST["haslocation1"])) { $haslocation1 = $_POST["haslocation1"]; }
if(!isset($haslocation1)) $haslocation1='both';
if(isset($_GET["hasprice"])) { $hasprice = $_GET["hasprice"]; }
if(isset($_POST["hasprice"])) { $hasprice = $_POST["hasprice"]; }
if(!isset($hasprice)) $hasprice='both';
if(isset($_GET["hastype"])) { $hastype = $_GET["hastype"]; }
if(isset($_POST["hastype"])) { $hastype = $_POST["hastype"]; }
if(!isset($hastype)) $hastype='both';

# store names array
$result1 = ExecuteSQLi($sql="SELECT * FROM stores");
if($result1->num_rows > 0) {
	$storenames = array();
	$storename[0]='StoreZero';
	while($row1 = $result1->fetch_assoc()) {
		$storenames[$row1['store_id']] = $row1['location'];
	}
}

# cache
$result1 = ExecuteSQLi($sql="SELECT location_id, name FROM store_location WHERE store_id=".$login->Store()." ORDER BY ord, birthdate");
if($result1->num_rows > 0) {
	while($row1 = $result1->fetch_assoc()) {
		$locationname[$row1["location_id"]] = $row1['name'];
	}
}
$result1 = ExecuteSQLi($sql="SELECT manufacturer_id, name FROM store_manufacturer WHERE account_id=".$login->AcctID()." ORDER BY name");
if($result1->num_rows > 0) {
	while($row1 = $result1->fetch_assoc()) {
		$manufacturername[$row1["manufacturer_id"]] = $row1['name'];
	}
}
# only those with no children (having ord value which is not a subset of another)
$result1 = ExecuteSQLi($sql="SELECT parent.type_id, parent.name FROM `store_type` as parent LEFT JOIN `store_type` as child on SUBSTRING(child.ord,1,LENGTH(parent.ord))=parent.ord AND LENGTH(child.ord)>LENGTH(parent.ord) WHERE parent.account_id=".$login->AcctID()." AND child.ord IS NULL GROUP BY parent.name ORDER BY parent.NAME ASC");
if($result1->num_rows > 0) {
	while($row1 = $result1->fetch_assoc()) {
		$typename[$row1["type_id"]] = $row1['name'];
	}
}

if(isset($_GET["prodfilter"])) { # cleared by sending zero
	$n = after($_GET['prodfilter'], ':');
	if($n>0) {
		if(before($_GET['prodfilter'], ':')=='manufacturer') $prodfilter = array('manufacturer', $n);
		if(before($_GET['prodfilter'], ':')=='type') $prodfilter = array('type', $n);
		if(before($_GET['prodfilter'], ':')=='location1') $prodfilter = array('location1', $n);
	}
}
# set $area
if(isset($_POST["area"])) {
	$t = ValidArea($_POST["area"]); if($t !== false) $area = $t;
}
if(isset($_GET["area"])) { # priority on get
	$t = ValidArea($_GET["area"]); if($t !== false) $area = $t;
}
if(!isset($area)) $area='store_product';
foreach($tablearr as $t => $a) {
	if(isset($_POST["{$t}ord"])) { $tableord[$t] = $_POST["{$t}ord"]; }
	if(isset($_GET["{$t}ord"])) { $tableord[$t] = $_GET["{$t}ord"]; }
}
if(isset($_GET["tblorder"])) { $tableord[$area] = $_GET["tblorder"]; }
if(isset($_POST["tblorder"])) { $tableord[$area] = $_POST["tblorder"]; }
if($area=='store_product') {
	if(!isset($tableord[$area])) $tableord[$area] = 'type';
} else {
	if(!isset($tableord[$area])) $tableord[$area] = 'ord';
}
if(!isset($findstr)) $findstr = '';
if(isset($_POST['history'])) $history = unserialize(base64_decode($_POST['history']));
if(isset($_GET['history'])) $history = unserialize(base64_decode($_GET['history']));
if(isset($_GET['clearhistory']) && isset($history)) unset($history);

# set $RcpGroup (must be after set area)
if(isset($_GET['RcpGroup'])) $RcpGroup = intval($_GET['RcpGroup']);
if(isset($_POST['RcpGroup'])) $RcpGroup = intval($_POST['RcpGroup']);
if(!isset($RcpGroup)) $RcpGroup=1;
if(isset($_POST['FirstRecipeGroup'])) { $RcpGroup=1; }
if(isset($_POST['PrevRecipeGroup']) && $RcpGroup>1) { $RcpGroup=$RcpGroup-1; }
if(isset($_POST['NextRecipeGroup'])) { $RcpGroup=$RcpGroup+1; }
# if(isset($_POST['LastRecipeGroup']) && $RcpLastGroup>0) { $RcpGroup=$RcpLastGroup; }
if(isset($_POST['GoRecipeGroup'])) { $RcpGroup=$_POST['RcpGroupNum']; }
if(isset($area) && $area=='store_product') {
	$numingroup=10;
} else {
	$numingroup=100; $RcpGroup=1;
}

# findstring
if(isset($_POST['findit'])) {
	$findstr = html_entity_decode($_POST['what2find']);
	$RcpGroup=1;
	if(isset($_GET["edit"])) unset($_GET["edit"]); # force browse
	$recz[$area]=0;
} elseif(isset($_POST['findall'])) {
	$findstr = html_entity_decode($_POST['what2find']);
	$haspictures='both';
	$haslocation1='both';
	$hasprice='both';
	$hastype='both';
	if(isset($prodfilter)) unset($prodfilter);
	$RcpGroup=1;
	if(isset($_GET["edit"])) unset($_GET["edit"]);
	$recz[$area]=0;
} else {
	if(isset($_POST['findstr'])) {
		$findstr = html_entity_decode($_POST['findstr']); # filter_var($_POST['findstr'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
	}
	if(isset($_GET['findstr'])) {
		if($_GET['findstr']=='') $findstr='';
		else $findstr = html_entity_decode($_GET['findstr']); # filter_var($_GET['findstr'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
	}
}

# any checked?
foreach($_POST as $vr => $vl) if(substr($vr, 0, 5)=='mark_') $marked[]=substr($vr, 5);

# process requests this area (qrcode current record)

#echo $_POST['SaveRecord'].' - '.$_POST['z'];
if(isset($area)) {
	$idfield = $tablearr[$area]['idfield'];
	if(isset($_GET["edit"])) { # set new record for area
		if($_GET["edit"]=='0') $recz[$area]=0;
		else {
			$recz[$area] = FilterZ($_GET["edit"], 4);
			if(!defined('PHONE')) $sethistory = true;
		}
	}
	# set $thisrow
	if($recz[$area]) {
		$going='>';
		$thisrow = GetARecord($area, $recz[$area]);
		# get next, previous records - very difficult! Not used yet.
		if($thisrow !== false) {
			if(isset($sethistory)) $history[$thisrow['z']] = array('area'=>$area, 'name'=>$thisrow['name']);
			if(1==0 && $area=='store_product') {
				# get next record
				$pdo = NEW myPDO(true);
				$f = array(); $v = array();
				$f[] = 'p.account_id'; $v[] = $login->AcctID();
				if(isset($prodfilter) && is_array($prodfilter)) {
					$f[] = "p.{$prodfilter[0]}_id"; $v[] = $prodfilter[1];
				}
				if($findstr) {
					$f[] = "concat_ws(p.z, p.name, p.product_option{$store}, p.keywords, store_manufacturer.name, store_manufacturer.keywords, store_type.name, store_type.keywords) RLIKE ?";
					$v[] = $findstr;
				}
				if($haspictures == 'nopics') $f[] = 'p.filename=""'; elseif($haspictures == 'withpics') $f[] = 'p.filename<>""';
				if($haslocation1 == 'no') $f[] = "p.location1_id{$store}=0"; elseif($haslocation1 == 'yes') $f[] = "p.location1_id{$store}<>0";
				if($hasprice == 'no') $f[] = "p.price{$store}=0"; elseif($hasprice == 'yes') $f[] = "p.price{$store}<>0";
				if($hastype == 'no') $f[] = 'p.type_id=0'; elseif($hastype == 'yes') $f[] = 'p.type_id<>0';

				if($tableord[$area]=='date') { $pdo->SetOrder("p.birthdate DESC"); $f[]="p.birthdate>'{$thisrow['birthdate']}'"; }
				elseif($tableord[$area]=='type') { $pdo->SetOrder('store_type.ord, store_type.name, p.name'); $f[]="p.birthdate>'{$thisrow['birthdate']}'"; }
				elseif($tableord[$area]=='name') { $pdo->SetOrder('p.name'); }
				elseif($tableord[$area]=='ord') { $pdo->SetOrder('p.ord'); }
				elseif($tableord[$area]=='filesize') { $pdo->SetOrder('p.filesize DESC'); }
				elseif($tableord[$area]=='entered') { $pdo->SetOrder('store_type.ord, p.name, p.name_differentiator, p.product_option{$store}'); }
				elseif($tableord[$area]=='pricelist') { $pdo->SetOrder('p.product_id DESC'); }
				elseif($tableord[$area]=='picratio') { $pdo->SetOrder('p.filewidth / IF(p.fileheight=0, 1, p.fileheight)'); }
				else { $pdo->SetOrder('ord, birthdate'); }
				$pdo->WhereFields($f); $pdo->WhereValues($v);
		        $pdo->SetLimit("0,1");
				$joins = array();
				$joins[] = "LEFT JOIN store_location ON store_location.location1_id=p.location1_id{$store}";
				$joins[] = "LEFT JOIN store_type ON store_type.type_id=p.type_id";
				$joins[] = "LEFT JOIN store_manufacturer ON store_manufacturer.manufacturer_id=p.manufacturer_id";
				$n = $pdo->Prepare('SELECT', 'p.product_id', "store_product p ".implode($joins, ' '));
				$temp = $pdo->Execute();
				# echo $pdo->SQL();
			}
		} else {
			$recz[$area]=''; unset($thisrow);
		}
	}

	if(isset($_POST["Add1"])) {
		#if(mb_strlen($_POST['QRCode']) == 4 && ZExists($_POST['QRCode'])) {
		#	$err[] = "Cannot add this code, it already exists in the system.";
		#} else {
			$n = InsertRecord($area, '', (isset($_POST['ProdName'])?$_POST['ProdName']:''));
			if(!is_array($n)) {
				$err[] = "Could not add this code, error.";
			} else {
				$recz[$area] = $n[1];
				$thisrow = GetARecord($area, $recz[$area]);
			}
		#}

	} elseif(isset($_POST['deletethem']) && isset($_POST['confirm'])) {
		foreach($_POST as $vr => $vl) {
			if(substr($vr, 0, 5)=='mark_') {
				$n=substr($vr, 5);
				ExecuteSQLi("DELETE from $area WHERE z='{$n}' AND account_id=".$login->AcctID());
			}
		}

	} elseif(isset($_POST['linkthem'])) {
		foreach($_POST as $vr => $vl) {
			if(substr($vr, 0, 5)=='mark_') {
				$n=substr($vr, 5);
				$linkto = GetSQLRecordi($sql="SELECT product_id FROM store_product WHERE z='".FilterZ($n,4)."' AND account_id=".$login->AcctID());
				$linkfrom = GetSQLRecordi($sql="SELECT product_id FROM store_product WHERE z='".FilterZ($_POST['linkthemto'], 4)."' AND account_id=".$login->AcctID());
				if($linkto && $linkfrom) {
					ExecuteSQLi($sql="INSERT INTO store_links SET account_id=".$login->AcctID().", id1={$linkfrom['product_id']}, table1='product', id2={$linkto['product_id']}, table2='product', reason='related product', moddate=now(), birthdate=now()");
				}
				$recz['product'] = FilterZ($_POST['linkthemto'], 4);
				$thisrow = GetARecord('store_product', $recz['product']);
			}
		}

	} elseif(isset($_POST['multisave'])) {
		if(!isset($marked)) {
			$err[] = "No records were checked so none were updated.";
		} else {
			foreach($marked as $z) {
				if(isset($_POST['qty']) && $_POST['qty']!='') {
					ExecuteSQLi($sql="UPDATE {$area} SET qty{$store}='{$_POST['qty']}' WHERE {$area}.z='{$z}'");
					$fld['quantity'] = true;
				}
				if(isset($_POST['um']) && $_POST['um']!='') {
					ExecuteSQLi($sql="UPDATE {$area} SET um{$store}='{$_POST['um']}' WHERE {$area}.z='{$z}'");
					$fld['units'] = true;
				}
				if($area=='store_product') {
					if(isset($_POST['manufacturer_z']) && $_POST['manufacturer_z']!='0') {
						$setto = GetSQLRecordi("SELECT manufacturer_id FROM store_manufacturer WHERE z='".FilterZ($_POST['manufacturer_z'],4)."' AND account_id=".$login->AcctID());
						ExecuteSQLi("UPDATE store_product SET manufacturer_id='{$setto['manufacturer_id']}' WHERE z='{$z}' AND account_id=".$login->AcctID());
						$fld['manufacturer'] = true;
					}
					if(isset($_POST['location1_z']) && $_POST['location1_z']!='0') {
						$setto = GetSQLRecordi("SELECT location1_id{$store} FROM store_location WHERE z='".FilterZ($_POST['location1_z'],4)."' AND account_id=".$login->AcctID());
						ExecuteSQLi("UPDATE store_product SET location1_id{$store}='{$setto["location1_id"]}' WHERE z='{$z}' AND account_id=".$login->AcctID());
						$fld['type'] = true;
					}
					if(isset($_POST['type_z']) && $_POST['type_z']!='0') {
						$setto = GetSQLRecordi("SELECT type_id FROM store_type WHERE z='".FilterZ($_POST['type_z'],4)."' AND account_id=".$login->AcctID());
						ExecuteSQLi("UPDATE store_product SET type_id='{$setto['type_id']}' WHERE z='{$z}' AND account_id=".$login->AcctID());
						$fld['type'] = true;
					}
				}
			}
			unset($marked);
			if(isset($fld)) $msg[] = "The ".implode(", ", array_keys($fld)).' fields were updated in the checked records.';
		}

	} elseif(isset($_POST['saveall']) || isset($_POST['saveallcontinue'])) {
		$z='';
		foreach($_POST as $vr => $vl) {
			# name might be disabled, price always available to get $z
			$z='';
			if($area=='store_product') { # do not combine next
				if(substr($vr, 0, 6)=='price_' && substr($vr, -1) != "*") { # dont save for hidden marker field
					$z=substr($vr, 6);
				}
			} elseif(substr($vr, 0, 4)=='ord_' && substr($vr, -1) != "*") {
				# ord is editable by all
				$z=substr($vr, 4);
			}
			if($z) {
				$pdo = NEW myPDO(true);
				$f = array(); $v = array();
				if(isset($_POST["name_{$z}*"])) { 				$f[] = "name"; 					$v[] = $_POST["name_{$z}"]; }
				if(isset($_POST["keywords_{$z}*"])) { 			$f[] = "keywords"; 				$v[] = $_POST["keywords_{$z}"]; }
				if(isset($_POST["description_{$z}*"])) { 		$f[] = "description"; 			$v[] = $_POST["description_{$z}"]; }
				if(isset($_POST["ord_{$z}*"])) { 				$f[] = "ord"; 					$v[] = $_POST["ord_{$z}"]; }
				if(isset($_POST["parentz_{$z}*"])) { 			$f[] = "parentz"; 				$v[] = $_POST["parentz_{$z}"]; }
				if(isset($_POST["filename_{$z}*"])) { 			$f[] = "filename"; 				$v[] = $_POST["filename_{$z}"]; }
				if(isset($_POST["thumbfile_{$z}*"])) { 			$f[] = "thumbfile"; 			$v[] = $_POST["thumbfile_{$z}"]; }
				if(isset($_POST["url_{$z}*"])) { 				$f[] = "url"; 					$v[] = $_POST["url_{$z}"]; }
				if(isset($_POST["differentiator_{$z}*"])) { 	$f[] = "name_differentiator"; 	$v[] = $_POST["differentiator_{$z}"]; }
				if(isset($_POST["manufacturer_id_{$z}*"])) { 	$f[] = "manufacturer_id"; 		$v[] = $_POST["manufacturer_id_{$z}"]; }
				if(isset($_POST["type_id_{$z}*"])) { 			$f[] = "type_id"; 				$v[] = $_POST["type_id_{$z}"]; }
				if(isset($_POST["product_option_{$z}*"])) { 	$f[] = "product_option{$store}"; $v[] = $_POST["product_option_{$z}"]; }
				if(isset($_POST["sds_{$z}*"])) { 				$f[] = "sds"; 					$v[] = $_POST["sds_{$z}"]; }
				if(isset($_POST["cas_{$z}*"])) { 				$f[] = "cas"; 					$v[] = $_POST["cas_{$z}"]; }
				if(isset($_POST["price_{$z}*"])) { 				$f[] = "price{$store}"; 		$v[] = $_POST["price_{$z}"]; }
				if(isset($_POST["qty_{$z}*"])) { 				$f[] = "qty{$store}"; 			$v[] = $_POST["qty_{$z}"]; }
				if(isset($_POST["qty_discounts_{$z}*"])) { 		$f[] = "qty_discounts{$store}"; $v[] = $_POST["qty_discounts_{$z}"]; }
				if(isset($_POST["um_{$z}*"])) { 				$f[] = "um{$store}"; 			$v[] = $_POST["um_{$z}"]; }
				if(isset($_POST["NFS_{$z}*"])) { 				$f[] = "NFS{$store}"; 			$v[] = ($_POST["NFS_{$z}"]?1:0); }
				if(isset($_POST["location1_id_{$z}*"])) { 		$f[] = "location1_id{$store}"; 	$v[] = $_POST["location1_id_{$z}"]; }
				if(isset($_POST["location2_id_{$z}*"])) { 		$f[] = "location2_id{$store}"; 	$v[] = $_POST["location2_id_{$z}"]; }
				if(isset($_POST["location3_id_{$z}*"])) { 		$f[] = "location3_id{$store}"; 	$v[] = $_POST["location3_id_{$z}"]; }
				$f[] = "moddate=now()";
				# $f[] = "featured{$store}";
				$pdo->SetFields($f); $pdo->SetValues($v);
				$pdo->WhereFields(array('z', 'account_id'));
				$pdo->WhereValues(array($z, $login->AcctID()));
				if($pdo->Prepare('UPDATE', '*', $area)) {
					$r = $pdo->Execute();
				}
				SaveFileSize($_POST['filename_'.$z], $area, $z);
			}
		}
		if(!isset($v)) $err[] = "Saving a row requires that at least the name column have a value.";
		if(isset($_POST['saveallcontinue'])) $_POST['editthem']=true;

	} elseif(isset($_POST['SaveRecord']) && isset($_POST['z']) && $_POST['z']==FilterZ($_POST['z'], 4)) {
		# caller has marked fields with *, save those
		$pdo = NEW myPDO(true);
		$pdo->WhereFields($idfield);
		$pdo->WhereValues($thisrow[$idfield]);
		$f = array(); $v = array();
		$t = 'z';
		if(isset($_POST["{$t}*"]) && mb_strlen($_POST['z'])==4 && $_POST['z']==FilterZ($_POST['z'], 4)) {
			$f[] = 'z'; $v[] = $_POST['z'];
		}
		if(isset($_POST['parent_picker']) && $_POST['parent_picker'] != '0') {
			$f[] = 'parentz'; $v[] = $_POST['parent_picker'];
		}
		$t='name'; 		if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='keywords'; 	if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='ord'; 		if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='description'; if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='filename'; 	if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='thumbfile'; if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='url'; 		if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='price';
		if(isset($_POST["{$t}*"])) {
			if(!strpos($_POST[$t], "\t")===false) { $price = str_replace("\t", ';', $_POST[$t]); }
			else { $price = $_POST[$t]; }
			$f[] = "{$t}{$store}"; $v[] = $price;
		}
		$t="product_option"; 	if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='qty'; 				if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='um'; 				if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='qty_discounts'; 	if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='NFS'; 				if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = (isset($_POST[$t]) && $_POST[$t]?1:0); }
		$t='location1_id'; 		if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='location2_id'; 		if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='location3_id'; 		if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = $_POST[$t]; }
		$t='name_differentiator'; if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='type_id'; 			if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='manufacturer_id'; 	if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='sds'; 				if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		$t='cas'; 				if(isset($_POST["{$t}*"])) { $f[] = $t; $v[] = $_POST[$t]; }
		# $t = 'featured'; if(isset($_POST["{$t}*"])) { $f[] = $t.$store; $v[] = (isset($_POST[$t]) && $_POST[$t]?1:0);

		$f[] = 'moddate=now()';

		$pdo->SetFields($f); $pdo->SetValues($v);
		if($pdo->Prepare('UPDATE', '*', $area)) {
			if($pdo->Execute()) {
				# echo $pdo->SQL;
				$n = $pdo->GetResult();
				$thisrow = GetARecord($area, $thisrow[$idfield], $idfield); # keep edit view
				SaveFileSize($thisrow['filename'], $area, $thisrow['z']);
				# $recz[$area]=0; unset($thisrow); # go back to browse
			} else {
				$err[] = "Failed to save {$area}.";
				echo $pdo->SQL();
			}
		} else {
			$err[] = "Failed to prepare statement.";
			echo $pdo->SQL();
		}
		foreach($_POST as $vr => $vl) {
			if(substr($vr, 0, 10)=='delparent_') {
				$n=intval(substr($vr, 10));
				ExecuteSQLi("DELETE FROM store_links WHERE link_id={$n} AND account_id=".$login->AcctID());
			} elseif(substr($vr, 0, 9)=='delchild_') {
				$n=intval(substr($vr, 9));
				ExecuteSQLi("DELETE FROM store_links WHERE link_id={$n} AND account_id=".$login->AcctID());
			}
		}

	} elseif(isset($_FILES) && isset($_POST['Upload'])) {
		# Array ( [file] => Array ( [name] => vida-fel-logo.jpg [type] => image/jpeg [tmp_name] => /tmp/phpIni8q7 [error] => 0 [size] => 225211 ) '
		if(isset($_FILES["file"]["error"]) && $_FILES["file"]["error"] > 0) {
			$err[] = "Error ".$_FILES["file"]["error"].' while uploading.';
		} elseif(isset($_FILES["file"]["name"])) {
			#echo '<br />$_FILES: '; print_r($_FILES);
			#echo '<br />$_POST: '; print_r($_POST);
			#echo '<br />$thisrow: '; print_r($thisrow);
			if($_FILES["file"]["type"] != 'application/pdf' && $_FILES["file"]["type"] != 'image/jpeg' && $_FILES["file"]["type"] != 'image/gif' && $_FILES["file"]["type"] != 'image/png' && $_FILES["file"]["type"] != 'image/svg+xml') {
				$err[] = "Only jpg/gif/png/svg/pdf image uploads allowed.";
			} else {
				if($_FILES["file"]["type"] == 'image/jpeg') $ex = 'jpg';
				elseif($_FILES["file"]["type"] == 'image/png') $ex = 'png';
				elseif($_FILES["file"]["type"] == 'image/gif') $ex = 'gif';
				elseif($_FILES["file"]["type"] == 'image/svg+xml') $ex = 'svg';
				else $ex = 'pdf';
				# if one exists of another extension
				if(!file_exists('/var/www/plainsman/store/files')) mkdir('files');
				if(!file_exists(STOREFILEDIR.$thisrow['store_id'])) mkdir(STOREFILEDIR.$thisrow['store_id']);
				if(!file_exists(STOREFILEDIR.$thisrow['store_id'])) echo "Could not create files/".$thisrow['store_id'];
				$fieldname='filename';
				if(isset($_POST['SaveAsThumb'])) $fieldname='thumbfile';
				if(file_exists(STOREFILEDIR.$thisrow['store_id'])) {
					$dest = STOREFILEDIR.$thisrow['store_id']."/{$thisrow[$fieldname]}";
					if($thisrow[$fieldname] != '' && file_exists($dest)) {
						$err[] = "Deleted existing {$thisrow[$fieldname]}";
						unlink($dest);
						if(file_exists($dest)) $err[]="Unable to remove existing file.";
					}
					if($ex=='pdf') $dest = STOREFILEDIR.$thisrow['store_id']."/pdf/{$thisrow['z']}.pdf";
					else $dest = STOREFILEDIR.$thisrow['store_id']."/{$thisrow['z']}.{$ex}";
					if(file_exists($dest)) {
						unlink($dest);
						if(file_exists($dest)) $err[]="Unable to delete existing file.";
					}
					copy($_FILES["file"]["tmp_name"], $dest);
					if(!file_exists($dest)) {
						$err[] = "Error: {$_FILES["file"]["tmp_name"]} not copied to {$dest}!";
					} else {
						# echo "Successfully uploaded {$dest}";
						$pdo = NEW myPDO(true);
						$pdo->WhereFields($idfield);
						$pdo->WhereValues($thisrow[$idfield]);
						$pdo->SetFields(array($fieldname, 'moddate=now()'));
						$pdo->SetValues(array("{$thisrow['z']}.{$ex}"));
						if($pdo->Prepare('UPDATE', '*', $area)) {
							if($pdo->Execute()) {
								$n = $pdo->GetResult();
								$thisrow = GetARecord($area, $thisrow[$idfield], $idfield);
								if($fieldname=='filename') SaveFileSize($thisrow['filename'], $area, $thisrow['z']);
							} else {
								$err[] = "Failed to save {$area} file {$thisrow['z']}.{$ex}.";
							}
						}
						$thisrow = GetARecord($area, $recz[$area]); # so picture will display
					}
				}
			}
		}

	} elseif(isset($_FILES) && isset($_POST['BatchUpload'])) {
		if(isset($_FILES["file"]["error"]) && $_FILES["file"]["error"] > 0) {
			$err[] = "Error ".$_FILES["file"]["error"].' while uploading.';
		} elseif(isset($_FILES["zipfile"]["name"])) {
			if($_FILES["zipfile"]["type"] != 'application/zip' && $_FILES["zipfile"]["type"] != 'application/x-zip-compressed') {
				$err[] = "Only zip files allowed.";
			} else {
				if(!file_exists("/var/www/plainsman/cache/")) {
					mkdir("/var/www/plainsman/cache/");
					if(!file_exists("/var/www/plainsman/cache/")) {
						echo "Failed to create cache folder";
					}
				} else {
					$filename = "/var/www/plainsman/cache/".$_FILES["zipfile"]["name"];
					echo "Moving ZIP file to $filename.";
					$source = $_FILES["zipfile"]["tmp_name"];
					$target_path = $filename;  // change this to the correct site path
					if(move_uploaded_file($source, $target_path)) {
						$za = new ZipArchive(); 
						$res = $za->open($filename);
						if($res === true) {
							echo "Finding products having same name as files.";
							for( $i = 0; $i < $za->numFiles; $i++ ) { 
							    $stat = $za->statIndex( $i ); 
							    $fname = basename($stat['name']);
							    $fl = substr($fname, 0, 1);
							    if($fl != '.' && $fl != '_') {
								    echo "<p>Filename '$fname': ";
									$pdo = NEW myPDO(true);
									$pdo->WhereFields(array('name', 'type_id', 'manufacturer_id', 'account_id')); # 
									$pdo->WhereValues(array(before($fname,'.'), $thisrow['type_id'], $thisrow['manufacturer_id'], $login->AcctID()));
									$temp = $pdo->Prepare('SELECT', 'z, product_id', "store_product");
									$found = $pdo->Execute();
									if($found) {
										echo "Found in database. ";
										$STH = $pdo->GetResult();
										$row = $STH->fetch(PDO::FETCH_ASSOC);
										# echo "Found. "; print_r($row);
										$z1=$row['z'];
										$nname = $z1.'.'.after($fname, '.');
										$target = STOREFILEDIR.$thisrow['store_id'].'/';
		                    			if(file_exists($target.$fname)) { echo "Erasing existing {$target}{$fname}. "; unlink($target.$fname); }
		                    			$za->extractTo($target, $fname);
										if(!file_exists($target.$fname)) {
											echo "Extraction failed! ";
										} else {
			                    			rename($target.$fname, $target.$nname);
											if(!file_exists($target.$nname)) {
												echo "Extraction succeeded, rename failed. ";
				                    		} else {
				                    			echo "Extracted and renamed to {$target}{$nname}.<br />";
												$f=array(); $vl=array();
			                    				if(isset($_POST['SaveAsThumbs'])) $temp='thumbfile'; else $temp='filename';
												$f[] = $temp; $vl[]=$nname;
												$f[] = 'moddate=now()';
												$pdo = NEW myPDO(true);
												$pdo->SetFields($f); $pdo->SetValues($vl);
												$pdo->WhereFields(array('product_id')); 
												$pdo->WhereValues(array($row['product_id']));
												if($pdo->Prepare('UPDATE', '*', "store_product")) {
													$r = $pdo->Execute();
												}
											}
										}
									} else {
										echo "Not found in database. ";
										$z2 = InsertRecord($area, '', before($fname,'.'), array('ord'=>$_POST['ord'], "price{$store}"=>$_POST['price'], "qty{$store}"=>$_POST['qty'], "um{$store}"=>$_POST['um'], 'manufacturer_id'=>$_POST['manufacturer_id'], "location1_id{$store}"=>$_POST["location1_id"], 'type_id'=>$_POST['type_id']));
										if(!is_array($z2)) {
											$err[] = "Could not add $fname database record."; break;
										} else {
											$nname = $z2[1].'.'.after($stat['name'], '.');
											$target = STOREFILEDIR.$thisrow['store_id'].'/';
			                    			$za->extractTo($target, $stat['name']);
			                    			echo "Extracting. ";
			                    			rename($target.$fname, $target.$nname);
			                    			if(file_exists($target.$nname)) {
			                    				if(isset($_POST['SaveAsThumbs'])) $temp='thumbfile'; else $temp='filename';
				                    			echo "Renaming to $nname. Updating {$temp}='{$nname}'. ";
			                        			ExecuteSQLi($sql="UPDATE store_product SET {$temp}='{$nname}' WHERE z='{$z2[1]}' AND account_id=".$login->AcctID());
			                        		} else {
			                        			echo "Failed to extract or rename picture.";
			                        		}
										}
									}
								}
							}
							$za->close();
							unlink($target_path);
							if(isset($z2) && is_array($z2)) {
								$recz[$area] = $z2[1]; $thisrow = GetARecord($area, $recz[$area]); # position to last one
							} elseif(isset($z1)) {
								$recz[$area] = $z1; $thisrow = GetARecord($area, $recz[$area]); # position to last one
							}
						}
					} else {
						echo "Could not move $source to $target_path.";
					}
				}
			}
		}
	} elseif(isset($_POST['AddOne'])) {
		# echo "Adding {$thisrow['name']}, ";
		$temp = MakeQRCode(4); while(ZExists($temp)) $temp = MakeQRCode(4);
		$f=array(); $vl=array();
		$f[]="z"; $vl[]=$temp;
		$f[]="name"; $vl[]=$thisrow["name"].' copy';
		if($area=='store_product') {
			if($thisrow["product_option{$store}"]) {
				$f[]="product_option{$store}"; $vl[]=$thisrow["product_option{$store}"];
			}
			$f[]="NFS{$store}"; $vl[]=$thisrow["NFS{$store}"];
			$f[]="qty{$store}"; $vl[]=$thisrow["qty{$store}"];
			$f[]="qty_discounts{$store}"; $vl[]=$thisrow["qty_discounts{$store}"];
			$f[]="um{$store}"; $vl[]=$thisrow["um{$store}"];
			$f[]="location1_id{$store}"; $vl[]=$thisrow["location1_id{$store}"];
			$f[]="manufacturer_id"; $vl[]=$thisrow["manufacturer_id"];
			$f[]="type_id"; $vl[]=$thisrow["type_id"];
		}
		$f[]="account_id"; $vl[]=$login->AcctID();
		$f[]="store_id"; $vl[]=$login->Store();
		$f[]='moddate=now(), birthdate=now()';
		$pdo = NEW myPDO(true);
		$pdo->SetFields($f); $pdo->SetValues($vl);
		if($pdo->Prepare('INSERT', '*', $area)) {
			$r = $pdo->Execute();
			$recz[$area] = $temp;
			$thisrow = GetARecord($area, $recz[$area]);
		}

	} elseif(isset($_POST['AddMoreLikeThis'])) {
		if(!isset($_POST['names']) || $_POST['names']=='') {
			$err[] = "No product names supplied, could not add.";
		} elseif(1==1) {
			if($store==1 && $area=='store_product' && !isset($_POST['UpgradeNamesOnly'])) {
				echo "<p>Cannot import table data in store 1.</p>";
			} elseif(!strpos($_POST['names'], "\n")===false) { # lines with prices?
				if($thisrow["um{$store}"]) {
					$temp = explode(';', $thisrow["um{$store}"]); $numunits=count($temp);
				} else {
					$numunits = 1;
				}
				$temp = explode("\n", $_POST['names']);
				foreach($temp as $item) {
					if($item) {
						if(!strpos($item, "\t")===false) {
							$temp=trim(after($item, "\t"));
							$subscript=trim(before($item, "\t"));
							$names[$subscript]['prices']=''; # only names
							$names[$subscript]['picture']='';
							$names[$subscript]['thumbfile']='';
							if($temp!='') {
								$col = explode("\t", $temp);
								if(!is_numeric($col[0])) { # col1 already parsed off as name
									$priceerror=true;
									echo 'Error: Product "'.$subscript.'" has an unexpected non-numeric value in the second column<br />';
									break;
								} else {
									$v=0;
									if(count($col)==1) {
										$names[$subscript]['prices']=$col[0];
									} else {
										$v1=array();
										foreach($col as $slice) { # name, prices, pictures
											if(substr($slice, 0, 1)=='$') { # a price
												$v1[]=substr($slice,1);
											} elseif(is_numeric($slice)) { # a price
												$v1[]=$slice;
											} elseif($names[$subscript]['picture']=='') { # prioritize picture
												$names[$subscript]['picture']=$slice;
												$picturesgiven=true;
											} else {
												$names[$subscript]['thumbfile']=$slice;
												$thumbsgiven=true;
											}
										}
										if(count($v1) != $numunits) {
											$priceerror=true;
											echo 'Product "'.$subscript.'" being imported has '.count($v1).' prices (however the same product already exists but it has '.($numunits==1?'only one unit':$numunits).'). Not updating.<br />';

										}
										if($v1) $names[$subscript]['prices']=implode($v1, ';');
									}
								}
							}
						}
					}
				}
				if(!isset($priceerror)) echo 'Importing: ';
			} elseif($_POST['names']) {
				$names[$_POST['names']]=0; # add one
			}
			if(isset($priceerror)) {
				echo "<p>Price Error (aborting this import): Import text not formatted as expected. The number of cost columns must match the number of units in the pattern product. The second column must be numeric.</p>";
			} else {
				foreach($names as $name => $v) {
					if($area=='store_product') {
						$f=array(); $vl=array();
						# can upload (will go to store folder) but not change name if not my own
						if(isset($picturesgiven) && $thisrow['store_id']==$store) {
							$f[]='filename';
							if($v['picture'] && $thisrow['filename']) {
								$x = strrpos($thisrow['filename'], '/');
								$vl[] = substr($thisrow['filename'],0,$x).'/'.$v['picture'];
							} elseif($v['picture']) {
								$vl[] = $v['picture'];
							} else {
								$vl[] = $thisrow['filename'];
							}
						}
						if(isset($thumbsgiven) && $thisrow['store_id']==$store) { # only update if my own
							# https://www.spectrumglazes.com/mainimages/900s/900.jpg
							$f[]='thumbfile';
							if($v['thumbfile'] && $thisrow['thumbfile']) {
								$x = strrpos($thisrow['thumbfile'], '/');
								$vl[] = substr($thisrow['thumbfile'],0,$x).'/'.$v['thumbfile'];
							} elseif($v['thumbfile']) {
								$vl[] = $v['thumbfile'];
							} else {
								$vl[] = $thisrow['thumbfile'];
							}
						}
						if($thisrow['ord']) { $f[]='ord'; $vl[]=$thisrow['ord']; }
						if($v['prices']) {
							$f[]="price{$store}"; $vl[]=$v['prices'];
						} elseif($v['prices']>0) {
							$f[]="price{$store}"; $vl[]=$thisrow["price"];
						}
						$f[]='manufacturer_id'; $vl[]=$thisrow['manufacturer_id'];
						$f[]='parentz'; $vl[]=(isset($thisrow['parent_picker'])?$thisrow['parent_picker']:'');
						if($thisrow["location1_id"]) { $f[]="location1_id{$store}"; $vl[]=$thisrow["location1_id"]; }
						$f[]='type_id'; $vl[]=$thisrow['type_id'];
						# search here for same name, type, manufacturer, acctid
						$pdo = NEW myPDO(true);
						if(isset($_POST['UpgradeNamesOnly'])) {
							# $pdo->WhereFields(array("'{$name}' RLIKE name AND type_id={$thisrow['type_id']} AND manufacturer_id={$thisrow['manufacturer_id']} AND account_id=".$login->AcctID()));
							$pdo->WhereFields(array('type_id', 'manufacturer_id', 'account_id', "'{$name}' RLIKE name")); # 
							$pdo->WhereValues(array($thisrow['type_id'], $thisrow['manufacturer_id'], $login->AcctID()));
						} else {
							$pdo->WhereFields(array('name', 'type_id', 'manufacturer_id', 'account_id')); # 
							$pdo->WhereValues(array($name, $thisrow['type_id'], $thisrow['manufacturer_id'], $login->AcctID()));
						}
						$n = $pdo->Prepare('SELECT', 'product_id', "store_product");
						# print_r($thisrow); echo '<br />';
						$found = $pdo->Execute();
						if($found) {
							$STH = $pdo->GetResult();
							$row = $STH->fetch(PDO::FETCH_ASSOC);
						} else $found=false;
						if($found) {
							echo " updating {$name}, ";
							if(isset($_POST['UpgradeNamesOnly'])) {
								$f=array(); $vl=array();
								$f[] = 'name'; $vl[]=$name;
							} else {
								$f[]="qty{$store}"; $vl[]=$thisrow["qty{$store}"];
								$f[]="um{$store}"; $vl[]=$thisrow["um{$store}"];
							}
							$f[] = 'moddate=now()';
							$pdo = NEW myPDO(true);
							$pdo->SetFields($f); $pdo->SetValues($vl);
							$pdo->WhereFields(array('product_id')); 
							$pdo->WhereValues(array($row['product_id']));
							if($pdo->Prepare('UPDATE', '*', "store_product")) {
								$r = $pdo->Execute();
								# $recz[$area] = $n[1];
							}
						} elseif(!isset($_POST['UpgradeNamesOnly'])) {
							echo " <b>adding {$name}</b>, ";
							$temp = MakeQRCode(4); while(ZExists($temp)) $temp = MakeQRCode(4);
							$f[]="z"; $vl[]=$temp;
							$f[]="qty{$store}"; $vl[]=$thisrow["qty{$store}"];
							$f[]="um{$store}"; $vl[]=$thisrow["um{$store}"];
							$f[]="account_id"; $vl[]=$login->AcctID();
							$f[]="store_id"; $vl[]=$login->Store();
							$f[] = 'name'; $vl[]=$name;
							$f[] = 'moddate=now(), birthdate=now()';
							$pdo = NEW myPDO(true);
							$pdo->SetFields($f); $pdo->SetValues($vl);
							if($pdo->Prepare('INSERT', '*', "store_product")) {
								$r = $pdo->Execute();
								$recz[$area] = $temp;
							}
						} else echo " no action taken with {$name}, ";
						$added = true;
					} else {
						$added = true;
						$n = InsertRecord($area, '', trim($name), array('ord'=>$thisrow['ord'])); #name, order, new code
						if(!is_array($n)) $err[] = "Could not add $name, error.";
						else $recz[$area] = $n[1];
					}
					# add link records
					if(1==1) {
						if(!defined('PHONE')) $history[$n[1]] = array('area'=>$area, 'name'=>trim($name));
						$sql = "SELECT id1, table1, id2, table2 FROM store_links WHERE id2={$thisrow[$idfield]} AND table2='{$area}'";
						$result1 = ExecuteSQLi($sql);
						if($result1->num_rows > 0) {
							while($row1 = $result1->fetch_assoc()) {
								InsertLinkRecord($row1['table1'], $row1['id1'], $area, $n[0]);
							}
						}
					}
				}
				if(isset($added)) {
					$thisrow = GetARecord($area, $recz[$area]);
					if(isset($picturefile)) unset($picturefile);
				}
			}
		}

	} elseif(isset($_POST['LinkParent'])) {
		if(!mb_strpos($_POST['link_picker'], ':') === false) {
			if($_POST['link_direction']=='0') {
				$err[] = 'You must choose a link direction.';
			} else {
				list($z, $t) = explode(':', $_POST['link_picker']);
				$t1 = ValidArea($t);
				if($t1 !== false) {
					$id1 = GetIDFromZ($z, $t1);
					if($id1 !== false) {
						$id2 = $thisrow['z'];
						if($_POST['link_direction']=='child') echo InsertLinkRecord($t, $id1, $area, $thisrow[$idfield]);
						else echo InsertLinkRecord($area, $thisrow[$idfield], $t, $id1);
					}
				}
			}
		}
	}

	# Delete picture, record
	if(isset($_GET["delete"]) && $thisrow['account_id']==$login->AcctID()) {
		ExecuteSQLi($sql="DELETE FROM $area WHERE {$idfield}={$thisrow[$idfield]}");
		ExecuteSQLi($sql="DELETE FROM store_link WHERE id1={$thisrow[$idfield]} AND table1='{$area}'");
		ExecuteSQLi($sql="DELETE FROM store_link WHERE id2={$thisrow[$idfield]} AND table2='{$area}'");
		$recz[$area]=0; unset($thisrow);
	} elseif(isset($_GET["deletepicture"]) && $store) {
		$dest = STOREFILEDIR.$thisrow['store_id'].'/'.$thisrow['filename'];
		if(file_exists($thisrow['filename'] != '' && $dest)) {
			$msg[] = "Deleted existing {$thisrow['filename']}";
			unlink($dest); # could be PNG and replacing with JPG
		}
		$dest = STOREFILEDIR.$thisrow['store_id'].'/'.$thisrow['thumbfile'];
		if(file_exists($thisrow['thumbfile'] != '' && $dest)) {
			$msg[] = "Deleted thumbnail {$thisrow['thumbfile']}";
			unlink($dest); # could be PNG and replacing with JPG
		}
		ExecuteSQLi("UPDATE $area SET filename='', thumbfile='' WHERE z='{$thisrow['z']}' && account_id=".$login->AcctID());
		$thisrow = GetARecord($area, $thisrow[$idfield], $idfield);
	}

} else { # no area open
	for($x=1; $x<3; $x++) {
		if(isset($_FILES) && isset($_POST['UploadMap'.$x])) {
			if(isset($_FILES["file"]["error"]) && $_FILES["file"]["error"] > 0) {
				$err[] = "Error ".$_FILES["file"]["error"].' while uploading.';
			} elseif(isset($_FILES["file"]["name"])) {
				if($_FILES["file"]["type"] != 'image/svg+xml') {
					$err[] = "Only svg image upload allowed.";
				} else {
					$dest = STOREFILEDIR.$thisrow['store_id'].'/map'.$x.'.svg';
					if(file_exists($dest)) unlink($dest);
					copy($_FILES["file"]["tmp_name"], $dest);
					$err[] = "Copying ".$_FILES["file"]["tmp_name"].' to '.$dest;
					if(!file_exists($dest)) $err[] = 'Error: Was not copied!';
				}
			}
		}
	}
}

$metakeys = 'Plainsman Products';
$metadescrip = 'Find products, shop.';
$metatitle = 'Plainsman Store';
# if(defined('PHONE')) 
$hidenavbar=true;
include('inc/bootstrapheader.php');

if(defined("PHONE")) $bf[] = '<div class="container">';
else $bf[] = '<div style="padding:0px 10px 10px 10px;">';


# get area menu tool tips
if(isset($recz['location1']) && $recz['location1'] != '') {
	$j1[] = "LEFT JOIN store_location lo ON lo.z='{$recz['location1']}'";
	$j2[] = "lo.name AS loname";
}
if(isset($recz['manufacturer']) && $recz['manufacturer'] != '') {
	$j1[] = "LEFT JOIN store_manufacturer ma ON ma.z='{$recz['manufacturer']}'";
	$j2[] = "ma.name AS maname";
}
if(isset($recz['type']) && $recz['type'] != '') {
	$j1[] = "LEFT JOIN store_type lo ON ty.z='{$recz['type']}'";
	$j2[] = "ty.name AS tyname";
}

if(isset($j1)) $mrow = GetSQLRecordi("SELECT li.link_id, ".implode($j2, ', ').' FROM store_links li '.implode($j1, ' ').' LIMIT 0,1');
# area menu
foreach($tablearr as $item => $a) {
	if(isset($area) && $item == $area) {
		$areas[] = '<a href="?'.MakeLink(array('area'=>$item, 'edit'=>0)).'"><span style="background-color:black; padding:2px 5px 2px 5px; color:white;">'.ProperAreaName($item).'s</span></a>'; # , 'prodfilter'=>0
	} elseif(isset($mrow)) {
		$temp = '<a ';
		if(isset($mrow["{$a['abbr']}name"])) $temp .='title="'.$mrow["{$a['abbr']}name"].'" ';
		$temp .= 'href="?'.MakeLink(array('area'=>$item)).'">'.ProperAreaName($item).'s</a>';
		$areas[] = $temp;
	} else {
		$areas[] = '<a href="?'.MakeLink(array('area'=>$item)).'">'.ProperAreaName($item).'s</a>';
	}
}
$bf[] = "\n<p>";
$bf[] = '<a href="https://plainsmanclays.com">Home</a> | ';
$bf[] = '<a href="https://plainsmanclays.com/store/ProductDatabaseHelp.pdf">Help</a> | ';
for($x=1; $x<3; $x++) {
	if(isset($_GET['map'])) $bf[] = '<span style="background-color:black; padding:2px 5px 2px 5px; color:white;">Map '.($x).'</span>';
	else $bf[] = '<a href="?map'.($x).'=yes">Map '.($x).'</a> | ';
}
$bf[] = (isset($areas)?implode($areas, " | \n").'</a>':'');
if($login->Mexico()) {
	$temp = array();
	for($n=1; $n<=NUMSTORES; $n++) {
		if($store==$n) $temp[] = '<span style="background-color:black; color:white; font-weight:bold; padding:0px 2px 0px 2px">'.$storenames[$n].'</span>';
		else $temp[] = $n; # '<a href="?store='.$n.'&'.MakeLink().'">'.$n.'</a>';
	}
	$bf[] = ' - (Store #'.implode($temp, '|').')';

	$result = ExecuteSQLi($sql="SELECT firstname, z, store_id FROM people WHERE store_id>0 ORDER BY store_id");
	if($result->num_rows > 0) {
		$temp = array();
		while($row = $result->fetch_assoc()) {
			if($row['firstname']==$login->Field('firstname')) $temp[$row['firstname']]='<span style="background-color:black; color:white;">'.$row['firstname'].'</span>';
			else $temp[$row['firstname']] = '<a href="?pz='.$row['z'].'&'.MakeLink().'">'.$row['firstname'].'</a>';
		}
		$bf[] = ' - '.implode($temp, '|');
	}
}

# message
if(isset($msg)) {
	$bf[] = '<p style="background-color:green; padding:4px 10px 5px 10px; border:thin solid black;">'.implode($msg, ' ').'</p>';
}
if(isset($err)) {
	$bf[] = '<p style="background-color:yellow; padding:4px 10px 5px 10px; border:thin solid black;">'.implode($err, ' ').'</p>';
} elseif(isset($history) && is_array($history)) {
	$bf[] = '<p style="background-color:#dddddd; padding:4px 10px 5px 10px; border:thin solid black; font-size:80%;">Last Ten: ';
	$temp = '';
	foreach($history as $k => $col) {
		$temp = '<a href="?'.MakeLink(array('edit'=>$k, 'area'=>$col['area'])).'">'.ProperAreaName($col['area']).'-'.trim($col['name']).'</a> | ';
		$bf[] = $temp;
	}
	$bf[] = '<a href="?'.MakeLink(array('clearhistory'=>'yes')).'">Clear</a></p>';
}

$bf[] = '<form method="POST" name="wholepage" action="?'.MakeLink().'">';

# hidden fields
$bf[] = '<input type="hidden" name="area" value="'.$area.'">';
$bf[] = '<input type="hidden" name="haspictures" value="'.$haspictures.'">';
$bf[] = '<input type="hidden" name="haslocation1" value="'.$haslocation1.'">';
$bf[] = '<input type="hidden" name="hasprice" value="'.$hasprice.'">';
$bf[] = '<input type="hidden" name="hastype" value="'.$hastype.'">';
$bf[] = '<input type="hidden" name="RcpGroup" value="'.$RcpGroup.'">';
if(isset($history) && is_array($history)) $bf[] = '<input type="hidden" name="history" value="'.base64_encode(serialize($history)).'">';
if(isset($prodfilter)) $bf[] = '<input type="hidden" name="prodfilter" value="'.$prodfilter[0].':'.$prodfilter[1].'">';
if(isset($findstr) && $findstr) $bf[] = '<input type="hidden" name="findstr" value="'.$findstr.'">'; # filter_var($findstr, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH)
foreach($tablearr as $t => $a) {
	if(isset($tableord[$area])) $bf[] = '<input type="hidden" name="'."{$t}ord".'" value="'.$tableord[$area].'">';
	if($area == $t && $recz[$area]) {
		$bf[] = '<input type="hidden" name="z" value="'.$recz[$area].'">';
	} elseif($recz[$t]) {
		$bf[] = '<input type="hidden" name="z" value="'.$recz[$t].'">';
	}
}

# show maps? area not set so next two sections will fail
for($x=1; $x<3; $x++) {
	if(isset($_GET["map{$x}"])) {
		$mapfile = STOREFILEDIR.$store.'/map'.($x).'.svg';
		if(!file_exists('/var/www/plainsman/store/files')) mkdir('/var/www/plainsman/store/files');
		if(!file_exists(STOREFILEDIR.$store)) mkdir(STOREFILEDIR.$store);
		if(!file_exists(STOREFILEDIR.$store)) echo "Could not create files/".$store;
		if(file_exists($mapfile)) $bf[] = '<p><img src="'.$mapfile.'" style="width:100%" /></p>';
		$bf[] = '<h3>Upload Map '.($x).'</h3>';
		$bf[] = "\n".'</form><p><form method="post" name="mapupload" enctype="multipart/form-data" action="?'.MakeLink(array('map'=>'yes')).'">
		<input type="file" name="file" />';
		$bf[] = '<input type="submit" name="UploadMap'.($x).'" value="Upload Map" /></p>';
	}
}

$bf[] = '<p>';
$bf[] = FieldPut(array('securitytype'=>'searchalphanum', 'name'=>'what2find', 'width'=>'150px', 'maxlength'=>'20', 'value'=>$findstr)); # filter_var($findstr, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH))
$bf[] = '<input type="submit" value="Find" name="findit">';
$bf[] = '<input type="submit" value="Find All" name="findall">';

if(isset($area) && isset($thisrow) && $thisrow) {
	#$bf[] = '<h1>'.ProperAreaName($area).' <span style="font-style:italic; color:#888888;">'.$thisrow['z'].'</span></h1>';
	if($area=='store_location') $f='location1:'.$thisrow["location_id"];
	elseif($area=='store_type') $f='type:'.$thisrow["type_id"];
	elseif($area=='store_manufacturer') $f='manufacturer:'.$thisrow["manufacturer_id"];
	else $f='';

	$bf[] = '</p><table><tr><td><b>'.(isset($_POST['AddOne'])?'New ':'').ProperAreaName($area).' Name</b>';
	if($f) {
		$temp = ' (';
		$temp.='<a href="?'.MakeLink(array('area'=>'product', 'findstr'=>'', 'edit'=>'0', 'prodfilter'=>$f)).'">Browse</a>';
		$temp.=', <a target="_blank" href="pricelist.php?type='.$thisrow['type_id'].'">Price Preview</a>';
		$temp.=' or <a target="_blank" href="index.php?type='.$thisrow['type_id'].'">Shop Preview</a>';
		$temp.=' these products)';
	} else $temp='';
	$bf[] = $temp.'<br />';
	# name (SaveRecord)
	$farray = array('securitytype'=>'alphanum', 'name'=>'name', 'width'=>(defined('PHONE')?'100%':'450px'), 'maxlength'=>'99', 'value'=>$thisrow['name']);
	$farray['style'] = 'font-size:130%; font-weight:bold; color:red;';
	$farray['other']=array();
	if($store != $thisrow['store_id']) { # edit if created by us
		$farray['other'][]='disabled="disabled"';
		$bf[] = FieldPut($farray);
	} else $bf[] = FieldPut($farray, true);
	$bf[] = '</td>';

	if($area=='store_product') {
		$bf[] = '<td style="padding-left:10px;"><b>Differentiator</b><br />';
		# name_differentiator
		$farray = array('securitytype'=>'alphanum', 'name'=>'name_differentiator', 'width'=>'30px', 'maxlength'=>'1', 'value'=>$thisrow['name_differentiator']);
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Use values here to group options in products having the same name (for multiple pictures each of which show some of the options).";
		}
		if($store != $thisrow['store_id']) {
			$farray['other'][]='disabled="disabled"';
			$bf[] = FieldPut($farray);
		} else $bf[] = FieldPut($farray, true);
		$bf[] = '</td>';
	}
	$bf[] = '</tr></table>';

	if($area=='store_product') {
		# what stores are pricing it?
		$s=array(); $asterisk=false; $temp='';
		for($x=1; $x<=NUMSTORES; $x++) {
			if($thisrow["price{$x}"]>0) {
				$temp=$storenames[$x];
				if($thisrow["NFS{$x}"]) {
					$temp .= '*'; # priced but nfs
					$asterisk=true;
				}
				$s[] = $temp;
			}
		}
		$bf[] = '<i style="font-size:80%; color:#888888;">Master: '.$storenames[$thisrow['store_id']];
		if($s) $bf[] = " (priced in ".implode($s, ', ').')'.($asterisk?' *NFS':'').'<br /><br />';
		$bf[] = '</i>';

		# price
		$price = $thisrow["price{$store}"];
		$farray = array('securitytype'=>'alphanum', 'name'=>'price', 'width'=>'250px', 'maxlength'=>'60', 'value'=>$price);
		$farray['other']=array();
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Separate multiple prices with semi-colons. The presence of a price puts a product on-sale (unless NFS is checked).";
		}
		$bf[] = '<table><tr>';
		$bf[] = '<td><b>Price</b><br />'.FieldPut($farray, true).'</td>';

		# product_option
		$productoption = $thisrow["product_option{$store}"];
		$farray['other']=array();
		$farray = array('securitytype'=>'alphanum', 'name'=>'product_option', 'width'=>'250px', 'maxlength'=>'25', 'value'=>$productoption);
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Specify color, size, etc. Different options for products having the same name with list under one name in the pricelist or online store.";
		}
		$bf[] = '<td><b>Option</b>';
		# if this different than the master for this product?
		if($store != $thisrow['store_id'] && $productoption=='' && $thisrow["product_option{$thisrow['store_id']}"]!='') 
			$bf[] = '<span style="font-style:italic; color:#888888;">(Master: '.htmlentities($thisrow["product_option1"]).')</span>';
		$bf[] = '<br />'.FieldPut($farray, true).'</td>';
		$bf[] = '</tr><tr>';

		# um
		$um = $thisrow["um{$store}"];
		$farray = array('securitytype'=>'alphanum', 'name'=>'um', 'width'=>'250px', 'maxlength'=>'59', 'value'=>$um);
		$farray['other']=array();
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Separate multiple units of measure with semi-colons";
		}
		$bf[] = '<td><b>U/M</b>';
		if($store != $thisrow['store_id'] && $um=='' && $thisrow["um{$thisrow['store_id']}"]!='') 
			$bf[] = '<span style="font-style:italic; color:#888888;">(Master: '.htmlentities($thisrow["um1"]).')</span>';
		$bf[] = '<br />'.FieldPut($farray, true).'</td>';

		# qty_discounts
		$qtydiscounts = $thisrow["qty_discounts{$store}"];
		$farray = array('securitytype'=>'alphanum', 'name'=>'qty_discounts', 'width'=>'250px', 'maxlength'=>'60', 'value'=>$qtydiscounts);
		$farray['other']=array();
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Separate multiple with semi-colons, number of items must be the same as the number of prices.";
		}
		$bf[] = '<td><b>Qty Discounts</b>';
		if($store != $thisrow['store_id'] && $qtydiscounts=='' && $thisrow["qty_discounts{$thisrow['store_id']}"]!='') 
			$bf[] = '<span style="font-style:italic; color:#888888;">(Master: '.htmlentities($thisrow["qty_discounts1"]).')</span>';
		$bf[] = '<br />'.FieldPut($farray, true).'</td>';
		$bf[] = '</tr></table>';

		# qty
		#$qty = $thisrow["qty{$store}"];
		#$farray = array('securitytype'=>'alphanum', 'name'=>'qty', 'width'=>'250px', 'maxlength'=>'60', 'value'=>$qty);
		#if($store != $thisrow['store_id'] && $qty=='' && $thisrow["qty1"]!='') { $farray['other'][]='placeholder="'.$storenames[$thisrow['store_id']].': '.$thisrow["qty1"].'"'; }
		#$bf[] = '<td><b>Qty</b> (not yet used)<br />'.FieldPut($farray, true).'</td>';
	}
	# Keywords
	$bf[] = '<b>Keywords</b><br />';
	$farray = array('securitytype'=>'alphanum', 'name'=>'keywords', 'width'=>(defined('PHONE')?'100%':'400px'), 'maxlength'=>'50', 'value'=>$thisrow['keywords']);
	if(!defined('PHONE')) {
		$farray['other']=array();
		$farray['other'][]='data-toggle="tooltip"';
		$farray['title']="Let's accumulate these together (leave existing ones). Whenever a customer complains that a search did not find a products add it here.";
	}
	$bf[] = FieldPut($farray, true);

	# ord
	$bf[] = '<table><tr>';
	if($area=='store_type' || $area=='store_manufacturer') {
		$farray = array('securitytype'=>'alphanum', 'name'=>'ord', 'width'=>'80px', 'maxlength'=>'4', 'value'=>$thisrow['ord']);
		$farray['other']=array();
		if(!defined('PHONE')) {
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="When products are ordered by type or manufacturer, this controls the order of the groups on the pricelist and in the store.";
		}
		$bf[] = '<td style="padding-right:10px;"><b>Order</b><br />'.FieldPut($farray, true).'</td>';
	}

	# QRCode
	$farray['other']=array();
	$farray = array('securitytype'=>'alphanum', 'name'=>'z', 'width'=>'80px', 'maxlength'=>'4', 'value'=>$thisrow['z'], 'style'=>'font-size:120%; font-weight:bold;');
	if(!defined('PHONE')) {
		$farray['other'][]='data-toggle="tooltip"';
		$farray['title']="QRCodes are assigned automatically as four unique lower case letters.";
	}
	if($store != $thisrow['store_id']) { # not created by us
		$farray['other'][]='disabled="disabled"';
		$bf[] = '<td><b>QRCode</b><br />'.FieldPut($farray).'</td>';
	} else $bf[] = '<td><b>QRCode</b><br />'.FieldPut($farray, true).'</td>';

	# NFS
	if($area=='store_product') {
		$bf[] = '<td style="padding-left:10px;">';
		$temp = '<input type="hidden" value="1" name="NFS*" />'; # mark for saving
		$temp .= '<input type="checkbox" value="on" name="NFS"'.(isset($thisrow["NFS{$store}"]) && $thisrow["NFS{$store}"]?' checked="checked"':'');
		$temp .= 'data-toggle="tooltip" title="Not-for-sale. It is not necessary to NFS a product from another store if you have not assigned a price."';
		$temp .= '> <b>NFS</b>';
		$bf[] = $temp;
		$temp = array();
		for($x=1; $x<=NUMSTORES; $x++) {
			if($thisrow["NFS{$x}"]) $temp[]=$x;
		}
		if($temp) $bf[]=' (store'.(count($temp)>1?'s':'').' '.implode($temp,',').')';
		# $bf[] = '<input type="hidden" value="1" name="featured*" />';
		# $bf[] = ' <input type="checkbox" value="on" name="featured"'.(isset($thisrow["featured{$store}"]) && $thisrow["featured{$store}"]?' checked':'').'> <b>Featured</b>';
		$bf[] = '</td>';
	}
	$bf[] = '</tr></table>';

	if($area=='store_product') {
		$temp = array();
		# location, manufacturer, type
		if(!defined('PHONE')) $tip = ' data-toggle="tooltip" title="Choose primary location in showroom (as defined in the Locations area)"'; else 
		$tip='';
		$temp1 = '<b>Location1</b><br />';
		$temp1 .= ParentPicker1($thisrow, "location_id", "location1_id{$store}", 'store_location', "location1_id", "Choose");
		if($thisrow["location1_id{$store}"]>0) $temp1 .= ' <a href="?'.MakeLink(array('findstr'=>'', 'edit'=>'0', 'prodfilter'=>'location1'.':'.$thisrow["location1_id{$store}"])).'">Browse </a> items in this location';
		$temp[] = $temp1;

		# manufacturer
		$tip='';
		$temp1 = '<b>Manufacturer</b><br />';
		if($store != $thisrow['store_id'])  { $disabled=true; $showmarker=false; } else { $disabled=false; $showmarker=true; }
		$temp1 .= ParentPicker1($thisrow, 'manufacturer_id', 'manufacturer_id', 'store_manufacturer', "manufacturer_id", "Choose", $disabled, $showmarker);
		if($thisrow["manufacturer_id"]>0) $temp1 .= ' <a href="?'.MakeLink(array('findstr'=>'', 'edit'=>'0', 'prodfilter'=>'manufacturer'.':'.$thisrow["manufacturer_id"])).'">Browse </a> products of this manfuacturer';
		$temp[] = $temp1;

		# type
		$tip='';
		$temp1 = '<b>Type</b><br />';
		$temp1 .= ParentPicker1($thisrow, 'type_id', 'type_id', 'store_type', "type_id", "Choose", $disabled, $showmarker); # disabled
		if($thisrow["type_id"]>0) {
			$temp1 .= ' <a href="?'.MakeLink(array('findstr'=>'', 'edit'=>'0', 'prodfilter'=>'type'.':'.$thisrow["type_id"])).'">Browse</a> or ';
			$temp1.='<a target="_blank" href="pricelist.php?type='.$thisrow['type_id'].'">Price Preview</a>';
			$temp1 .= ' all products of this type';
		}
		$temp[] = $temp1;
		$bf[] = '<p>'.implode($temp, '<br />').'</p>';
		
		#$bf[] = '<h3 style="margin-bottom:0px;">Related Products</h3>';
		#$result1 = ExecuteSQLi($sql="SELECT p.name, p.product_id FROM store_links l LEFT JOIN store_product p ON p.product_id=l.id2 WHERE l.id1={$thisrow['product_id']} AND l.table1='product'");
		#if($result1->num_rows > 0) {
		#	$temp = array();
		#	while($row1 = $result1->fetch_assoc()) {
		#		$temp[] = '<input type="checkbox" value="on" name="delchild_'.$row1["link_id"].'"> '.$row1['name'];
		#	}
		#	$bf[] = implode($temp, '<br />');
		#}
	}

	$controls = array('child'=>array(1,2), 'parent'=>array(2,1));
	foreach($controls as $towho => $n) {
		$sql = "SELECT li.link_id, li.id1, li.table1, li.id2, li.table2, ";
		$j=array(); $f = array();
		foreach($tablearr as $t => $a) {
			# join all except for this one
			#if($t != $area) {
				$p = substr($t,6,2); # letter 6,7 e.g. store_location = lo
				$j[] = "LEFT JOIN $t {$p} ON {$p}.{$a['idfield']}=li.id{$n[1]} AND li.table{$n[1]}='$t' ";
				$f[] = "{$p}.z AS {$p}z, {$p}.name AS {$p}name";
			#}
		}
		$sql .= implode($f, ', ');
		$sql .= "\nFROM store_links li";
		$sql .= "\n".implode($j, "\n");
		$sql1 = "\nWHERE li.id{$n[0]}={$thisrow[$idfield]} AND li.table{$n[0]}='{$area}'";
		$result1 = ExecuteSQLi($sql.$sql1);
		if($result1->num_rows > 0) {
			# $bf[] = '<div style="margin-left:10px;"><h4>'.$towho.'</h4>';
			$bf[] = '<p>';
			while($row1 = $result1->fetch_assoc()) {
				$temp = '';
				foreach($tablearr as $t => $a) {
					$p = substr($t,0,2);
					if(!is_null($row1["{$p}z"])) {
						$temp .= '<input type="checkbox" value="on" name="delparent_'.$row1["link_id"].'"> '.ProperAreaName($row1["table{$n[1]}"]);
						$temp .= ' <a href="?edit='.$row1["{$p}z"].'&'.MakeLink(array('area'=>$row1["table{$n[1]}"], 'edit'=>$row1["{$p}z"])).'">'.$row1["{$p}z"].'</a> - '.$row1["{$p}name"];
						$temp .= ' <i type="color:#888888;">('.$towho.')</i>';
						break;
					}
				}
				if($temp) $bf[] = $temp.'<br />';
			}
			$bf[] = '</div>';
		}
	}
	# description
	$farray = array('securitytype'=>'alphanum', 'name'=>'description', 'width'=>(defined('PHONE')?'100%':'500px'), 'maxlength'=>'5000', 'value'=>$thisrow['description']);
	$farray['height'] = (mb_strlen($thisrow['description'])==0?'30px':'100px');
	if(!defined('PHONE')) {
		$farray['other']=array();
		$farray['other'][]='data-toggle="tooltip"';
		$farray['title']="We will refine product descriptions together on shared products. Whatever you change here others will use.";
	}
	$bf[] = '<b>Description</b>';
	$bf[] = '<br />'.FieldPut($farray, true);

	# More..
	$bf[] = '<div id="details" class="collapse">';
	# parent
	if(isset($tablearr[$area]['parenting'])) $bf[] = '<br />Parent '.ProperAreaName($area).' '.ParentPicker($area, 'parent', $thisrow['parentz']).'<br />';
	
	# URL
	$farray = array('securitytype'=>'alphanum', 'name'=>'url', 'width'=>(defined('PHONE')?'100%':'600px'), 'maxlength'=>'255', 'value'=>$thisrow['url']);
	if(!defined('PHONE')) {
		$farray['other']=array();
		$farray['other'][]='data-toggle="tooltip"';
		$farray['title']="Manufacturer URL for this product. We will accumulate these as a team.";
	}
	$bf[] = '<br /><b>URL</b><br />'.FieldPut($farray, true);
	
	if($area=='store_product') {
		# SDS
		$farray = array('securitytype'=>'alphanum', 'name'=>'sds', 'width'=>(defined('PHONE')?'100%':'600px'), 'maxlength'=>'255', 'value'=>(isset($thisrow['sds'])?$thisrow['sds']:''));
		if(!defined('PHONE')) {
			$farray['other']=array();
			$farray['other'][]='data-toggle="tooltip"';
			$farray['title']="Safety Data Sheet URL for this product. We will accumulate these as a team.";
		}
		$bf[] = '<br /><b>SDS</b><br />'.FieldPut($farray, true);
		
		# CAS
		$farray = array('securitytype'=>'alphanum', 'name'=>'cas', 'width'=>(defined('PHONE')?'100%':'150px'), 'maxlength'=>'50', 'value'=>(isset($thisrow['cas'])?$thisrow['cas']:''));
		$bf[] = '<br /><b>CAS#</b><br />';
		$bf[] = FieldPut($farray, true);

		# Locations 2,3
		if(!defined('PHONE')) $tip = ' data-toggle="tooltip" title="Choose secondary location in showroom (as defined in the Locations area)"'; else $tip='';
		$temp1 = '<br /><b>Location2</b><br />';
		$temp1 .= ParentPicker1($thisrow, "location_id", "location2_id{$store}", "store_location", "location_id", "Choose");
		if($thisrow["location2_id{$store}"]>0) $temp1 .= ' <a href="?'.MakeLink(array('findstr'=>'', 'edit'=>'0', 'prodfilter'=>'location2'.':'.$thisrow["location2_id{$store}"])).'">Browse </a>';
		$bf[] = $temp1;
		$temp1 = '<br /><b>Location3</b><br />';
		$temp1 .= ParentPicker1($thisrow, "location_id", "location3_id{$store}", "store_location", "location_id");
		if($thisrow["location3_id{$store}"]>0) $temp1 .= ' <a href="?'.MakeLink(array('findstr'=>'', 'edit'=>'0', 'prodfilter'=>'location2'.':'.$thisrow["location3_id{$store}"])).'">Browse </a>';
		$bf[] = $temp1;
	}

	# Picture
	if(isset($thisrow['filename'])) {
		$bf[] = '<br /><b>'.(substr($thisrow['filename'],-4)=='.pdf'?'PDF':'Picture').' FileName</b> ';
		$bf[] = ' <i>('.$thisrow['filesize'];
		if(substr($thisrow['filename'],-4)!='.pdf') $bf[] = '-'.$thisrow['filewidth'].'x'.$thisrow['fileheight'].')';
		$bf[] = '</i>)';
		if(substr($thisrow['filename'],0,4)=='http') $bf[] = '<br />'.STOREFILEURL.$thisrow['store_id'].'/'.(substr($thisrow['filename'],-4)=='.pdf'?'pdf/':'').$thisrow['filename'];
	} else {
		$bf[] = '<br /><b>FileName</b> ';
	}
	$farray = array('securitytype'=>'alphanum', 'name'=>'filename', 'width'=>(defined('PHONE')?'100%':'600px'), 'maxlength'=>'100', 'value'=>$thisrow['filename']);
	$bf[] = '<br />'.FieldPut($farray, true);
	# Thumbnail
	if($area=='store_product') {
		$bf[] = '<br /><b>ThumbFile</b> ';
		$farray = array('securitytype'=>'alphanum', 'name'=>'thumbfile', 'width'=>(defined('PHONE')?'100%':'600px'), 'maxlength'=>'100', 'value'=>$thisrow['thumbfile']);
		$bf[] = '<br />'.FieldPut($farray, true);
	}
	
	# Links
	$bf[] = '<h3>Links</h3><p>';
	$temp = '<p>'.LinkPicker($area, 'link');
	if($temp !== false) {
		$bf[] = $temp.'<button type="submit" class="btn info" name="LinkParent" value="LinkParent">Link</button>';
		if($area=='store_product') $bf[] = ' <a href="?'.MakeLink(array('linktoothers'=>$thisrow['z'], 'edit'=>'0')).'">Link other products</a>';
		$bf[] = '</p>';
	} else {
		$bf[] = 'Cannot link to others, none open.</p>';
	}
	$bf[] = '</div>';
	$bf[] = '<p><a href="#details" data-toggle="collapse">More</a>..</p>';

	$bf[] = '<p style="margin-top:15px;">';
	if($area=='store_product' || $thisrow['store_id']==$login->Store()) {
		$bf[] = '<button type="submit" class="btn btn-primary" name="SaveRecord" value="Save">Save</button>';
	}
	$bf[] = ' <button type="submit" class="btn btn-info" name="AddOne" value="Add One">Duplicate</button>';
	if($thisrow['store_id']==$login->Store()) {
		$bf[] = ' <button type="submit" class="btn btn-danger" name="Delete" value="Delete">Delete</button> ';
	}
	if(isset($_POST['Delete']) && $store) {
		$_GET["edit"] = '0';
		$result = ExecuteSQLi($sql="SELECT * FROM store_links WHERE id1={$thisrow[$idfield]} AND table1='{$area}'");
		if($result->num_rows > 0) {
			$bf[] = "Cannot remove this record, it has children.";
		} else {
			$bf[] = "Delete ".$thisrow['name'].'? <a href="?'.MakeLink(array('delete'=>$thisrow['z'])).'">Confirm</a>';
		} 
	} elseif($thisrow['filename'] || $thisrow['thumbfile']) {
		if(substr($thisrow['filename'],-4)=='.pdf')
			$bf[] = '<button type="submit" class="btn btn-warning" name="DeletePicture" value="DeletePicture">Delete PDF</button>';
		elseif($thisrow['filename'])
			$bf[] = '<button type="submit" class="btn btn-warning" name="DeletePicture" value="DeletePicture">Delete Picture</button>';
		else
			$bf[] = '<button type="submit" class="btn btn-warning" name="DeletePicture" value="DeletePicture">Delete Thumbnail</button>';
		if(isset($_POST['DeletePicture']) && $thisrow['store_id']==$store) { # only delete own pictures
			$bf[] = 'Delete picture? <a href="?'.MakeLink(array('deletepicture'=>'yes')).'">Confirm</a>';
		}
		$bf[] = '</p>';
		if(defined('PHONE')) $bf[] = '<p>'.Picture($thisrow, '100%', '', true).'</p>'; # prefer thumbnail
		else $bf[] = '<p>'.Picture($thisrow, '', '200px').'</p>';
	} else $bf[] = '</p>';
	$bf[] = '</form>';

	# Upload picture
	$bf[] = '<h3>Upload a Picture</h3>';
	if($thisrow['store_id']==$login->Store()) {
		$bf[] = "\n".'<form method="post" name="pictureupload" enctype="multipart/form-data" action="?'.MakeLink().'">';
		$bf[] = '<input type="file" name="file" />';
		if(defined('PHONE')) $bf[] = '<br />';
		$bf[] = '<input type="checkbox" value="on" name="SaveAsThumb">Thumbnail ';
		$bf[] = '<input type="submit" name="Upload" value="Upload Picture" />';
		$bf[] = '</form>';
	} else {
		$bf[] = 'Please submit your image(s) to the record owner for uploading.';
	}

	if($area=='store_product') {
		# Add
		$bf[] = '<form method="post" name="addlikethis" action="?'.MakeLink().'">';
		$bf[] = '<input type="hidden" value="'.$thisrow['z'].'" name="z" />';
		$bf[] = '<input type="hidden" value="'.$thisrow['ord'].'" name="ord" />';
		$bf[] = '<input type="hidden" value="'.$thisrow['name'].'" name="name" />';
		if($area=='store_product') {
			$bf[] = '<input type="hidden" value="'.$thisrow["price{$store}"].'" name="price" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["qty{$store}"].'" name="qty" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["qty_discounts{$store}"].'" name="qty_discounts" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["um{$store}"].'" name="um" />';
			$bf[] = '<input type="hidden" value="'.$thisrow['manufacturer_id'].'" name="manufacturer_id" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["location1_id{$store}"].'" name="location1_id" />';
			$bf[] = '<input type="hidden" value="'.$thisrow['type_id'].'" name="type_id" />';
		}
		$bf[] = '<h3 style="bottom-top:0px;">Add/Update Multiple</h3>';
		$bf[] = '<p>';
		#if($store>1) {
		$temp = 'To import multiple products of the same type/manufacturer but having different names, prices and pictures:'."\n";
		$temp .= '-Prepare one model product having the correct type and manufactuer, the units-of-measure (seprated by semi-colons), option, location, picture URL and thumbnail URL (deduce the URLs from the supplier website).'."\n";
		$temp .= '-Paste the data into this box. e.g.'."\n";
		$temp .= '700 Clear	13.00	70.00	700.jpg	700w.jpg'."\n";
		$temp .= '701 Snow white	16.00	92.00	701.jpg	701w.jpg'."\n";
		$temp .= '702 Porcelain white	16.00	92.00	702.jpg	702w.jpg'."\n\n";
		$temp .= '*The same data can be imported multiple times. Succesive imports update existing records.';
		$bf[] .= FieldPut(array('securitytype'=>'alphanum', 'name'=>'names', 'width'=>(defined('PHONE')?'100%':'75%'), 'height'=>'200px', 'maxlength'=>'10000', 'style'=>'font-family:monospace; font-size:80%;', 'other'=>'placeholder="'.$temp.'"'));
		$bf[] .= '<br /><input type="checkbox" value="on" name="UpgradeNamesOnly">Upgrade product names only';
		$bf[] .= '<br /><button type="submit" class="btn btn-info" name="AddMoreLikeThis" value="AddMoreLikeThis">Add</button>';
		$bf[] .= '</form>';
	}

	# Upload pictures to make more like this
	if($area=='store_product') {
		$bf[] = '<h3>Upload a ZIP Archive of Pictures</h3>';
		$bf[] = '<p style="margin:0px 0px 0px 0px; color:#888888;">Pictures will attach to products having the same name (or new products will be patterned after "'.$thisrow['name'].'" and named after the incoming pictures)<br />';
		$bf[] = '<input type="checkbox" value="on" name="SaveAsThumbs">Save as thumbnails only</p>';
		$bf[] = "\n".'<form method="post" enctype="multipart/form-data" action="?'.MakeLink().'">';
		$bf[] = '<input type="file" name="zipfile" />';
		if(defined('PHONE')) $bf[] = '<br />';
		$bf[] = '<input type="hidden" value="'.$thisrow['z'].'" name="z" />';
		$bf[] = '<input type="hidden" value="'.$thisrow['ord'].'" name="ord" />';
		$bf[] = '<input type="hidden" value="'.$thisrow['name'].'" name="name" />';
		if($area=='store_product') {
			$bf[] = '<input type="hidden" value="'.$thisrow["price{$store}"].'" name="price" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["qty{$store}"].'" name="qty" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["qty_discounts{$store}"].'" name="qty_discounts" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["um{$store}"].'" name="um" />';
			$bf[] = '<input type="hidden" value="'.$thisrow['manufacturer_id'].'" name="manufacturer_id" />';
			$bf[] = '<input type="hidden" value="'.$thisrow["location1_id{$store}"].'" name="location1_id" />';
			$bf[] = '<input type="hidden" value="'.$thisrow['type_id'].'" name="type_id" />';
		}
		$bf[] = '<input type="submit" name="BatchUpload" value="Upload Batch" />';
	}

	# children
	if($area=='store_type' || $area=='store_manufacturer') {
		$tbl = after($area, '_');
		$sql = "SELECT p.product_id, p.name, ";
		for($x=1; $x<=NUMSTORES; $x++) $sql .= "p.NFS{$x}, p.um{$x}, ";
		$sql .= "p.z FROM {$area} a LEFT JOIN store_product p ON p.{$tbl}_id=a.{$tbl}_id WHERE a.{$tbl}_id={$thisrow[$idfield]} ORDER BY p.name"; # p.featured{$store}
		$result1 = ExecuteSQLi($sql);
		if($result1->num_rows > 0) {
			$bf[] = '<h3>Children</h3><p>';
			while($row1 = $result1->fetch_assoc()) {
				if($row1["name"]) {
					$bf[] = '<input type="checkbox" value="on" name="unlink_'.$row1["z"].'"> ';
					$bf[] = '<a href="?'.MakeLink(array('area'=>'product', 'edit'=>$row1["z"])).'">'.$row1["name"].'</a>';
					if($row1["um{$store}"]) $bf[] = '('.$row1["um{$store}"].')';
					$temp = array();
					for($x=1; $x<=NUMSTORES; $x++) {
						if($row1["NFS{$x}"]) $temp[]=$x;
					}
					if($temp) $bf[]=' <span style="color:red">*NFS in store'.(count($temp)>1?'s':'').' '.implode($temp,',').'</span>';
					# if($row1["NFS{$store}"]) $bf[] = ' <i>(NFS)</i>';
					# if($row1["featured{$store}"]) $bf[] = ' <i>(Featured)</i>';
					$bf[] = '<br />';
				}
			}
			$bf[] = '</p>';
		}
	} elseif($area=='store_location') {
		$sql="SELECT p.product_id, p.name, ";
		for($x=1; $x<=NUMSTORES; $x++) $sql .= "p.NFS{$x}, p.um{$x}, ";
		$sql .= "p.z FROM store_location l LEFT JOIN store_product p ON p.location1_id{$store}=l.location_id OR p.location2_id=l.location_id OR p.location3_id=l.location_id WHERE l.location_id={$thisrow[$idfield]} ORDER BY p.name"; # p.featured{$store} 
		$result1 = ExecuteSQLi($sql);
		if($result1->num_rows > 0) {
			$bf[] = '<h3>Children</h3><p>';
			while($row1 = $result1->fetch_assoc()) {
				if($row1["name"]) {
					$bf[] = '<input type="checkbox" value="on" name="unlink_'.$row1["z"].'"> ';
					$bf[] = '<a href="?'.MakeLink(array('area'=>'product', 'edit'=>$row1["z"])).'">'.$row1["name"].'</a>';
					if($row1["um{$store}"]) $bf[] = '('.$row1["um{$store}"].')';
					$temp = array();
					for($x=1; $x<=NUMSTORES; $x++) {
						if($row1["NFS{$x}"]) $temp[]=$x;
					}
					if($temp) $bf[]=' <span style="color:red">*NFS in store'.(count($temp)>1?'s':'').' '.implode($temp,',').'</span>';
					# if($row1["NFS{$store}"]) $bf[] = ' <i>(NFS)</i>';
					# if($row1["featured{$store}"]) $bf[] = ' <i>(Featured)</i>';
					$bf[] = '<br />';
				}
			}
			$bf[] = '</p>';
		}
	}

} elseif(isset($area)) {
	if(!isset($_POST['editthem'])) $bf[] = ' <input type="submit" value="Edit Them" name="editthem"> ';
	# $bf[] = '<input type="checkbox" name="withpicture" value="on"> With Picture';
	$bf[] = ' | <a href="#controls1" data-toggle="collapse">More</a>..';
	if(1==0 && isset($_GET['multisave'])) {
		$bf[] = '<br />Qty '.FieldPut(array('securitytype'=>'alphanum', 'name'=>'qty', 'width'=>'100px', 'maxlength'=>'60'));
		$bf[] = 'U/M '.FieldPut(array('securitytype'=>'alphanum', 'name'=>'um', 'width'=>'100px', 'maxlength'=>'60'));
		$bf[] = ParentPicker1(0, 'location_id', "location1_id{$store}", 'store_location', "location_id{$store}", 'Location').' ';
		$bf[] = ParentPicker1(0, 'manufacturer_id', 'manufacturer_id', 'store_manufacturer', "manufacturer_id", 'Manufacturer').' ';
		$bf[] = ParentPicker1(0, 'type_id', 'type_id', 'store_type', "type_id", 'Type');
		# $bf[] = ' <input type="submit" value="MultiSave" name="multisave"><br />';
	}

	$row = GetSQLRecordi("SELECT length(ord) FROM store_type WHERE account_id=".$login->AcctID()." && length(ord)>0 AND price{$store}>0 ORDER BY length(ord) LIMIT 0,1");
	$maintopicordlen = $row['length(ord)'];
	# show records
	if(isset($idfield)) {
		$temp = GetSQLRecordi("SELECT count(*) FROM {$area} WHERE account_id=".$login->AcctID()." AND price{$store}>0");
		$recs = $temp['count(*)'];
		# $bf[] = '<h2>'.$temp['count(*)'].' '.ProperAreaName(isset($tablearr[$area]['plural'])?$tablearr[$area]['plural']:$area.'s').'</h2>';
		$bf[]='<p><b>'.$recs.' priced records sorted by</b> ';
		if($area=='store_product') {
			#$bf[]='<input type="radio" name="tblorder" onchange="this.form.submit();" value="location1" '.($tblorder=='location1'?' checked':'').'> Loc ';
			#$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="manufacturer" '.($tblorder=='manufacturer'?' checked':'').'> Man ';
			$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="type" '.($tableord[$area]=='type'?' checked':'').'> Type ';
			$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="filesize" '.($tableord[$area]=='filesize'?' checked':'').'> FileSize ';
			$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="picratio" '.($tableord[$area]=='picratio'?' checked':'').'> PictureRatio ';
			$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="pricelist" '.($tableord[$area]=='pricelist'?' checked':'').'> PriceList ';
		}
		$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="entered" '.($tableord[$area]=='entered'?' checked':'').'> Added ';
		$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="date" '.($tableord[$area]=='date'?' checked':'').'> Date ';
		$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="name" '.($tableord[$area]=='name'?' checked':'').'> Name';
		$bf[]=' <input type="radio" name="tblorder" onchange="this.form.submit();" value="ord" '.($tableord[$area]=='ord'?' checked':'').'> Ord';
		$bf[] = '</p>';

		$bf[] = '<div id="controls1" class="collapse">';
		$bf[] = '<input type="checkbox" name="showsql" value="on"> ShowSQL';
		# $bf[] = ' | <a href="?'.MakeLink(array('multisave'=>'yes')).'">MultiSave</a>';
		if($area=='store_product') {
			$bf[]='<br />';
			$bf[]=' <b>Pictures</b> <input type="radio" name="haspictures" onchange="this.form.submit();" value="withpics" '.($haspictures=='withpics'?' checked':'').'> With ';
			$bf[]=' <input type="radio" name="haspictures" onchange="this.form.submit();" value="nopics" '.($haspictures=='nopics'?' checked':'').'> Without ';
			$bf[]=' <input type="radio" name="haspictures" onchange="this.form.submit();" value="both" '.($haspictures=='both'?' checked':'').'> Both ';
			$bf[]='<br />';
			$bf[]=' <b>Location1</b> <input type="radio" name="haslocation1" onchange="this.form.submit();" value="yes" '.($haslocation1=='yes'?' checked':'').'> With ';
			$bf[]=' <input type="radio" name="haslocation1" onchange="this.form.submit();" value="no" '.($haslocation1=='no'?' checked':'').'> Without ';
			$bf[]=' <input type="radio" name="haslocation1" onchange="this.form.submit();" value="both" '.($haslocation1=='both'?' checked':'').'> Either ';
			$bf[]='<br />';
			$bf[]=' <b>Price</b> <input type="radio" name="hasprice" onchange="this.form.submit();" value="yes" '.($hasprice=='yes'?' checked':'').'> With ';
			$bf[]=' <input type="radio" name="hasprice" onchange="this.form.submit();" value="no" '.($hasprice=='no'?' checked':'').'> Without ';
			$bf[]=' <input type="radio" name="hasprice" onchange="this.form.submit();" value="both" '.($hasprice=='both'?' checked':'').'> Either ';
			$bf[]=' <a target="_blank" href="pricelist.php">PriceList</a>'; 
			$bf[]='<br />';
			$bf[]=' <b>Type</b> <input type="radio" name="hastype" onchange="this.form.submit();" value="yes" '.($hastype=='yes'?' checked':'').'> With ';
			$bf[]=' <input type="radio" name="hastype" onchange="this.form.submit();" value="no" '.($hastype=='no'?' checked':'').'> Without ';
			$bf[]=' <input type="radio" name="hastype" onchange="this.form.submit();" value="both" '.($hastype=='both'?' checked':'').'> Either ';
		}
		$bf[]='</p>';
		$bf[]='</div>'; # controls1
		$pdo = NEW myPDO(true);
		$f = array(); $v = array();
		if($area=='store_product') {
			$f[] = 'p.account_id'; $v[] = $login->AcctID();
			if(isset($marked) && !isset($_POST['deletethem'])) { # so it does not seek non-existent when deleting checked
				$f[] = 'p.z IN ("'.implode($marked, '", "').'")';
			} else {
				if(isset($prodfilter) && is_array($prodfilter)) {
					$f[] = "p.{$prodfilter[0]}_id"; $v[] = $prodfilter[1];
				}
				if($findstr) {
					# man.name, type.names could be null if not assigned, concat returns null, use concat_ws
					$f[] = "concat_ws(p.z, p.name, p.product_option{$store}, p.keywords, store_manufacturer.name, store_manufacturer.keywords, store_type.name, store_type.keywords) RLIKE ?";
					$v[] = $findstr;
				}
			}
			if($haspictures == 'nopics') $f[] = 'p.filename=""';
			elseif($haspictures == 'withpics') $f[] = 'p.filename<>""';
			if($haslocation1 == 'no') $f[] = "p.location1_id{$store}=0";
			elseif($haslocation1 == 'yes') $f[] = "p.location1_id{$store}<>0";
			if($hasprice == 'no') $f[] = "p.price{$store}=0";
			elseif($hasprice == 'yes') $f[] = "p.price{$store}<>0";
			if($hastype == 'no') $f[] = 'p.type_id=0';
			elseif($hastype == 'yes') $f[] = 'p.type_id<>0';

		} else {
			if($area=='store_location') {
				$f[] = 'store_id'; $v[] = $store;
			} else {
				$f[] = 'account_id'; $v[] = $login->AcctID();
			}
			if(isset($marked)) {
				$f[] = 'z IN ("'.implode($marked, '", "').'")';
			} elseif($findstr) {
				$f[] = 'concat(z, name, keywords) RLIKE ?';
				$v[] = $findstr;
			}
		}

		if($tableord[$area]=='date') $pdo->SetOrder(($area=='store_product'?'p.':'')."birthdate DESC");
		elseif($tableord[$area]=='type') $pdo->SetOrder(($area=='store_product'?'store_type.ord, store_type.name, p.name':'ord'));
		elseif($tableord[$area]=='name') $pdo->SetOrder(($area=='store_product'?'p.name':'name'));
		elseif($tableord[$area]=='ord') $pdo->SetOrder(($area=='store_product'?'p.ord':'ord')); # elseif($area=='store_product') $pdo->SetOrder("{$tblorder}.ord, p.birthdate");
		elseif($tableord[$area]=='filesize') $pdo->SetOrder(($area=='store_product'?'p.filesize DESC':'filesize DESC'));
		elseif($tableord[$area]=='pricelist') $pdo->SetOrder("store_type.ord, p.name, p.name_differentiator, p.product_option{$store}");
		elseif($tableord[$area]=='entered') $pdo->SetOrder($idfield.' DESC');
		elseif($tableord[$area]=='picratio') $pdo->SetOrder(($area=='store_product'?'p.filewidth / IF(p.fileheight=0, 1, p.fileheight)':'ord'));
		else $pdo->SetOrder('ord, birthdate');
		$pdo->WhereFields($f);
		$pdo->WhereValues($v);
		if($area=='store_product') {
			$joins = array();
			$joins[] = "LEFT JOIN store_location ON store_location.location_id=p.location1_id{$store}";
			$joins[] = "LEFT JOIN store_type ON store_type.type_id=p.type_id";
			$joins[] = "LEFT JOIN store_manufacturer ON store_manufacturer.manufacturer_id=p.manufacturer_id";
		}
		# Prepare and get all records
		if($area=='store_product') {
			$n = $pdo->Prepare('SELECT', 'p.product_id', "store_product p ".implode($joins, ' '));
		} else {
			$n = $pdo->Prepare('SELECT', '*', $area);
		}
		$totalfound = $pdo->Execute(); # returns false or the row count
		$numgroups = ceil($totalfound/$numingroup);  # round up
		if($RcpGroup < 1) $RcpGroup=1;
		if($RcpGroup > $numgroups && $numgroups>0) { $RcpGroup=$numgroups; }

		# Prepare again and get this group
        $pdo->SetLimit((($RcpGroup*$numingroup)-$numingroup).", {$numingroup}");
		if($area=='store_product') {
			$n = $pdo->Prepare('SELECT', 'p.*, store_type.z AS tz, store_manufacturer.z AS mz', "store_product p ".implode($joins, ' '));
		} else {
			$n = $pdo->Prepare('SELECT', '*', $area);
		}
		$numfound = $pdo->Execute();
		if(isset($_POST['showsql']) || isset($_GET['showsql'])) $bf[] = $pdo->SQL();
		# echo $pdo->SQL();
		# $numfound is number of rows
		if($numfound || $RcpGroup>1 || $numgroups>1) {
			#$bf[] = $numfound.' '.ProperAreaName(isset($tablearr[$area]['plural'])?$tablearr[$area]['plural']:$area.'s').' of '.$totalfound.' found.';
			# Pager ================
			$temp='';
			if($numgroups>1) {
				$temp .= '<p style="white-space:nowrap; margin:9px 0px 5px 0px;">';
				if($RcpGroup > 1) {
					$temp .= MakeAButton("FirstRecipeGroup", "<<", "Go to first group of $numingroup");
					$temp .= MakeAButton("PrevRecipeGroup", "<", "Go to previous group of $numingroup");
				}
				$temp .= " Group ".'<input type="text" name="RcpGroupNum" style="width:26px" value="'.$RcpGroup.'"> of '.$numgroups.' ';
				if($RcpGroup < $numgroups) {
					$temp .= MakeAButton("NextRecipeGroup", ">", "Go to next group of $numingroup");
					#$temp .= MakeAButton("LastRecipeGroup", ">>", "Go to last group of $numingroup");
				}
				$temp .= MakeAButton("GoRecipeGroup", "Go", "Go directly to the group indicated").'</p>';
			}
			$bf[] = $temp.'<input type="hidden" name="RcpGroup" value="'.$RcpGroup.'">';
			$bf[] = '<input type="hidden" name="RcpLastGroup" value="'.$numgroups.'">';
			# ====================

			# edit phone
			if(isset($_POST['editthem']) && defined('PHONE')) {
				$bf[] = '<table class="table table-striped table-hover table-condensed">';
				$temp = '<thead><tr>';
				$temp .= '<th>Code/Name'.($area=='store_products'?'Location1':'').'</th>';
			# edit desktop
			} elseif(isset($_POST['editthem'])) {
				$bf[] = '<input type="hidden" name="ord*" value="1">';
				$bf[] = '<input type="hidden" name="name*" value="1">';
				$bf[] = '<input type="hidden" name="filename*" value="1">';
				if($area=='store_product') {
					$bf[] = '<input type="hidden" name="differentiator*" value="1">';
					$bf[] = '<input type="hidden" name="product_option*" value="1">';
					$bf[] = '<input type="hidden" name="um*" value="1">';
					$bf[] = '<input type="hidden" name="price*" value="1">';
					$bf[] = '<input type="hidden" name="location1_id*" value="1">';
					$bf[] = '<input type="hidden" name="manufacturer_id*" value="1">';
					$bf[] = '<input type="hidden" name="type_id*" value="1">';
					$bf[] = '<input type="hidden" name="sds*" value="1">';
					$bf[] = '<input type="hidden" name="cas*" value="1">';
				}
				$bf[] = '<table class="table table-striped table-hover table-condensed">';
				$temp = '<thead><tr>';
				if($area=='store_product') {
					$temp .= '<th>Code/Ord</th><th>NFS</th><th>Name/Differentiator</th><th>Price/Location1/Manufacturer/Type</th><th>Descrip/Keywords</th><th>Picture</th><th>FileName/Thumb/URL/SDS</th>';
				} else {
					$temp .= '<th style="width:82px;">Code</th><th style="width:82px;">Ord</th><th style="width:82px;">Parent</th><th style="width:90px;">Owner</th><th>Name</th><th>Keywords</th>';
				}
			# browse phone
			} elseif(defined('PHONE')) { 
				$bf[] = '<table class="table table-striped table-hover table-condensed">';
				$temp = '<thead><tr>';
				$temp .= '<th>QR</th><th>Name'.($area=='store_product'?'/Location1':'').'</th><th>Picture</th>'; #:'<th>Picture/Ord</th>');
			# browse desktop
			} else { 
				$bf[] = '<table class="table table-striped table-hover table-condensed">';
				$temp = '<thead><tr>';
				if($area=='store_product') {
					$temp .= '<th>Code/Ord</th><th>NFS</th><th>Name</th><th>Price/Units<br />QtyDisc</th><th>Loc1/Manufac<br />Type</th><th>Descrip/Keywords</th><th>Picture</th><th>URL/SDS/CAS</th>';
				} else {
					$temp .= '<th>Code</th><th>Ord</th>';
					if($area=='store_type') $temp .= '<th>Parent</th>';
					$temp .= '<th>Owner</th><th>Name</th><th>Keywords</th><th>Descrip</th>'.($area=='store_manufacturer' || $area=='store_product'?'<th>Picture</th>':'').'<th>URL</th>';
				}
			}
			$temp .= '</tr></thead><tbody>';
			$bf[] = $temp;
			$STH = $pdo->GetResult();
			while($row = $STH->fetch(PDO::FETCH_ASSOC)) {
				if($row['z']=='') {
					$temp = MakeQRCode(4); while(ZExists($temp)) $temp = MakeQRCode(4);
					ExecuteSQLi($sql="UPDATE {$area} SET z='{$temp}' WHERE {$idfield}='{$row[$idfield]}'");
					$row = GetARecord($area, $row[$idfield], $idfield);
				}
				# edit phone
				if(isset($_POST['editthem']) && defined('PHONE')) {
					$bf[] = '<tr>';
					$bf[] = '<td>';
					#$bf[] = '<input type="checkbox" value="on" name="mark_'.$row['z'].'">';
					$bf[] = '<a href="?'.MakeLink(array('edit'=>$row['z'])).'">'.($row['z']?$row['z']:"Edit").'</a><br />';
					$bf[] = FieldPut(array('securitytype'=>'alphanum', 'name'=>'name_'.$row['z'], 'width'=>'100%', 'maxlength'=>'99', 'value'=>$row['name'], 'style'=>'font-weight:bold; color:red;'));
					$bf[] = '<br />';
					if($area=='store_product') {
						$bf[] = ParentPicker1($row, 'location_id', "location1_id{$row['store_id']}", 'store_location', "location1_id_".$row['z'], 'Location1');
						$bf[] = '</td>';
					}
					$editthem[] = $row['z'];
				# edit desktop
				} elseif(isset($_POST['editthem'])) {
					$bf[] = '<tr'.($row["NFS{$store}"]?' class="danger"':'').'>';
					$bf[] = '<td>';
					#$bf[] = '<input type="checkbox" value="on" name="mark_'.$row['z'].'"> ';
					$bf[] = '<a href="?'.MakeLink(array('edit'=>$row['z'])).'">'.($row['z']?$row['z']:"Edit").'</a>';
					if($area!='store_product') $bf[] = '</td><td>';
					# ord
					$farray = array('securitytype'=>'alphanum', 'name'=>'ord_'.$row['z'], 'width'=>'80px', 'maxlength'=>'4', 'value'=>$row['ord']);
					if(1==1 || $login->TonyHansen() || $row['store_id']==$login->Store()) {
						$temp = FieldPut($farray, true);
					} else {
						$farray['other']='disabled="disabled"';
						$temp = FieldPut($farray);
					}
					if($area=='store_product') $bf[] = '<br />'.$temp; else $bf[] = $temp.'</td>';
					if($area=='store_type') {
						# parentz
						$farray = array('securitytype'=>'alphanum', 'name'=>'parentz_'.$row['z'], 'width'=>'80px', 'maxlength'=>'4', 'value'=>$row['parentz']);
						if($login->TonyHansen() || $row['store_id']==$login->Store()) {
							$temp = '<td>'.FieldPut($farray, true).'</td>';
						} else {
							$farray['other']='disabled="disabled"';
							$temp = '<td>'.FieldPut($farray).'</td>';
						}
					}
					# owner
					$temp = '<span style="font-size:85%; color:#888888">'.$storenames[$row['store_id']].'</span>';
					if($area=='store_product') $bf[] = '<br />'.$temp.'</td>'; else $bf[] = '<td>'.$temp.'</td>';

					if($area=='store_product') {
						$bf[] = '</td>';
						$bf[] = '<td><input type="checkbox" value="on" name="NFS_'.$row['z'].'"'.($row["NFS{$store}"]?' checked':'').'>';
						$temp = array();
						for($x=1; $x<=NUMSTORES; $x++) {
							if($row["NFS{$x}"]) $temp[]=$x;
						}
						if($temp) $bf[]='<br />'.implode($temp);
						# $bf[] = ' <input type="checkbox" value="on" name="featured_'.$row['z'].'"'.($row["featured{$store}"]?' checked':'').'>';
						$bf[] = '</td>';
					}
					$bf[] = '<td style="white-space:nowrap;">';
					# name
					$farray = array('securitytype'=>'alphanum', 'name'=>'name_'.$row['z'], 'width'=>($area!='store_product'?'100%':'90%'), 'maxlength'=>'99', 'value'=>$row['name'], 'style'=>'font-weight:bold;');
					if($row['store_id']!=$login->Store()) {
						$farray['other']='disabled="disabled"';
						$bf[] = FieldPut($farray);
					} else {
						$bf[] = FieldPut($farray, true);
					}
					if($area=='store_product') {
						$farray = array('securitytype'=>'alphanum', 'name'=>'differentiator_'.$row['z'], 'width'=>'8%', 'maxlength'=>'1', 'value'=>$row['name_differentiator']);
						if($row['store_id']!=$login->Store()) {
							$farray['other']='disabled="disabled"';
							$bf[] = '&nbsp;'.FieldPut($farray);
						} else {
							$bf[] = '&nbsp;'.FieldPut($farray, true);
						}
					}
					$bf[] = '<br />';
					if($area=='store_product') {
						# product_option
						$farray = array('securitytype'=>'alphanum', 'name'=>'product_option_'.$row['z'], 'width'=>'150px', 'maxlength'=>'25', 'value'=>$row["product_option{$store}"]);
						if($row["product_option{$store}"]=='' && $store != $row['store_id'] && $row["product_option{$row['store_id']}"]!='') $farray['other'][]='placeholder="'.$storenames[$row['store_id']].': '.htmlentities($row["product_option{$row['store_id']}"]).'"';
						$bf[] = 'Option '.FieldPut($farray, true).'<br />';
						$farray = array('securitytype'=>'alphanum', 'name'=>'um_'.$row['z'], 'width'=>'200px', 'maxlength'=>'60', 'value'=>$row["um{$store}"]);
						$bf[] = '<nobr>U/M '.FieldPut($farray, true).'</nobr>';
						# $bf[] = FieldPut(array('securitytype'=>'alphanum', 'name'=>'qty_'.$row['z'], 'width'=>'100%', 'maxlength'=>'60', 'value'=>$row["qty{$store}"]));
						# um
						$farray = array('securitytype'=>'alphanum', 'name'=>'cas_'.$row['z'], 'width'=>'200px', 'maxlength'=>'50', 'value'=>$row['cas']);
						if($row['store_id']!=$login->Store()) {
							$farray['other']='disabled="disabled"';
							$bf[] = '<br />CAS '.FieldPut($farray);
						} else {
							$bf[] = '<br />CAS '.FieldPut($farray, true);
						}

						$bf[] = '</td>';
						# manfac, type, location
						$bf[] = '<td>';
						$farray = array('securitytype'=>'alphanum', 'name'=>'price_'.$row['z'], 'width'=>'100%', 'maxlength'=>'60', 'value'=>$row["price{$store}"]);
						$bf[] = FieldPut($farray, true).'<br />';
						$bf[] = ParentPicker1($row, "location_id", "location1_id{$row['store_id']}", 'store_location', "location1_id_".$row['z'], 'Location').'<br />';
						if($row['store_id']!=$login->Store()) $disabled=true; else $disabled=false;
						$bf[] = ParentPicker1($row, 'manufacturer_id', 'manufacturer_id', 'store_manufacturer', "manufacturer_id_".$row['z'], 'Manufacturer', $disabled).'<br />';
						$bf[] = ParentPicker1($row, 'type_id', 'type_id', 'store_type', "type_id_".$row['z'], 'Type', $disabled).'</td>';
					}
					if($area="store_product") {
						$bf[] = '<td>';
						$farray = array('securitytype'=>'alphanum', 'name'=>'description_'.$row['z'], 'width'=>'100%', 'height'=>'50px', 'maxlength'=>'5000', 'value'=>$row['description'], 'style'=>'font-size:80%;');
						$bf[] = FieldPut($farray, true);
						$bf[] = '<br />';
						$farray = array('securitytype'=>'alphanum', 'name'=>'keywords_'.$row['z'], 'width'=>'100%', 'height'=>'50px', 'maxlength'=>'100', 'value'=>$row['keywords'], 'style'=>'font-size:80%;');
						$bf[] = FieldPut($farray, true);
						$bf[] = '</td>';
					}

					if($area != 'store_type') $bf[] = '<td>'.Picture($row, '', '80px').'</td>';


					$bf[] = '<td>';
					# filename, url, sds, cas
					$farray = array('securitytype'=>'alphanum', 'name'=>'filename_'.$row['z'], 'width'=>'100%', 'maxlength'=>'99', 'value'=>$row['filename']);
					if($row['store_id']!=$login->Store()) {
						$farray['other']='disabled="disabled"';
						$bf[] = FieldPut($farray);
					} else {
						$bf[] = FieldPut($farray, true);
					}
					if($area=='store_product') {
						$farray = array('securitytype'=>'alphanum', 'name'=>'thumbfile_'.$row['z'], 'width'=>'100%', 'maxlength'=>'99', 'value'=>$row['thumbfile']);
						if($row['store_id']!=$login->Store()) {
							$farray['other']='disabled="disabled"';
							$bf[] = FieldPut($farray);
						} else {
							$bf[] = FieldPut($farray, true);
						}
					}
					$farray = array('securitytype'=>'alphanum', 'name'=>'url_'.$row['z'], 'width'=>'100%', 'maxlength'=>'255', 'value'=>$row['url']);
					if($row['store_id']!=$login->Store()) {
						$farray['other']='disabled="disabled"';
						$bf[] = FieldPut($farray);
					} else {
						$bf[] = FieldPut($farray, true);
					}
					if($area=='store_product') {
						$farray = array('securitytype'=>'alphanum', 'name'=>'sds_'.$row['z'], 'width'=>'100%', 'maxlength'=>'255', 'value'=>$row['sds']);
						if($row['store_id']!=$login->Store()) {
							$farray['other']='disabled="disabled"';
							$bf[] = FieldPut($farray);
						} else {
							$bf[] = FieldPut($farray, true);
						}
					}
					$bf[] = '</td>';
					$editthem[] = $row['z'];
				# browse phone
				} elseif(defined('PHONE')) {
					$bf[] = '<tr'.($row["NFS{$store}"]?' class="danger"':'').'>';
					$bf[] = '<td><input type="checkbox" value="on" name="mark_'.$row['z'].'"><br /><span style="font-size:60%">'.$row['z'].'</span></td>';
				
					$bf[] = '<td><b><a href="?'.MakeLink(array('edit'=>$row['z'])).'">'.$row['name'].'</a></b>'.($row['keywords']?' <i>('.$row['keywords'].'</i>)':'');
					if($area=='store_product') {
						# $bf[] = '<br>'.$row["price{$store}"].'/'.$row["um{$store}"].' '.$row["qty{$store}"];
						# $bf[] = '</td><td>';
						$a=array();
						# $a[] = '<a href="?'.MakeLink(array('prodfilter'=>'locationq:'.$row["location1_id{$store}"])).'">'.$locationname[$row["location1_id{$store}"]].'</a>';
						# $a[] = '<a href="?'.MakeLink(array('prodfilter'=>'type:'.$row["type_id"])).'">'.$typename[$row["type_id"]].'</a>';
						$a[] = '<a href="?'.MakeLink(array('area'=>'type', 'edit'=>$row['tz'])).'">'.$typename[$row["type_id"]].'</a>';
						# $a[] = '<a href="?'.MakeLink(array('prodfilter'=>'manufacturer:'.$row["manufacturer_id"])).'">'.$manufacturername[$row["manufacturer_id"]].'</a>';
						$a[] = '<a href="?'.MakeLink(array('area'=>'manufacturer', 'edit'=>$row['mz'])).'">'.$manufacturername[$row["manufacturer_id"]].'</a>';
						$bf[] = '<br>'.implode($a, '<br />');
						$bf[]='</td>';
					} else $bf[]='</td>';
					$bf[] = '<td>'.Picture($row, '80px', '80px').'</td>';

				# browse desktop
				} else {
					$bf[] = '<tr'.($area=='store_product' && $row["NFS{$store}"]?' class="danger"':'').'>';
					#checkbox
					$bf[] = '<td><nobr>';
					$farray = array('type'=>'checkbox', 'name'=>'mark_'.$row['z'], 'value'=>'on');
					$bf[] = '<p style="font-size:70%; color:#888888; margin:0px 0px 0px 0px; white-space: nowrap">'.FieldPut($farray).' '.$row['z'];
					if($area!='store_product') $bf[] = '</td><td>';
					# ord
					$bf[] = $row['ord'];
					if($area!='store_product') $bf[] = '</td>';
					# parentz
					if($area=='store_type') $bf[] = '<td>'.$row['parentz'].'</td>';
					# owner
					if($area!='store_product') $bf[] = '<td>';
					$bf[] = $storenames[$row['store_id']];
					if($area!='store_product') $bf[] = '</td>';
					# NFS
					if($area=='store_product') {
						$bf[] = '<td>'.($row["NFS{$store}"]?'NFS<br />':'');
						$temp = array();
						for($x=1; $x<=NUMSTORES; $x++) {
							if($row["NFS{$x}"]) $temp[]=$x;
						}
						if($temp) $bf[]='<span style="color:red">'.implode($temp,',').'</span>';
						# $bf[] = '<br />'.($row["featured{$store}"]?'Fea.':'');
						$bf[] = '</td>';
					}
					# name/option
					if($area=='store_type') {
						$temp = '<td';
						if(strlen($row['ord'])==1) $temp .= ' style="font-size:130%; font-weight:bold;"';
						if(strlen($row['ord'])==2) $temp .= ' style="font-size:110%; font-weight:bold; padding-left:15px;"';
						if(strlen($row['ord'])==3) $temp .= ' style="padding-left:30px;"';
						if(strlen($row['ord'])==4) $temp .= ' style="font-size:90%; padding-left:45px;"';
						$temp .= '><a href="?'.MakeLink(array('edit'=>$row['z'])).'">'.$row['name'].'</a>';
						$bf[] = $temp;
					} else {
						$temp = '<a href="?'.MakeLink(array('edit'=>$row['z'])).'">'.$row['name'].'</a>';
						if($row['name_differentiator']) $temp.=' ('.$row['name_differentiator'].')';
						if($row["product_option{$store}"]) {
							$temp .= ' - '.$row["product_option{$store}"];
						} elseif($store != $row['store_id'] && $row["product_option{$row['store_id']}"]) {
							$temp .= ' - <span style="color:#bbbbbb;">'.$row["product_option{$row['store_id']}"].'</span>';
						}
						$bf[] = '<td>'.$temp.'</td>';
					}

					if($area=='store_product') {
						$bf[] = '<td>';
						#if($row["price{$store}"]==0 && $store != $row['store_id'] && $row["price{$row['store_id']}"]>0) {
						#	$bf[] = '<span style="color:#bbbbbb;">'.$row["price{$row['store_id']}"].'</span><br />';
						#} else {
						$bf[] = $row["price{$store}"].'<br />';
						# um
						if($row["um{$store}"]=='' && $store != $row['store_id'] && $row["um{$row['store_id']}"]!='') {
							$bf[] = '<span style="color:#bbbbbb;">'.$row["um{$row['store_id']}"].'</span><br />';
						} else {
							$bf[] = $row["um{$store}"].'<br />';
						}
						# qty_discount
						if($row["qty_discounts{$store}"]=='' && $store != $row['store_id'] && $row["qty_discounts{$row['store_id']}"]!='') {
							$bf[] = '<span style="color:#bbbbbb;">'.$row["qty_discounts{$row['store_id']}"].'</span><br />';
						} elseif($row["qty_discounts{$store}"]) {
							$bf[] = $row["qty_discounts{$store}"]; # <span style="color:#888888;">'.$row["qty{$store}"].'</span>
						}
						$bf[] = '</td>';
						# location
						$bf[] = '<td>';
						if($row["location1_id{$store}"]>0 && array_key_exists($row["location1_id{$store}"], $locationname)) {
							$bf[] = '<a href="?'.MakeLink(array('findstr'=>'', 'prodfilter'=>'location1:'.$row["location1_id{$store}"])).'">'.$locationname[$row["location1_id{$store}"]].'</a><br />';
						} else {
							if($row["location1_id{$store}"]>0) $bf[] = "Invalid Location:".$row["location1_id{$store}"].'<br />';
						}
						# manufacturer
						if($row["manufacturer_id"]>0) {
							$bf[] = '<a href="?'.MakeLink(array('area'=>'store_manufacturer', 'edit'=>$row["mz"])).'">'.$manufacturername[$row["manufacturer_id"]].'</a><br />';
							# $bf[] = '<a href="?'.MakeLink(array('findstr'=>'', 'prodfilter'=>'manufacturer:'.$row["manufacturer_id"])).'">'.$manufacturername[$row["manufacturer_id"]].'</a><br />';
						}
						# type
						if($row["type_id"]>0 && array_key_exists($row["type_id"], $typename)) {
							$bf[] = '<a href="?'.MakeLink(array('area'=>'store_type', 'edit'=>$row["tz"])).'">'.$typename[$row["type_id"]].'</a>';
							# $bf[] = '<a href="?'.MakeLink(array('findstr'=>'', 'prodfilter'=>'type:'.$row["type_id"])).'">'.$typename[$row["type_id"]].'</a>';
						} else {
							if($row["type_id"]>0) $bf[] = 'Invalid type:'.$row["type_id"];
						}
						$bf[] = '</td>';
					}
					# description, keywords
					$bf[] = '<td>';
					$temp = $row['description']; if(mb_strlen($temp)>45) $temp = mb_substr($temp, 0, 45).'..';
					if($temp) $bf[] = $temp.'<br />';
					if($row['keywords']) $bf[] = '<i>'.$row['keywords'].'</i>';
					$bf[] = '</td>';
					# picture
					if($area != 'store_type') $bf[] = '<td>'.Picture($row, '', '80px', true).'</td>'; # isset($_POST['withpicture']) || isset($_GET['multisave'])


					$bf[] = '<td>';
					$temp = $row['url']; if(mb_strlen($temp)>35) $temp = '<a target="_blank" href="'.$temp.'">'.mb_substr($temp, 0, 15).'..'.mb_substr($temp, -15).'</a>';
					$bf[] = $temp;
					if($area=='store_product') {
						if(isset($row['sds'])) {
							$temp = $row['sds']; if(mb_strlen($temp)>35) $temp = '<a target="_blank" href="'.$temp.'">'.mb_substr($temp, 0, 15).'..'.mb_substr($temp, -15).'</a>';
							$bf[] = '<br />'.$temp;
						}
						if(isset($row['cas'])) {
							$temp = $row['cas']; $bf[] = '<br />'.$temp;
							$bf[] = '</td>';
						}
					}
				}
				# ord, pic
				#if($row['name']!='') {
				#	$file=PhotoExists($row['name']);
				#	if($file !== false) {
				#		ExecuteSQLi($sql="UPDATE store_product SET filename='{$file}' WHERE product_id={$row['product_id']}");
				#		$row['filename']=$file;
				#	}
				#}
				# description
				$bf[] = '</tr>';
			}
			$bf[] = '</tbody></table>';
			if(isset($_POST['editthem'])) {
				$bf[] = '<input type="submit" value="Save All" name="saveall">';
				$bf[] = '<input type="submit" value="Save All and Continue" name="saveallcontinue">';
			} else {
				$bf[] = ' <input type="submit" value="Delete Them" name="deletethem"> ';
				if(isset($_GET['linktoothers'])) {
					$bf[] = ' <input type="hidden" name="linkthemto" value="'.FilterZ($_GET['linktoothers'], 4).'"> ';
					$bf[] = ' <input type="submit" value="Link Them to Product '.FilterZ($_GET['linktoothers'], 4).'" name="linkthem"> ';
				}
				$bf[] = ' <input type="checkbox" name="confirm" value="on">Confirm';
			}
		}
	}

	# Add
	$bf[] = '<h3><button type="submit" class="btn btn-primary" name="Add1" value="submit">Add</button>';
	$bf[] = ' '.ProperAreaName($area).' of</h3><p>';
	$bf[] = ' Name '.FieldPut(array('securitytype'=>'alphanum', 'name'=>'ProdName', 'width'=>'200px', 'maxlength'=>'99'));
	if($login->Plainsman()) $bf[] = ' Override QRCode (optional) '.FieldPut(array('securitytype'=>'alphanum', 'name'=>'QRCode', 'width'=>'100px', 'maxlength'=>'4'));
	/*
	$bf[] = '</p>';
	$bf[] = '<h4>Also specify or create parents for this '.$area.'</h4>';
	if(isset($tablearr[$area]['parents'])) {
		foreach($tablearr[$area]['parents'] as $parent) {
			$bf[] = '<p>'.ParentPicker($parent).' or create and link '.$parent.' '.FieldPut(array('securitytype'=>'alphanum', 'name'=>$t.'_parent', 'width'=>'120px', 'maxlength'=>'50'));
			$bf[] = ' of code '.FieldPut(array('securitytype'=>'alphanum', 'name'=>$parent.'_code', 'width'=>'80px', 'maxlength'=>'4'));
			$bf[] = '</p>';
		}
	} */
	# $bf[] = '<input type="submit" value="Go" name="Add">';
	#$bf[] = '</p>';
	if(isset($_POST['editthem'])) {
		$bf[] = '<h3>Upload a Picture to All of These Items</h3>';
		$bf[] = "\n".'</form><form name="pictureupload" enctype="multipart/form-data" action="?'.MakeLink().'" method="post">
		<input type="file" name="file" />';
		if(isset($editthem)) $bf[] = '<input type="hidden" value="'.implode($editthem,':').'" name="records">';
		$bf[] = '<input type="submit" name="Upload" value="Upload Picture" />';
	}
}


/*
$fld='filename';
$result = ExecuteSQLi($sql="SELECT product_id, store_id, $fld FROM store_product p WHERE filename <> '' AND filename NOT RLIKE '^http'");
if($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$temp = '/var/www/plainsman/store/files/'.$row['store_id'].'/'.$row[$fld];
		if(!file_exists($temp)) {
			echo '<br />'.$temp;
			echo ' no ';
			$temp1 = '/var/www/plainsman/files/1/'.$row[$fld];
			if(file_exists($temp1)) {
				echo $row['filename'].', ';
			} else {
				echo $temp1.' no';
				$temp1 = '/var/www/plainsman/files/3/'.$row[$fld];
				if(file_exists($temp1)) {
					echo $temp1.' no';
					echo $row['filename'].', ';
				} else {
					echo $temp1.' no';
				}
				# rename($temp1, $temp);
			}
		} 
	}
}
*/

function Picture($row, $width='', $height='', $thumb=false) {
	if($width) $style=' style="width:'.$width.'"';
	elseif($height) $style=' style="height:'.$height.'"';
	$title='';
	$n = 2;
	while($n>0) {
		if($thumb && $row['thumbfile']) {  # array_key_exists('thumbfile', $row) &&
			if(substr($row['thumbfile'],0,4) === 'http') {
				return '<img src="'.$row['thumbfile'].'"'.$style.' />'; # no need for link to thumbnail
			} else {
				$fileloc = STOREFILEDIR.$row['store_id'].'/'.$row['thumbfile'];
				if(file_exists($fileloc)) {
					$url = STOREFILEURL.$row['store_id'].'/'.$row['thumbfile'];
					if(!defined("PHONE")) $title = ' title="'.before(filesize($fileloc)/1000,'.').'k"';
					$temp = '<img src="'.$url.'"'.$style.$title.' />';
					return $temp;
				}
			}
		} elseif($row['filename']) {
			if(substr($row['filename'],0,4) === 'http') {
				return '<a target="_blank" href="'.$row['filename'].'"><img src="'.$row['filename'].'"'.$style.' /></a>';
			} else {
				$fileloc = STOREFILEDIR.$row['store_id'].'/'.(substr($row['filename'],-4)=='.pdf'?'pdf/':'').$row['filename'];
				if(file_exists($fileloc)) {
					$url = STOREFILEURL.$row['store_id'].'/'.(substr($row['filename'],-4)=='.pdf'?'pdf/':'').$row['filename'];
					if(substr($row['filename'], -4)=='.pdf') {
						$temp = '<a target="_blank" href="'.$url.'">'.$row['filename'].'</a>';
					} else {
						if(!defined("PHONE")) $title = ' title="'.before(filesize($fileloc)/1000,'.').'k"';
						$temp = '<a target="_blank" href="'.$url.'"><img src="'.$url.'"'.$style.$title.' /></a>';
					}
					return $temp;
				} else {
					if($row['thumbfile']) $thumb = true; # go round again and try thumbnail
				}
			}
		} else {
			if($row['thumbfile']) $thumb = true;
		}
		$n--;
	}
	return "Picture not found";
}

$bf[] = '</form>';
$bf[] = '</div>'; # bootstrap container
$bf[] = '</body></html>';

echo implode($bf, "\n");

function HierTD($param, $cargo='') {
	if(mb_strlen($param) == 1) return '<td style="font-size:120%; font-weight:bold;">'.$cargo;
	if(mb_strlen($param) == 2) return '<td style="font-size:110%; color:#555555; padding-left:10px;">'.$cargo;
	if(mb_strlen($param) == 3) return '<td style="font-size:100%; color:#777777; padding-left:20px;">'.$cargo;
	if(mb_strlen($param) == 4) return '<td style="font-size:90%; color:#999999; padding-left:25px;">'.$cargo;
	else return '<td>'.$cargo;
}

function MakeLink($param=array()) {
	global $tablearr, $recz, $area, $tableord, $prodfilter, $findstr, $history, $haspictures, $haslocation1, $hasprice, $hastype, $RcpGroup;
	# has the caller passed one var as string eg: zyx=1
	if(is_array($param)) {
		if($param) {
			foreach($param as $k => $v) {
				$bf[$k] = $v;
			}
		}
	} else echo "Error: MakeLink requires array parameter";
	foreach($tablearr as $t => $a) {
		if($recz[$t]) $bf[$a['abbr'].'z']=$recz[$t];
		if(array_key_exists($t, $tableord)) $bf[$t.'ord']=$tableord[$t];
	}
	# $param passed must take precendence
	if(!isset($bf['findstr']) && isset($findstr) && $findstr) $bf['findstr'] = filter_var($findstr, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
	if(!isset($bf['haspictures'])) $bf['haspictures']=$haspictures;
	if(!isset($bf['haslocation1'])) $bf['haslocation1']=$haslocation1;
	if(!isset($bf['hastype'])) $bf['hastype']=$haslocation1;
	if(!isset($bf['hasprice'])) $bf['hasprice']=$hasprice;
	if(!isset($bf['RcpGroup'])) $bf['RcpGroup']=$RcpGroup;
	if(!isset($bf['prodfilter']) && isset($prodfilter) && is_array($prodfilter)) $bf['prodfilter']=$prodfilter[0].':'.$prodfilter[1];
	if(!isset($bf['area'])) $bf['area']=$area;
	if(!isset($bf['history']) && isset($history)) {
		$bf['history']=base64_encode(serialize($history));
		if(sizeof($history) > 9) array_shift($history);
	}
	foreach($bf as $k => $v) $temp[] = "{$k}={$v}";
	return implode($temp, "&");
}

function MakeAButton($buttonname, $label, $tip='', $style='', $levelneeded=0, $type="submit", $value='') {
	# can be imagebutton also
	$bf = array();
	if($type == 'image') { # if image, then label is image\
		$bf[] = '<input type="image" src="'.$label.'"';
		$style = 'vertical-align:bottom';
	} else {	 
		$bf[] = '<input type="'.$type.'"';
	}
	if($value) $bf[] = 'value="'.$value.'"';
	else $bf[] = 'value="'.$label.'"';
	
	$bf[] = 'name="'.$buttonname.'"';
	if($tip) $bf[] = 'title="'.$tip.'"';
	if($style) {
		if(substr($style,0,3)=='id=') $bf[] = $style;
		else $bf[] = 'style="'.$style.'"';
	}
	return implode($bf, ' ').'>';
}

function SaveFileSize($filename, $area, $z) {
	global $login;
	if($filename && substr($filename, 0, 4)!='http' && $filename != '') {
		$file=STOREFILEDIR.$login->Store().'/'.(substr($filename,-4)=='.pdf'?'pdf/':'').$filename;
		if(file_exists($file)) {
			$pdo = NEW myPDO(true);
			$f=array(); $v=array();
			$f[] = 'filesize'; $v[] = filesize($file);
			if(substr($file,-4)!='.pdf') {
				list($width, $height, $type, $attr) = getimagesize($file);
				$f[] = 'filewidth'; $v[] = $width;
				$f[] = 'fileheight'; $v[] = $height;
			}
			$pdo->SetFields($f); $pdo->SetValues($v);
			$pdo->WhereFields(array('z', 'account_id')); 
			$pdo->WhereValues(array($z, $login->AcctID()));
			if($pdo->Prepare('UPDATE', '*', $area)) {
				$r = $pdo->Execute();
			}
		}
	}
}

function PhotoExists($param) {
	global $login;
	static $files;
	if(!isset($param)) return false;
	if($param=='') return false;
	if(!isset($files)) {
		$files = scandir(STOREFILEDIR.$store);
	}
	foreach($files as $file) {
		if(!strpos(" $file", "{$param}.")===false || !strpos(" $file", "{$param},")===false) {
			return $file;
		}
	}
	return false;
}

?>
