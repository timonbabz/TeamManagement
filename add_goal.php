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

if (isset($_GET['warning']) && $_GET['warning'] == 1) {
    // Pre-fill modal data
    $userId = htmlspecialchars($_GET['user_id']);
    $managerId = htmlspecialchars($_GET['manager_id']);
    $goalDescription = htmlspecialchars($_GET['goal_description']);
    $kpi = htmlspecialchars($_GET['kpi']);
    $startDate = htmlspecialchars($_GET['start_date']);
    $endDate = htmlspecialchars($_GET['end_date']);
    echo "
        <style>
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 500px;
            text-align: center;
        }

        .modal-content p {
            margin-bottom: 20px;
        }

        .modal-content button {
            margin: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .proceed {
            background-color:rgb(0, 98, 209);
            width: 60%;
            color: white;
        }

        .cancel {
            background-color: #f44336;
            width: 30%;
            color: white;
        }
    </style>
    <div id='warningModal' class='modal'>
        <div class='modal-content'>
            <p style='color: red;'>The selected employee already has an ongoing goal. Are you sure you want to add another goal?</p>
            <form action='task.php' method='POST' id='proceedForm'>
                <input type='hidden' name='user_id' value='$userId'>
                <input type='hidden' name='manager_id' value='$managerId'>
                <input type='hidden' name='goal_description' value='$goalDescription'>
                <input type='hidden' name='kpi' value='$kpi'>
                <input type='hidden' name='start_date' value='$startDate'>
                <input type='hidden' name='end_date' value='$endDate'>
                <input type='hidden' name='override' value='1'>
                <button type='submit' class='proceed'>Proceed Anyway</button>
            </form>
            <button class='cancel' onclick='closeModal()'>Cancel</button>
        </div>
    </div>
    ";
}
elseif (isset($_GET['warning']) && $_GET['warning'] == 2) {

    echo "
        <style>
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 500px;
            text-align: center;
        }

        .modal-content p {
            margin-bottom: 20px;
        }

        .modal-content button {
            margin: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .proceed {
            background-color:rgb(0, 98, 209);
            width: 60%;
            color: white;
        }

        .cancel {
            background-color: #f44336;
            width: 30%;
            color: white;
        }
    </style>
    <div id='warningModal' class='modal'>
        <div class='modal-content'>
            <p style='color: red;'>The selected employee has reached the limit for set goals which are currently in progress</p>
            <button class='cancel' onclick='closeModal()'>Cancel</button>
        </div>
    </div>
    ";
}


?>
<!DOCTYPE html>
<html lang="en">

<?php include_once 'header.php';?>

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

                    <div class="card-header"><span class="text-primary font-weight-bold">Assign New Task</span></div>
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
                        <script>
                            // Show the modal if it exists
                            const modal = document.getElementById('warningModal');
                            if (modal) {
                                modal.style.display = 'flex';
                            }

                            // Function to close the modal
                            function closeModal() {
                                if (modal) {
                                    modal.style.display = 'none';
                                }
                                history.back(); // Go back to the previous page
                            }
                        </script>
                            <form id="goalForm" method="POST" action="task.php" class="user offset-sm-3">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                            <label for="user_id">Personnel :</label>
                            <?php 
                                    require_once 'dbconnect.php';
                                    $sql = "SELECT * FROM users WHERE assignee = 1";
                                    $result = $link ->query($sql);
                                    echo "<select name='user_id' id='user_id' class='form-select' required>";
                                    echo "<option value=''>-- Select Employee --</option>";
                                         while ($row = $result -> fetch_assoc()){
                                            echo "<option value='" . $row['ID'] . "'>" . $row['names'] . "</option>";
                                         }
                                    echo "</select>";
                                ?>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="goal_description">Goal Description:</label>
                                <textarea id="goal_description" class="form-control" name="goal_description" required></textarea>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="kpi">KPI (Key Performance Indicator):</label>
                                <input type="text" id="kpi" name="kpi" class="form-control" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0 w-25">
                                <label for="start_date">Start Date:</label>
                                <input type="date" id="start_date" class="form-control" name="start_date" required>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0 w-25">
                                <label for="end_date">End Date:</label>
                                <input type="date" id="end_date" class="form-control" name="end_date" required><br>
                            </div><br>
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <button type="submit" id="submit" class="btn btn-primary">Add Goal</button>
                            </div>
                                <br><br>
                            </form>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include_once 'footer.php';?>
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
