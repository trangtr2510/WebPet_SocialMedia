<?php
    session_start();

    if(isset($_SESSION["user_id"])){
        require_once(__DIR__ . '/../../config/config.php');
        mysqli_query($conn, "DELETE FROM temp_messages WHERE expires_at <= NOW()");
        $outgoing_id = mysqli_real_escape_string($conn, $_POST["outgoing_id"]);
        $incoming_id = mysqli_real_escape_string($conn, $_POST["incoming_id"]);
        $message = mysqli_real_escape_string($conn, $_POST["message"]);

        if(!empty($message)){
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 days'));

            $sql = mysqli_query($conn, "INSERT INTO temp_messages 
                (sender_id, recipient_id, message_type, content, expires_at) 
                VALUES 
                ({$incoming_id}, {$outgoing_id}, 'message', '{$message}', '{$expires_at}')");
            }
    }
    else{
        header('../../views/auth/login_register.php');
    }

?>
