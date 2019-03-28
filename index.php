<?php

require_once "simpleSeoReport.php";

if(isset($_POST["url"])){
	
	$url = $_POST["url"];
	
	$report = new SeoReport($url);
	
	$reqHTML = $report->getSeoReport();
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Jupiter - A Simple SEO Report</title>
	<!-- Import Google Icon Font -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<!-- Import materialize.css -->
	<link type="text/css" rel="stylesheet" href="css/materialize.css"  media="screen,projection"/>
	<!-- Import custom CSS -->
	<link type="text/css" rel="stylesheet" href="css/style.css"/>
	<!--Let browser know website is optimized for mobile-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<!-- Add jQuery -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>

<body>
<nav>
    <div class="nav-wrapper teal lighten-2">
      <a href="#" class="brand-logo center"><img src="./img/jupiter.svg" class="logo" width="40px" height="40px">Jupiter SEO</a>
    </div>
  </nav>

<div class="container">
<div class="row">
<div class="col s6 offset-s3">
<div class="input-field col s12" id="search-form">
	<form action="index.php" method="post">
		<div class="row">
			<div class="input-field col s12 center">
				<input id="url" type="text" class="validate" name="url">
				<label for="url">Inserisci l'URL.</label>
				<div class="input-field col s12 center">
					<button class="btn waves-effect waves-light" type="submit" name="action">Analizza
					<i class="material-icons right">search</i>
					</button>
				</div>
			</div>
		</div>
	</form>	
</div>
</div>

	<?php
	if(isset($reqHTML)){
		echo $reqHTML;
	}
	?>

</div>
</div>
	
	<!--JavaScript at end of body for optimized loading-->
	<script type="text/javascript" src="js/materialize.js"></script>

	<script>
		$(document).ready(function(){
    $('.open_h1').click(function(){
    
       $('.show_h1').slideToggle('slow');
       if($(this).text() == 'Riduci')
       {
           $(this).text('Mostrali tutti');
       }
       else
       {
           $(this).text('Riduci');
       }
           });
    
        $('.open_h2').click(function(){

            $('.show_h2').slideToggle('slow');
       if($(this).text() == 'Riduci')
       {
           $(this).text('Mostrali tutti');
       }
       else
       {
           $(this).text('Riduci');
       }
           });
    });
</script>
</body>
</html>