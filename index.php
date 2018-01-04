<?php
#define('DATABASE_USER', 'qrcode');
#define('DATABASE_PASSWD', '1f8sU34msq');
# define('DATABASE_NAME', 'qrcode');
define('START', microtime()); # in other files start with if(!defined('START')) die;
session_start();
include('../inc/init-plainsman.php'); # starts session
include('../../mastercopies/fieldgetput.php');
# include('../inc/functions.php');
include('../inc/login.php'); # this handles logout, unsetting $_SESSION['username']
$login = New Login;
$thisphpfile = 'index.php';
if(isset($_GET['store'])) {
    if($_GET['store']>0 && $_GET['store']<5) {
        $_SESSION['store']=$_GET['store'];
    }
}
if(isset($_SESSION['store'])) $store=$_SESSION['store'];
else $store = '1';
#if($login->AcctID()==0) die('Not logged in');
# stores: SELECT name, manufacturer_id FROM manufacturer WHERE store=1
# product types: SELECT name, type_id FROM store_type WHERE store=1

function Picker($table, $id=0, $controlname, $idname, $labelfield='name', $firstchoice=null) {
    global $login;
    $buf = array();
    # $result = ExecuteSQLi("SELECT * FROM $table t LEFT JOIN store_product p ON p.{$idname}=t.{$idname} WHERE p.{$idname} IS NOT NULL ORDER BY t.name"); # not ord
    $result = ExecuteSQLi("SELECT * FROM $table ORDER BY name"); # not ord
    if($result->num_rows > 0) {
        $buf[] = '<select name="'.$controlname.'" style="color:white;" onchange="this.form.submit();">';
        if($firstchoice) $buf[] = '<option value="'.$firstchoice[1].'">'.$firstchoice[0].'</option>';
        while($row = $result->fetch_assoc()) {
            $temp = '<option value="'.$row[$idname].'"';
            if($row[$idname] == $id) $temp .= ' selected'; # $_POST['assign_name_pick']
            $temp .= '>'.stripslashes($row[$labelfield]).'</option>';
            $buf[] = $temp;
        }
        $buf[] = "</select>";
    }
    return implode($buf, "");
}

$result1 = ExecuteSQLi("SELECT location_id, name, z FROM store_location WHERE account_id=1 ORDER BY ord, birthdate");
if($result1->num_rows > 0) while($row1 = $result1->fetch_assoc()) $locationname[$row1["location_id"]] = $row1['name'];
$result1 = ExecuteSQLi("SELECT manufacturer_id, name, z FROM store_manufacturer WHERE account_id=1 ORDER BY ord, birthdate");
if($result1->num_rows > 0) while($row1 = $result1->fetch_assoc()) $manufacturername[$row1["manufacturer_id"]] = $row1['name'];
$result1 = ExecuteSQLi("SELECT type_id, name, z, parentz, ord FROM store_type WHERE account_id=1 ORDER BY ord, birthdate");
if($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
        $typename[$row1["type_id"]] = $row1['name'];
        $typeparent[$row1["type_id"]] = $row1['parentz'];
        $typez[$row1["type_id"]] = $row1['z'];
        $typeord[$row1["type_id"]] = $row1['ord'];
        if(!isset($hometype)) $hometype=$row1['type_id'];
    }
}
$result1 = ExecuteSQLi("SELECT t.type_id, t.name, t.z, t.parentz, t.ord FROM store_type t LEFT JOIN store_product p ON p.type_id=t.type_id WHERE t.account_id=1 AND p.price{$store}>0 GROUP BY t.ord, t.birthdate");
if($result1->num_rows > 0) {
    while($row1 = $result1->fetch_assoc()) {
        if(!isset($firstord)) $firstord=$row1['type_id'];
        $typehasproducts[$row1["type_id"]] = true;
        if(strlen($row1['ord']) > 1) {
            $temp = GetParent($row1['type_id']); if($temp) $typehasproducts[$temp] = true;
        }
        if(strlen($row1['ord']) > 2) {
            $temp = GetParent($temp); if($temp) $typehasproducts[$temp] = true;
        }
    }
}

function GetSiblings($id) {
    global $typeord;
    $ord = $typeord[$id]; $s=strlen($ord);
    $bf = array();
    foreach($typeord as $tid => $od) {
        if($s==1 && strlen($od)==1) {
            $bf[] = $tid;
        } elseif(strlen($od)==$s && substr($ord,0,$s-1)==substr($od,0,$s-1)) {
            $bf[] = $tid;
        }
    }
    return $bf;
}
function GetChildren($id) {
    global $typeord;
    $ord = $typeord[$id]; $s=strlen($ord);
    $bf = array();
    foreach($typeord as $tid => $od) {
        if(strlen($od)==$s+1 && substr($od,0,$s)==$ord) {
            $bf[] = $tid;
        }
    }
    return $bf;
}
function GetParent($id) {
    global $typeord;
    $ord = $typeord[$id]; $s=strlen($ord);
    if($s==1) return 0;
    foreach($typeord as $tid => $od) {
        if(strlen($od)==$s-1 && substr($ord,0,$s-1)==$od) return $tid;
    }
    return 0;
}

$searchfor = '';
if(isset($_GET['find'])) $_POST['SearchFor']=urldecode($_GET['find']);
if(isset($_POST['SearchFor'])) {
	$searchfor = preg_replace('/[^a-zA-Z0-9 ]\'/', '', $_POST['SearchFor']);
    if($searchfor == '123') {
        $store=1;
    }
}

if(isset($_GET['type']) && $_GET['type']>0) {
    #if(array_key_exists(intval($_GET['type']), $typename)) {
        $typelist[]=intval($_GET['type']);
        $tp=intval($_GET['type']);
    #}
}
if(isset($_POST['ProductType'])) $typelist[]=intval($_POST['ProductType']);
if(isset($typelist) && !isset($typename[$typelist[0]])) unset($typelist);

if(isset($_GET['manufacturer'])) $getmfg=intval($_GET['manufacturer']);
if(isset($_POST['ProductManufacturer'])) $getmfg=intval($_POST['ProductManufacturer']);
if(isset($getmfg) && !isset($manufacturername[$getmfg])) unset($getmfg);

if(isset($_POST['Reset'])) {
    if(isset($typelist)) unset($typelist);
    $getmfg = -1; $searchfor = '';
}

if($searchfor) {
    # exact match for type, manufacturer: Highest priority
    if(!isset($typelist)) {
    	# only find type that have products
        $result = ExecuteSQLi("SELECT t.name, t.type_id FROM store_type t LEFT JOIN store_product p ON p.type_id=t.type_id WHERE t.store={$store} GROUP BY t.name"); # AND p.product_id IS NOT NULL 
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if(strcasecmp($row['name'], $searchfor)==0) {
                	$typelist[]=$row['type_id'];
                    # $searchfor = '';
                    break;
                }
            }
        }
    } elseif(!isset($getmfg)) {
        $result = ExecuteSQLi("SELECT m.name, m.manufacturer_id FROM store_manufacturer m LEFT JOIN store_product p ON p.manufacturer_id=m.manufacturer_id WHERE store={$store} AND p.product_id IS NOT NULL GROUP BY m.name");
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if(strcasecmp($row['name'], $searchfor)==0) {
                    $getmfg=$row['manufacturer_id'];
                    echo $getmfg;
                    $searchfor = '';
                    break;
                }
            }
        }
    }
}
# set up $where, priority is search
if($searchfor) {
    echo "Searching for $searchfor<br />";
    # approx match in types, manufacturers? Make links to choose
    $pdo = NEW myPDO(true);
    $f = array(); $v = array();
    $f[] = 'concat(name, keywords) RLIKE ?'; $v[] = $searchfor;
    $pdo->WhereFields($f); $pdo->WhereValues($v);
    # $joins[] = "LEFT JOIN store_type ON type.type_id=p.type_id";
    $pdo->SetOrder('name');
    $n = $pdo->Prepare('SELECT', 'type_id, name', "store_type"); # .implode($joins, ' ')
    $n = $pdo->Execute(); # returns false or the row count
    if(isset($_POST['showsql'])) echo $pdo->SQL();
    if($n) {
        $STH = $pdo->GetResult();
        while($row = $STH->fetch(PDO::FETCH_ASSOC)) $typelist[]=$row['type_id'];
    }
    $pdo = NEW myPDO(true);
    $pdo->WhereFields($f); $pdo->WhereValues($v);
    $pdo->SetOrder('name');
    $n = $pdo->Prepare('SELECT', 'manufacturer_id, name', "store_manufacturer"); # .implode($joins, ' ')
    $n = $pdo->Execute(); # returns false or the row count
    if(isset($_POST['showsql'])) echo $pdo->SQL();
    if($n) {
        $STH = $pdo->GetResult();
        while($row = $STH->fetch(PDO::FETCH_ASSOC)) $typelist[]=$row['type_id'];
    }
    $where[] = "concat(p.name,p.description) RLIKE '{$searchfor}' AND p.price{$store}>0";
} elseif(isset($tp)) {
    $where[] = "p.type_id IN (".implode($typelist, ', ').") AND p.price{$store}>0";
    $title = $typename[$tp];
} elseif(isset($getmfg) && $getmfg > 0) {
    $where[] = "p.manufacturer_id=".$getmfg." AND p.price{$store}>0";
    $title = $manufacturername[$getmfg];
} else {
    $where[] = "false";
}
$where[] = "NOT NFS{$store}";
if(isset($_GET['type'])) $where[] = 'p.type_id='.intval($_GET['type']);
#$where[] = "(p.filewidth > 0 OR substring(p.filename, 1, 4)='http')";
$msql = "SELECT p.product_id, p.name, p.name_differentiator, p.description, p.price{$store}, p.filewidth, p.fileheight, p.thumbfile, p.filename, p.qty{$store}, p.um{$store}, p.qty_discounts{$store}, p.product_option{$store}, m.name as mname, t.name as tname, t.type_id FROM store_product p 
LEFT JOIN store_manufacturer m ON m.manufacturer_id=p.manufacturer_id
LEFT JOIN store_type t ON t.type_id=p.type_id";
if(isset($where)) $msql .= " WHERE ".implode($where, ' AND ');
$msql .= " GROUP BY p.name, p.name_differentiator LIMIT 0,300"; # ORDER BY p.name, p.product_option{$store} 
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <title>Plainsman Products</title>
    <!-- <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"> -->
    <link rel="stylesheet" type="text/css" href="./css/app.css">
</head>
<body><?php
    if(isset($_POST['showsql']) || isset($_GET['showsql'])) {
        echo $msql.'<br />';
        print_r($_POST);
    }
    #if(!isset($_GET['find'])) {
        echo '<form method="POST" action="index.php" style="margin-top:0px;">';
        echo FieldPut(array('securitytype'=>'alphanum', 'name'=>'SearchFor', 'width'=>'150px', 'maxlength'=>'50', 'value'=>$searchfor, 'style'=>'color:white;')).' ';
        echo '<input type="submit" value="Search" name="Search" style="color:white;"> ';
        # echo '<input type="submit" value="Reset" name="Reset" style="color:white;"> ';
        echo '<input type="checkbox" value="on" name="showsql"><br />';
        echo '</form>';
    #}
    # menu
    $tlist=array(); $typepickids=array();
    $spanreverse='<span style="color:black; background-color:white; padding:0px 4px 0px 4px;">';
    $spanbig='<span style="font-size:120%;">';
    $spantype='<span style="white-space:nowrap">';
    echo '<p class="crumb">';
    if($tp==0) { # main menu
        $temp = GetSiblings($hometype);
        foreach($temp as $x) {
            if(isset($typehasproducts) && array_key_exists($x, $typehasproducts)) $tlist[]='<a href="?type='.$x.'&xyz=1">'.$typename[$x].'</a>';
        }
        echo implode($tlist, ' | ');
    } else {
        $x = GetParent($tp);
        if($x) {
            # if parent show it, and this
            $y = GetParent($x);
            if(!$y) echo $spanbig.'<a href="?type=0&xyz=2">Home</a></span> -> ';
            else echo $spanbig.'<a href="?type='.$x.'&xyz=3">'.$typename[$y].'</a></span> -> ';
            echo $spanbig.'<a href="?type='.$x.'&xyz=4">'.$typename[$x].'</a></span> -> ';
        } else {
            echo $spanbig.'<a href="?type=0&xyz=5">Home</a></span> -> ';
        }
        # show children or siblings
        $bf = GetChildren($tp); $foundsome=false;
        if($bf) {
            echo $spanbig.'<a href="?type='.$tp.'&xyz=6">'.$typename[$tp].'</a></span><br />';
            foreach($bf as $x) {
                if(isset($typehasproducts) && array_key_exists($x, $typehasproducts)) {
                    $tlist[]=$spantype.'<a href="?type='.$x.'&xyz=7">'.$typename[$x].'</a></span>';
                    $foundsome = true; $typepickids[]=$x;
                }
            }
            if($foundsome) echo implode($tlist, ' | '); else echo 'Coming Soon';
        } else {
            $temp = GetSiblings($tp);
            foreach($temp as $x) {
                if(isset($typehasproducts) && array_key_exists($x, $typehasproducts))  {
                    if($x==$tp) $tlist[]=$spanreverse.$typename[$x].'</span>';
                    else $tlist[]=$spantype.'<a href="?type='.$x.'&xyz=8">'.$typename[$x].'</a></span>';
                    $foundsome = true; $typepickids[]=$x;
                }
            }
            if($foundsome) echo implode($tlist, ' | '); else echo 'Coming Soon';
        }
    } 
    echo '</p>';

    $result = ExecuteSQLi($msql);
    if($result->num_rows > 0) {
        if($searchfor) echo "<h1>Searching Product Names: {$searchfor}</h1>"; 
        if($title) echo "<h1>".$title.' - <a href="http://plainsmanclays.com">Home</a>'."</h1>"; ?>
        <div class="masonry">
        <div class="grid-sizer"></div>
        <div class="gutter-sizer"></div>
        <?php
        # excluse stain, cones manufacturers for now, these products need to be moving to a linked product_options table
        while($row = $result->fetch_assoc()) {
            $optionsexist = false; $somedifferent = false; $ops = array(); $prc = array(); $um = array();
            if(isset($p)) unset($p);
            if($row["product_option{$store}"]) { # There are options of items with same name
                $result1 = ExecuteSQLi("SELECT product_option{$store}, price{$store} FROM store_product WHERE name='{$row['name']}' && name_differentiator='{$row['name_differentiator']}'");
                if($result1->num_rows > 0) {
                    $optionsexist = true;
                    while($row1 = $result1->fetch_assoc()) {
                        $ops[] = $row1["product_option{$store}"];
						if(!strpos($row1["price{$store}"], ';')===false) {
							$prc[] = explode(';', $row1["price{$store}"]);
                        } else {
	                        $prc[] = $row1["price{$store}"];
	    				}
                		if(!strpos($row["um{$store}"], ';')===false) {
                			$um = explode(';', $row["um{$store}"]);
                		} else {
                			$um = $row1["um{$store}"];
                		}
	    			}
                    foreach($prc as $temp) {
                        if(isset($p) && $p != $temp) $somedifferent = true;
                        $p = $temp;
                    }
                }
            }
            # $imgURL = 'http://via.placeholder.com/'.$row['filewidth'].'x'.$row['fileheight'];
            if($row['thumbfile']) {
                if(substr($row['thumbfile'],0,4)=='http') $imgURL = $row['thumbfile'];
                else $imgURL = STOREFILEURL."{$store}/".$row['thumbfile'];
            } else {
                if(substr($row['filename'],0,4)=='http') $imgURL = $row['filename'];
                else $imgURL = STOREFILEURL."{$store}/".$row['filename'];
            }
            echo '
            <div class="item">';
            echo '<img class="lazy" data-original="'.$imgURL.'">';
            echo '<span class="white-text hidden">'.$row['mname'].' '.$row['name'].'</span> <br/>';
            if(trim($row['description'])!='') echo '<span class="grey-text hidden">'.$row['description'].'</span> <br/>';
            
            echo '<span class="white-text hidden">';
            if($store && !($optionsexist && $somedifferent)) {
                $prices = array(); $units = array(); $discounts[0] = 1;
                if($row["qty_discounts{$store}"] != '') $discounts = explode(';', $row["qty_discounts{$store}"]); # show always have ;
                if($row["price{$store}"] != '') {
                    if(!strpos($row["price{$store}"], ';')===false) $prices = explode(';', $row["price{$store}"]);
                    else $prices[0] = $row["price{$store}"];
                }
                if($row["um{$store}"] != '') {
                    if(!strpos($row["um{$store}"], ';')===false) $units = explode(';', $row["um{$store}"]);
                    else $units[0] = $row["um{$store}"];
                }
                if(count($discounts)>1) {
                    if(count($discounts)==count($prices)) {
                        for($x=0; $x<count($discounts); $x++) {
                            echo $discounts[$x].': $'.$prices[$x].'/'.implode($units, ' or ').'<br />';
                        }
                    } else {
                        echo "Quantity Price error";
                    }
                } else {
                    if(count($prices)==0) {
                        echo 'Price N/A';
                    } elseif(count($prices)>1) { # multiple prices
                        if($units) {
                            if(count($prices)==count($units)) {
                                for($x=0; $x<count($prices); $x++) {
                                  echo '$'.$prices[$x].' - '.$units[$x].'<br />';
                                }
                            } else {
                               for($x=0; $x<count($prices); $x++) {
                                  echo '$'.$prices[$x].' - '.implode($units, ' or ').'<br />';
                                }
                            }
                        } else {
                            echo "Price error 2";
                        }
                    } else { # one price
                        if(count($units)>1) {
                            echo '$'.$prices[0].' - '.implode($units, ' or ');
                        } elseif(count($units)==1) {
                            echo '$'.$prices[0].($units[0]!='ea'?' - '.$units[0]:''); # one price, one unit
                        } else {
                            echo '$'.$prices[0];
                        }
                    }
                }
            }
            echo '</span>';
            if($optionsexist) {
                echo '<span class="grey-text hidden">'; # <br />Options: 
                if(!$somedifferent) echo '<br />';
                for($x=0; $x<count($ops); $x++) {
                	if($somedifferent) echo '<br />';
                    echo $ops[$x].($somedifferent?': ':', ');
                    if($store && $somedifferent) {
                    	if(is_array($prc[$x])) {
                    		for($y=0; $y<sizeof($prc[$x]); $y++) {
                    			echo ' $'.$prc[$x][$y];
                    			if($um && is_array($um) && isset($um[$y])) echo ' '.$um[$y]; 
                    			elseif($um) echo ' '.$um;
                    			if($y<count($prc[$x])-1) echo ' or ';
                    		}
                    	} else {
                    		echo ' $'.$prc[$x];
		                    # if($x<count($ops)-1) echo ', ';
                    	}
                    }
                }
                echo '</span>';
            }
            if($searchfor && !is_null($row['type_id']) && array_search($row['type_id'], $typepickids)===false) {
	            echo '<br /><span class="white-text hidden">';
	            echo '<a href="?type='.$row['type_id'].'">'.$row['tname'].'</a>'; # .($searchfor?'&find='.urlencode($searchfor):'')
	            echo '</span>';
                $typepickids[] = $row['type_id'];
	        }
            echo '</div>';
        }
        echo '</div>';
    } else {
        # echo '<h1>None found.</h1>';
    } 
    #echo '<form method="POST" action="index.php" style="margin-top:0px;">';
    #echo '<p>'.Picker('store_type', 0, 'ProductType', 'type_id', 'name', array('Product Type', '-1')).'</p>';
    #echo '<p>'.Picker('store_manufacturer', 0, 'ProductManufacturer', 'manufacturer_id', 'name', array('Manufacturer', '-1')).'</p>';
    # echo '<input type="submit" value="Go" name="Go" style="color:white;"> ';
    # echo '<p><a href="?reset=1">Reset</a> your search.</p>';
    #echo '</form>';

    echo '<p><a href="../index.php">Home</a>';
    if(isset($typelist) || isset($getmfg)) echo ' | <a href="index.php">Search</a>';
    echo '</p>';
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.min.js"></script>
    <script src="https://masonry.desandro.com/masonry.pkgd.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.3/imagesloaded.pkgd.min.js"></script>
    <script src="./js/app.js"></script>
</body>
</html>
