<?php
// Initialize the session
if(session_status() == PHP_SESSION_NONE){
session_start();
}
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.html");
    exit;
}
 
//Read data from submitted form
require_once "dbconnect.php";
$userName = "";
$email = "";
$phone = "";
$department = "";
$role = "";
$password = "";
$errorMessage = "";
$userDesignation = "";

if ($_SERVER['REQUEST_METHOD'] == "POST" ){
    $userName = ucwords($_POST["userName"]);
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $department = $_POST["department"];
    $role = $_POST["role"];
    $password = $_POST["password"];
    $userDesignation = $_POST["userDesignation"];
    //Check for any empty fields in the form
    do{
        if(empty($userName) || empty($email) || empty($phone) || empty($department) || empty($role) || empty($password) || empty($userDesignation)){
            $errorMessage = "All the fields are required";
            break;
        }
        //check if email already exists
        $sql_check = "SELECT COUNT(email) as emCount FROM users WHERE email = '$email'";
        $result_check = $link ->query($sql_check);
        $data = mysqli_fetch_array($result_check, MYSQLI_NUM);
        if($data[0] > 0){
            $errorMessage = "User with this email already exists";
            break;
            }else{
                //Add new user into the database
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (email, password, names, department, role, phone, designation) VALUES ('$email','$param_password','$userName','$department','$role','$phone','$userDesignation')";
                $result = $link ->query($sql);
                if(!$result){
                    $errorMessage = "Invalide query: " . $link->error;
                    break;
                }
                $userName = "";
                $email = "";
                $phone = ""; 
                $department = "";
                $role = "";
                $password = "";
                $errorMessage = "";
                $userDesignation = "";

                header("location:users.php");
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
                    <div class="card-header">Add a new user</div>
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
                        <form method="POST" class="user offset-sm-3">
                            <br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="text" class="form-control" name="userName" id="exampleFirstName"
                                    placeholder="FirstName SecondName ThirdName" value="<?php echo $userName; ?>">
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="email" class="form-control" name="email" id="exampleFirstName"
                                    placeholder="youremail@email.com" value="<?php echo $email; ?>">
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="number" class="form-control" name="phone" id="exampleFirstName"
                                    placeholder="Phone" value="<?php echo $phone; ?>">
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <?php
                                    require_once "dbconnect.php";
                                    $sql_query = "SELECT name FROM departments";
                                    $result = $link ->query($sql_query);
                                    echo "<select name='department' id='select' class='form-control'>";
                                    echo "<option value=''>-- Select Department --</option>";
                                    while ($department = $result -> fetch_assoc()) {
                                        echo "<option value='" . $department['name'] . "'>" . $department['name'] . "</option>";
                                    }
                                    echo "</select>";
                                ?>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <?php
                                    require_once "dbconnect.php";
                                    $sql_query = "SELECT names FROM userDesignation";
                                    $result = $link ->query($sql_query);
                                    echo "<select name='userDesignation' id='select' class='form-control'>";
                                    echo "<option value=''>-- Select Designation --</option>";
                                    while ($userDesignation = $result -> fetch_assoc()) {
                                        echo "<option value='" . $userDesignation['names'] . "'>" . $userDesignation['names'] . "</option>";
                                    }
                                    echo "</select>";
                                ?>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <?php
                                    require_once "dbconnect.php";
                                    $sql_query = "SELECT role FROM roles";
                                    $result = $link ->query($sql_query);
                                    echo "<select name='role' id='select' class='form-control'>";
                                    echo "<option value=''>-- Select Role --</option>";
                                    while ($role = $result -> fetch_assoc()) {
                                        echo "<option value='" . $role['role'] . "'>" . $role['role'] . "</option>";
                                    }
                                    echo "</select>";
                                ?>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="password" class="form-control" name="password" id="exampleFirstName"
                                    placeholder="Password" value="<?php echo $password; ?>">
                            </div><br>
                            <div class="row mb-3">
                                <div class="col-sm-3 d-grid">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        Save</button>
                                </div>
                                <div class="col-sm-3 d-grid">
                                <a href="users.php" class="btn btn-primary btn-danger btn-block">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
                    </div>
                </div>
            </footer>
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