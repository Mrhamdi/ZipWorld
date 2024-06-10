<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $errors = [];
$_SESSION['errors']="";
    $path = 'uploads/';
    $all_files = count($_FILES['files']['tmp_name']);
    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'uploaded_files_') . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
        $errors[] = 'Could not create a zip file';
    } else {
        for ($i = 0; $i < $all_files; $i++) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Disallow .zip files
            if ($file_ext === 'zip') {
                $errors[] = 'File type not allowed: .zip';
                continue;
            }

            // Set a size limit
            if ($file_size > 838860800) { // 800MB limit
                $errors[] = 'File size exceeds limit: ' . $file_name;
            }

            if (empty($errors)) {
                $zip->addFile($file_tmp, $file_name);
            }
        }
        $zip->close();
    }

    if (empty($errors)) {
        // Set headers to force download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="uploaded_files.zip"');
        header('Content-Length: ' . filesize($zipFileName));

        // Read the file
        readfile($zipFileName);

        // Delete the file after download
        unlink($zipFileName);
        $_SESSION['success'] = '';
        exit;
    } else {
        $_SESSION['errors'] = $errors;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZipWorld - Create Zip File</title>
    <meta name="description" content="ZipWorld allows you to easily create zip files from multiple files. Upload your files, and we'll package them into a single zip file for you to download.">
    <meta name="keywords" content="zip, create zip, file compression, upload files, download zip">
    <meta name="author" content="ZipWorld">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #007BFF;
            color: white;
            padding: 10px 0;
            width: 100%;
            text-align: center;
            font-size: 24px;
        }
        .container {
            text-align: center;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            margin-top: 20px;
            flex: 1 0 auto;
        }
        .upload-field {
            padding: 10px;
            border: 2px dashed #007BFF;
            border-radius: 5px;
            cursor: pointer;
            color: #007BFF;
            transition: background-color 0.3s ease;
        }
        .upload-field:hover {
            background-color: #e0f0ff;
        }
        .upload-field input[type="file"] {
            display: none;
        }
        .upload-btn {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .upload-btn:hover {
            background-color: #0056b3;
        }
        .upload-btn:active {
            transform: scale(0.95);
        }
        .animated-btn {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        .message {
            margin-top: 20px;
            color: red;
        }
        .success {
            margin-top: 20px;
            color: green;
        }
        .info {
            margin-top: 20px;
        }
        .footer {
            background-color: #007BFF;
            color: white;
            padding: 10px 0;
            width: 100%;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .loading-overlay.active {
            display: flex;
        }
        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #007BFF;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="header">ZipWorld</div>
    <div class="container">
        <h1>Create a Zip File</h1>
        <form id="uploadForm" action="" method="post" enctype="multipart/form-data">
            <label class="upload-field">
                Click to select files
                <input type="file" name="files[]" multiple required>
            </label><br><br>
            <button type="submit" class="upload-btn animated-btn">Create Zip</button>
        </form>
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
        </div>
        <?php
        if (isset($_SESSION['errors'])) {
            echo '<div class="message">';
            foreach ($_SESSION['errors'] as $error) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
            unset($_SESSION['errors']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success">';
            echo '<p>' . $_SESSION['success'] . '</p>';
            echo '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <div class="info">
            <h2>About ZipWorld</h2>
            <p>ZipWorld is your go-to tool for creating zip files effortlessly. Whether you're a professional needing to bundle documents for clients or a student organizing project files, ZipWorld makes it simple and quick.</p>
            <h2>Features</h2>
            <ul>
                <li>Upload multiple files at once with a modern, intuitive interface.</li>
                <li>Automatic creation of zip files for easy download.</li>
                <li>Supports a wide range of file types, ensuring flexibility for all your needs.</li>
                <li>Quick processing with immediate file download after creation.</li>
            </ul>
            <h2>Benefits</h2>
            <ul>
                <li><strong>Convenience:</strong> Manage and bundle your files efficiently without hassle.</li>
                <li><strong>Space Saving:</strong> Compress files to save storage space on your devices.</li>
                <li><strong>Easy Sharing:</strong> Share multiple files as a single zip file, simplifying file transfers.</li>
                <li><strong>Security:</strong> Reduce the risk of file corruption by bundling files into a zip format.</li>
                <li><strong>Professionalism:</strong> Present files in an organized and professional manner.</li>
            </ul>
            <h2>How to Use</h2>
            <p>Using ZipWorld is straightforward:</p>
            <ol>
                <li>Click on the upload field to select the files you want to zip.</li>
                <li>Ensure your files are under 800MB each and not in .zip format.</li>
                <li>Click the "Create Zip" button and wait for the zip file to be created and downloaded automatically.</li>
            </ol>
        </div>
    </div>
    <div class="footer">

        &copy; 2024 ZipWorld. All rights reserved. | <a href="privacy.html" style="color: white;">Privacy Policy</a> | <a href="terms.html" style="color: white;">Terms of Service</a>
    </div>
</body>
</html>
