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
$assigneeMail = $_SESSION['email'];
require_once "dbconnect.php";

// Get assignee names
$sql_query = "SELECT names FROM users WHERE email = ?";
$stmt = $link->prepare($sql_query);
$stmt->bind_param("s", $assigneeMail);
$stmt->execute();
$result = $stmt->get_result();
$assigneeMail1 = $result->fetch_assoc()['names'] ?? null;

// Get the current open week
$sql_query = "SELECT ID FROM weeks WHERE closed = 0";
$result = $link->query($sql_query);
$assigneeWeek = $result->fetch_assoc()['ID'] ?? null;

if (!$assigneeMail1 || !$assigneeWeek) {
    die("Assignee data or current week not found.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $email2 = $_POST['email'] ?? [];
        $site1_id = $_POST['site_id'] ?? [];
        $facilitate = $_POST['facilitate'] ?? [];
        $ref = $_POST['ref'] ?? [];
        $ref = array_map('strtoupper', $ref);
        $totalB = $_POST['TotalB'] ?? [];
        $amountD2 = $_POST['amountD'] ?? [];
        
        // Start a transaction
        $link->begin_transaction();

        try {
            $totalDistributed = 0; // Track the total distributed amount

            // Get the current balance and total receipts from received_total
            $sql_balance = "SELECT balance, total_receipts, allocated FROM received_total WHERE week = ?";
            $stmt_balance = $link->prepare($sql_balance);
            $stmt_balance->bind_param("s", $assigneeWeek);
            $stmt_balance->execute();
            $stmt_balance->bind_result($currentBalance, $totalReceipts, $allocated);
            if (!$stmt_balance->fetch()) {
                throw new Exception("Error: Less or No balance found for the selected week.");
            }
            $stmt_balance->close();

            // Loop through the distributions
            foreach ($email2 as $index => $email2s) {
                $s_email2 = $email2s;
                $s_site = $site1_id[$index];
                $s_facilitate = $facilitate[$index];
                $s_ref = $ref[$index];
                $s_totalB = $totalB[$index];
                $s_amountD2 = $amountD2[$index];

                // Validate individual facilitation amounts against submitted budgets
                if (($s_facilitate + $s_amountD2) > $s_totalB) {
                    throw new Exception("Error: Kindly check the facilitated amounts against the submitted budgets.");
                }

                // Add to the total distributed amount
                $totalDistributed += $s_facilitate;

                // Insert into facilitation table
                $sql = "INSERT INTO facilitation (email, site_id, amount, trxref, week) VALUES (?, ?, ?, ?, ?)";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("sidss", $s_email2, $s_site, $s_facilitate, $s_ref, $assigneeWeek);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting into facilitation: " . $stmt->error);
                }

                // Update the budget table
                $sql_up = "UPDATE budget SET facilitated = 1 WHERE email = ? AND site_id = ? AND week = ?";
                $stmt = $link->prepare($sql_up);
                $stmt->bind_param("sis", $s_email2, $s_site, $assigneeWeek);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating budget: " . $stmt->error);
                }

                // Update the distribution table
                $sql_dist = "UPDATE distribution D
                    SET amount = (
                        SELECT COALESCE(SUM(amount), 0) 
                        FROM facilitation 
                        WHERE facilitation.email = D.email 
                        AND facilitation.site_id = D.site_id 
                        AND facilitation.week = D.week
                    )
                    WHERE D.week = ? AND D.email = ? and D.site_id = ?";
                $stmt = $link->prepare($sql_dist);
                $stmt->bind_param("ssi", $assigneeWeek, $s_email2, $s_site);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating distribution amount: " . $stmt->error);
                }

                $sql_dist2 = "UPDATE distribution SET deficit = budget - amount WHERE week = ? AND email = ? AND site_id = ?";
                $stmt = $link->prepare($sql_dist2);
                $stmt->bind_param("ssi", $assigneeWeek, $s_email2, $s_site);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating distribution deficit: " . $stmt->error);
                }
            }

            // Validate total distributed amount against the current balance
            if ($totalDistributed > $currentBalance) {
                throw new Exception("Error: Total distribution amount exceeds the available balance for the selected week.");
            }

            // Update the received_total balance
            $newBalance = $currentBalance - $totalDistributed;
            $newAllocated = $allocated + $totalDistributed; // Update allocated
            $newBalance = max(0, $newBalance); // Ensure balance is non-negative

            // Update the allocated amount and balance in received_total
            $sql_update_balance = "UPDATE received_total SET balance = ?, allocated = ? WHERE week = ?";
            $stmt_update_balance = $link->prepare($sql_update_balance);
            $stmt_update_balance->bind_param("dds", $newBalance, $newAllocated, $assigneeWeek);
            if (!$stmt_update_balance->execute()) {
                throw new Exception("Error updating received_total balance and allocated: " . $stmt_update_balance->error);
            }

            // Check if all funds are allocated
            if ($newAllocated == $totalReceipts) {
                // If fully allocated, set balance to 0
                $sql_update_full_alloc = "UPDATE received_total SET balance = 0 WHERE week = ?";
                $stmt_update_full_alloc = $link->prepare($sql_update_full_alloc);
                $stmt_update_full_alloc->bind_param("s", $assigneeWeek);
                if (!$stmt_update_full_alloc->execute()) {
                    throw new Exception("Error updating received_total balance to 0: " . $stmt_update_full_alloc->error);
                }
            }

            // Commit transaction if all queries succeed
            $link->commit();
            $_SESSION['errorMessage'] = 'Funds distribution done successfully';
            header("location:allocations.php");
            exit;

        } catch (Exception $e) {
            // Roll back transaction if any query fails
            $link->rollback();
            $_SESSION['errorMessage'] = $e->getMessage();
            header("location:allocations.php");
            exit;
        }
    }
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

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Budget: Funds Distribution</h1>
                        <?php 
                        if(!empty($_SESSION['errorMessage'])){
                                echo "<div class='alert alert-warning alert-dismissable fade show' role='alert'>
                                    <strong>".$_SESSION['errorMessage']."</strong>
                                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                </div>";
                                unset($_SESSION['errorMessage']);
                            }
                        ?>
                    <!-- Page Heading -->
                    <?php 
                        require_once "dbconnect.php";
                        $sql_query = "SELECT fromdate, todate FROM weeks WHERE closed = 0";
                        if ($result = $link ->query($sql_query)) {
                            while ($row = $result -> fetch_assoc()) { 
                                $fromdate = $row['fromdate'];
                                $todate = $row['todate'];
                            }}
                    ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <?php 
                            require_once "dbconnect.php";
                            $sql_query = "SELECT names FROM users WHERE email = '$assigneeMail'";
                            if ($result = $link ->query($sql_query)) {
                                while ($row = $result -> fetch_assoc()) { 
                                    $assigneeMail1 = $row['names'];
                                }}

                            $sql_query = "SELECT ID FROM weeks WHERE closed = 0";
                            if ($result = $link ->query($sql_query)) {
                                while ($row = $result -> fetch_assoc()) { 
                                    $assigneeWeek = $row['ID'];
                                }}
                            ?>
                            <h6 class="m-0 font-weight-bold text-primary">Funds distribution for week <?php echo $assigneeWeek; ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
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
                                <form method="POST" action="#">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Personnel</th>
                                            <th>Site</th>
                                            <th>Budget</th>
                                            <th>Facilitated</th>
                                            <th>Balance</th>
                                            <th>Facilitate</th>
                                            <th>TRX Ref</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th>Personnel</th>
                                            <th>Site</th>
                                            <th>Budget</th>
                                            <th>Facilitated</th>
                                            <th>Balance</th>
                                            <th>Facilitate</th>
                                            <th>TRX Ref</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php 
                                            require_once "dbconnect.php";
                                            $nameloged = $_SESSION["names"];
                                            $sql_query = "SELECT 
                                                            users.names, 
                                                            budget.email, 
                                                            budget.site_id,
                                                            sites.names AS site, 
                                                            distribution.amount, 
                                                            distribution.deficit, 
                                                            SUM(budget.accommodation + budget.meals + budget.airtime + budget.fare) AS TotalB
                                                        FROM 
                                                            budget
                                                        INNER JOIN 
                                                            users ON budget.email = users.email
                                                        INNER JOIN 
                                                            weeks ON budget.week = weeks.ID
                                                        INNER JOIN
                                                            sites ON budget.site_id = sites.ID    
                                                        INNER JOIN 
                                                            distribution ON budget.email = distribution.email AND budget.site_id = distribution.site_id
                                                        WHERE 
                                                            weeks.closed = 0 
                                                            AND budget.approved = 'Approved' 
                                                            AND distribution.deficit > 0
                                                        GROUP BY 
                                                            budget.site_id, 
                                                            distribution.email, 
                                                            users.names, 
                                                            budget.email, 
                                                            distribution.amount, 
                                                            distribution.deficit;";

                                            if ($result = $link ->query($sql_query)) {
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result -> fetch_assoc()) { 
                                                        $email1 = $row['email'];
                                                        $personnel = $row['names'];
                                                        $site_id = $row['site_id'];
                                                        $site = $row['site'];
                                                        $TotalB = $row['TotalB'];
                                                        $amountD = $row['amount'];
                                                        $deficit = $row['deficit'];
                                        ?>
                                                    <tr data-id="<?php echo $site_id; ?>">
                                                        <input class="form-control" name="email[]" type="hidden" value="<?php echo $email1; ?>" readonly>
                                                        <input class="form-control" name="site_id[]" type="hidden" value="<?php echo $site_id; ?>" readonly>
                                                        <td><button class="remove-row btn btn-danger"><i class="fa fa-minus-circle" aria-hidden="true"></i></button></td>
                                                        <td><input class="form-control" name="personnel[]" type="text" value="<?php echo $personnel; ?>" readonly></td>
                                                        <td><input class="form-control" name="site[]" type="text" value="<?php echo $site; ?>" readonly> </td>
                                                        <td><input class="form-control" name="TotalB[]" type="text" value="<?php echo $TotalB; ?>" readonly></td>
                                                        <td><input class="form-control" name="amountD[]" type="number" value="<?php echo $amountD;?>" readonly></td>
                                                        <td><input class="form-control" name="deficit[]" type="number" value="<?php echo $deficit;?>" readonly></td>
                                                        <td><input class="form-control" type="number" min="0" name="facilitate[]" required></td>
                                                        <td><input class="form-control" type="text" name="ref[]" required></td>
                                                    </tr>
                                        <?php
                                                    } 
                                                } else {
                                                    // Display message when no records are found
                                                    echo "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                                                }
                                            } else {
                                                // Handle query error
                                                echo "<tr><td colspan='8' class='text-center'>Error fetching data</td></tr>";
                                            }
                                        ?>
                                    </tbody>

                                </table><br>
                                <div class="row mb-3 justify-content-center">
                                <div class="col-sm-3 d-grid">
                                    <button type="submit" class="btn btn-primary btn-block" name="submit">
                                        Facilitate</button>
                                </div>
                                <div class="col-sm-3 d-grid">
                                <a href="index.php" class="btn btn-primary btn-danger btn-block">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                                </form>

                            </div>
                        </div>
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
    <script>
        $(document).ready( function() {
            $('#dataTable').dataTable({
                /* No ordering applied by DataTables during initialisation */
                "order": []
            });
        })
    </script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="js/messages.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#dataTable').DataTable();

            // Handle row removal when the remove button is clicked
            $('#dataTable').on('click', '.remove-row', function() {
                // Find the row that contains the button
                var row = $(this).closest('tr'); 
                
                // Optionally, you can use the row ID to confirm the row should be removed
                var rowId = row.data('id');  // Get the 'data-id' attribute of the row

                // Remove the row from the DataTable (frontend only)
                table.row(row).remove().draw();
            });
        });
    </script>

</body>

</html>