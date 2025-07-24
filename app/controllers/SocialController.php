<?php
    session_start();
    include_once(__DIR__ .'/../../config/config.php');
    mysqli_query($conn, "DELETE FROM temp_messages WHERE expires_at <= NOW()");
    $outgoing_id = $_SESSION['user_id'];
    $sql = mysqli_query($conn, "SELECT * FROM users WHERE NOT user_id = {$outgoing_id} AND is_active = 1");
    $output = "";
   
    if(mysqli_num_rows($sql) == 0){
        $output = "No users are available to chat";
    }elseif(mysqli_num_rows($sql) > 0){
        while($row = mysqli_fetch_assoc($sql)){
            $sql2 = "SELECT * FROM temp_messages
                    WHERE ((sender_id = {$outgoing_id} AND recipient_id = {$row['user_id']})
                        OR (sender_id = {$row['user_id']} AND recipient_id = {$outgoing_id}))
                    AND expires_at > NOW()
                    ORDER BY message_id DESC
                    LIMIT 1";
            $query2 = mysqli_query($conn, $sql2); // Sửa tên biến từ qurery2 thành query2
            
            if(mysqli_num_rows($query2) > 0){
                $row2 = mysqli_fetch_assoc($query2);
                $result = $row2['content'];
                $you = ($row2['recipient_id'] == $outgoing_id) ? "You: " : "";
            } else {
                $result = 'No message available';
                $you = "";
            }
            
            // Trimming message if words are more than 28 characters
            (strlen($result) > 28) ? $msg = substr($result, 0, 28) . '...' : $msg = $result;
            
            $output .= '<a href="#" class="user-item" data-user="' . $row['user_id'] . '">
                            <div class="content">
                                <img src="../../public/uploads/avatar/' . $row['img'] . '" alt="">
                                <div class="details">
                                    <span>' . $row['full_name'] . ' ' . $row['username'] . '</span>
                                    <p>'. $you . $msg .'</p>
                                </div>
                            </div>
                            <div class="status-dot">
                                <i class="fas fa-circle"></i>
                            </div>
                        </a>';
        }
    }
    echo $output;
?>