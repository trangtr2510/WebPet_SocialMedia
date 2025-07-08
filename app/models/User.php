<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Đăng nhập bằng email hoặc username
    public function login($emailOrUsername) {
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // 2. Đăng ký tài khoản
    public function register($username, $full_name, $email, $password_hash, $role = 'customer') {
        $stmt = $this->conn->prepare("
            INSERT INTO users (username, full_name, email, password_hash, user_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $username, $full_name, $email, $password_hash, $role);
        return $stmt->execute();
    }

    // 3. Tạo user mới (toàn quyền)
    public function createUser($username, $full_name, $email, $password_hash, $phone, $address, $date_of_birth, $gender, $user_type, $img) {
        $stmt = $this->conn->prepare("
            INSERT INTO users (username, full_name, email, password_hash, phone, address, date_of_birth, gender, user_type, img)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssssss", $username, $full_name, $email, $password_hash, $phone, $address, $date_of_birth, $gender, $user_type, $img);
        return $stmt->execute();
    }

    // 4. Cập nhật user
    public function updateUser($user_id, $username, $full_name, $email, $phone, $address, $date_of_birth, $gender, $user_type, $img) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, user_type = ?, img = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssssssi", $username, $full_name, $email, $phone, $address, $date_of_birth, $gender, $user_type, $img, $user_id);
        return $stmt->execute();
    }

    public function updateAvatar($user_id, $img) {
        $stmt = $this->conn->prepare("UPDATE users SET img = ? WHERE user_id = ?");
        $stmt->bind_param("si", $img, $user_id);
        return $stmt->execute();
    }

    // 5. Xóa user
    public function deleteUser($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }

    // 6. Tìm kiếm user theo tên hoặc email
    public function searchUser($keyword) {
        $keyword = "%" . $keyword . "%";
        $stmt = $this->conn->prepare("
            SELECT * FROM users 
            WHERE full_name LIKE ? OR email LIKE ?
        ");
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 7. Đổi mật khẩu
    public function changePass($user_id, $new_password_hash) {
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_password_hash, $user_id);
        return $stmt->execute();
    }

    // 8. Lấy tất cả user theo vai trò
    public function getAllUserByRole($role) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_type = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 8.1. Lấy user theo vai trò
    public function getUser($role, $user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_type = ? And user_id = ?");
        $stmt->bind_param("si", $role, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc(); // chỉ trả về 1 dòng (không phải mảng nhiều dòng)
    }

    // 9. Kiểm tra có phải Admin không
    public function isAdmin($user) {
        return isset($user['user_type']) && $user['user_type'] === 'admin';
    }

    // 10. Kiểm tra có phải Nhân viên không
    public function isEmployee($user) {
        return isset($user['user_type']) && $user['user_type'] === 'employee';
    }

    // 11. Kiểm tra có phải Khách hàng không
    public function isCustomer($user) {
        return isset($user['user_type']) && $user['user_type'] === 'customer';
    }

    // Hàm kiểm tra username đã tồn tại chưa
    public function usernameExists($username) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

}
