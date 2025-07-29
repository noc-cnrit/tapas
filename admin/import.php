<?php
/**
 * Web Interface for CSV Import
 * Run this in the browser to import CSV data
 */

require_once '../classes/Auth.php';

// Require authentication
Auth::requireAuth();

// Refresh session
Auth::refreshSession();

// Set execution time limit
set_time_limit(300); // 5 minutes

// Include the import script
require_once '../database/import_csv.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Menu CSV Import - Plate St. Pete Admin</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .output { 
            background: #f0f0f0; 
            padding: 20px; 
            border-radius: 5px; 
            white-space: pre-line; 
            margin: 20px 0;
        }
        .button { 
            background: #dc3545; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: bold;
        }
        .button:hover { 
            background: #c82333; 
        }
        .nav-links {
            text-align: center;
            margin-bottom: 30px;
        }
        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin: 20px 0;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Menu CSV Import</h1>
        
        <div class="nav-links">
            <a href="index.php">← Admin Dashboard</a>
            <a href="menus.php">Manage Menus</a>
            <a href="sections.php">Manage Sections</a>
            <a href="items.php">Manage Items</a>
            <a href="login.php?logout=1">Logout</a>
        </div>
        
        <?php if (isset($_POST['import'])): ?>
            <h2>Import Results:</h2>
            <div class="output">
                <?php
                // Capture output
                ob_start();
                $success = importCSV();
                $output = ob_get_clean();
                echo htmlspecialchars($output);
                ?>
            </div>
            
            <?php if ($success): ?>
                <p style="color: green; font-weight: bold;">Import completed successfully!</p>
                <p><a href="../index.php">View Menu</a></p>
            <?php else: ?>
                <p style="color: red; font-weight: bold;">Import failed. Check the output above for details.</p>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Warning:</strong> This will clear all existing menu items and replace them with data from the CSV file.
            </div>
            
            <p>This tool will import all menu items from the CSV file located in the database directory into the database.</p>
            <p>The import process will:</p>
            <ul>
                <li>Delete all existing menu items, sections, and menus</li>
                <li>Import new data from the CSV file</li>
                <li>Create new menu structure based on the CSV data</li>
            </ul>
            
            <form method="POST">
                <button type="submit" name="import" class="button" onclick="return confirm('Are you sure you want to import? This will PERMANENTLY DELETE all existing menu data and replace it with CSV data. This action cannot be undone!')">
                    🚨 Import CSV Data (Destructive Operation)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
