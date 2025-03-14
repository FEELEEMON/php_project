<?php
	require "../db_connect.php";
	require "../message_display.php";
	require "verify_member.php";
	require "header_member.php";
?>

<html>
	<head>
		<title>My Issued Books - LMS</title>
		<link rel="stylesheet" type="text/css" href="../css/global_styles.css">
		<link rel="stylesheet" type="text/css" href="../css/custom_checkbox_style.css">
		<link rel="stylesheet" type="text/css" href="css/my_books_style.css">
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
				width: 80px;
				height: 100px;
				object-fit: cover;
				border-radius: 5px;
			}
		</style>
	</head>
	<body>
	
		<?php
			$query = $con->prepare("SELECT book_isbn, due_date FROM book_issue_log WHERE member = ?;");
			$query->bind_param("s", $_SESSION['username']);
			$query->execute();
			$result = $query->get_result();
			$rows = mysqli_num_rows($result);
			
			if ($rows == 0) {
				echo "<h2 align='center'>There Are No Issued Books Yet!</h2>";
			} else {
				echo "<form class='cd-form' method='POST' action='#'>";
				echo "<center><legend>My Issued Books</legend></center>";
				echo "<div class='success-message' id='success-message'><p id='success'></p></div>";
				echo "<div class='error-message' id='error-message'><p id='error'></p></div>";
				echo "<table cellpadding='10' cellspacing='10'>
						<tr>
							<th>Cover Image<hr></th>
							<th>ISBN<hr></th>
							<th>Title<hr></th>
							<th>Author<hr></th>
							<th>Category<hr></th>
							<th>Due Date<hr></th>
							<th>Select<hr></th>
						</tr>";

				while ($row = mysqli_fetch_assoc($result)) {
					$isbn = $row['book_isbn'];
					$due_date = $row['due_date'];

					// Fetch book details with proper validation
					$bookQuery = $con->prepare("SELECT title, author, category, image FROM book WHERE isbn = ?;");
					$bookQuery->bind_param("s", $isbn);
					$bookQuery->execute();
					$bookResult = $bookQuery->get_result();

					if ($bookResult->num_rows > 0) {
						$bookDetails = $bookResult->fetch_assoc();
						$title = $bookDetails['title'] ?? "Unknown";
						$author = $bookDetails['author'] ?? "Unknown";
						$category = $bookDetails['category'] ?? "Unknown";
						$imagePath = (!empty($bookDetails['image'])) ? "../uploads/" . $bookDetails['image'] : "../uploads/default-book.png";
					} else {
						// If the book does not exist in the database
						$title = "Not Found";
						$author = "-";
						$category = "-";
						$imagePath = "../uploads/default-book.png";
					}

					echo "<tr>";
					echo "<td><img src='$imagePath' alt='Book Cover'></td>";
					echo "<td>".$isbn."</td>";
					echo "<td>".$title."</td>";
					echo "<td>".$author."</td>";
					echo "<td>".$category."</td>";
					echo "<td>".$due_date."</td>";
					echo "<td>
							<label class='control control--checkbox'>
								<input type='checkbox' name='cb_book[]' value='".$isbn."'>
								<div class='control__indicator'></div>
							</label>
						  </td>";
					echo "</tr>";
				}

				echo "</table><br />";
				echo "<input type='submit' name='b_return' value='Return Selected Books' />";
				echo "</form>";
			}

			// Handle book return
			if (isset($_POST['b_return'])) {
				if (!isset($_POST['cb_book'])) {
					echo error_without_field("Please select a book to return.");
				} else {
					$booksReturned = count($_POST['cb_book']);
					$totalPenalty = 0;

					foreach ($_POST['cb_book'] as $isbn) {
						$query = $con->prepare("SELECT due_date FROM book_issue_log WHERE member = ? AND book_isbn = ?;");
						$query->bind_param("ss", $_SESSION['username'], $isbn);
						$query->execute();
						$due_date_result = $query->get_result();

						if ($due_date_result->num_rows > 0) {
							$due_date_row = $due_date_result->fetch_assoc();
							$due_date = $due_date_row['due_date'];

							$query = $con->prepare("SELECT DATEDIFF(CURRENT_DATE, ?);");
							$query->bind_param("s", $due_date);
							$query->execute();
							$daysLateResult = $query->get_result();
							$daysLate = ($daysLateResult->num_rows > 0) ? (int)$daysLateResult->fetch_array()[0] : 0;

							// Remove the book from issue log
							$query = $con->prepare("DELETE FROM book_issue_log WHERE member = ? AND book_isbn = ?;");
							$query->bind_param("ss", $_SESSION['username'], $isbn);
							$query->execute();

							if ($daysLate > 0) {
								$penalty = 5 * $daysLate;

								$query = $con->prepare("SELECT price FROM book WHERE isbn = ?;");
								$query->bind_param("s", $isbn);
								$query->execute();
								$bookPriceResult = $query->get_result();
								$bookPrice = ($bookPriceResult->num_rows > 0) ? (int)$bookPriceResult->fetch_array()[0] : 0;

								if ($bookPrice < $penalty) {
									$penalty = $bookPrice;
								}

								$totalPenalty += $penalty;

								$query = $con->prepare("UPDATE member SET balance = balance - ? WHERE username = ?;");
								$query->bind_param("ds", $penalty, $_SESSION['username']);
								$query->execute();

								echo '<script>
										document.getElementById("error").innerHTML += "A penalty of GH₵ '.$penalty.' was charged for keeping book '.$isbn.' for '.$daysLate.' days after the due date.<br />";
										document.getElementById("error-message").style.display = "block";
									</script>';
							}
						}
					}

					echo '<script>
							document.getElementById("success").innerHTML = "Successfully returned '.$booksReturned.' books";
							document.getElementById("success-message").style.display = "block";
						</script>';

					$query = $con->prepare("SELECT balance FROM member WHERE username = ?;");
					$query->bind_param("s", $_SESSION['username']);
					$query->execute();
					$balanceResult = $query->get_result();
					$balance = ($balanceResult->num_rows > 0) ? (int)$balanceResult->fetch_array()[0] : 0;

					if ($balance < 0) {
						header("Location: ../logout.php");
					}
				}
			}
		?>
	</body>
</html>
