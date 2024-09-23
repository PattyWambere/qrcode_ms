<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "user_info");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include QR Code library (chillerlan/php-qrcode)
require 'vendor/autoload.php';
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Handle file upload (passport photo)
    $photo = $_FILES['photo']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);

    // Generate QR code content
    $qrContent = "First Name: $firstname, Last Name: $lastname, Age: $age, Gender: $gender, Phone: $phone, Address: $address";

    // Save the QR code image
    $qrCodeFileName = 'qrcodes/' . $phone . '.png';
    $options = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG]);
    (new QRCode($options))->render($qrContent, $qrCodeFileName);

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO persons (firstname, lastname, age, gender, phone, address, photo, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssss", $firstname, $lastname, $age, $gender, $phone, $address, $photo, $qrCodeFileName);
    $stmt->execute();
}

// Handle data display based on phone number
$selectedPerson = null;
if (isset($_GET['search_phone'])) {
    $searchPhone = $_GET['search_phone'];
    $result = $conn->query("SELECT * FROM persons WHERE phone='$searchPhone'");
    $selectedPerson = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>QR Code Person Info</title>
    <style>
    @media print {
        body * {
            display: none; /* Hide everything */
        }
        .printable-content, .printable-content * {
            display: block; /* Show only printable content */
            visibility: visible; /* Ensure it is visible */
        }
        .printable-content {
            position: absolute; /* Position the content */
            left: 0;
            top: 0;
            width: 2.63in; /* Set width for printing */
            height: 3.88in; /* Set height for printing */
        }
    }
</style>





</head>
<body>
<div class="container mt-5">
    <div class="row">
        <!-- Left Column: Form to input person data -->
        <div class="col-md-6">
            <h5>Enter Person Information</h5><hr>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" class="form-control" id="age" name="age" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label for="photo">Passport Photo</label>
                    <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

        <!-- Right Column: Output the person's data -->
        <div class="col-md-6">
            <h5>Search Person by Phone</h5><hr>
            <form method="GET">
                <div class="form-group">
                    <label for="search_phone">Phone</label>
                    <input type="text" class="form-control" id="search_phone" name="search_phone" required>
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>

            <?php
                if ($selectedPerson) {
                    ?>
                    <div class="mt-4 printable-content card shadow-lg" style="width: 2.40in; height: auto; margin: auto;">
                        <div class="card-body">
                            <h5 class="card-title"><strong><?php echo $selectedPerson['firstname']; ?> <?php echo $selectedPerson['lastname']; ?></strong></h5>
                            <p class="card-text"><strong><hr></strong></p>
                            <img src="uploads/<?php echo $selectedPerson['photo']; ?>" alt="Passport Photo" class="img-fluid" style="width: 2in; height: 2in;">
                            <p class="card-text mt-2"><strong><hr></strong></p>
                            <img src="<?php echo $selectedPerson['qr_code']; ?>" alt="QR Code" class="img-fluid" style="width: 2in; height: 2in;">
                        </div>
                    </div>
                    <button class='btn btn-secondary print-btn mt-3' onclick='window.print()'>Print</button>
                    <?php
                } else {
                    // Handle case when no person is selected
                    ?>  
                        <hr>
                        <p class="p-5"><strong> No Record Selected!.</strong></p>
                    <?php
                }
            ?>


        </div>
    </div>
</div>
</body>
</html>
