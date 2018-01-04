<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('html_errors', true);
session_start();
define('START', microtime()); # in other files start with if(!defined('START')) die;
include('../inc/init-plainsman.php');
include('../../mastercopies/fieldgetput.php');
Header("Content-type: text/html; charset=utf-8"); 
ob_end_flush(); # output buffer here to be sure cookies get set above

include('../inc/login.php');
$login = New Login;
if($login->LoggedIn()) {
	$person = $login->Fields();
} elseif($_POST['firstname'] || $_POST['lastname']) {
	# they have filled in data, create account, login them in, NotifyTony()
}

# Center of Medicine Hat
$mhlat = 50.0405; $mhlon = -110.6764;
$ipAddress  = $_SERVER['REMOTE_ADDR'];
$result = json_decode(file_get_contents("http://ip-api.com/json/{$ipAddress}"));
$user_latitude = $result->lat;
$user_longitude = $result->lon;
$distance = distance($mhlat, $mhlon, $user_latitude, $user_longitude);
function distance($lat1, $lon1, $lat2, $lon2) {
    $pi80 = M_PI / 180;
    $lat1 *= $pi80;
    $lon1 *= $pi80;
    $lat2 *= $pi80;
    $lon2 *= $pi80;

    $r = 6372.797; // mean radius of Earth in km
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $km = $r * $c;

    //echo '<br/>'.$km;
    return $km;
}
# from redirect
#$lat=(isset($_GET['lat']))?$_GET['lat']:'';
#$long=(isset($_GET['long']))?$_GET['long']:'';

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html lang='en'>
<head>
<head>
<?php echo ViewPortMeta(); ?>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta description="Content-Type" content="Plainsman store" />
<meta keywords="Content-Type" content="buy, purchase" />
<title>Plainsman E-Store</title>
<style>
body {font-family:sans-serif;}
td {vertical-align:top}
</style>
<script>
var x = document.getElementById("demo");
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(redirectToPosition);
    } else { 
        x.innerHTML = "Geolocation is not supported by this browser.";
    }
}
function redirectToPosition(position) {
    window.location='weather.php.php?lat='+position.coords.latitude+'&long='+position.coords.longitude;
}
</script>
<?php echo '<script type="text/javascript">'."\n".'function popup() { alert("'.$temp.'") }'."\n".'</script>'."\n"; ?>
</head>
<?php
if($errmsg) echo '<body onload="'.$errmsg.'">'."\n"; else echo "<body>\n";
# onload="getLocation()"

$allowed = false;
# if($distance < 200 || isset($_SESSION['zt'])) $allowed = true;
if(isset($_GET['qr'])) $allowed = true;
elseif(isset($_SESSION['zt'])) $allowed = true;
if($allowed && !isset($_SESSION['zt'])) $_SESSION['zt'] = MkPasswd(4); # die('Value: '.$_SESSION['zt']);
if(!$allowed) die("Not allowed unless you scan a qrcode at Plainsman.");

$temp = GetClayPrices('', true);
foreach($temp as $clayname => $clayprice) {
	if($clayprice > 0) {
		$products[$clayname]=array('Name'=>$clayname, 'Price'=>$clayprice, 'Qty'=>'1', 'UM'=>'Box', 'Location'=>'7GgNTaZcY3', 'Supplier'=>'7w8iTaZcY4', 'Type'=>'CS4KFNnk8S');
	}
}
GetMasonProducts();
GetKemperTools();

$locations["7GgNTaZca6"]=array('Name'=>"Tool Cabinet #1");
$locations["7GgNTaZcz5"]=array('Name'=>"Tool Cabinet #2");
$locations["7GgNTaZcY5"]=array('Name'=>"Shelving Unit #1");
$locations["7GgNTaZcY6"]=array('Name'=>"Shelving Unit #2");
$locations["7GgNTaZcY7"]=array('Name'=>"Shelving Unit #3");
$locations["7GgNTaZcY8"]=array('Name'=>"Rack #1");
$locations["7GgNTaZcY9"]=array('Name'=>"Rack #2");
$locations["stainrack"]=array('Name'=>"Stain Rack");
$locations["7GgNTaZcY3"]=array('Name'=>"Clay Warehouse");

$types["7GgwdaZcY3"]=array('Name'=>"Tool");
$types["CS4KFNnk8S"]=array('Name'=>"Clay");
$types["powglaze"]=array('Name'=>"Powdered Glaze");
$types["botglaze"]=array('Name'=>"Bottled Glaze");
$types["stain"]=array('Name'=>"Stains");

$suppliers["7w8iTaZcY4"]=array('Name'=>"Plainsman");
$suppliers["7w8iTaZcY3"]=array('Name'=>"Kemper");
$suppliers["7w8iTaZcY2"]=array('Name'=>"Shimpo");
$suppliers["7w8iTaZcY1"]=array('Name'=>"Amaco");
$suppliers["mason"]=array('Name'=>"Mason");

function ShowProduct($q1, $details) {
	return '<a href="?qr='.$q1.'">'.$details['Name'].'</a> - '.ShowPrice($details);
}
function ShowPrice($details) {
	return '$<b>'.$details['Price'].'</b>'.($details['UM']?'/'.$details['UM']:'');
}
# echo "<h2>Plainsman Catalog</h2>"; # <p>You are about ".intval($distance)." km from city center ($mhlat, $mhlon) - ($user_latitude, $user_longitude).</p>";
if(isset($_GET['qr'])) {
	$qscan =  FilterZ($_GET['qr']);
	$foundit = false;
	foreach($suppliers as $q => $details) {
		if($q == $qscan) { $foundit = true;
			echo '<h2>'.$suppliers[$q]['Name'].'</h2>';
			$plist = array();
			foreach($products as $q1 => $details1) {
				if($details1['Supplier']==$q) $plist[] = ShowProduct($q1, $details1);
			}
			if($plist) echo '<p>'.implode($plist, '<br />').'</p>';
			else echo '<p>No products yet defined for this supplier</p>';
		}
	}
	if(!$foundit) {
		foreach($types as $q => $details) {
			if($q == $qscan) { $foundit = true;
				echo '<h2>'.$types[$q]['Name'].'</h2>';
				$plist = array();
				foreach($products as $q1 => $details1) {
					if($details1['Type']==$q) $plist[] = ShowProduct($q1, $details1);
				}
				if($plist) echo '<p>'.implode($plist, '<br />').'</p>';
				else echo '<p>No products yet defined for this type</p>';
			}
		}
	}
	if(!$foundit) {
		foreach($locations as $q => $details) {
			if($q == $qscan) { $foundit = true;
				echo '<h2>'.$locations[$q]['Name'].'</h2>';
				$plist = array();
				foreach($products as $q1 => $details1) {
					if($details1['Location']==$q) $plist[] = ShowProduct($q1, $details1);
					# add type from supplier at location
				}
				if($plist) echo '<p>'.implode($plist, '<br />').'</p>';
				else echo '<p>No products yet defined for this location</p>';
			}
		}
	}
	if(!$foundit) {
		echo "<h2>Product</h2>";
		foreach($products as $q => $details) {
			if($q == $qscan) { $foundit = true;
				echo '<p>Product: <b>'.$details['Name'].'</b></p>';
				echo '<p>Details: ';
				if($details['Type'] && $types[$details['Type']])
					echo '<a href="?qr='.$details['Type'].'">'.$types[$details['Type']]['Name'].'</a> ';
				if($details['Supplier'] && $suppliers[$details['Supplier']])
					echo 'from <a href="?qr='.$details['Supplier'].'">'.$suppliers[$details['Supplier']]['Name'].'</a> ';
				if($details['Location'] && $locations[$details['Location']])
					echo 'at <a href="?qr='.$details['Location'].'">'.$locations[$details['Location']]['Name'].'</a>';
				echo '</p>';
				echo '<p>Price: '.ShowPrice($details).'</p>';
				$files1 = scandir('photos');
				foreach($files1 as $temp) {
					$thisfile = trim($temp); $thisname = trim($details['Name']);
					if(strpos($thisfile, $thisname)!==false) {
						echo '<img src="photos/'.$thisfile.'">';
					} # else echo 'File:'.$thisfile.', Len:'.strlen($thisfile).', Searching:'.$details['Name'].', Len:'.strlen($details['Name'])."<br />";
				}
				# add type, from supplier, at location
				break;
			}
		}
	}
	if(!$foundit) echo '<p>Requested QRCode not found!</p>';
}

echo '<p>&nbsp;</p><hr />';
# locations
$plist = array();
foreach($locations as $locz => $locinfo) {
	$plist[] = '<a href="index.php?qr='.$locz.'">'.$locinfo['Name'].'</a>';
}
if($plist) echo '<h2>Locations</h2><p>'.implode($plist, ' | ').'</p>';
# types
$plist = array();
foreach($types as $locz => $locinfo) {
	$plist[] = '<a href="index.php?qr='.$locz.'">'.$locinfo['Name'].'</a>';
}
if($plist) echo '<h2>Types</h2><p>'.implode($plist, ' | ').'</p>';
# supplier
$plist = array();
foreach($suppliers as $locz => $locinfo) {
	$plist[] = '<a href="index.php?qr='.$locz.'">'.$locinfo['Name'].'</a>';
}
if($plist) echo '<h2>Suppliers</h2><p>'.implode($plist, ' | ').'</p>';

#echo '<h1>Import Products</h1>';
#echo '<p>Add type, supplier, location. Require product name, option Prod#, um, qty, price</p>';

# if(!defined('PHONE')) 
echo '</body></html>';

function GetClayPrices() {
$clays = 'H431 	20.85	19.95	19.05 	18.15
H435	20.85	19.95	19.05	18.15
H440 	22.75	21.85	20.95	20.05
H440G 	23.25	22.35	21.45	20.55
H441G 	20.75	19.85	18.95	18.05
Raku/Throwing	25.85	24.95	24.05	23.15
H443 	23.90	23.00	22.10	21.20
H450 	25.40	24.50	23.60	22.70	
H550 	21.05	20.15	19.25	18.35
H555 	28.10	27.20	26.30	25.40
H570	31.25	30.35	29.45	28.55
Woodfire 	29.25	28.35	27.45	26.55
Sculpture	27.65	26.75	25.85	24.95
L210 	22.75	21.85	20.95	20.05
L211 	24.90	24.00	23.10	22.20
L212 	24.55	23.65	22.75	21.85
L213	30.20	29.30	28.40	27.50	
L215 	25.15	24.25	23.35	22.45
Terrastone 	25.15	24.25	23.35	22.45
Buffstone	21.30	20.40	19.50	18.60
M325 	20.90	20.00	19.10	18.20
M332 	22.20	21.30	20.40	19.50
M332G 	21.50	20.60	19.70	18.80
M340 	21.75	20.85	19.95	19.05	
M340S 	22.00	21.10	20.20	19.30	
M340GS 	22.15	21.25	20.35	19.45	
M350	23.80	22.90	22.00	21.10
M370	30.25	29.35	28.45	27.55
M390 	25.50	24.60	23.70	22.80	
MSCULP 	25.30	24.40	23.50	22.60
P300 	31.75	30.85	29.95	29.05
P580 	30.55	29.65	28.75	27.85
P600 	33.85	32.95	32.05	31.15
P700 	50.80	49.90	49.00	48.10
Polar Ice 	67.35			
Modelling Clay	19.90	18.90	17.90	16.90';
	$temp = explode("\n", $clays);
	foreach($temp as $line) {
		$items = explode("\t", $line);
		$itemlist[trim($items[0])] = $items[1];
	}
	return $itemlist;
}

function GetMasonProducts() {
	global $products;
$items = 'Mason Color Chart	6.00	ea
6001 Alpine Rose	14.20
6003 Crimson	14.20	
6006 Deep Crimson	14.20
6020 Mang Alumina Pink	7.80
6021 (K5987) Red	28.25
6027 Tangerine	16.50
6088 Dark Red	26.65
6100 Woodland Brown	8.50
6107 Dark Golden Brown	8.00
6121 Saturn Orange	8.00
6160 Dark Chocolate	6.70
6201 Celadon Green	8.40	
6213 (K5994) Hemlock Green	11.60
6242 Bermuda Green	11.05
6300 Mazerine Blue	15.30
6304 Tin Violet	28.80
6305 Teal Blue	10.10
6364 Turquoise Blue	11.00
6376 Robin\'s Egg Blue	10.10
6381 Blackberry Wine	14.20
6385 Pansy Purple	14.30
6404 Vanadium Yellow	17.50
6450 Praseod. Yellow	10.80	
6500 Sage Grey	10.80
6600 Black	12.05
6666 Cobalt Free Black	9.75
6700 White	6.75';
	$temp = explode("\n", $items);
	foreach($temp as $line) {
		$items = explode("\t", $line);
		$itemlist[before($items[0], ' ')] = $items;
	}
	foreach($itemlist as $prodz => $prodinfo) {
		if($prodinfo[1] > 0) {
			$um = '125g bag';
			if(isset($prodinfo[2]) && $prodinfo[2]) $um = $prodinfo[2];
			$products[$prodz]=array('Name'=>$prodinfo[0], 'Price'=>$prodinfo[1], 'Qty'=>'', 'UM'=>$um, 'Location'=>'stainrack', 'Supplier'=>'mason', 'Type'=>'stain');
		}
	}
}

/*
*Indicates discontinued when stock runs out.

Plainsman Glaze Cone 6
Whiteware Gloss - 2.5kg	14.50
Stoneware Gloss - 2.5kg	17.75
Matte - 2.5kg	16.25


Page 10
Cerdec Stains
	125 g 
Red (279496)	23.40
High fire Red (279497)	23.40
Yellow (239416)	23.40
Orange (239616)	23.40

Miscellaneous
Liquid Materials
Gum Solution (litre)	12.80
Mould Rubber (pint)	21.90
Mould Rubber (4 litre)	160.00
Sodium Silicate (litre)	7.50
Sodium Silicate (4 litre)	25.50
Darvan #7 (litre)	18.65
Darvan #7 (4 litre)	70.30
Wax Emulsion (litre)	15.85
Wax Emulsion (4 litre)	57.50
Wax Emulsion Reed (litre)	24.75
Wax Emulsion Reed (4 litre)	95.75 
Mould Soap 	6.95
Other Clay Bodies
Vallauris / Plastisial             Self Hardening Clay
20 Kg	49.00
5 Kg	12.25
50:50 Casting Mix 50 lb bags
Amtalc Talc	24.80	KT#1-4 Ball Clay	25.50
Roma Plastilina
40lb box	205.00
2lb block	11.50
Other - Slip Clay
Porcelain Slip Cone 6 (4 L)	23.50
White Star Slip (4 Litre)	14.00	White Star Slip (20 Litre)	65.00

Amaco - Glaze PC cone 6
PC Series (Colors - Pint) 	16.85	PC-32,48,55,57	21.75	PC-1	28.75
www.chrysanthos.com
Underglazes (All Colors) 500 ML	22.00

LOWFIRE
Superior Glaze (Color) 500 ML	16.50	Superior Glaze (Clear) 5 Litre	78.00
Superior Glaze (Clear) 500 ML	11.75 


Page 11
Books
Magazines
Ceramics Monthly	9.00
Clay Times	8.25
Pottery Making Illustrated	8.25
Miscellaneous
500 Bowls (Lark)	27.00
500 Ceramic Sculpture (Lark)	27.00	500 Cups (Lark)	27.00	500 Figures in Clay (Lark)	32.00	500 Pitchers (Lark)	32.00	500 Plates & Chargers (Lark)	30.00	500 Prints on Clay (Lark)	30.00
500 Raku (Lark)	30.00
500 Teapots (Lark)	30.00
500 Tile (Lark)	30.00
500 Vases (Lark)	30.00	Advanced Raku Tech (ACS)	42.00
Alternative Firing Techniques (Lark) - Watkins	18.00
Barrel, Pit, and Saggar Firing - Dassow	42.00
Celadon Blues (Krause) - Tichane	39.00
Centering - Richards 	41.00	Ceramic Bead Jewelry (Lark) - Heyenen	20.00	Ceramic Glaze Handbook (Lark) - Burleson	27.00	Ceramics for Beginners: Handbuilding (Lark) - Amber	23.00
Ceramics for Beginners: Surfaces, Glazing (Lark) - Pozo	32.00	Ceramics for Beginners: Wheel Throwing (Lark) Reason	34.00	Ceramics: Mastering The Craft (ACS) - Zakin	62.00
Ceramic Extruder - Conrad	35.00
Ceramic Extruding - Latka	45.00
Ceramic Sculpture Inspiring Tech (ACS) - Turner	30.00	Ceramic Sculpture: Making Faces (Lark)	27.00
Ceramic Spectrum (ACS) - Hopper	45.00
Ceramic Studio: Wheel Throwing (Lark)	24.00
Ceramics Ways of Creation - Zakin	62.00
Children, Clay & Sculpture - Topal	30.00
Clay: A Studio Handbook - Pitelka	56.00
Clay & Glazes For The Potter (Krause) - Rhodes/Hopper	60.00
Clay Canvas (Chilton) - Wittig	26.00	Clay Handbuilding (Davis) - Sapiro	25.00
Claywork (Davis) - Nigrosh	76.00
Clay Whistles - Moniot	21.00
Complete Guide to Mid Range Glazes (Lark)	34.00
Complete Guide to High Fire Glazes (Lark)	20.00
Cone 3 Ceramic Formulas (Falkin) - Conrad 	33.00	Contemporary Porcelain - Lane	59.00
Craft and Art of Clay - Peterson	71.00
Craft Of the Potter - Casson	31.00
Creative Ideas For Clay Artists (ACS) - Turner	42.00
Electric Firing: Creative Techniques (ACS) - Turner	36.00
Electric Kiln (Penn) - Fraser	52.00	Electric Kiln Ceramics (ACS) - Zakin	68.00	Essential Guide to Mold Making, Casting (Lark) - Martin	28.00
Exploring Electric Kiln Tech. (ACS) - Dassow	42.00
Extruder Book (ACS) - Baird	63.00
Extruded Ceramics - Panciol	39.00	Extruder,Mold and Tile: Forming Tech (ACS) - Turner	36.00	Figure in Clay - Lark	32.00
Firemarks (Gentle Breeze) - Herr/Rains	40.00
Functional Pottery (ACS) - Hopper	68.00
Glazes (Batsford) - Copper	41.00
Glazes Cone 6  (Penn) - Bailey	47.00
Glazes & Glaze Techniques - Daly	45.00	Glazes and Glazing: Finishing Tech (ACS) - Turner	36.00
Glazes for the Craft Potter (ACS) - Fraser	46.00
Graphic Clay (Lark)	32.00
Hamada - Potter (Kodansha) - Leach	51.00
Hand Formed Ceramics (Krause) - Zakin	62.00
Illustrated Dictionery of Practical Pottery - Fournier	62.00
Impressed and Incised Ceramics (Gentle Br) - Mineque	34.00
Kiln Book, The (ACS) - Olsen	70.00
Large Scale Ceramics (ACS) - Robinson	36.00
Leach Legacy (Sansom) - Whybrow	54.00
Lettering on Ceramics (ACS) - White	30.00
Low Fire - Nigrosh	38.00
Lustre Pottery (Gentle Breeze) - Caiger-Smith	63.00	Made of Clay (BC Guild)	45.00	Making & Installing Handmade Tiles (Lark) - Pozo	22.00
Making Marks (ACS) - Hopper	45.00	Mastering Cone 6 Glazes - Hesselberth/Roy	46.00	Masters: Earthenware (Lark)	32.00
Masters: Porcelain (Lark)	30.00
Mastering Raku (Lark) - Branfman	39.00	Modelling The Figure in Clay - Lucchesi	36.00
Mosaic Book - Vance/Clarke	34.00
Mud Pie Dilemma (ACS) - Nance	24.00
Out of the Earth  Into the Fire (ACS) - Obstler	69.00
Paperclay - Gault	46.00
Potters Comp Book of Clay & Glazes (Watsun) - Chap	83.00
Potters Directory of Shape and Form - French	30.00
Potters Palette (Krause) - Constant/Ogden	31.00
Potters Primer (Krause) - Hall	37.00
Potters Professional Hdbk (Krause) - Branfman	44.00	Pottery: A Guide to Advanced Techniques - Wensley	54.00
Pottery in Alberta - Antonelli & Forbes	18.00
Pottery Making Techniques (ACS) - Turner	42.00
Pottery Palace (HBJ) - Gibbons	15.00	Raised In Clay (NC Press) - Sweezy	50.00
Raku (ACS) - Mathieson	39.00
Raku - 2nd Edition (Krause) - Andrews	53.00 
Raku - A Practical Approach - Branfman	45.00
Raku Firing, Advanced Tech. (ACS) - Jones	30.00	Raku, Pit and Barrel (ACS) - Turner	30.00	Raku Pottery (Pebble Press) - Piepenburg	47.00
Raku, The Complete Potter - Byers	34.00
Revolution in Clay (Scripps) - MacNaughton	65.00
Safety in The Ceramic Studio - Zamek	33.00
Salt Glazed Ceramics (Chilton) - Mansfield	60.00
Sculpting Clay - Nigrosh	43.00
Sculpture as Experience (Krause) - Peck	30.00
Slips and Slipware (Batsford) - Phillips	33.00
Smoke-Fired Pottery - Perryman	63.00
Soda, Clay and Fire (ACS) - Nichols	35.00	Soda Glazing - Tudball	39.00
Stoneware Glazes (Bootstrap) - Currie	62.00	Studio Ceramics Dictionary - Conrad	26.00
Studio Practices, Tech. & Tips (ACS) - Turner	42.00
Surface Decoration Low Fire  (Lark) - Peters	41.00	Surface Decoration: Finishing Tech (ACS) - Turner	36.00	Surface Design for Ceramics (Lark) - Mills	24.00
Throwing & Handbuilding: Forming Tech (ACS) - Turner	36.00
Tony Birks Pottery (A&C Black)	47.00
What Every Potter Should Know - Zamek	42.00	Wood Firing Journeys & Techniques (ACS)	42.00
Working with Tiles 	24.00	


Page 12, 13
Cones
Jr.(Small 50/box)	12.20	Std. (Large 50/box)	17.65
Self Supporting (25/box)	12.70	Plaques	1.85
Exhaust Systems
Orton Vent Master	615.00
Orton Vent Master Expansion Kit	235.00
Skutt Envirovent	745.00
Kiln Parts
Interties Olympic (pair)	48.00	Interties Skutt (pair)	45.00
Kanthal Wire (Per Metre) - 16 gauge	2.50
Peephole Plugs Olympic	8.50	Peephole Plugs Skutt	7.25
Feeder Wire Set - 3 Ring Skutt	60.00
Harness Wire Set - 3 Ring Skutt	45.00
Refractories
Soft brick K23, 2300 F(each) 9 x 4 1/2 x 2 1/2	7.85
Soft brick K23, 2300 F (12/box)	84.80
Firebrick Electric Grooved	15.00
Fibrefax 2ft Wide x 1' (per ft.) 2300 F	5.75
Fibrefax (per case 25') 2300 F	129.40
Thermolite Castable 25 kg Bag	37.50
Thermolite Castable 2.5 kg	6.55
Pre-Mixed Superfine Mortar 25 kg Pail 	99.65
Gas Kiln Accessories
Heritage Burner Straight	155.00
Heritage Burner 90o	175.00
Manual Firing Valve (Ball Valve)	17.50
Safety Shut-Off Valve	215.00
Pilot  Assembly Only 	75.00
Thermocouple Only	42.00
Blank Burner Orifice	9.00
Raku Tongs, T1, straight 35"	37.50
Raku Kiln Burner System	175.00
For Softbrick and Fibrefax, please see "Refractories"
Kiln Switches/Relays
Olympic 3 pos. 	48.00
Olympic Infinite	48.00
Skutt 3 pos.	75.00
Skutt Infinite	49.50
Coneart 3 pos.	35.00
Coneart Infinite	47.00
Plainsman Infinite	45.00
Skutt Relay P+B 12 Volt Black	42.75
Skutt Relay Detrol 25 Amp Clear	52.00
Cone Art Relay 12 Volt	38.80
ConeArt Relay 240 Volt	52.00
Kilns
Pyrometers
Olympic	180.00	Skutt	180.00
Industrial Pyrometer system	445.00
(complete with thermocouple and mounting plate)
Digital Pyrometer System Skutt	205.00	Dual Input for Pyrometer - T/Couple/Bloclk/Wire	79.00
Dawson Sitters & Accessories
No Tube Or Timer	150.00	No Tube w/Timer	212.00
Sensing Rod	14.20	Rod Claw	11.50
Cone Supports (Set)	.........		14.25
Replacement Tubes Skutt	78.00
Replacement Tubes Cone Art	75.00
Porecelain Contact Block Assembly	107.00
Firing Gauge	7.75
Timer (Limit Timer Motor Only)	75.00
Plunger Button	12.50
Kiln Elements
Olympic 129FL" 	Call	Olympic 18" 	46.50
Olympic 23" 	65.00	Olympic 28"	72.00
Skutt 818-3	69.50
Skutt 1027-3, 1018-3  	89.00
Skutt 1227-3  	89.00
ConeArt 1822	Call
ConeArt 2327	Call 
ConeArt 2818, 2827  	Call
Element Connectors - Brass	2.50
Element Connector Wires (10")	3.25
Element Wire ConeArt - foot	2.05
Element Wire Skutt 12g - foot	3.85
Element Wire Skutt 14g - foot	3.05
Skutt Element Connectors (pkg of 6)	16.50
Thermocouples
Olympic Metal (specify for pyrometer or controller)	75.00
Terminal Blocks	20.00
Pyradia 30"	45.00	Alpine 14"	55.50
Skutt #1594 Metal (electronic control)  	95.00
Skutt #1515 c/w Insulators (electronic control)	35.00
Skutt #1595 c/w Plug (KM-1 wall mount controller)	95.00
Skutt #0021 c/w Wire for Pyrometer	75.00
Cone Art 8 Gauge - 8"	37.50	Cone Art 8 Gauge - 12"	44.50
Thermocouple Protector Tubes	30.00
Kiln Furniture
(Shelves Can be Cut at 3.50 Per Cut)
High Alumina
10" Full round	14.00	21" Half round	43.00
12" Full round	20.00	21" Full round	85.50
13" Full round	28.50    26 1/2" Half round	66.50
15 1/2" Half round	18.70	26 1/2" Full round	126.10
15 1/2" Full round	39.10	

High Alumina Rectangular
10 1/2x21x3/4	47.00	14x28x1  	119.50
12x24x3/4	64.00	16x16x5/8	51.70
12x24x1	80.00	18x18x5/8	55.00
21x21x3/4	88.00	20x20x3/4	86.00
8x16x5/8	22.00	13x26x3/4	65.00
Kiln Posts (1.5" x 1.5" Square)
1/2	1.35	5"	4.80
1"	1.45	6"	5.80
2"	1.95	8"	7.70
3"	2.90	10"	9.65
4"	3.85	12"	11.75
Steel Stilts (ea.)
Pointed Type A0 1/2"	1.10	Pointed Type A4 1 1/2"	2.00
Pointed Type A1 1"	1.40	Pointed Type A6 2 1/2"	2.15
Pointed Type A2 1 1/4"	1.60	Pointed Type A8 3"	2.50
Bar Stilt 3"	3.10	Pointed Type A10 3 1/2"	2.75
Equipment
Pugmills
Shimpo / Venco / Peter Pugger / Bluebird / Frema	$Call

Slab Rollers/Extruders
North Star 24" Std. Slab Roller Package	1535.00
North Star 24" Polaris Slab Roller Package	1290.00	North Star 24" Super Slab RollerPackage	2230.00
North Star 30" Standard Slab Roller Package	1700.00	North Star 30" Super Slab Roller Package	2805.00
North Star 18" Porta Roller	975.00
North Star Standard Hand Extruder Package	610.00
North Star Stainless Steel Hand Extruder Package	695.00
North Star Big Blue Extruder	1690.00

Frema 30" Slab Roller Package	1275.00
Frema 30" Slab Roller - Only	Call
Frema Extruder package	555.00
Frema Holllow Die Set	85.00


North Star Shelf Truck Pkg.	925.00
North Star Shelf Truck Pkg with shelves	1090.00
Brent Wheels
Model IE Wheel	1360.00	Model B Wheel	1835.00
Model C Wheel	1940.00	Model CXC Wheel	2075.00	Stool	95.00	Splash Pan	90.00

Pacifica  Wheels
GT400 Wheel	1575.00
Splash Pan	95.00

Page 14
Shimpo Wheels
Wheels (pan included)
RK Whisper	1745.00	VL Whisper 	1890.00	VL Lite	1140.00	Aspire	690.00
Stool Adjustable	97.50	Aspire w/Pedal	860.00
Stool Professional Potters ST-4 Speedball	175.00

Splash Pans
Clips(ea)	5.75	Plugs	5.00
One piece Large	70.00	One piece Small	65.00
Two piece (yellow)	135.00	Table (Small)	95.00
Two piece (snap fit)	78.00	RK2 Drive Ring	140.00
Batt Pins (pair)	3.40	Replacement Legs	10.75
Giffin Grip
Giffin Grip	325.00	Bottom Brackets(ea)	7.50
Lidmaster Calipers	17.50
Flex Slider	13.60
Wide Slider	10.00	Basic sliders (ea)	11.50
Foam Pads (each)	1.25	Full Set Rods (15 pc)	57.50
Hands w/Foam (each)	5.00	Set of Rods (3 pc)	16.10
Batts
Wood WonderBat 12"	13.00	Wood WonderBat 14"	16.00	Wood WonderBat 14" w/6" inserts		39.00
Wood Inserts 6" ............(box of 6)		21.00	Batmate 12	13.40	Batmate 14"	14.95	Plaster 12"	10.75	Plaster 14"	12.75
Banding Wheels
Shimpo 25 High	147.00	Shimpo 25 Low	130.00
Shimpo 22 Low	115.00
Shimpo 30 M	172.00
Lazy Susan Bearings	7.25
Laguna #275	65.00
Glaze Equipment
Air Brushes and Supplies
"PAASCHE"
Air Brush Type H Kit	105.00
1 Oz. Glass  Bottle	1.25	1 Oz. Jar Assembly	3.60
3 Oz. Glass Bottle	1.85	3 Oz. Bottle Ass Glass	6.20
3 Oz. Plastic Bottle	2.95	3 Oz. Bottle Ass Plast	4.90
Compressor D500 1/10 H.P.	195.00
Air Brush Type VL Kit (2 Action)	150.00
Air Glaze Pen	239.00
PAINTEC  Glaze Sprayer	75.00
HVLP Glaze Sprayer w/Regulator (NEW)	86.00
Safety
Dust Mask	2.75
Safety Glasses to View Cones	33.75
Kelvar Gloves -per pair	61.50
Asbestos Free  Gloves (400 F)	29.75

Scales & Accessories
Ohaus 760 Triple Beam Scale	335.00
Plastic Scoop w/Counterweight	25.00
Weight Set	70.75
MY Weigh 2500g Digital w/Bowl & Adapter	95.00
MY Weigh Triple Beam Scale w/Weight	125.00
MY Weigh Scoop & Counterweight	25.00
Sieves
Super 6.5" Screen
30M Plastic	35.00	100M Plastic	35.00
60M Plastic	35.00	120M Plastic	35.00
80M Plastic	35.00	200M Plastic	35.00
Talisman
Rotary Sieve Ony	230.00	Hand Sieve	67.00
Replacement Screen	51.75	Test Sieve	23.00
Replacement Brushes ea.	12.70	Brush Holder	19.50
Gleco Trap
Sink Trap	185.00
Replacement Bottles 64oz	11.75
Glaze Decoration
Underglaze Pencils/Crayons/Pens
Blue,Brown,Green,Black,Rose,Yellow	16.50
Underglaze Pens - Axner	9.95
Underglaze Pen Tips - Axner	2.00
Underglaze Pen Refills - Pint	31.50
Glazes, Colours, Lustres
Liquid Bright Metal Colours (2 gm)
Bright Gold Laguna	49.75	Bright Palladium	40.00
Bright Platinum	29.50	Bright Copper	21.00
Bright Bronze	23.10

Sherrill Mudtools
Mudrib - Rubber Ribs	10.50	Mudwire - Cut Off Wire	10.50	Mudwire - Cut Off Wire Heavy Duty	12.00
Mudwire - Cut Off Wire Curly	12.00
Mudshark Needle Tool	12.00
Mud HAX Tool	15.00
Mud Drag Tool	29.50
Mud Do-It-All Tool	29.50
Mud -Shredder	12.00
Mudrib - Bowl Rib	12.00


Page 15
Accessories
Miscellaneous Accessories
Butter Spreaders - W ood	2.20
Clock Numerals (Set)	1.75
Clock Movement Quartz (Hands Included)	7.65
Goblet Stems - Wood	4.15
Honey Dipper 	2.75
Hummingbird Feeders	1.65
Pate Knives - Wood 5.5" 	2.90
Pate Knives - Metal	2.20
Plastic Spigot	5.75
Small Spoon - Wood 	2.85
Sugar Spoon - Wood	2.60
E6000 Adhesive	9.75
APT 2 Ceramic Enhancer	10.90
Corks
Stoppers
Rubber 1/2"	.40
Rubber 5/8"	.50	Rubber 3/4"	.60
Rough Natural
1 1/4" Top Diameter	2.00	1 1/2" Top Diameter	2.80
2" Top Diameter	4.50	2 1/2" Top Diameter	6.35
3" Top Diameter	7.20	3 1/2" Top Diameter	8.80
4" Top Diameter	9.85	4 1/2" Top Diameter	11.95
5" Top Diameter	13.50	5 1/2" Top Diameter	14.00
6" Top Diameter	14.50
Smooth
3/8" Top Diameter	.15	1/2" Top Diameter	.21
3/4" Top Diameter	.30	1"  Top Diameter	.75
1 1/2" Top Diameter	1.75	2" Top Diameter	3.10
2 1/2" Top Diameter	4.05	2 3/4" Top Diameter	4.35
3" Top Diameter	4.85	3 1/2" Top Diameter	5.85
4" Top Diameter	7.40	4 1/2" Top Diameter	8.80
5" Top Diameter	9.80	5 1/2" Top Diameter	12.20
6" Top Diameter	13.50
Cork Pads (Per 100)	3.05	Felt Pads (per 100)	2.85
Oil Lamp Accessories
Burners 3" w/wick	3.05	Chimney Votive 1 5/8"	2.25
Chimney 10"	5.95	Chimney 8-1/2"	5.75
Burner Mini	1.05	Chimney Mini	2.80
Pin Frogs
	1-11	12-47	48+
PF1 (11/8" Diameter)	3.95	3.75	3.55
PF2 (21/16")	7.50	7.15	6.75
PF3 (31/16")	13.25	12.60	11.95
Glass Wick Holders
GW-H	1.45 (each)	 1.30 each (10 dozen)
Wick (3/16")	1.10 (per foot)	.95 (over 50 feet)
Dispenser Pumps
With Rubber (White)	1.75	With Cork (White)	1.75
Dispenser Pumps, Liquid (White,Tan, Black)	1.45
Teapot Handles
Cane Craft
4" Standard	5.85	6" Standard Topknot	8.75
5" Standard	6.70	6" Bnd Sq/Oval	10.30	5" Standard Topknot	7.50	6" Square/Oval	8.50
5" Oval/Square 	7.40	6" Bound Standard	10.30
5" Bnd Square 	9.50	6" Oval Topknot	9.00
5 1/2" Standard 	7.40	7" Standard	8.50
5 1/2" Standard Topknot.	8.45	6" Standard "Aftosa"	4.00	6" Standard	7.90	6" Bamboo	4.00
Tiles White (Bisqued to cone 06)
6" X 6" (50/box)	72.00	6" X 6" (each)	1.60
4.25" X 4.25" (100/box)	94.50	4.25" X 4.25" (each)	1.05	6" Tile Holder	48.95	4" Tile Holder	32.65
Tools
Brushes
Bamboo #2	6.70	Bamboo #6	8.45
Bamboo #8	9.35	Bamboo #12	9.95
Hake 1"	7.00	Hake 1 1/2"	8.35
Hake 2"	10.35	Break Apart	12.50
Hake 3"	14.45
Rollers/Stamps
Mini roller	14.30	Large roller	15.75
Slik sets	13.25	Hand stamp	11.50
Xiem Art Roller 	14.90	Xiem Handles	5.95
Sponges
Elephant Ear Large	5.50	Synthetic 2 1/2" Round	.95
Elephant Ear Medium	4.25	Silk Large	2.50	Mud Sponge Blue	7.50	Xiem Telescopic Spnge	9.95
Mud Sponge Orange	7.50	Mud Sponge White	10.50

Trim Tools
AT Metal Tool	6.90
Brown Replacement Blades	3.35
SSTT Stainless Trim Tool	8.00

Danish
Steel ribbon #1	13.55	Steel ribbon #11/1	13.55
Steel ribbon #11/5	13.55	Steel ribbon #53	13.55
Steel ribbon #57	13.55
Miscellaneous Tools
Bamboo Knife/Comb/Fluting Tool	7.20
Hole Cutter Small, Med, Large -Kent	6.50
Hydrometer	35.00
Stem Turning Tools -Kent	8.10
Xiem Slip Trailer Applicator w/Tips	29.85
4 oz. Jars (empty)	.75
Pint Jars (empty)	1.40
Plastic Spigots	5.75


Page 16
*/
function GetKemperTools() {
	global $products;
$items = '210	17.00
212	17.00
402	8.65
404	8.65
406	8.65
8A1 	8.75
8B2 	8.75
8C3 	8.75
8D4 	8.75
8E5	8.75
8G7 	8.75
8H8 	8.75
Ribbon Tools
8R1 	6.75
8R2 	6.75
8R3 	6.75
8R4 	6.75
8R5 	6.75
8R6 	6.75
8R7 	6.75
8RSS 	30.95
A2  	5.90
A3N	4.60
A3R 	4.45
AB  	4.45
Calipers 
AL8 	10.65
AL10	12.25
AL12	14.25
ATCG	14.40
B3	4.90
BAS 	2.30
BB  	5.15
BK  	15.10
BSL	4.30
BSS 	3.60
C3  	3.00
C4  	3.00
CA10	16.10
CBBH	12.95
CHJ 	5.45
CHS 	6.20
CLS 	11.95
CLS-A	11.95
COH 	5.45
COR 	5.60
CTK7 	38.85
CUB 	6.45
D1 - D10  	5.75
DSS  	28.35
DB  	6.60
DBSL	4.60
DBS	4.60
DBSS	4.45
DCL 	6.45
DCS 	5.60
DG1 	6.45
DPC 	5.30
DR2 	5.30
DR4 	5.05
DR6 	9.65
Dipping Tongs
DTA  	16.55
EMBS	4.30
ELP 	5.05
ELS 	6.45
Fettling Knives
F96 	10.05
F97 	10.05
FCR 	5.30
FPC1	9.80
Rubber Ribs
FRH 	5.75
FRSM	5.75
FRSO	5.75
FRSS	5.60
FT451	7.20
FT452	7.20
FT453	7.60
FTB 	4.60
FW11	5.75
FW12	5.75
FW21	5.75
FW22	5.75
FW31	5.75
FW32	5.75
FWPL 	20.85
FWPS 	20.85
GBC 	6.45
GCK 	6.45
GF2	6.75
GF3	6.47
GFF-01	2.85
GPL  	21.60
GPS  	61.60
GPSC	6.05
HB1 	11.35
Hole Cutters
HC1 	5.15
HC1A	5.75
HC2 	5.60
HC3 	6.30
HC4 	13.65
HCP1	10.95
HCP2	11.65
HCS1	7.20
HCS2	7.90
HCS3	8.20
HCS4	10.05
HM1 	3.85
HM2 	4.00
HTS 	5.60
HTW 	9.50
Industrial Tools
ISF114	41.75
ISF134	42.45
ISF212	43.15
ISRC3	44.65
ISRC5	48.95
ISRS3	43.20
ISRS4	46.05
ISRS6	51.85
ISSA	10.80
ISSB	10.80
ISSC	16.55
ISSD	10.80
ISSE	12.95
ISW38114	45.35
ISW382	45.35
ISW401	45.35
ISW40112	45.35
ISW4012	45.35
ISW4014	45.35
ISW4018	45.35
ISW51112	45.35
ISW512	45.35
ISWB521	45.35
ISWJN112	45.35
Wood Model Tool
JA2 	2.45
JA3	2.45
JA4	2.45
JA5 	2.45
JA6 	2.45
JA7	2.45
JA8 	2.45
JA10	2.45
JA12	2.30
JA13	2.45
JA14	2.45
JA15	2.45
JA16	2.45
JA17	2.45
JA18	2.45
JA20	2.45
JA22	2.45
JA24	2.45
JA28	2.45
JA32	2.45
JA37	2.45
JA104	2.60
JA132	2.60
JA137	2.60
JAS  	24.20	
JPS	18.65
K20 	4.90
K21 	4.60
K23	4.90
K24	5.05
K25 	4.75
K26 	4.75
K27 	5.05
K31 	5.90
K32 	6.20
K33 	6.45
K33L	6.75
K34 	4.00
Cutt Off Wire
K35 	4.45
K36 	4.30
K37T	5.75
K38	5.60
Klay Gun
K45  	23.75
K43 	2.85
K45D	5.75
K45DS	4.90
K46 	15.85
K50 	15.85
KDP 	0.50
KDSP	0.70
KDS13	26.65
KDS24	26.65
KQC280	4.30
KQC320	4.30
KQC400	4.30
KQC600	4.30
KRS1	4.00
KSP1	7.60
KSP4	7.60
LFC 	5.45
LFF 	5.45
LFM 	5.45
LFTS	15.70
Loop Tools
LT1 	5.75
LT2 	5.75
LT3 	5.75
LT4 	5.75
LT5 	5.75
LT6 	5.75
LT7 	5.75
LT8 	5.75
MDT 	4.60
MFTS	9.20
MG1 	4.30
MKN 	7.90
MR1	4.45
MR2	4.45
MR3	4.45
MR4	4.45
MR5	4.45
MR6	4.45
MRS  	24.30
MS01	12.50
MS02	12.50
MS03	12.50
MS04	12.50
MS05	12.50
MS06	12.50
MS07	12.50
MS11	12.50
MS12	12.50
P2  	1.55
P41 	8.50
P47 	8.65
P5D 	8.65
PAD35	11.50
PAS 	5.75
PCB	12.50
Pro-Line Tools
PT210	14.10
PT220C	18.55
PT310	17.25
PT315	16.70
PT320	16.70
PT322	16.70
PT323	16.70
PT325	16.70
PT340	16.70
PT345	16.70
PT350	16.70
PT360	16.70
PT370	16.70
PT410	15.55
PT410S	15.55
PT420	15.55
PT430	15.55
PT440	15.55
PT445	15.55
PT455	15.55
PT460	15.55
PT480L	15.95
PT480S	15.55
PT510	16.70
PT511	15.25
PT512	13.40
PT570	16.70
PT571	15.25
PT572	13.40
PTM10	11.80
PTM15	11.80
PTM20	11.80
PTM30	11.80
PTM50	11.80
PTM55	11.80
PTM60	11.80
PTM70	11.80
PTM80	11.80
PTM90	11.80
PTM95	11.80
PTS10	13.40
PTS20	13.40
PTS30	13.40
PTS50	13.40
PTS60	13.40
PTS70	13.40
PTS80	13.40
PTS85	13.40
Needle Tool
PCN 	1.55
PNH 	2.45
PRO 	3.75
PL1  	17.25	Set
PL2 	15.10	Set
PL3 	14.40	Set
Potters Tool Kit
PTK  	30.25
PT1 	3.15
PT2 	3.85
PT3 	4.15
PT4	4.30
PT5	3.85
PT100	10.05
PT101	9.80
PT110	18.25
PT135	16.70
PT140	18.25
PT150	18.25	
QKT 	3.30
Ribbon Tools
R1  	5.30
R2  	5.30
R3  	5.30
R4  	5.30
R5  	5.30
RSS  	24.45
Wooden Ribs
RB1 	7.45
RB2 	6.45
RB3 	6.30
RB4 	7.05
RB5 	8.65
RB6 	7.75
RB7 	6.60
RB8 	7.90
RBT1	11.45
RBT2	11.45
RBT3	11.45
RBT4	11.45
RBT5	11.45
RBT6	11.45
RBT7	11.45
RBT8	11.45
RBOO	12.80
RDS	4.90
RE2 	15.10
RE3 	15.10
RE4 	15.10
RK37 	82.05
RK45 	82.05
RS100	2.45
RS220	2.45	
RTC	10.50	
Steel Scrapers
S1  	3.30
S3  	3.30
S4  	3.15
S6  	3.00
S10 	4.00
S11S	5.15
S12 	5.30
SA4 	3.30
SA10	4.00	
SB	7.20
SBR 	6.05
SBT 	9.90
SCL	5.15
SCP	3.00
SKT 	6.20
SKT-12 	15.55
SLD 	7.20
SLT 	7.45
SLT-12 	16.10
SMS 	4.75
SPD 	6.60
SPG 	7.35
SPN  	13.65
SPR 	1.15
SSF	4.15
SSM	3.60
SSMF	3.75
SSSF	3.60
SSUF	3.60
STB 	5.60
STCS	11.50
STH 	11.50
SWB 	5.60
SWZ 	5.30
TRM 	9.50
TS1 	17.10
TS2 	18.15
TT1 	16.85
TT2 	16.85
TT3 	16.85
TT4 	16.85
TT5 	16.85
UK  	5.75
UKR 	5.30
UKS	10.05
W1A 	8.75
W1C 	8.35
W1D 	8.65
W2A 	8.35
W3E 	8.35
W5  	8.35
W21 	7.35
W22 	7.35
W23 	7.35
W24 	7.35
W25 	7.35
W26 	7.35
WBEF	6.30
WC1	5.90
WC3	5.90
WC5	5.90
WC15	6.05
WC27	5.90
WE8A	11.80
WE8B	11.80
WE8C	11.80
WE8D	11.80
WE8E	12.25
WG2 	6.05
WLS 	5.45
WOT 	8.75
WS  	6.30
Wood Modelling Tools
WT1 	5.30
WT2 	6.05
WT3 	5.90
WT4 	6.03
WT5 	5.60
WT6 	6.05
WT11	5.75
WT12	6.30
WT15	6.40
WT16	6.30
WT18	6.05
WT19	5.75
WT20	5.90
WT21	5.75
WT22	5.90
WT26	6.05
WT27	5.90
WT28	6.05
WT29	5.75';
	$temp = explode("\n", $items);
	foreach($temp as $line) {
		if(!strpos($line, "\t")===false) {
			$items = explode("\t", $line);
			$itemlist[before($items[0], ' ')] = $items;
		}
	}
	foreach($itemlist as $prodz => $prodinfo) {
		if($prodinfo[1] > 0) {
			$um = 'ea';
			if(isset($prodinfo[2]) && $prodinfo[2]) $um = $prodinfo[2];
			$products[$prodz]=array('Name'=>$prodinfo[0], 'Price'=>$prodinfo[1], 'Qty'=>'', 'UM'=>$um, 'Location'=>'7GgNTaZca6', 'Supplier'=>'7w8iTaZcY3', 'Type'=>'7GgwdaZcY3');
		}
	}
}


?>