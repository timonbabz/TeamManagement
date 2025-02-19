<?php
// Initialize the session
if(session_status() == PHP_SESSION_NONE){
session_start();
}
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

//Read data from submitted form
require_once "dbconnect.php";
$clientName = "";
$email = "";
$phone = ""; 
$cltype = "";
$contactName = "";
$designation = "";
$assigned = "";
$shortName = "";
$assigned2 = "";
$county = "";
$startDate = "";
$address = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == "POST" ){
    //Get data from the form
    $clientName = ucwords($_POST["clientName"]);
    $shortName = ucwords($_POST["shortName"]);
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $cltype = $_POST["cltype"];
    $contactName = ucwords($_POST["contactName"]);
    $designation = $_POST["designation"];
    $county = $_POST["county"];
    $address = $_POST["address"];
    $assigned = $_POST["assigned"];
    $assigned2 = $_POST["assigned2"];
    $startDate = $_POST["startDate"];;
    //Check for any empty fields in the form
    do{
        if(empty($clientName) || empty($cltype) || empty($email) || empty($phone) || empty($contactName) || empty($designation) || empty($assigned) || empty($county) || empty($startDate)){
            $errorMessage = "All the fields are required";
            break;
        }
        //check if email already exists
        $sql_check = "SELECT COUNT(names) as emCount FROM sites WHERE names = '$clientName'";
        $result_check = $link ->query($sql_check);
        $data = mysqli_fetch_array($result_check, MYSQLI_NUM);
        if($data[0] > 0){
            $errorMessage = "This client is already captured";
            break;
            }else if($assigned == $assigned2){
                $errorMessage = "Assignee 2 should be a different personnel";
                break;
            }else{
                //Add new client into the database
                $sql = "INSERT INTO sites (names, shortName, region, phone,contact, email, assigned, assigned2, contDes, clientType, start_date,address) VALUES ('$clientName', '$shortName','$county','$phone','$contactName','$email','$assigned','$assigned2','$designation','$cltype','$startDate','$address')";
                $result = $link ->query($sql);
                if(!$result){
                    $errorMessage = "Invalide query: " . $link->error;
                    break;
                }
                $clientName = "";
                $email = "";
                $phone = ""; 
                $cltype = "";
                $contactName = "";
                $designation = "";
                $assigned = "";
                $assigned2 = "";
                $county = "";
                $shortName = "";
                $address = "";
                $startDate = "";
                $errorMessage = "";

                $_SESSION['clientMsg'] = "client added successfully!";
                header("location:clientsetup.php");
                exit;
        }

    }while(false);
}
?>
<!DOCTYPE html>
<html lang="en">

<!--header php here-->
<?php include_once 'header.php';?>

<!--header php above there-->

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php 
        if($_SESSION["role"] === 'User'){
            include_once 'sidebarUser.php'; //user with basic rights (basic user)
        }elseif($_SESSION["role"] === 'Administrator'){
            include_once 'sidebarAdmin.php';//user with advanced rights (administrator)
        }elseif($_SESSION["role"] === 'Developer'){
            include_once 'sidebarDeveloper.php';//user with advanced rights (developer)
        }elseif($_SESSION["role"] === 'Co-operate Training'){
            include_once 'sidebarCooptraining.php';//user with advanced rights (cooperate training)
        }elseif($_SESSION["role"] === 'Director'){
            include_once 'sidebarDirector.php';//user with advanced rights (Director)
        }elseif($_SESSION["role"] === 'Customer satisfaction'){
            include_once 'sidebarCsatisfaction.php';//user with advanced rights (satisfaction)
        }else{
            include_once 'sidebar.php';//user with all rights (super admin)
        }
        ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include_once 'topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Beginning of Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="card shadow mb-4">
                    <div class="font-weight-bold card-header text-primary">Add a new Client</div>
                    <!-- Display of error message on empty fields -->
                        <?php 
                            if(!empty($errorMessage)){
                                echo "<div class='alert alert-warning alert-dismissable fade show' role='alert'>
                                    <strong>$errorMessage</strong>
                                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                </div>";
                            }
                        ?>
                        <!-- End of error message display -->
                        <form method="POST" class="user offset-sm-3" action="#">
                            <br>
                            <div class="col-sm-6 mb-sm-0 mb-3 form-floating">
                                <input type="text" class="form-control" style="width: 150%;" name="clientName" id="clientNameInput"
                                    placeholder="Client Name" value="<?php echo $clientName; ?>" required>
                                <label for="clientNameInput" class="text-primary" style="padding-left:30px">Client Name</label>
                            </div><br>
                            <div class="col-sm-6 mb-sm-0 mb-3 input-group">
                                <span class="input-group-text" id="shortName">Short Name: </span>
                                <input type="text" class="form-control" name="shortName" id="shortNameInput"
                                    placeholder="Short Name" value="<?php echo $shortName; ?>" aria-describedby="shortName" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <?php
                                require_once "dbconnect.php";

                                // Query to get client types
                                $sql_query = "SELECT cltype FROM clientType";
                                $result = $link->query($sql_query);

                                // Check if query executed successfully and returned results
                                if ($result && $result->num_rows > 0) {
                                    echo "<select name='cltype' id='clientTypeSelect' class='form-control' required>";
                                    echo "<option value='' disabled selected>-- Client Type --</option>";  // Default placeholder

                                    // Fetch and populate options
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['cltype'], ENT_QUOTES) . "'>"
                                            . htmlspecialchars($row['cltype'], ENT_QUOTES) . "</option>";
                                    }
                                    echo "</select>";
                                } else {
                                    echo "<select name='cltype' id='clientTypeSelect' class='form-control' required>";
                                    echo "<option value='' disabled>No client types available</option>";
                                    echo "</select>";
                                }
                                ?>
                            </div><br>

                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <?php
                                require_once "dbconnect.php";

                                // Query to get counties
                                $sql_query = "SELECT * FROM county";
                                $result = $link->query($sql_query);

                                // Check if query executed successfully and returned results
                                if ($result && $result->num_rows > 0) {
                                    echo "<select name='county' id='countySelect' class='form-control' required>";
                                    echo "<option value='' disabled selected>-- County | Area --</option>";  // Default placeholder

                                    // Fetch and populate options
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['county'], ENT_QUOTES) . "'>"
                                            . htmlspecialchars($row['county'], ENT_QUOTES) . " | " . htmlspecialchars($row['province'], ENT_QUOTES) . "</option>";
                                    }
                                    echo "</select>";
                                } else {
                                    echo "<select name='county' id='countySelect' class='form-control' required>";
                                    echo "<option value='' disabled>No counties available</option>";
                                    echo "</select>";
                                }
                                ?>
                            </div><br>

                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="email" class="form-control" name="email" id="emailInput"
                                    placeholder="clientemail@email.com" value="<?php echo $email; ?>" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="text" class="form-control" name="address" id="address"
                                    placeholder="Address e.g P.O Box 270801-00100, Nairobi" value="<?php echo $address; ?>" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="phone" class="font-weight-bold text-primary">Phone:</label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        class="form-control"
                                        name="phone" 
                                        value="<?php echo $phone; ?>"
                                        pattern="07[0-9]{8}" 
                                        maxlength="10" 
                                        minlength="10" 
                                        placeholder="0712345678" 
                                        required 
                                    >
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="text" class="form-control" name="contactName" id="contactNameInput"
                                    placeholder="Contact's Name" value="<?php echo $contactName; ?>" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="designation" class="font-weight-bold text-primary">Contact's Designation: </label>
                                <?php
                                require_once "dbconnect.php";

                                // Query to fetch designations
                                $sql_query = "SELECT designation FROM clientDesignation";
                                $result = $link->query($sql_query);

                                if ($result && $result->num_rows > 0) {
                                    echo "<select name='designation' id='designationSelect' class='form-control' required>";
                                    echo "<option value=''>-- Contact's Designation --</option>";

                                    // Output the options for the designations
                                    while ($designation = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($designation['designation'], ENT_QUOTES) . "'>" . htmlspecialchars($designation['designation'], ENT_QUOTES) . "</option>";
                                    }
                                    echo "</select>";
                                } else {
                                    echo "<select name='designation' id='designationSelect' class='form-control' required>";
                                    echo "<option value='' disabled>No designations available</option>";
                                    echo "</select>";
                                }
                                ?>
                            </div><br>

                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="assigned" class="font-weight-bold text-primary">Assignee: </label>
                                <?php
                                require_once "dbconnect.php";

                                // Query to fetch active assignees
                                $sql_query = "SELECT names FROM users WHERE assignee = 1 and active = 1";
                                $result = $link->query($sql_query);

                                if ($result && $result->num_rows > 0) {
                                    echo "<select name='assigned' id='assignedSelect' class='form-control' required>";
                                    echo "<option value=''>-- Assignee --</option>";

                                    // Output the options for the assignees
                                    while ($assigned = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($assigned['names'], ENT_QUOTES) . "'>" . htmlspecialchars($assigned['names'], ENT_QUOTES) . "</option>";
                                    }
                                    echo "</select>";
                                } else {
                                    echo "<select name='assigned' id='assignedSelect' class='form-control' required>";
                                    echo "<option value='' disabled>No assignees available</option>";
                                    echo "</select>";
                                }
                                ?>
                            </div><br>

                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="assigned2" class="font-weight-bold text-primary">Assignee 2 (Optional): </label>
                                <?php
                                require_once "dbconnect.php";

                                // Query to fetch active assignees again for second assignee
                                $sql_query = "SELECT names,email FROM users WHERE assignee = 1 and active = 1";
                                $result = $link->query($sql_query);

                                if ($result && $result->num_rows > 0) {
                                    echo "<select name='assigned2' id='assigned2Select' class='form-control'>";
                                    echo "<option value=''>-- Assignee 2 (optional)--</option>";

                                    // Output the options for the second assignee
                                    while ($assigned2 = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($assigned2['names'], ENT_QUOTES) . "'>" . htmlspecialchars($assigned2['names'], ENT_QUOTES) . "</option>";
                                    }
                                    echo "</select>";
                                } else {
                                    echo "<select name='assigned2' id='assigned2Select' class='form-control'>";
                                    echo "<option value='' disabled>No assignees available</option>";
                                    echo "</select>";
                                }
                                ?>
                            </div><br>

                            <div class="col-sm-6 mb-3 mb-sm-0 form-floating">
                                <input type="date" class="form-control" name="startDate" id="startDate"
                                    placeholder="" value="<?php echo $startDate; ?>" required>
                                <label for="startDate" style="padding-left:30px" class="text-primary">Expected Start Date</label>
                            </div><br>
                            
                            <!-- CSRF token for protection -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="row mb-3">
                                <div class="col-sm-3 d-grid">
                                    <button type="submit" class="btn btn-primary btn-block">Save</button>
                                </div>
                                <div class="col-sm-3 d-grid">
                                    <a href="clientsetup.php" class="btn btn-primary btn-danger btn-block">Cancel</a>
                                </div>
                            </div>
                        </form>

                        <script>
                            // Initialize phone number input with Kenya as the default country
                            var phoneInput = document.getElementById('phone');
                            intlTelInput(phoneInput, {
                                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                                initialCountry: "KE",  // Lock to Kenya
                                nationalMode: false,   // Prevent international numbers
                                preferredCountries: ['KE']  // Limit selection to Kenya
                            });
                        </script>

                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include_once 'footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <script src="js/messages.js"></script>

</body>

</html>