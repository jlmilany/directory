<?php
require 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Add building
if (isset($_POST['add'])) {
    $name = trim($_POST['building_name']);
    if ($name === '') {
        $message = "Building name cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO buildings (building_name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $message = "Building added successfully.";
        } else {
            $message = "Error adding building: " . $conn->error;
        }
        $stmt->close();
    }
}

// Delete building
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM buildings WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Building deleted successfully.";
    } else {
        $message = "Error deleting building: " . $conn->error;
        }
    $stmt->close();
}

// Edit building - load data
$edit_building = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM buildings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_building = $result->fetch_assoc();
    $stmt->close();
}

// Update building
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['building_name']);
    if ($name === '') {
        $message = "Building name cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE buildings SET building_name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            $message = "Building updated successfully.";
            $edit_building = null;  // Clear edit data
        } else {
            $message = "Error updating building: " . $conn->error;
        }
        $stmt->close();
    }
}

$buildings = $conn->query("SELECT * FROM buildings ORDER BY building_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Buildings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .page-header {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            border: none;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            border-radius: var(--border-radius);
            padding: 8px 16px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            padding: 12px 15px;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .table tr:nth-child(even) {
            background-color: rgba(52, 152, 219, 0.03);
        }
        
        .table tr:hover {
            background-color: rgba(52, 152, 219, 0.08);
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .alert {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .form-control {
            border-radius: var(--border-radius);
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--primary-hover);
            text-decoration: none;
            transform: translateX(-3px);
        }
        
        .add-building-btn {
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 6px;
            }
            
            .action-btns .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert <?= strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show">
                        <div class="d-flex align-items-center">
                            <i class="fas <?= strpos($message, 'Error') === false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Buildings Header -->
<div class="row mb-4 align-items-center">
    <div class="col-md-8 col-12 d-flex align-items-center mb-2 mb-md-0">
        <i class="fas fa-list text-primary me-2 fs-4"></i>
        <h3 class="h5 mb-0 fw-bold text-primary">Buildings List</h3>
    </div>
    <div class="col-md-4 col-12 text-md-end text-start">
        <button type="button" 
                class="btn btn-primary shadow-sm add-building-btn" 
                data-bs-toggle="modal" 
                data-bs-target="#addBuildingModal">
            <i class="fas fa-plus me-1"></i> Add New Building
        </button>
    </div>
    <div class="col-12 mt-3">
        <a href="index.php" class="text-decoration-none back-link d-inline-flex align-items-center text-muted fw-semibold">
            <i class="fas fa-arrow-left me-2"></i> Back to Directory
        </a>
    </div>
</div>


        <!-- Buildings List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if ($buildings->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="100">ID</th>
                                            <th>Building Name</th>
                                            <th width="200">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($b = $buildings->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $b['id'] ?></td>
                                                <td><?= htmlspecialchars($b['building_name']) ?></td>
                                                <td class="action-btns">
                                                    <button class="btn btn-sm btn-primary edit-building-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editBuildingModal"
                                                            data-id="<?= $b['id'] ?>"
                                                            data-name="<?= htmlspecialchars($b['building_name']) ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <a href="?delete=<?= $b['id'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this building? This will delete all associated contacts.')">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-building"></i>
                                <h4>No Buildings Found</h4>
                                <p>Get started by adding your first building</p>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                                    <i class="fas fa-plus me-1"></i> Add Building
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Building Modal -->
        <div class="modal fade" id="addBuildingModal" tabindex="-1" aria-labelledby="addBuildingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBuildingModalLabel">
                            <i class="fas fa-plus-circle text-primary me-2"></i>Add New Building
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="modalBuildingName" class="form-label">Building Name</label>
                                <input type="text" class="form-control" id="modalBuildingName" name="building_name" 
                                    placeholder="Enter building name" required autofocus>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" name="add" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Building
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Building Modal -->
        <div class="modal fade" id="editBuildingModal" tabindex="-1" aria-labelledby="editBuildingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBuildingModalLabel">
                            <i class="fas fa-edit text-primary me-2"></i>Edit Building
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="">
                        <input type="hidden" name="id" id="editBuildingId">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="editBuildingName" class="form-label">Building Name</label>
                                <input type="text" class="form-control" id="editBuildingName" name="building_name" 
                                    placeholder="Enter building name" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Building
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);

        // Clear add modal form when closed
        document.getElementById('addBuildingModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalBuildingName').value = '';
        });

        // Focus on input field when add modal opens
        document.getElementById('addBuildingModal').addEventListener('shown.bs.modal', function () {
            document.getElementById('modalBuildingName').focus();
        });

        // Handle edit building modal
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-building-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editBuildingModal'));
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const buildingId = this.getAttribute('data-id');
                    const buildingName = this.getAttribute('data-name');
                    
                    document.getElementById('editBuildingId').value = buildingId;
                    document.getElementById('editBuildingName').value = buildingName;
                    
                    // Focus on input field when modal opens
                    editModal.show();
                    setTimeout(() => {
                        document.getElementById('editBuildingName').focus();
                    }, 500);
                });
            });

            // Add animation to table rows
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>