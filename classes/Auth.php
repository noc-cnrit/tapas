<?php
/**
 * Authentication System
 * Handles user login, logout, and session management
 */

class Auth {
    
    // Default admin credentials (in production, use database)
    private static $users = [
        'admin' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // "password"
            'role' => 'admin',
            'name' => 'Administrator',
            'force_password_change' => false
        ],
        'manager' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // "password"
            'role' => 'manager',
            'name' => 'Manager',
            'force_password_change' => false
        ],
        'plateadmin' => [
            'password' => '', // Empty password - must be changed on first login
            'role' => 'admin',
            'name' => 'Plate Admin',
            'force_password_change' => true
        ]
    ];
    
    /**
     * Start session if not already started
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Authenticate user with username and password
     */
    public static function login($username, $password) {
        self::startSession();
        
        // Load persistent user data
        self::loadUserData();
        
        if (!isset(self::$users[$username])) {
            return false;
        }
        
        $user = self::$users[$username];
        
        // Special handling for empty password (plateadmin)
        $passwordValid = false;
        if ($user['password'] === '' && $password === '') {
            $passwordValid = true;
        } elseif ($user['password'] !== '' && password_verify($password, $user['password'])) {
            $passwordValid = true;
        }
        
        if ($passwordValid) {
            // Set session variables
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $username;
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['login_time'] = time();
            $_SESSION['force_password_change'] = $user['force_password_change'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user and destroy session
     */
    public static function logout() {
        self::startSession();
        
        // Clear session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        self::startSession();
        
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        self::startSession();
        
        return self::isAuthenticated() && 
               isset($_SESSION['user_role']) && 
               $_SESSION['user_role'] === $role;
    }
    
    /**
     * Get current user information
     */
    public static function getUser() {
        self::startSession();
        
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'login_time' => $_SESSION['login_time'],
            'force_password_change' => $_SESSION['force_password_change'] ?? false
        ];
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth($redirectTo = 'login.php') {
        if (!self::isAuthenticated()) {
            header('Location: ' . $redirectTo);
            exit;
        }
        
        // Check if password change is required
        self::startSession();
        if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change']) {
            // Only allow access to change password page
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'change_password.php' && $currentPage !== 'login.php') {
                header('Location: change_password.php');
                exit;
            }
        }
    }
    
    /**
     * Generate a secure password hash
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Check if session is expired (optional timeout)
     */
    public static function isSessionExpired($timeoutMinutes = 120) {
        self::startSession();
        
        if (!self::isAuthenticated()) {
            return true;
        }
        
        $loginTime = $_SESSION['login_time'] ?? 0;
        $currentTime = time();
        $sessionAge = ($currentTime - $loginTime) / 60; // minutes
        
        return $sessionAge > $timeoutMinutes;
    }
    
    /**
     * Refresh session timestamp
     */
    public static function refreshSession() {
        self::startSession();
        
        if (self::isAuthenticated()) {
            $_SESSION['login_time'] = time();
        }
    }
    
    /**
     * Check if user requires password change
     */
    public static function requiresPasswordChange() {
        self::startSession();
        return isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'];
    }
    
    /**
     * Change user password with file persistence
     */
    public static function changePassword($username, $newPassword) {
        if (isset(self::$users[$username])) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user data in memory
            self::$users[$username]['password'] = $hashedPassword;
            self::$users[$username]['force_password_change'] = false;
            
            // Save to file for persistence
            self::saveUserData($username, $hashedPassword, false);
            
            // Update session
            self::startSession();
            $_SESSION['force_password_change'] = false;
            
            return true;
        }
        return false;
    }
    
    /**
     * Save user data to file for persistence
     */
    private static function saveUserData($username, $passwordHash, $forcePasswordChange) {
        $userFile = __DIR__ . '/../admin/.users.json';
        
        // Load existing data
        $userData = [];
        if (file_exists($userFile)) {
            $userData = json_decode(file_get_contents($userFile), true) ?: [];
        }
        
        // Update user data
        $userData[$username] = [
            'password' => $passwordHash,
            'force_password_change' => $forcePasswordChange,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Save to file
        file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));
        chmod($userFile, 0600); // Restrict file permissions
    }
    
    /**
     * Load user data from file
     */
    private static function loadUserData() {
        $userFile = __DIR__ . '/../admin/.users.json';
        
        if (file_exists($userFile)) {
            $userData = json_decode(file_get_contents($userFile), true) ?: [];
            
            // Merge with default users
            foreach ($userData as $username => $data) {
                if (isset(self::$users[$username])) {
                    self::$users[$username]['password'] = $data['password'];
                    self::$users[$username]['force_password_change'] = $data['force_password_change'];
                }
            }
        }
    }
}
?>
