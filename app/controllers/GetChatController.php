<?php
    session_start();

    if(isset($_SESSION["user_id"])){
        require_once(__DIR__ . '/../../config/config.php');
        mysqli_query($conn, "DELETE FROM temp_messages WHERE expires_at <= NOW()");
        $outgoing_id = mysqli_real_escape_string($conn, $_POST["outgoing_id"]);
        $incoming_id = mysqli_real_escape_string($conn, $_POST["incoming_id"]);
        $output = "";

        $sql = "SELECT * FROM temp_messages 
                LEFT JOIN users ON users.user_id = sender_id 
                WHERE 
                    (
                        (recipient_id = {$outgoing_id} AND sender_id = {$incoming_id}) 
                        OR 
                        (recipient_id = {$incoming_id} AND sender_id = {$outgoing_id})
                    )
                    AND message_type = 'message'
                    AND expires_at > NOW()
                ORDER BY message_id ASC";
        $query = mysqli_query($conn, $sql);
        if(mysqli_num_rows($query) > 0){
            while($row = mysqli_fetch_assoc($query)){
                if($row['recipient_id'] === $outgoing_id){
                    $output .= '<div class="chat outgoing">
                                    <div class="details">
                                        <p>'. $row['content'] .'</p>
                                    </div>
                                </div>';
                }else{
                    $output .= '<div class="chat incoming">
                                    <img src="../../public/uploads/avatar/'. $row['img'] .'" alt="">
                                    <div class="details">
                                        <p>'. $row['content'] .'</p>
                                    </div>
                                </div>';
                }
            }
            echo $output;
        }
    }
    else{
        header('../../views/auth/login_register.php');
    }

?>
