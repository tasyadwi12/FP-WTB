<?php
/**
 * Authentication Functions
 * File: functions/auth.php
 */

// ============================================
// LOGIN FUNCTIONS
// ============================================

/**
 * Attempt Login
 */
function attemptLogin($username_or_email, $password) {
    try {
        // Check if input is email or username
        $field = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Query user
        $sql = "SELECT * FROM users WHERE $field = :identifier AND is_active = 1";
        $result = query($sql, ['identifier' => $username_or_email]);
        
        if ($result && $result->rowCount() > 0) {
            $user = $result->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set login session
                setLoginSession($user);
                
                // Update last login
                updateLastLogin($user['user_id']);
                
                return [
                    'success' => true,
                    'message' => 'Login berhasil',
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Password salah'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan atau tidak aktif'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Update Last Login
 */
function updateLastLogin($user_id) {
    $sql = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
    return execute($sql, ['user_id' => $user_id]);
}

// ============================================
// REGISTER FUNCTIONS
// ============================================

/**
 * Register New User
 */
function registerUser($data) {
    try {
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => 'Field ' . $field . ' wajib diisi'
                ];
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Format email tidak valid'
            ];
        }
        
        // Check if username exists
        if (usernameExists($data['username'])) {
            return [
                'success' => false,
                'message' => 'Username sudah digunakan'
            ];
        }
        
        // Check if email exists
        if (emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'Email sudah terdaftar'
            ];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, full_name, role, phone, is_active) 
                VALUES (:username, :email, :password, :full_name, :role, :phone, 1)";
        
        $params = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashed_password,
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Registrasi berhasil',
                'user_id' => getLastInsertId()
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal registrasi'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if Username Exists
 */
function usernameExists($username) {
    $sql = "SELECT user_id FROM users WHERE username = :username";
    $result = query($sql, ['username' => $username]);
    return $result && $result->rowCount() > 0;
}

/**
 * Check if Email Exists
 */
function emailExists($email) {
    $sql = "SELECT user_id FROM users WHERE email = :email";
    $result = query($sql, ['email' => $email]);
    return $result && $result->rowCount() > 0;
}

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Get User by ID
 */
function getUserById($user_id) {
    $sql = "SELECT * FROM users WHERE user_id = :user_id";
    $result = query($sql, ['user_id' => $user_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Get User by Username
 */
function getUserByUsername($username) {
    $sql = "SELECT * FROM users WHERE username = :username";
    $result = query($sql, ['username' => $username]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Get User by Email
 */
function getUserByEmail($email) {
    $sql = "SELECT * FROM users WHERE email = :email";
    $result = query($sql, ['email' => $email]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Update User Profile
 */
function updateUserProfile($user_id, $data) {
    try {
        $sql = "UPDATE users SET 
                full_name = :full_name,
                email = :email,
                phone = :phone,
                bio = :bio,
                updated_at = NOW()
                WHERE user_id = :user_id";
        
        $params = [
            'user_id' => $user_id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Profile berhasil diupdate'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal update profile'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Update User Avatar
 */
function updateUserAvatar($user_id, $avatar) {
    $sql = "UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE user_id = :user_id";
    return execute($sql, ['user_id' => $user_id, 'avatar' => $avatar]);
}

/**
 * Change Password
 */
function changePassword($user_id, $old_password, $new_password) {
    try {
        // Get current user
        $user = getUserById($user_id);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan'
            ];
        }
        
        // Verify old password
        if (!password_verify($old_password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Password lama salah'
            ];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
        
        if (execute($sql, ['user_id' => $user_id, 'password' => $hashed_password])) {
            return [
                'success' => true,
                'message' => 'Password berhasil diubah'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal ubah password'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Toggle User Status
 */
function toggleUserStatus($user_id) {
    $sql = "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE user_id = :user_id";
    return execute($sql, ['user_id' => $user_id]);
}

/**
 * Delete User
 */
function deleteUser($user_id) {
    // Don't allow deleting admin
    $user = getUserById($user_id);
    if ($user && $user['role'] === 'admin') {
        return [
            'success' => false,
            'message' => 'Tidak bisa menghapus admin'
        ];
    }
    
    $sql = "DELETE FROM users WHERE user_id = :user_id";
    if (execute($sql, ['user_id' => $user_id])) {
        return [
            'success' => true,
            'message' => 'User berhasil dihapus'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal menghapus user'
    ];
}

// ============================================
// PASSWORD RESET FUNCTIONS
// ============================================

/**
 * Request Password Reset
 */
function requestPasswordReset($email) {
    try {
        $user = getUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email tidak terdaftar'
            ];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database (you need to create password_resets table)
        $sql = "INSERT INTO password_resets (email, token, expires_at) 
                VALUES (:email, :token, :expires_at)
                ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at";
        
        if (execute($sql, ['email' => $email, 'token' => $token, 'expires_at' => $expires])) {
            // TODO: Send email with reset link
            // sendPasswordResetEmail($email, $token);
            
            return [
                'success' => true,
                'message' => 'Link reset password telah dikirim ke email Anda',
                'token' => $token // For testing only, remove in production
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal membuat reset token'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Reset Password with Token
 */
function resetPassword($token, $new_password) {
    try {
        // Verify token
        $sql = "SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()";
        $result = query($sql, ['token' => $token]);
        
        if (!$result || $result->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ];
        }
        
        $reset = $result->fetch(PDO::FETCH_ASSOC);
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password WHERE email = :email";
        
        if (execute($sql, ['email' => $reset['email'], 'password' => $hashed_password])) {
            // Delete used token
            $sql = "DELETE FROM password_resets WHERE token = :token";
            execute($sql, ['token' => $token]);
            
            return [
                'success' => true,
                'message' => 'Password berhasil direset'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal reset password'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}
?>