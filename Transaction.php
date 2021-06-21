<?php 
    session_start();
    require_once("connection.php");


    // if(isset($_SESSION['transaction_successfull'])){
    //     header('Location: reciept.php');
    //     exit;
    // }

    //variable declaration
    $control = 0;
    $recipient_account_num_error = '';
    $name_error = '';
    $c_account_error = '';
    $amount_error = '';
    $name = '';
    $recipient_account_num = '';

    function filterText($str){
        $str = strip_tags($str);
        $str = trim($str);
        $str = addslashes($str);
        return $str;
    }
    //Getting the customer_id from the home page, the customer's data is taken out to be displayed
    if(isset($_GET['c_id'])){
        $customer_id = $_GET['c_id'];
        $sql = "SELECT name, account_num, current_balance FROM customers WHERE customer_id='$customer_id'";
        $result = $conn->query($sql);
        if($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $sender_name = $row['name'];
            $sender_acc_num = $row['account_num'];
            $sender_balance = $row['current_balance'];
        }
        else {
            header('Location: index.php');
            $_SESSION['message'] = 'Invalid customer ID';
            $_SESSION['message-css'] = 'error-msg';
            exit;
        }
    }
    //if the customer_id is wrong, the client is redirected back to the home page
    else{
        $_SESSION['message'] = 'Invalid customer ID';
        $_SESSION['message-css'] = 'error-msg';
        header('Location: index.php');
        exit;
    }

    if(isset($_POST["name"]) && isset($_POST["account_num"]) && isset($_POST["c_account_num"]) && isset($_POST["amount"])){
        $control = 1;
        $name = filterText($_POST["name"]);
        $recipient_account_num = filterText($_POST["account_num"]);
        $c_account_num = filterText($_POST["c_account_num"]);
        $amount = filterText($_POST["amount"]);
        if($name==''){
            $name_error = 'Name required';
            $control = 0;
        }
        if($recipient_account_num==''){
            $recipient_account_num_error = 'Account number required';
            $control = 0;
        }
        //if recipient's account number doesn't exist
        else{
            $sql = "SELECT * FROM customers WHERE name='$name' AND account_num='$recipient_account_num'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            if($result->num_rows == 0){
                $control = 0;
                $recipient_account_num_error = 'Invalid credentials';
            }
            else $recipient_balance = $row['current_balance'];
        }
        if($c_account_num==''){
            $c_account_error = 'Please confirm your account number';
            $control = 0;
        }
        else if($sender_acc_num==$recipient_account_num){
            $recipient_account_num_error = 'Cannot transfer money to own account';
            $control = 0;
        }
        else if($c_account_num!=$recipient_account_num){
            $c_account_error = 'Account numbers not matched';
            $control = 0;
        }
        if($amount==''){
            $amount_error = 'Amount required';
            $control = 0;
        }
        else if($amount<0){
            $control = 0;
            $amount_error = 'Invalid amount';
        }
        else if($amount>$sender_balance){
            $control = 0;
            $amount_error = 'Insufficient balance';
        }

        if($control){

            //updating sender and recipient balance
            $new_sender_bal = $sender_balance - $amount;
            $new_recipient_bal = $recipient_balance + $amount;
            $sql = "UPDATE `customers` SET current_balance='$new_sender_bal' WHERE account_num='$sender_acc_num'";
            $conn->query($sql);

            $new_sender_bal = $sender_balance-$amount;
            $sql = "UPDATE `customers` SET current_balance='$new_recipient_bal' WHERE account_num='$recipient_account_num'";
            $conn->query($sql);

            //sending transaction details to the transaction table
            $sql = "INSERT INTO `transactions` (`transaction_id`, `sender_acc_num`, `recipient_acc_num`, `amount`,  `timestamp`) VALUES (NULL, '$sender_acc_num', '$recipient_account_num', '$amount', current_timestamp())";
            $conn->query($sql);

            //after the transaction is complete, the details for the reciept are sent to the reciept page by session
            $sql = "SELECT * FROM transactions ORDER BY transaction_id DESC LIMIT 0, 1";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $_SESSION['transaction_successfull'] = true;
            $_SESSION['success_msg'] = 'Transaction successful';
            $_SESSION['sender_account_num'] = $sender_acc_num;
            $_SESSION['recipient_account_num'] = $recipient_account_num;
            $_SESSION['amount'] = $amount;
            $_SESSION['transaction_id'] = $row['transaction_id'];
            $_SESSION['transaction_time'] = $row['timestamp'];
            $_SESSION['reciept-working'] = 1;

            header('Location: reciept.php');
            exit;
        }


    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer money</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/2e3ffa36c7.js" crossorigin="anonymous"></script>
</head>
<body>
    <img src="bank.jpg" class="index-bg">

    <div class="header1">
        <div class="header-text">
        Welcome To Sparks Bank Portal
        </div>  
    </div>

        <div class="wrapper">
            <div class="child">
                <div class="card">
                    <h3 class="header">
                        Sender's details
                    </h3>
                    <div class="sender-detail">
                        <label>Name </label>
                        <div><?php echo $sender_name; ?></div>
                    </div>
                    <div class="sender-detail">
                        <label>Account number </label>
                        <div><?php echo $sender_acc_num; ?></div>
                    </div>
                    <div class="sender-detail">
                        <label>Balance </label>
                        <div><?php echo $sender_balance; ?></div>
                    </div>
                </div>

            </div>

            <div class="partition">
                <i class="fas fa-long-arrow-alt-right"></i>
            </div> 

            <div class="child">
                <div class="card">
                    <h3 class="header">
                    Enter recipient details
                    </h3>

                    <form action="" method="post" class="form">
                        <div class="input-container">
                            <label for="name" >Name </label>
                            <input type="text" name="name" class="input" value="<?php echo $name; ?>">
                            <div class="error">
                                <?php echo $name_error; ?>
                            </div>
                        </div>
                        <div class="input-container">
                            <label for="account_num">A/C No </label>
                            <input type="number" name="account_num" class="input" value="<?php echo $recipient_account_num; ?>">
                            <div class="error">
                                <?php echo $recipient_account_num_error; ?>
                            </div>
                        </div>
                        <div class="input-container">
                            <label for="c_account_num">Confirm A/C No </label>
                            <input type="number" name="c_account_num" class="input" >
                            <div class="error">
                                <?php echo $c_account_error; ?>
                            </div>
                        </div>
                        <div class="input-container">
                            <label for="amount">Amount </label>
                            <input type="number" step="0.01" name="amount" class="input" >
                            <div class="error">
                                <?php echo $amount_error; ?>
                            </div>
                        </div>
                        <div class="button-container">
                            <button type="submit" class="button">Confirm</button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    
</body>
</html>
