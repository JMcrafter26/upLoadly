<?php

require_once('../config.php');

try {
    $db = new PDO($config['db_connection'], $config['db_username'], $config['db_password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

if(isset($_GET['file'])) {
	
	$path = $config['upload_path'] . '/' . $_GET['file'];
	// check if file exists in Database
	try {
		$file = $db->query("SELECT * FROM " . $config['db_prefix'] . "files WHERE path='$path'")->fetchAll();
		$file = $file[0];
		
	} catch (Exception $e) {
		$message = array(
		'type' => 'error',
		'message' => 'File not found or has expired'
		);
	}
	
	if(isset($file) && $file && file_exists('../' . $path)) {
		@$db->exec("UPDATE " . $DB_PREFIX . "files SET downloads='" . ($file['downloads'] + 1) . "' WHERE id='" . $file['id'] . "'");
		@$db->exec("UPDATE " . $DB_PREFIX . "files SET lastdownload='" . time() . "' WHERE id='" . $file['id'] . "'");
		header("Location: ../$path");
		//echo json_encode($file);
		//echo $file['downloads'];
		
		exit;
	} else {
		$message = array(
		'type' => 'error',
		'message' => 'File not found or has expired - ' . $path
		);
	}
	
} else {
$message = array(
		'type' => 'error',
		'message' => 'Invalid link'
	);
}

 ?>


<!DOCTYPE html>
<html>
	<head>
		<title>upLoadly</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="../assets/css/water.css" />
		<link rel="stylesheet" href="../assets/css/main.css" />
	
		</style>
	</head>
	<body>
		<div class="container">
			<h1><a href="../">upLoadly</a></h1>
			<hr>
			<?php 
			// echo $path;
				if(isset($message)) {
					?>
					<div class="alert <?php echo $message['type']; ?>">
						<?php echo $message['message']; 
							
						?>
					</div>
					<?php 
				} ?>