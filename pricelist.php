
<?php
define('START', microtime()); # in other files start with if(!defined('START')) die;
session_start();
include('../inc/init-plainsman.php'); # starts session, sets store
include('../inc/login.php'); # this handles logout, unsetting $_SESSION['username']
$login = New Login;
$thisphpfile = 'pricelist.php';
$store=$login->Store();
if($store == 0) die("Could not establish the location your store.");

$sql = "SELECT p.z, p.name, p.price{$store}, p.um{$store}, p.qty_discounts{$store}, 
p.product_option1, p.product_option2, p.product_option3, p.product_option4, p.product_option5, 
store_type.name as tname, store_manufacturer.name as mname, store_type.ord as tord
FROM store_product p 
LEFT JOIN store_location ON store_location.location_id=p.location1_id{$store} 
LEFT JOIN store_type ON store_type.type_id=p.type_id  
LEFT JOIN store_manufacturer ON store_manufacturer.manufacturer_id=p.manufacturer_id 
WHERE p.account_id=".$login->AcctID()." AND NOT NFS{$store} AND p.price{$store}>0";
if(isset($_GET['type'])) $sql .= ' AND p.type_id='.intval($_GET['type']);
$sql .= " ORDER BY tord, p.name, p.name_differentiator, p.product_option{$store}"; # manufacturer.name, 
# echo nl2br($sql)."<br />";
echo '<h1>Pricelist</h1>';
$result = ExecuteSQLi($sql);
if($result->num_rows > 0) {
	$type = '';
	while($row = $result->fetch_assoc()) {
		if($row['tname'] != $type) {
			echo '<h2>'.$row['tname'].'</h2>';
		}
		echo $row['mname'];
		# if($login->ProductEditor()) echo ' <b><a target="_blank" href="?edit='.$row['z'].'&area=product">'.$row['name'].'</a></b>';
		# do not repeat manufacturer name twice if typename specifies it.
		if(substr($row['name'], 0, strlen($row['mname']))==$row['mname']) echo '<b>'.substr($row['name'], strlen($row['mname'])).'</b>';
		else echo ' <b>'.$row['name'].'</b>';
		if($row["product_option{$store}"]) echo ' - '.$row["product_option{$store}"];
		echo ' - ';
		if($row["price{$store}"]) {
			if(!strpos($row["price{$store}"], ',')===false || !strpos($row["um{$store}"], ',')===false) {
				echo '<span style="color:red">Comma instead of semi-colon found in price or units</span><br />';
			} else {
				$prices = explode(';', $row["price{$store}"]);
				$units = explode(';', $row["um{$store}"]);
				$qtydiscounts = explode(';', $row["qty_discounts{$store}"]);
				$pricenum = count($prices); $unitnum = count($units); $discnum = count($qtydiscounts);
				if($pricenum>0) {
					if($row["qty_discounts{$store}"]) {
						echo implode($units, ' or ').' - ';
						if($discnum==$pricenum) {
							$temp = array();
							for($x=0; $x<$pricenum; $x++) {
								$temp[] = '<nobr>'.$qtydiscounts[$x].': $<b>'.$prices[$x].'</b></nobr>';
							}
							echo implode($temp, ', ');
						} else {
							echo '<span style="color:red"># of discounts does not match number of prices</span>';
						}
					} elseif($pricenum==$unitnum) {
						$temp = array();
						if($pricenum>1) {
							for($x=0; $x<$pricenum; $x++) {
								$temp[] = '<nobr>'.$units[$x].': $<b>'.$prices[$x].'</b></nobr>';
							}
							echo implode($temp, ', ');
						} else {
							if($units && $units[0] !='ea') echo implode($units, ' or ').' - ';
							echo ' $<b>';
							echo $prices[0];
							echo '</b>';
						}
					} else {
						if($units && $units[0]!='ea') echo implode($units, ' or ').': ';
						echo '$<b>'.$prices[0].'</b>';
					}
				} else {
					echo $prices[0];
				}
				echo '<br />'; # ' '.$row["qty_discounts{$store}"].
			}
		} else {
			echo '<nobr>'.$row["um{$store}"].': <span style="color:red">No price</span><br /></nobr>';
		}
		$type = $row['tname'];
	}
	#echo '<p style="margin-top:20px"><a href="?">Continue</a></p>';
}

?>
