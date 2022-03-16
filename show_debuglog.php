<?php
/*
Version: 20201115 - Ajout de boutons
*/

$GLOBALS['ip_autorisees'] = array(
    '83.156.180.79',  // Viry Châtillon
    '78.216.63.36',  // 66300
    '2a01:e34:ed83:f240:b97c:1f76:de63:deae',  // 66300
    '2a01:e34:ed83:f240:1cd7:2c01:395e:7687',  // 66300
    '81.250.134.3'  // agence point com
);

// if (!in_array($_SERVER['REMOTE_ADDR'], $GLOBALS['ip_autorisees'])) {
    // die('Your IP is not allowed to acces this file.');
// }


if (isset($_GET['vide_fichier_log']) && ($_GET['vide_fichier_log'] == 1)) {
	vide_fichier_log();
	
	$url_sans_param = str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
	$url_sans_param = str_replace('?', '', $url_sans_param);
	header('Location:'.$url_sans_param);
	exit;	
}

/**
 * Vide le fichier wp-content/debug.log
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since 20150726
 */
function vide_fichier_log() {
	$f = @fopen('./wp-content/debug.log', 'r+');
	if ($f !== false) {
		ftruncate($f, 0);
		fclose($f);
	}
}


function Afficher_la_pagination($ou = 'haut') {
	$this_file_url = 'show_debuglog.php';
	
	if ($ou == 'bas') {
		echo '<hr class="mt-2 mb-2" />';		
	}
	
	$href = $this_file_url.'?vide_fichier_log=1';	
	echo '<a href="'.$href.'" class="btn btn-danger mr-2 ml-2 mt-2" style="">Vider le fichier de log</a>';
	
	$href = $this_file_url;
	echo '<a href="'.$href.'" class="btn btn-success mr-2 ml-2 mt-2" style="">Rafraichir</a>';
	
	$href = $this_file_url.'?sans_les_notices=1';
	echo '<a href="'.$href.'" class="btn btn-info mr-2 ml-2 mt-2" style="">Rafraichir sans les NOTICES</a>';
	
	$href = $this_file_url.'?juste_les_erreurs=1';
	echo '<a href="'.$href.'" class="btn btn-danger mr-2 ml-2 mt-2" style="">Juste les ERRORS</a>';
	
	if ($ou == 'haut') {
		echo '<hr class="mt-2 mb-2" />';		
	}
	
	if ($ou == 'bas') {
		echo '<br /><br />';		
	}
	
	
}

?><!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<title>Débogage</title>
	</head>
	<body>
	<?php Afficher_la_pagination(); ?>
	<div class="container-fluid">
		<?php
		$file = file('./wp-content/debug.log', true);
		foreach ($file as $f) {
			
			if (isset($_GET['juste_les_erreurs']) && $_GET['juste_les_erreurs']==1 && strpos($f, 'syntax error') === false) {
				continue;
			}
			else {
			}
			
			if (isset($_GET['sans_les_notices']) && $_GET['sans_les_notices']==1 && strpos($f, 'PHP Notice') !== false) {
				continue;
			}
			else {
			}
			
			if (strpos($f, 'syntax error') !== false) {
				$f = '<span style="color:red;">'.$f.'</span>';
			}
			if (strpos($f, 'PHP Warning') !== false) {
				$f = '<span style="color:orange;">'.$f.'</span>';
			}
			echo $f.'<br />';
		}
		?>
	</div>
	<?php Afficher_la_pagination('bas'); ?>
	</body>
</html>
<?php











