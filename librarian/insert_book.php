<?php
	require "../db_connect.php";
	require "../message_display.php";
	require "verify_librarian.php";
	require "header_librarian.php";
?>

<html>
	<head>
		<title>Add New Book</title>
		<link rel="stylesheet" type="text/css" href="../css/global_styles.css" />
		<link rel="stylesheet" type="text/css" href="../css/form_styles.css" />
	</head>
	<body>
		<form class="cd-form" method="POST" action="" enctype="multipart/form-data">
			<center><legend>Add New Book Details</legend></center>
			
			<div class="error-message" id="error-message">
				<p id="error"></p>
			</div>
			
			<div class="icon">
				<input class="b-isbn" id="b_isbn" type="number" name="b_isbn" placeholder="ISBN" required />
			</div>
			
			<div class="icon">
				<input class="b-title" type="text" name="b_title" placeholder="Book Title" required />
			</div>
			
			<div class="icon">
				<input class="b-author" type="text" name="b_author" placeholder="Author Name" required />
			</div>
			
			<div>
				<h4>Category</h4>
				<p class="cd-select icon">
					<select class="b-category" name="b_category">
						<option>History</option>
						<option>Comics</option>
						<option>Fiction</option>
						<option>Non-Fiction</option>
						<option>Biography</option>
						<option>Medical</option>
						<option>Fantasy</option>
						<option>Education</option>
						<option>Sports</option>
						<option>Technology</option>
						<option>Literature</option>
					</select>
				</p>
			</div>

			<div class="icon">
				<input class="b-price" type="number" name="b_price" placeholder="Price (GH₵)" required />
			</div>
			
			<div class="icon">
				<input class="b-copies" type="number" name="b_copies" placeholder="Number of Copies" required />
			</div>

			<!-- Image Upload Field -->
			<div class="icon">
				<input class="book" type="file" name="b_image" accept="image/*" required />
			</div>
			
			<br />
			<input class="b-submit" type="submit" name="b_add" value="Add Book" />
		</form>
	</body>

	<?php
		if (isset($_POST['b_add'])) {
			// Check if ISBN already exists
			$query = $con->prepare("SELECT isbn FROM book WHERE isbn = ?");
			$query->bind_param("s", $_POST['b_isbn']);
			$query->execute();
			$query->store_result();

			if ($query->num_rows > 0) {
				echo error_with_field("A book with that ISBN already exists", "b_isbn");
			} else {
				// Handle Image Upload
				$targetDir = "../uploads/";
				$imageName = basename($_FILES["b_image"]["name"]);
				$targetFilePath = $targetDir . $imageName;
				$imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

				// Allowed file types
				$allowedTypes = array("jpg", "jpeg", "png", "gif");

				if (in_array($imageFileType, $allowedTypes)) {
					// Move uploaded file to uploads folder
					if (move_uploaded_file($_FILES["b_image"]["tmp_name"], $targetFilePath)) {
						// Insert book into database
						$query = $con->prepare("INSERT INTO book (isbn, title, author, category, price, copies, image) VALUES(?, ?, ?, ?, ?, ?, ?)");
						$query->bind_param("ssssdis", $_POST['b_isbn'], $_POST['b_title'], $_POST['b_author'], $_POST['b_category'], $_POST['b_price'], $_POST['b_copies'], $imageName);

						if (!$query->execute()) {
							die(error_without_field("ERROR: Couldn't add book"));
						}

						// Redirect to the librarian index page after successful addition
						header("Location: ../librarian/index.php");
						exit();
					} else {
						echo error_without_field("ERROR: Image upload failed");
					}
				} else {
					echo error_without_field("ERROR: Only JPG, JPEG, PNG, & GIF files are allowed.");
				}
			}
		}
	?>
</html>
