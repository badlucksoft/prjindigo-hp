<?php
header('Access-Control-Allow-Origin: https://html5shim.googlecode.com');
header('Access-Control-Allow-Origin: https://oss.maxcdn.com');
header('Access-Control-Allow-Origin: https://stackpath.bootstrapcdn.com');
header('Access-Control-Allow-Origin: https://cdnjs.cloudflare.com');
header('Access-Control-Allow-Origin: https://code.jquery.com');
header('Vary: origin');
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?php echo SITE_NAME;?></title>
<?php

?>
<link rel="preload" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" as="style">
<link rel="preload" href="https://code.jquery.com/jquery-3.3.1.min.js" as="script" crossorigin="anonymous">
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" as="script" crossorigin="anonymous">
<link rel="preload" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" as="script" crossorigin="anonymous">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<!--[if lt IE 9]>
<script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    </head>
<body<?php
?> style="background: indigo; padding: 50px;">
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="<?php echo BASE_URL;?>"><?php
  	if( strcmp('/',THE_URI) == 0 || preg_match('#^\/(index(\.(htm|html|php|asp|aspx|shtml|php3|php4|php5|py))?)?$#i',THE_URI) > 0) {
  	} else {
  		echo SITE_NAME;
  	}
  ?></a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
    <!--<div class="navbar-nav">-->
    <?php
    	outputNavBar();
    ?>
    <!--</div>-->
  </div>
</nav>
<div id="main_body_div" style="background: white; padding: 10px;">
