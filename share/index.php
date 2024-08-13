<?php

require_once('../config.php');

try {
    $db = new PDO($config['db_connection'], $config['db_username'], $config['db_password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}


if(!isset($_GET['url'])) {
	$message = array(
		'type' => 'error',
		'message' => 'Invalid link'
	);
} else {
	$path = $config['upload_path'] . '/' . $_GET['url'];
	// check if file exists in Database
	try {
		$files = $db->query("SELECT * FROM " . $config['db_prefix'] . "files WHERE directory='$path'")->fetchAll();
		// issue = $db->query("SELECT id, title, description, user, status, priority, notify_emails, entrytime FROM " . $DB_PREFIX . "issues WHERE id='$id'")->fetchAll();
	
	} catch (Exception $e) {
		$message = array(
		'type' => 'error',
		'message' => 'File not found or has expired'
		);
	}
	
	
	
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
			
			<?php 
				if(isset($files)) {
					foreach($files as $file) {
					
				
					$img = array(
					"jpg",
					"jpeg",
					"png",
					"gif",
					"bmp",
					"svg",
					"webp",
					"ico"
					);
					$video = array(
					"mp4",
					"webm",
					"mov",
					"avi"
					);
					$audio = array(
					"mp3",
					"m4a"
					);
					
					$categories = json_decode(file_get_contents('extensions.json'), true);
					if(isset($categories[$file['extension']])) {
					$category = $categories[$file['extension']];
					}
					// echo json_encode($file);
					?>
					<div class="container">
					<h3><?php echo $file['filename'] ?></h3>
					<?php
						if(in_array($file['extension'], $img)) { 
							echo '<img src="../'. $file['path'] . '" title="' . $file['filename'] . '" class="preview" />'; 
						} else if(in_array($file['extension'], $video)) {
							echo '<video controls nodownload title="' . $file['filename'] . '" class="preview" source src="../'. $file['path'] . '" type="video/' . $file['extension'] . '"></video>'; 
						
						} else if(in_array($file['extension'], $audio)) {
							echo '<audio controls nodownload title="' . $file['filename'] . '"  src="../'. $file['path'] . '" ></audio>'; 
						
						}
					
					
					
					?> 
					
					<br>
					<a class="button" href="./download.php?file=<?php echo explode('/', $file['directory'])[1] . '/' . $file['filename'] ?>">Download</a>
					
					<?php
					if(isset($category) && $category !== false) { 
					?>
					<p>Category: <b><?php echo strtoupper($category[0]) ?></b><br>
					<?php } else { ?>
						<p>
					<?php } ?>
					Filesize: <b><?php echo round($file['size'] / 1048576, 2); ?>MB<br>
					Downloads: <b><?php echo $file['downloads']; ?></b><br>
					Uploaded: <b><?php echo date('d M Y H:i', $file['created']); ?></b><br>
					Expires: <b><?php
					if(($file['created'] + $config['expire']) > time() &&
					($file['created'] + $config['expire']) > ($file['lastdownload'] + ($config['expire'] * $config['download_extend']))
					) {
					echo date('d M Y H:i', $file['created'] + $config['expire']);
					} else if(($file['lastdownload'] + ($config['expire'] * $config['download_extend'])) > time()) {
					echo date('d M Y H:i', $file['lastdownload'] + ($config['expire'] *  $config['download_extend']));
					} else {
					echo 'Expired';
					}
					?></b>
					</p>
					
					<p style="font-size: 0.8em">Download the file to extend the expiration date</p>
					
					
					
					</div>
					<hr>
					<?php } } ?>