<?php 


require __DIR__ . '/vendor/autoload.php';
require_once('config.php');

session_start();
	//     MySQL: mysql:dbname=<dbname>;host=<hostname>
	try {
		$db = new PDO($config['db_connection'], $config['db_username'], $config['db_password']);
	} catch (PDOException $e) {
		die("DB Connection failed: " . $e->getMessage());
	}

$maxFilesizeBytes = $config['max_filesize'] * 1024 * 1024;


if(isset($_POST["upload"]) && $_POST['upload'] == true) {
	
	if(!isset($_SESSION[$config['db_prefix'] . 'rand']) || $_SESSION[$config['db_prefix'] . 'rand']['expire'] + 60 * 30 < time()) {
		$rand = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION[$config['db_prefix'] . 'rand']['rand'] = $rand;
		$_SESSION[$config['db_prefix'] . 'rand']['expire'] = time();
	} else {
		$rand = $_SESSION[$config['db_prefix'] . 'rand']['rand'];
	}
	
	
	
	/////
	
	
	$files = array(); 
	foreach ($_FILES['files'] as $k => $l) {
		foreach ($l as $i => $v) {
			if (!array_key_exists($i, $files)) {
				$files[$i] = array();
			}
			$files[$i][$k] = $v;
		}
	}
	foreach ($files as $file) {
		$_FILES['fileToUpload'] = $file;
		$upload = new \Delight\FileUpload\FileUpload();
		$upload->from('fileToUpload');
		$upload->withTargetFilename(pathinfo($file['name'], PATHINFO_FILENAME));
		
		$upload->withTargetDirectory($config['upload_path'] . '/' . $rand . '/');
		// $upload->withMaximumSizeInMegabytes($config['max_filesize']); // !FIXME: This is not working
		
		try {
			$uploadedFile = $upload->save();
			
			// success
			
			$getFilenameWithExtension = $uploadedFile->getFilenameWithExtension();
			$getFilename = $uploadedFile->getFilename();
			$getExtension = $uploadedFile->getExtension();
			$getDirectory = $uploadedFile->getDirectory();
			$getPath = $uploadedFile->getPath();
			// $uploadedFile->getCanonicalPath()
			
			$ip = $_SERVER['REMOTE_ADDR'];
			$now = time();
			$query = "INSERT INTO " . $config['db_prefix'] . "files (filename, name, extension, path, directory, size, downloads, ip, created) values('$getFilenameWithExtension','$getFilename','$getExtension','$getPath', '$getDirectory', '" . filesize( $uploadedFile->getPath()) . "', '0', '$ip', '$now')"; // create
			$db->exec($query);
			
			
			$message = array(
			"type" => "success"	,
			"message" => "File uploaded successfully!"
			);
			
			// Define the session array key
$sessionArrayKey = $config['db_prefix'] . 'uploadly_files';

// Initialize the session array if it doesn't exist
if (!isset($_SESSION[$sessionArrayKey])) {
    $_SESSION[$sessionArrayKey] = [];
}

// Find the index of the directory if it exists
$index = array_search($rand, array_column($_SESSION[$sessionArrayKey], 'dir'));

if ($index === false) {
    // Directory not found, add a new entry
    $_SESSION[$sessionArrayKey][] = [
        "dir" => $rand,
        "files" => [$uploadedFile->getFilenameWithExtension()],
        "expire" => $now
    ];
} else {
    // Directory found, add the new file
    $_SESSION[$sessionArrayKey][$index]['files'][] = $uploadedFile->getFilenameWithExtension();
}
	
			
		}
		catch (\Delight\FileUpload\Throwable\InputNotFoundException $e) {
			// input not found
			$message = array(
			"type" => "error"	,
			"message" => "No input file provided!"
			);
		}
		catch (\Delight\FileUpload\Throwable\InvalidFilenameException $e) {
			// invalid filename
			$message = array(
			"type" => "error"	,
			"message" => "Invalid filename"
			);
		}
		catch (\Delight\FileUpload\Throwable\InvalidExtensionException $e) {
			// invalid extension
			$message = array(
			"type" => "error"	,
			"message" => "Invalid file extension"
			);
		}
		catch (\Delight\FileUpload\Throwable\FileTooLargeException $e) {
			// file too large
			$message = array(
			"type" => "error"	,
			"message" => "File is too large"
			);
		}
		catch (\Delight\FileUpload\Throwable\UploadCancelledException $e) {
			// upload cancelled
			$message = array(
			"type" => "error"	,
			"message" => "Upload was canceled"
			);
		}
	}
	
	if(isset($_POST['api']) && $_POST['api'] == true) {
		header('Content-Type: application/json');
		echo json_encode($message);
		exit();
	}
	
	
} else {
	$rand = bin2hex(openssl_random_pseudo_bytes(16));
	$_SESSION[$config['db_prefix'] . 'rand']['rand'] = $rand;
	$_SESSION[$config['db_prefix'] . 'rand']['expire'] = time();
}
 ?>


<!DOCTYPE html>
<html>
	<head>
		<title>upLoadly</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="assets/css/water.css" />
		<link rel="stylesheet" href="assets/css/filepond.min.css" />
		<link rel="stylesheet" href="assets/css/main.css" />
	
	<script src="/assets/js/eruda.js"></script>
    <script>eruda.init();</script>
	</head>
	<body>
		<div class="container">
			<h1><a href="./">upLoadly</a></h1>
			
			<hr>
			<?php 
				if(isset($message)) {
					?>
					<div class="alert <?php echo $message['type']; ?>">
						<?php echo $message['message']; 
							if($message['type'] == 'success') {
								$uploadUrl = 'share/' . explode('/', $uploadedFile->getDirectory())[1];
								echo "<br><a href='$uploadUrl'>$uploadUrl</a>";
 							}
						?>
					</div>
					<?php 
				}
			?>
			
			<div id="uploadContainer">
				<form action="?" method="post" enctype="multipart/form-data" id="uploadForm">
    				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxFilesizeBytes; ?>">
    				<input name="files[]" type="file" multiple id="files" required>
    				<button type="submit" name="upload" value="true">Upload</button>
				</form>
				<br>
				<?php if(!isset($_GET['plainUploader']) || $_GET['plainUploader'] != true) { ?>
					<p style="font-size: 0.8em">Having problems with the uploader? <a href="?plainUploader=true">Switch to the plain uploader</a></p>
				<?php } else {?>
					<p style="font-size: 0.8em">We have a newer uploader. <a href="?">Switch to the new uploader</a></p>
				<?php } ?>
			</div>
			
			<hr>
			<?php 
					if(isset($_SESSION[$config['db_prefix'] . 'uploadly_files']) ) {
						unset($files, $file);
						$files = $_SESSION[$config['db_prefix'] . 'uploadly_files'];
						// echo json_encode($files);
						$newFiles = array();
						?>
						<h3>Your files</h3>
						<div class="container">
						<?php
						foreach($files as $file) {
							$dbFile = $db->query("SELECT * FROM " . $config['db_prefix'] . "files WHERE directory='" . $config['upload_path'] . '/' . $file['dir'] . "'")->fetchAll();
							if(count($dbFile) == 0) {
								unset($file);
								continue;
							} else {
								$newFiles[] = $file;
							}
	
							?>
							<p><a href="share/<?php echo $file['dir'];  ?>"><?php echo $file['dir']; ?> (<?php echo count($file['files']); if(count($file['files']) == 1) { echo ' file'; } else { echo ' files'; }?>)</a> - <?php echo $file['expire'] ?></p>
						
						<?php 
						
						} ?>
						</div>
					<?php  
					// echo json_encode($newFiles);
					$_SESSION[$config['db_prefix'] . 'uploadly_files'] = $newFiles;
					
					} ?>
		
		</div>
		
		<?php if(!isset($_GET['plainUploader']) || $_GET['plainUploader'] != true) { ?>
			<script src="./assets/js/filepond-plugin-file-validate-size.js"></script>
			<script src="./assets/js/filepond.min.js"></script>
			
			<script>
			function initPond() {
            // Get a reference to the file input element
            const inputElement = document.querySelector('input[type="file"]');
			FilePond.registerPlugin(FilePondPluginFileValidateSize);
			
            // Create the FilePond instance
            const pond = FilePond.create(inputElement, {
			allowMultiple: true,
			name: "files[]",
			allowReorder: true,
			credits: false,
			allowRevert: false,
			storeAsFile: true,
			maxFileSize: '<?php echo $config['max_filesize']; ?>MB',
			maxTotalFileSize: '<?php echo ($config['max_filesize'] * $config['max_files']); ?>MB',
			maxFiles: <?php echo $config['max_files']; ?>,
			required: true,
			
			allowImagePreview: false,
			instantUpload: false,
			allowProcess: false,
			maxParallelUploads: 3,
			
			server: {
			process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
            // fieldName is the name of the input field
            // file is the actual file object to send
            const formData = new FormData();
            formData.append('files[]', file, file.name);
			formData.append('upload', true);
			formData.append('api', true);
			
            const request = new XMLHttpRequest();
            request.open('POST', 'index.php');
			
            // Should call the progress method to update the progress to 100% before calling load
            // Setting computable to false switches the loading indicator to infinite mode
            request.upload.onprogress = (e) => {
			progress(e.lengthComputable, e.loaded, e.total);
            };
			
            // Should call the load method when done and pass the returned server file id
            // this server file id is then used later on when reverting or restoring a file
            // so your server knows which file to return without exposing that info to the client
            request.onload = function () {
			if (request.status >= 200 && request.status < 300) {
			// the load method accepts either a string (id) or an object
			//alert(request.responseText);
			load();
			} else {
			// Can call the error method if something is wrong, should exit after
			error('oh no');
			}
            };
			
            request.send(formData);
			
            // Should expose an abort method so the request can be cancelled
            return {
			abort: () => {
			// This function is entered if the user has tapped the cancel button
			request.abort();
			
			// Let FilePond know the request has been cancelled
			abort();
			},
            };
			},
			},
			
            });
			
			
            // Easy console access for testing purposes
            window.pond = pond;
			const uploadForm = document.getElementById('uploadForm');
			const filesInput = uploadForm.querySelector('#files');
			
			
			
			// Attach submit event handler to form
			uploadForm.onsubmit = event => {
			event.preventDefault();
			
			pond.processFiles().then((files) => {
			// files have been processed
			location.reload();
			});
			}
			
			}
			
			initPond();
			
			</script>
		<?php } ?>
	</body>
</html>