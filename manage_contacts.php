<?php
require 'db.php';

$message = "";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch buildings for dropdown
$buildings_result = $conn->query("SELECT * FROM buildings ORDER BY building_name");
$buildings = [];
while ($row = $buildings_result->fetch_assoc()) {
    $buildings[$row['id']] = $row['building_name'];
}

// Add contact
if (isset($_POST['add'])) {
    $building_id = intval($_POST['building_id']);
    $department = trim($_POST['department']);
    $number = trim($_POST['number']);

    if (!$building_id || $department === '' || $number === '') {
        $message = "Please fill in all fields.";
    } elseif (!isset($buildings[$building_id])) {
        $message = "Invalid building selected.";
    } else {
        $stmt = $conn->prepare("INSERT INTO contacts (building_id, department, number) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $building_id, $department, $number);
        if ($stmt->execute()) {
            $message = "Contact added successfully.";
        } else {
            $message = "Error adding contact: " . $conn->error;
        }
        $stmt->close();
    }
}

// Delete contact
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Contact deleted successfully.";
    } else {
        $message = "Error deleting contact: " . $conn->error;
    }
    $stmt->close();
}

// Update contact
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $building_id = intval($_POST['building_id']);
    $department = trim($_POST['department']);
    $number = trim($_POST['number']);

    if (!$building_id || $department === '' || $number === '') {
        $message = "Please fill in all fields.";
    } elseif (!isset($buildings[$building_id])) {
        $message = "Invalid building selected.";
    } else {
        $stmt = $conn->prepare("UPDATE contacts SET building_id = ?, department = ?, number = ? WHERE id = ?");
        $stmt->bind_param("issi", $building_id, $department, $number, $id);
        if ($stmt->execute()) {
            $message = "Contact updated successfully.";
        } else {
            $message = "Error updating contact: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all contacts
$contacts_result = $conn->query("SELECT c.id, c.building_id, b.building_name, c.department, c.number
                                FROM contacts c
                                JOIN buildings b ON c.building_id = b.id
                                ORDER BY b.building_name, c.department");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contacts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --danger-color: #d92323ff;
            --warning-color: #f0de18ff;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --card-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: 25px;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 18px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }
        
        .table {
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        
        .table th {
            background-color: var(--light-color);
            font-weight: 600;
            position: sticky;
            top: 0;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 12px 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            background-color: white;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table tr:first-child td {
            border-top: none;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr {
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        
        .table tr:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateX(2px);
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            margin: 0 3px;
            font-size: 0.85rem;
            min-width: 80px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .alert {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 12px 20px;
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .container {
            max-width: 1400px;
            padding: 20px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #475569;
            font-size: 0.9rem;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .table-responsive {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            border-radius: 12px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 25px;
            max-width: 400px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 14px;
            color: #94a3b8;
        }
        
        .search-box input {
            padding-left: 45px;
            border-radius: 8px;
            height: 45px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-box input:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .badge-building {
            background-color: var(--info-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        .empty-state h5 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #475569;
        }
        
        .empty-state p {
            max-width: 400px;
            margin: 0 auto 20px;
        }
        
        .contact-number {
            font-family: 'SF Mono', 'Roboto Mono', monospace;
            font-weight: 500;
            color: #1e293b;
        }
        
        .department-name {
            font-weight: 500;
            color: #334155;
        }
        
        .building-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #1e293b;
        }
        
        .building-icon {
            width: 30px;
            height: 30px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                max-height: none;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-btn {
                width: 100%;
                margin: 2px 0;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <i class="fas <?= strpos($message, 'Error') === false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-address-book me-3 fs-4"></i>
                            <h4 class="mb-0">Manage Contacts</h4>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <a href="index.php" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#addContactModal">
                                <i class="fas fa-plus me-1"></i>Add Contact
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search contacts by building, department or number...">
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="30%">Building</th>
                                        <th width="30%">Department</th>
                                        <th width="25%">Number</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="contactsTable">
                                    <?php if ($contacts_result->num_rows > 0): ?>
                                        <?php while ($c = $contacts_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="building-name">
                                                        <span class="building-icon">
                                                            <i class="fas fa-building"></i>
                                                        </span>
                                                        <?= htmlspecialchars($c['building_name']) ?>
                                                    </div>
                                                </td>
                                                <td class="department-name"><?= htmlspecialchars($c['department']) ?></td>
                                                <td class="contact-number"><?= htmlspecialchars($c['number']) ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-warning action-btn edit-btn" 
                                                                data-id="<?= $c['id'] ?>"
                                                                data-building="<?= $c['building_id'] ?>"
                                                                data-department="<?= htmlspecialchars($c['department']) ?>"
                                                                data-number="<?= htmlspecialchars($c['number']) ?>"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editContactModal">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </button>
                                                        <a href="?delete=<?= $c['id'] ?>" 
                                                           class="btn btn-sm btn-danger action-btn"
                                                           onclick="return confirm('Are you sure you want to delete this contact?')">
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fas fa-address-book"></i>
                                                    <h5>No Contacts Found</h5>
                                                    <p>You haven't added any contacts yet. Click the "Add Contact" button to get started.</p>
                                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addContactModal">
                                                        <i class="fas fa-plus me-1"></i>Add Your First Contact
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Contact Modal -->
    <div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContactModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Add New Contact
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addBuildingId" class="form-label">Building</label>
                            <select class="form-select" id="addBuildingId" name="building_id" required>
                                <option value="">Select Building</option>
                                <?php foreach ($buildings as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addDepartment" class="form-label">Department</label>
                            <input type="text" class="form-control" id="addDepartment" name="department" placeholder="Enter department name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addNumber" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="addNumber" name="number" placeholder="Enter phone or extension number" required>
                            <small class="text-muted">Format: +1 (123) 456-7890 or ext. 1234</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" name="add" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Contact
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Contact Modal -->
    <div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContactModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Contact
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editBuildingId" class="form-label">Building</label>
                            <select class="form-select" id="editBuildingId" name="building_id" required>
                                <option value="">Select Building</option>
                                <?php foreach ($buildings as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDepartment" class="form-label">Department</label>
                            <input type="text" class="form-control" id="editDepartment" name="department" required>
                        </div>
                        <div class="mb-3">
                            <label for="editNumber" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="editNumber" name="number" required>
                            <small class="text-muted">Format: +1 (123) 456-7890 or ext. 1234</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Contact
                        </button>
                    </div>
                </form>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#contactsTable tr');
            let hasResults = false;
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show no results message if no matches found
            const noResultsRow = document.querySelector('.empty-state');
            if (noResultsRow && !hasResults && searchValue.length > 0) {
                noResultsRow.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h5>No Matching Contacts Found</h5>
                    <p>Try searching with different keywords.</p>
                `;
                noResultsRow.style.display = '';
            }
        });

        // Edit button click handler
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const buildingId = this.getAttribute('data-building');
                const department = this.getAttribute('data-department');
                const number = this.getAttribute('data-number');
                
                document.getElementById('editId').value = id;
                document.getElementById('editBuildingId').value = buildingId;
                document.getElementById('editDepartment').value = department;
                document.getElementById('editNumber').value = number;
            });
        });

        // Close modal after form submission to prevent resubmission
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                if (modal) {
                    modal.hide();
                }
            });
        });

        // Format phone number input
        const formatPhoneNumber = (input) => {
            // Remove all non-digit characters
            let phoneNumber = input.value.replace(/\D/g, '');
            
            // Format based on length
            if (phoneNumber.length > 0) {
                phoneNumber = phoneNumber.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                phoneNumber = !phoneNumber[2] ? phoneNumber[1] : 
                             '(' + phoneNumber[1] + ') ' + phoneNumber[2] + 
                             (phoneNumber[3] ? '-' + phoneNumber[3] : '');
            }
            
            input.value = phoneNumber;
        };

        // Apply formatting to phone number inputs
        document.getElementById('addNumber')?.addEventListener('input', (e) => formatPhoneNumber(e.target));
        document.getElementById('editNumber')?.addEventListener('input', (e) => formatPhoneNumber(e.target));
    </script>
</body>
</html>