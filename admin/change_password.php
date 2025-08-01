<?php
/**
 * Password Change Page
 * Forces users to change their password when required
 */

require_once '../classes/Auth.php';

// Must be logged in to change password
if (!Auth::isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
$message = '';
$error = '';

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($newPassword)) {
        $error = "New password is required.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Change password
        if (Auth::changePassword($user['id'], $newPassword)) {
            $message = "Password changed successfully! You can now access the admin area.";
            // Redirect to admin dashboard after successful change
            header('Location: index.php');
            exit;
        } else {
            $error = "Failed to change password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Plate St. Pete Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .change-password-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 2em;
        }
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 0.9em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background: #5a67d8;
        }
        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .user-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .user-info h3 {
            margin: 0 0 5px 0;
            color: #1976d2;
        }
        .user-info p {
            margin: 0;
            color: #666;
        }
        .password-requirements {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        .logout-link {
            text-align: center;
            margin-top: 20px;
        }
        .logout-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
        }
        .logout-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="logo">
            <h1>🔐 Change Password</h1>
            <p>Plate St. Pete Admin</p>
        </div>

        <div class="user-info">
            <h3>Welcome, <?= htmlspecialchars($user['name']) ?>!</h3>
            <p>You must set a new secure password before accessing the admin area.</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="password-requirements">
            <h4>Password Requirements:</h4>
            <ul>
                <li>Minimum 6 characters long</li>
                <li>Use a combination of letters, numbers, and symbols</li>
                <li>Avoid using personal information</li>
                <li>Make it unique and memorable</li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required 
                       placeholder="Enter your new password" minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required 
                       placeholder="Confirm your new password" minlength="6">
            </div>

            <button type="submit" class="submit-btn">Set New Password</button>
        </form>

        <div class="logout-link">
            <a href="login?logout=1">Logout</a>
        </div>
    </div>

    <script>
        // Add client-side password matching validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value && this.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
