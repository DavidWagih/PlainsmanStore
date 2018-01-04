<?php
# echo ViewPortMeta(); # in head

Header("Content-type: text/html; charset=utf-8"); 

echo '<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="'.$metadescrip.'">
		<meta name="keywords" content="'.$metakeys.'">
		<meta name="author" content="">
		<title>'.$metatitle.'</title>
		<!-- Bootstrap core CSS -->
		<link href="bootstrap/css/bootstrap.css" rel="stylesheet">
		<script src="assets/js/jquery.min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>
		<!-- Custom styles for this template -->
		<link href="navbar.css" rel="stylesheet">
		<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
';
if(isset($errmsg)) echo '		<script type="text/javascript">'."\n".'function popup() { alert("'.$errmsg.'") }'."\n".'</script>
';
echo '	</head>
';
if(isset($errmsg)) echo '	<body onload="'.$errmsg.'">'."\n"; else echo "	<body>\n";
echo '
<script>
    $(function () {
        $(\'[data-toggle="tooltip"]\').tooltip();
    });
</script>';

if(!isset($hidenavbar)) { echo '
	<div class="container">
		<!-- Static navbar -->
		<nav class="navbar navbar-default">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>';
					if($_SERVER['PHP_SELF']=='/index.php') echo '
					<span class="navbar-brand">QRCode.Store</span>';
					else echo '
					<a class="navbar-brand" href="/index.php">QRCode.Store</a>';
				echo '
				</div>
				<div id="navbar" class="navbar-collapse collapse">
					<ul class="nav navbar-nav">';
						if($_SERVER['PHP_SELF']!='/index.php') echo '
						<li'.($_SERVER['PHP_SELF']=='/index.php'?' class="active"':'').'><a href="index.php">Home</a></li>';
						if($_SERVER['PHP_SELF']!='/contact.php' && $_SERVER['PHP_SELF']!='/addedit.php') echo '
						<li><a href="contact.php">Contact</a></li>';
						if($login->AcctID()>0) echo '
				        <li class="dropdown">
				          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
				          <ul class="dropdown-menu">
				            <li><a href="addedit.php">Inventory</a></li>
				            <li role="separator" class="divider"></li>
				            <li><a href="#">Locations</a></li>
				          </ul>
				        </li>';
					echo '
					</ul>
					<ul class="nav navbar-nav navbar-right">';
					echo '
					<li><a href="?lang=ES"'.($_SESSION['lang']=='ES'?' class="active"':'').'>ES</a></li>';
					echo '
					<li><a href="?lang=EN"'.($_SESSION['lang']=='EN'?' class="active"':'').'>EN</a></li>';
					if($login->AcctID()==0) echo '
					<li'.($_SERVER['PHP_SELF']=='/signup.php' || isset($_GET['signup'])?' class="active"':'').'><a href="signup.php">SignUp <span class="sr-only">(current)</span></a></li>'.
					'<li'.($_SERVER['PHP_SELF']=='/login.php'?' class="active"':'').'><a href="login/login/main_login.php">Login <span class="sr-only">(current)</span></a></li>';
					else echo '
					<li'.($_SERVER['PHP_SELF']=='/login.php'?' class="active"':'').'><a href="?logout=yes">LogOut <span class="sr-only">(current)</span></a></li>';
					echo '
					</ul>
				</div>
				<!--/.nav-collapse -->
			</div>
			<!--/.container-fluid -->
		</nav>
	</div>         
';
}
?>
