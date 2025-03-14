<?php
    require "../db_connect.php";
    require "../message_display.php";
	require "verify_librarian.php";
	require "header_librarian.php";
?>

<html>
	<head>
		<title>View Books</title>
		<link rel="stylesheet" type="text/css" href="../member/css/home_style.css" />
        <link rel="stylesheet" type="text/css" href="../css/global_styles.css">
		<link rel="stylesheet" type="text/css" href="../css/home_style.css">
		<link rel="stylesheet" type="text/css" href="../member/css/custom_radio_button_style.css">
		<style>
			table {
				width: 100%;
				border-collapse: collapse;
			}
			th, td {
				padding: 10px;
				text-align: center;
				border-bottom: 1px solid #ddd;
			}
			th {
				background-color: #f4f4f4;
			}
			img {
				width: 80px; /* Adjust image size */
				height: 100px;
				object-fit: cover;
				border-radius: 5px;
			}
		</style>
	</head>
	<body>

    <?php
		$query = $con->prepare("SELECT * FROM book ORDER BY title");
		$query->execute();
		$result = $query->get_result();

		if(!$result)
			die("ERROR: Couldn't fetch books");

		$rows = mysqli_num_rows($result);
		if($rows == 0)
			echo "<h2 align='center'>No books available</h2>";
		else
		{
			echo "<form class='cd-form'>";
			echo "<div class='error-message' id='error-message'><p id='error'></p></div>";
			echo "<table cellpadding=10 cellspacing=10>";
			echo "<tr>
					<th>Cover Image<hr></th>
					<th>ISBN<hr></th>
					<th>Book Title<hr></th>
					<th>Author<hr></th>
					<th>Category<hr></th>
					<th>Price (GH₵)<hr></th>
					<th>Copies<hr></th>
				</tr>";

			while ($row = mysqli_fetch_assoc($result)) {
				$imagePath = (!empty($row['image'])) ? "../uploads/" . $row['image'] : "../uploads/default-book.png"; // Default image if none uploaded
				
				echo "<tr>";
				echo "<td><img src='$imagePath' alt='Book Cover'></td>";
				echo "<td>".$row['isbn']."</td>";
				echo "<td>".$row['title']."</td>";
				echo "<td>".$row['author']."</td>";
				echo "<td>".$row['category']."</td>";
				echo "<td>GH₵ ".$row['price']."</td>";
				echo "<td>".$row['copies']."</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "</form>";
		}
	?>
    </body>
</html>
