<?php
// controllers/myAccountController.php
session_start();
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
                
            case 'update_avatar':
                if (isset($_FILES['avatar'])) {
                    $result = $controller->updateAvatar($_FILES['avatar']);
                    echo json_encode($result);
                    exit();
                }
                break;
        }
    }
}

// Lấy thông tin người dùng để hiển thị
$userProfile = $controller->getUserProfile();
?>