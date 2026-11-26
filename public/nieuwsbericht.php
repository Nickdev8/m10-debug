<?php
/*
# maak een nieuw bestandje in je editor
# sla deze op in de folder waar je je nieuws pagina's hebt ( bijvoorbeeld /sites/site1/www/nieuws/ )
# noem dit bestand ".htaccess"
# plaats de volgende code in dit bestand en sla het op:
------------------------------------------------
# zet de engine aan
RewriteEngine On

# zeg wat er moet gebeuren
RewriteRule !(\.) nieuwsbericht.php [L]
------------------------------------------------
# waarbij nieuwsbericht.php de pagina is waar je naartoe linkt
# in deze pagina plaats je de onderstaande code om de url ( titel ) op te halen
------------------------------------------------
<?php
$mijnArr = explode( "/", trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$mijnTitel = end($mijnArr);
?>
------------------------------------------------
*/


require_once('./inc/settings.php');
require_once('./inc/connect.php');
require_once('./inc/functies.php');
require_once('./inc/nieuws_functies.php');


// ik ben een bericht aan het aanpassen, toevoegen of verwijderen?
if ( isset( $_GET['mode'] ) && $_GET['mode'] == "delete" ) {
	// ik ben een bericht aan het verwijderen
	// haal het bericht uit de database
	$mijnArr 		= explode( "/", trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
	$titelUitURL	= rawurldecode(end($mijnArr));
	$mijnBericht	= haalBerichtOp( $titelUitURL );
	if (!$mijnBericht) {
		header('Location: /');
		exit;
	}
	$updateRes 		= deleteBericht( $mijnBericht['id'] );
	$mijnBericht	= array( 'id'=>0, 'category_id'=>'', 'title'=>'', 'intro'=>'', 'message'=>'', 'publish_date'=>date( "Y-m-d H:i", time()) );	
	header('Location: /');
	exit;
	
} elseif ( isset( $_POST['id'] ) ) {
	// ik ben een bericht aan het aanpassen
	$updateRes 		= updateBericht( $_POST['id'] );	
	$titelUitURL 	= $updateRes;
	// haal het bericht uit de database
	$mijnBericht	= haalBerichtOp( $titelUitURL );
	header('Location: /' . rawurlencode($titelUitURL));
	exit;
	
} elseif ( isset( $_GET['mode'] ) && $_GET['mode'] == "new" ) {
	// voeg een nieuw bericht toe
	$titelUitURL 	= 'nieuw bericht';
	$updateRes 		= false;
	// Het formulier openen mag nog geen bericht toevoegen.
	$mijnBericht	= array( 'id'=>0, 'category_id'=>0, 'title'=>'', 'intro'=>'', 'message'=>'', 'publish_date'=>date( "Y-m-d H:i", time()) );
	
} else {
	// haal de titel uit de url
	$mijnArr 		= explode( "/", trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
	$titelUitURL	= rawurldecode(end($mijnArr));
	$updateRes 		= false;
	// haal het bericht uit de database
	$mijnBericht	= haalBerichtOp( $titelUitURL );
}



if (!$mijnBericht) {
    http_response_code(404);
    $siteTitle = 'Bericht niet gevonden';
    include './inc/header.php';
    echo '<div id="content"><h1>Bericht niet gevonden</h1><a href="/">Terug naar nieuws</a></div>';
    include './inc/footer.php';
    exit;
}

// laat het formulier zien
if ( isset( $_GET['mode'] ) && ( $_GET['mode'] == "new" || $_GET['mode'] == "edit" ) ) {
	$edit	= true;
} else {
	$edit	= false;
}



// stel de titel samen
$siteTitle = ( defined('SITE_NAME') )? SITE_NAME." - " : '';

if ( ! isset($mijnBericht['title']) || $mijnBericht['title'] == "" ) {
	$siteTitle .= $titelUitURL;
} else {
	$siteTitle .= $mijnBericht['title'];
}




// wel of niet ingelogd
// hier komt straks een controle
$ingelogd = true;
define( "INGELOGD", true );

// sendError($mijnBericht);


include_once('./inc/header.php');
?>
	<div id="content">
<?php
		
	// geef een melding als de update gelukt is.
	if ( $updateRes ) { 
		echo "<h1>$updateRes</h1>"; 
	}
	
	
	// laat de content zien
    if ( defined("INGELOGD") && true === INGELOGD && true === $edit ) {
		
		foreach (array('title', 'intro', 'message') as $veld) {
			$mijnBericht[$veld] = htmlspecialchars($mijnBericht[$veld], ENT_QUOTES, 'UTF-8');
		}
		echo "<form method='post'>
		<input type='hidden' name='id' value='{$mijnBericht['id']}' />
		<table>
		<tr><td>titel:		</td><td><input type='text' required name='title' style='width:550px;' value='{$mijnBericht['title']}' /></td></tr>
		<tr><td>datum: 		</td><td><input type='text' name='publish_date' style='width:550px;' value='".strftime("%d-%m-%Y", strtotime($mijnBericht['publish_date']) )."' /></td></tr>
		<tr><td>Category:	</td><td><input type='number' min='1' required name='category_id' value='{$mijnBericht['category_id']}'></td></tr>
		<tr><td>inleiding:	</td><td><textarea name='intro' style='width:550px;height:50px;'>{$mijnBericht['intro']}</textarea></td></tr>
		<tr><td>bericht:	</td><td><textarea name='message' style='width:550px;height:200px;'>{$mijnBericht['message']}</textarea></td></tr>
		<tr><td></td><td><input type='submit' value='Opslaan' /></td></tr></table></form>
		";
		
   } else {
	   
		echo "<h1>" . htmlspecialchars($mijnBericht['title'], ENT_QUOTES, 'UTF-8') . "</h1>";
		echo "<h2>".strftime("%A, %e %B %Y", strtotime($mijnBericht['publish_date']) )."</h2>";
		echo '<b>' . htmlspecialchars($mijnBericht['category'], ENT_QUOTES, 'UTF-8') . '</b><br>';
		echo '<em>' . htmlspecialchars($mijnBericht['intro'], ENT_QUOTES, 'UTF-8') . '</em>';
		echo '<p>' . nl2br(htmlspecialchars($mijnBericht['message'], ENT_QUOTES, 'UTF-8')) . '</p>';
   }
   ?>
    
    
	</div>
<?php
include_once("./inc/footer.php");