<?php
require_once( 'connect.php' );

/**
 * Functie om één bericht op te halen
 *
 * @param	String			$urlTitel de url title
 * @return	Array / false	array met alle velden uit de databse of false als er geen bericht is
*/
function haalBerichtOp ( $urlTitel = '' ) {
	global $mysqli_link;
	
	if ( empty( $urlTitel ) ) {
		// niets doen
		return false;
	}
	
	$urlTitel = mysqli_real_escape_string($mysqli_link, $urlTitel);
	// haal het bericht uit da database
	$query = "SELECT p.*, c.id as category_id , c.title as category
				FROM `post` as p
				LEFT JOIN categories as c ON c.id = p.category_id
				WHERE p.post_url='$urlTitel' LIMIT 1";
	$mysqli_result = mysqli_query ( $mysqli_link, $query );
	if ( mysqli_error( $mysqli_link ) ) {
		echo "haalBerichtOp: " . mysqli_error( $mysqli_link );
		die( );
	}
	
	// plaats het bericht ( mysql result ) in een ARRAY
	$berichtArr	= mysqli_fetch_assoc( $mysqli_result );
	
	// geef deze array terug
	return $berichtArr;
}




/**
 * Functie om één bericht te verwijderen
 *
 * @param	Int			$id de id van het bericht
 * @return	Boolean		is het wel of niet gelukt
*/
function deleteBericht ( $id = "" ){
	global $mysqli_link;
	$id = (int) $id;

	if ( $id == "" && isset($_POST['id']) ) {
		// niets doen
		return false;
	}
	
	$query = "DELETE FROM `post` WHERE `id`='$id' LIMIT 1";
	$mysqli_result = mysqli_query ( $mysqli_link, $query );
	if ( mysqli_error( $mysqli_link ) ) {
		echo "deleteBericht: " . mysqli_error( $mysqli_link );
		die( );
	}
	
	return $mysqli_result;
}



/**
 * Functie om één bericht te updaten met de nieuwe waardes
 *
 * @param	Int			$id de id van het bericht
 * @return	Boolean		is het wel of niet gelukt
*/
function updateBericht ( $id = "" ) {
	global $mysqli_link;
	
	if ( $id == "" && ! isset($_POST['id']) ) {
		// niets doen
		return false;
	}
	

	$id = (int) $id;
	foreach (array('title', 'intro', 'message') as $veld) {
		$_POST[$veld] = mysqli_real_escape_string($mysqli_link, $_POST[$veld]);
	}
	$_POST['category_id'] = (int) $_POST['category_id'];

	// is er een bericht?
	$query = "SELECT id FROM `post` WHERE `id`='$id' LIMIT 1";
	$mysqli_result = mysqli_query ( $mysqli_link, $query );
	if ( mysqli_error( $mysqli_link ) ) {
		echo "updateBericht: " . mysqli_error( $mysqli_link );
		die( );
	}
	
	if ( mysqli_num_rows( $mysqli_result ) > 0 ) {
		// er is een bericht, nu kun je een update doen	
		
		$datumtijd	= date( "Y-m-d H:i", strToTime($_POST['publish_date']) );
		$url 		= mysqli_real_escape_string($mysqli_link, str_replace(" ", "_", stripslashes($_POST['title'])));
		$res 		= mysqli_query($mysqli_link,
								"	UPDATE `post`
									SET `publish_date` 	= '". $datumtijd ."',
									 	`category_id` 	= '". $_POST['category_id'] ."',
									 	`title` 		= '". $_POST['title'] ."',
									 	`post_url` 		= '". $url ."',
									 	`intro` 		= '". $_POST['intro'] ."',
										`message` 		= '". $_POST['message'] ."'
									WHERE id='{$id}' LIMIT 1");

		if ( mysqli_error( $mysqli_link ) ) {
			echo "updateBericht - EDIT: " . mysqli_error( $mysqli_link );
			die( );
		}
		$res = stripslashes($url);
		
	} else {
		// er is nog geen bericht, maak een nieuwe
		$user_id 	= 1;
		$datumtijd	= date( "Y-m-d H:i", strToTime($_POST['publish_date']) );
		$url 		= mysqli_real_escape_string($mysqli_link, str_replace(" ", "_", stripslashes($_POST['title'])));
		$res 		= mysqli_query($mysqli_link,
									"INSERT INTO `post`
									( `publish_date` , `creation_date` , `title` , `post_url` , `category_id`, `intro` , `message` , `user_id` , `image_source`, `last_updated` )
									VALUES ( '$datumtijd', NOW(), '{$_POST['title']}', '{$url}', '{$_POST['category_id']}', '{$_POST['intro']}', '{$_POST['message']}', '$user_id', '', NOW())");

		if ( mysqli_error( $mysqli_link ) ) {
			echo "updateBericht - ADD: " . mysqli_error( $mysqli_link );
			die( );
		}						

		$res = stripslashes($url);
	}
	
	// geef deze array terug
	return $res;
}







/**
 * Functie om alle berichten op te halen en de titles  weer te geven
 *
 * @param	Number	maxMessages
 * @return	String	geformatteerde string
 */
function getMessages ( $maxMessages = 100 ) {
	global $mysqli_link;
	
	// controleer de variabele
	if ( ! is_numeric( $maxMessages ) ) {
		$maxMessages = 100;
	}
	
	// de output variabele
	$out = "";
	
	// haal de berichten op uit de databse
	$query = "	SELECT p.*, c.id as category_id , c.title as category
				FROM `post` as p
				LEFT JOIN categories as c ON c.id = p.category_id
				ORDER BY p.publish_date DESC LIMIT $maxMessages";
	$mysqli_result = mysqli_query ( $mysqli_link, $query );
	if ( mysqli_error( $mysqli_link ) ) {
		echo "GET Bericht: " . mysqli_error( $mysqli_link );
		die( );
	}
	
	while ( $item = mysqli_fetch_assoc( $mysqli_result ) ) 
	{
		$item['title'] = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
		$item['intro'] = htmlspecialchars($item['intro'], ENT_QUOTES, 'UTF-8');
		$item['post_url'] = '/' . rawurlencode($item['post_url']);
		$out .= "<li>
			<span class='date'>". strftime("%A, %e %B %Y", strtotime($item['publish_date']) ) ."</span><h3>{$item['title']}</h3>
			{$item['intro']}<br />";

		// laat een edit en verwijder knop zien als ik ben ingelogd
		if ( defined("INGELOGD") && true === INGELOGD ) {
			$out .= "<a href='{$item['post_url']}?mode=edit'>Wijzig bericht</a><br />";
			$out .= "<a href='{$item['post_url']}?mode=delete' onclick='return confirm(\"zeker weten?\");'>Verwijder bericht</a><br />";
		}

		$out .= "<a href='{$item['post_url']}'>Lees meer »</a></li>\n";
	}
	
	
	// geef het resultaat terug
	return "<ul id='messages'>" . $out . "</ul>";
}
