<?php
// controllers/myAccountController.php
// session_start();
include('../../config/config.php');
include(__DIR__ . '/../models/User.php');

class MyAccountController {
    private $userModel;
    
    public function __construct($conn) {
        $this->userModel = new User($conn);
    }
    
    public function getUserProfile() {
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../views/auth/login_register.php');
            exit();
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Lấy thông tin người dùng
        $userData = $this->userModel->getUserById($user_id);
        
        if (!empty($userData)) {
            return $userData; // Trả về user đầu tiên
        }
        
        return null;
    }
    
    public function updateProfile($data) {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }

        try {
            $user_id = $_SESSION['user_id'];
            $user = $this->userModel->getUserById($user_id);

            if (!$user) return ['success' => false, 'message' => 'Không tìm thấy người dùng'];

            $full_name = $data['firstName'] . ' ' . $data['lastName'];
            $username = $user['username'];
            $email = $data['email'];
            $phone = $data['phone'];
            $gender = $data['gender'];
            $date_of_birth = $data['date_of_birth'] ?? null;
            $address = $user['address'] ?? '';
            $user_type = $user['user_type'] ?? 'customer';
            $img = $user['img'] ?? 'default.jpg';

            $result = $this->userModel->updateUser($user_id, $username, $full_name, $email, $phone, $address, $date_of_birth, $gender, $user_type, $img);

            if ($result) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;
                return ['success' => true, 'message' => 'Cập nhật thành công!'];
            }

            return ['success' => false, 'message' => 'Cập nhật thất bại'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function updateAddress($data) {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }

        try {
            $user_id = $_SESSION['user_id'];
            $user = $this->userModel->getUserById($user_id);

            if (!$user) return ['success' => false, 'message' => 'Không tìm thấy người dùng'];

            // Giữ nguyên các thông tin khác
            $username = $user['username'];
            $full_name = $user['full_name'];
            $email = $user['email'];
            $phone = $user['phone'];
            $gender = $user['gender'];
            $date_of_birth = $user['date_of_birth'];
            $user_type = $user['user_type'] ?? 'customer';
            $img = $user['img'] ?? 'default.jpg';
            
            // Cập nhật địa chỉ
            $address = trim($data['address']);

            $result = $this->userModel->updateUser($user_id, $username, $full_name, $email, $phone, $address, $date_of_birth, $gender, $user_type, $img);

            if ($result) {
                $_SESSION['address'] = $address;
                return ['success' => true, 'message' => 'Cập nhật địa chỉ thành công!'];
            }

            return ['success' => false, 'message' => 'Cập nhật địa chỉ thất bại'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    public function updateAvatar($avatarFile) {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }

        try {
            $user_id = $_SESSION['user_id'];
            $user = $this->userModel->getUserById($user_id);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Không tìm thấy người dùng'];
            }
            
            $username = $user['username'];

            $uploadDir = __DIR__ . '/../../public/uploads/avatar/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Lỗi khi upload ảnh'];
            }

            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($avatarFile['type'], $allowed)) {
                return ['success' => false, 'message' => 'Chỉ chấp nhận JPG, PNG, GIF'];
            }

            // Xóa ảnh cũ (nếu không phải ảnh mặc định)
            $oldImg = $user['img'];
            if ($oldImg && $oldImg !== 'default.jpg' && file_exists($uploadDir . $oldImg)) {
                unlink($uploadDir . $oldImg);
            }

            $ext = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
            $newName = $username . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($avatarFile['tmp_name'], $targetPath)) {
                $result = $this->userModel->updateAvatar($user_id, $newName);
                if ($result) {
                    $_SESSION['img'] = $newName;
                    return [
                        'success' => true, 
                        'avatar' => $newName,
                        'message' => 'Cập nhật avatar thành công!'
                    ];
                }
            }

            return ['success' => false, 'message' => 'Không thể lưu file'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function changePassword($data) {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }

        try {
            $user_id = $_SESSION['user_id'];
            $current_password = $data['current_password'];
            $new_password = $data['new_password'];
            $confirm_password = $data['confirm_password'];

            // Kiểm tra mật khẩu mới và xác nhận mật khẩu
            if ($new_password !== $confirm_password) {
                return ['success' => false, 'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp'];
            }

            // Kiểm tra độ dài mật khẩu
            if (strlen($new_password) < 6) {
                return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
            }

            // Lấy thông tin người dùng hiện tại
            $user = $this->userModel->getUserById($user_id);
            if (!$user) {
                return ['success' => false, 'message' => 'Không tìm thấy người dùng'];
            }

            // Kiểm tra mật khẩu hiện tại
            if (!password_verify($current_password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
            }

            // Kiểm tra mật khẩu mới không được trùng với mật khẩu cũ
            if (password_verify($new_password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mật khẩu mới không được trùng với mật khẩu cũ'];
            }

            // Mã hóa mật khẩu mới
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu trong database
            $result = $this->userModel->changePass($user_id, $new_password_hash);

            if ($result) {
                return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
            } else {
                return ['success' => false, 'message' => 'Không thể cập nhật mật khẩu'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Xử lý các request
$controller = new MyAccountController($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $data = [
                    'firstName' => $_POST['firstName'],
                    'lastName' => $_POST['lastName'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                    'gender' => $_POST['gender'],
                    'date_of_birth' => $_POST['date_of_birth'] ?? null
                ];
                $result = $controller->updateProfile($data);
                echo json_encode($result);
                exit();
                break;

            case 'update_address':
                $data = [
                    'address' => $_POST['address'] ?? ''
                ];
                $result = $controller->updateAddress($data);
                echo json_encode($result);
                exit();
                break;
                
            case 'update_avatar':
                if (isset($_FILES['avatar'])) {
                    $result = $controller->updateAvatar($_FILES['avatar']);
                    echo json_encode($result);
                    exit();
                }
                break;
                
            case 'change_password':
                $data = [
                    'current_password' => $_POST['current_password'] ?? '',
                    'new_password' => $_POST['new_password'] ?? '',
                    'confirm_password' => $_POST['confirm_password'] ?? ''
                ];
                $result = $controller->changePassword($data);
                echo json_encode($result);
                exit();
                break;
        }
    }
}

// Lấy thông tin người dùng để hiển thị
$userProfile = $controller->getUserProfile();
?>