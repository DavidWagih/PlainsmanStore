<?php 
session_start();


if(haversineGreatCircleDistance(50.03144467,-110.65709329, $_POST['lati'],$_POST['longi'], 6371)<=190) {
	//$_SESSION['WithArea']="yes"; 
	echo "yes";
} else { 
	echo "no";
	//$_SESSION['WithArea']="NO"; 
}
	

function haversineGreatCircleDistance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
}

?>