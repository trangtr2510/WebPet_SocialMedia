<?php
class Message {
    private $conn;
    private $table = "temp_messages";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Gửi tin nhắn
    public function send($sender_id, $recipient_id, $message_type, $subject, $content) {
        $query = "INSERT INTO $this->table (sender_id, recipient_id, message_type, subject, content) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iisss", $sender_id, $recipient_id, $message_type, $subject, $content);
        return $stmt->execute();
    }

    // Chặn tin nhắn (set block = true)
    public function block($message_id) {
        $query = "UPDATE $this->table SET is_Block = 1 WHERE message_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $message_id);
        return $stmt->execute();
    }

    // Lấy tin nhắn của người dùng
    public function getMessagesForUser($user_id) {
        $query = "SELECT * FROM $this->table WHERE recipient_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        return $messages;
    }

    // Lấy tin nhắn đã gửi
    public function getSentMessages($sender_id) {
        $query = "SELECT * FROM $this->table WHERE sender_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        return $messages;
    }
}
?>
