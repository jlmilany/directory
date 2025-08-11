<?php
// Database configuration
$host = '127.0.0.1';
$dbname = 'phone_directory';
$username = 'your_username'; // Replace with your database username
$password = 'your_password'; // Replace with your database password

try {
    // Create a PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if we're handling an AJAX search request
    if (isset($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $stmt = $pdo->prepare("
            SELECT c.id, b.building_name, c.department, c.number 
            FROM contacts c
            JOIN buildings b ON c.building_id = b.id
            WHERE b.building_name LIKE :search OR c.department LIKE :search OR c.number LIKE :search
            ORDER BY b.building_name, c.department
        ");
        $stmt->execute(['search' => $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return JSON for AJAX requests
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    // For initial page load, get all contacts
    $contactsQuery = $pdo->query("
        SELECT c.id, b.building_name, c.department, c.number 
        FROM contacts c
        JOIN buildings b ON c.building_id = b.id
        ORDER BY b.building_name, c.department
        LIMIT 100
    ");
    $initialContacts = $contactsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Phone Directory - Real-time Search</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-light: #eef2ff;
      --primary-dark: #3a56d4;
      --secondary: #f72585;
      --light: #f8f9fa;
      --dark: #1a1a1a;
      --gray: #6c757d;
      --light-gray: #f1f3f5;
      --white: #ffffff;
      --success: #4cc9f0;
      --warning: #ffc107;
      --warning-dark: #e0a800;
      --border-radius: 12px;
      --border-radius-sm: 6px;
      --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      --box-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
      --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      --glass-effect: backdrop-filter: blur(12px);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      margin: 0;
      color: var(--dark);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    header {
      background: rgba(255, 255, 255, 0.85);
      padding: 1rem 2rem;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--box-shadow-sm);
      position: sticky;
      top: 0;
      z-index: 1000;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 1.5rem;
      font-weight: 700;
      margin-right: 1.5rem;
      color: var(--primary);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .logo:hover {
      transform: translateY(-2px);
    }
    
    .logo i {
      color: var(--primary);
      font-size: 1.8rem;
    }
    
    .search-container {
      flex: 1 1 auto;
      max-width: 700px;
      position: relative;
      margin: 0 2rem;
    }
    
    header input[type="search"] {
      width: 100%;
      padding: 0.85rem 1.5rem 0.85rem 3.5rem;
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: var(--border-radius);
      font-size: 1rem;
      background-color: var(--light-gray);
      transition: var(--transition);
      font-weight: 400;
      box-shadow: var(--box-shadow-sm);
    }
    
    header input[type="search"]:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
      background-color: var(--white);
      border-color: var(--primary);
    }
    
    .search-icon {
      position: absolute;
      left: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
      font-size: 1.1rem;
    }
    
    nav {
      display: flex;
      gap: 1rem;
    }
    
    nav a {
      background-color: var(--primary);
      padding: 0.85rem 1.75rem;
      text-decoration: none;
      border-radius: var(--border-radius);
      color: var(--white);
      font-weight: 500;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    nav a::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: var(--transition);
    }
    
    nav a:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(67, 97, 238, 0.3);
    }
    
    nav a:hover::before {
      left: 100%;
    }
    
    nav a i {
      font-size: 1.1rem;
    }
    
    .container {
      max-width: 2500px;
      margin: 1.5rem auto;
      padding: 0 2rem;
      flex: 1;
    }
    
    .table-wrapper {
      height: calc(100vh - 200px);
      width: 100%;
      max-width: 98vw;
      margin: 1rem auto;
      overflow: auto;
      background: var(--white);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      position: relative;
    }
    
    table {
      width: 100%;
      min-width: 1200px;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    th, td {
      padding: 1.25rem;
      text-align: left;
      border-bottom: 1px solid rgba(0, 0, 0, 0.03);
    }
    
    th {
      background-color: var(--primary-light);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
      color: var(--primary);
      position: sticky;
      top: 0;
    }
    
    tr:not(:first-child):hover td {
      background-color: var(--primary-light);
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem;
      color: var(--gray);
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 100%;
    }
    
    .empty-state i {
      font-size: 2rem;
      margin-bottom: 1.5rem;
      color: var(--light-gray);
      opacity: 0.7;
    }
    
    .empty-state h3 {
      margin: 0.5rem 0;
      color: var(--dark);
      font-weight: 600;
      font-size: 1.5rem;
    }
    
    .empty-state p {
      max-width: 400px;
      margin: 0 auto;
    }
    
    #loadingSpinner {
      display: none;
      margin: 2rem auto;
      border: 4px solid var(--light-gray);
      border-top: 4px solid var(--primary);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .result-count {
      margin: 1rem 0;
      color: var(--gray);
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .result-count i {
      color: var(--primary);
    }
    
    @media (max-width: 992px) {
      header {
        padding: 1rem;
      }
      
      .search-container {
        margin: 1rem 0;
        order: 3;
        width: 100%;
        max-width: 100%;
      }
      
      nav {
        margin-left: auto;
      }
    }
    
    @media (max-width: 768px) {
      nav {
        width: 100%;
        justify-content: space-between;
        margin-top: 1rem;
      }
      
      nav a {
        flex: 1;
        justify-content: center;
        padding: 0.85rem;
      }
      
      .container {
        padding: 0 1rem;
      }
      
      th, td {
        padding: 1rem 0.75rem;
      }
    }
    
    /* Modern scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    ::-webkit-scrollbar-track {
      background: var(--light-gray);
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }
  </style>
</head>
<body>

<header>
  <a href="#" class="logo">
    <i class="fas fa-phone-alt"></i>
    <span>Phone Directory</span>
  </a>
  
  <div class="search-container">
    <i class="fas fa-search search-icon"></i>
    <input type="search" id="search" placeholder="Search by building, department, or phone number..." aria-label="Search contacts" autocomplete="off" />
  </div>
  
  <nav role="navigation" aria-label="Manage links">
    <a href="manage_buildings.php"><i class="fas fa-building"></i> Manage Buildings</a>
    <a href="manage_contacts.php"><i class="fas fa-address-book"></i> Manage Contacts</a>
  </nav>
</header>

<main class="container">
  <div class="result-count" id="resultCount">
    <i class="fas fa-info-circle"></i>
    <span>Showing <?php echo count($initialContacts); ?> contacts</span>
  </div>
  
  <div class="table-wrapper">
    <table id="resultsTable" aria-live="polite" aria-relevant="all" role="grid">
      <thead>
        <tr role="row">
          <th scope="col">Building</th>
          <th scope="col">Department/Office</th>
          <th scope="col">Number</th>
        </tr>
      </thead>
      <tbody id="resultsBody">
        <?php if (count($initialContacts) > 0): ?>
          <?php foreach ($initialContacts as $contact): ?>
            <tr role="row">
              <td><?php echo htmlspecialchars($contact['building_name']); ?></td>
              <td><?php echo htmlspecialchars($contact['department']); ?></td>
              <td><?php echo htmlspecialchars($contact['number']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr class="empty-state-row">
            <td colspan="3">
              <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No Contacts Found</h3>
                <p>There are currently no contacts in the directory</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<div id="loadingSpinner" aria-hidden="true" title="Loading..."></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const resultsBody = document.getElementById('resultsBody');
    const resultCount = document.getElementById('resultCount');
    const loadingSpinner = document.getElementById('loadingSpinner');
    let searchTimeout;

    // Function to perform search
    function performSearch(query) {
        if (query.length < 1) {
            // If empty search, show initial contacts (you might want to fetch all instead)
            fetchInitialContacts();
            return;
        }

        loadingSpinner.style.display = 'block';
        
        fetch(`?search=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                updateResults(data);
                loadingSpinner.style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                loadingSpinner.style.display = 'none';
            });
    }

    // Function to fetch initial contacts
    function fetchInitialContacts() {
        fetch('?search=')
            .then(response => response.json())
            .then(data => {
                updateResults(data);
            });
    }

    // Function to update the results table
    function updateResults(data) {
        if (data.length > 0) {
            let html = '';
            data.forEach(contact => {
                html += `
                    <tr role="row">
                        <td>${escapeHtml(contact.building_name)}</td>
                        <td>${escapeHtml(contact.department)}</td>
                        <td>${escapeHtml(contact.number)}</td>
                    </tr>
                `;
            });
            resultsBody.innerHTML = html;
            resultCount.innerHTML = `<i class="fas fa-info-circle"></i><span>Found ${data.length} matching contacts</span>`;
        } else {
            resultsBody.innerHTML = `
                <tr class="empty-state-row">
                    <td colspan="3">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No Results Found</h3>
                            <p>No contacts match your search criteria</p>
                        </div>
                    </td>
                </tr>
            `;
            resultCount.innerHTML = `<i class="fas fa-info-circle"></i><span>No results found</span>`;
        }
    }

    // Simple HTML escape function
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Event listener for search input
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length > 0) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            fetchInitialContacts();
            resultCount.innerHTML = `<i class="fas fa-info-circle"></i><span>Showing all contacts</span>`;
        }
    });

    // Initial focus on search input
    searchInput.focus();
});
</script>

</body>
</html>