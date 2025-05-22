<?php
// login.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128
// Description: Handles user login and registration.

session_start();
require_once 'config.php'; // $conn is established here.

$login_error_message = '';
$registration_message = '';
$registration_message_type = ''; // 'success' or 'error'
$countries = [];

// Fetch countries for the registration form dropdown
if ($conn) { // Check if $conn is valid
    $sql_countries = "SELECT country_id, country_name FROM COUNTRY ORDER BY country_name ASC";
    $countries_result = $conn->query($sql_countries);
    if ($countries_result && $countries_result->num_rows > 0) {
        while ($row = $countries_result->fetch_assoc()) {
            $countries[] = $row;
        }
    }

} else {
    // Handle connection error for country fetching if necessary
    $registration_message = "Veritabanı bağlantı hatası nedeniyle ülkeler yüklenemedi.";
    $registration_message_type = 'error';
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        // Login processing
        $username = sanitize_input($conn, $_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT user_id, name, username, password, country_id FROM USERS WHERE username = ?");
        if ($stmt === false) {
            $login_error_message = "Database error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['country_id'] = $user['country_id'];

                    $update_stmt = $conn->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
                    $update_stmt->bind_param("i", $user['user_id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    header("Location: homepage.php");
                    exit();
                } else {
                    $login_error_message = "Invalid password.";
                }
            } else {
                $login_error_message = "Username not found.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['register'])) {
        // Registration processing
        $reg_name = sanitize_input($conn, $_POST['reg_name']);
        $reg_username = sanitize_input($conn, $_POST['reg_username']);
        $reg_email = sanitize_input($conn, $_POST['reg_email']);
        $reg_password = $_POST['reg_password'];
        $reg_password_confirm = $_POST['reg_password_confirm'];
        $reg_age = filter_input(INPUT_POST, 'reg_age', FILTER_VALIDATE_INT);
        $reg_country_id = filter_input(INPUT_POST, 'reg_country_id', FILTER_VALIDATE_INT);

        // Validations
        if (empty($reg_name) || empty($reg_username) || empty($reg_email) || empty($reg_password) || empty($reg_password_confirm) || $reg_age === false || $reg_country_id === false) {
            $registration_message = "Lütfen tüm alanları doldurun.";
            $registration_message_type = 'error';
        } elseif ($reg_password !== $reg_password_confirm) {
            $registration_message = "Şifreler eşleşmiyor.";
            $registration_message_type = 'error';
        } elseif ($reg_age <= 0) {
            $registration_message = "Geçerli bir yaş girin.";
            $registration_message_type = 'error';
        } else {
            // Check if username or email already exists
            $stmt_check = $conn->prepare("SELECT user_id FROM USERS WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $reg_username, $reg_email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $registration_message = "Bu kullanıcı adı veya e-posta zaten kayıtlı.";
                $registration_message_type = 'error';
            } else {
                // Hash the password
                $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);
                $date_joined = date('Y-m-d'); // Current date

                $stmt_insert = $conn->prepare("INSERT INTO USERS (name, username, email, password, age, country_id, date_joined, last_login) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssssiss", $reg_name, $reg_username, $reg_email, $hashed_password, $reg_age, $reg_country_id, $date_joined);
                    if ($stmt_insert->execute()) {
                        $registration_message = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
                        $registration_message_type = 'success';
                    } else {
                        $registration_message = "Kayıt sırasında bir hata oluştu: " . $stmt_insert->error;
                        $registration_message_type = 'error';
                    }
                    $stmt_insert->close();
                } else {
                    $registration_message = "Veritabanı hatası (insert prepare): " . $conn->error;
                    $registration_message_type = 'error';
                }
            }
            $stmt_check->close();
        }
    }
}

// Connection is not closed here if it was opened, 
// because it might be needed for displaying random user info if POST is not set.
// It will be closed at the very end of the script.

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - Music Player</title>
    <link rel="stylesheet" href="style.css"> <!-- Changed from styles.css to style.css -->
</head>
<body>
    <div class="container login-container">
        <h1>User Login</h1>
        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" name="login">Login</button> <!-- Added name="login" -->
            <?php
            if (!empty($login_error_message)) {
                echo '<p class="error-message">' . ($login_error_message) . '</p>';
            }
            ?>
        </form>

        <hr style="margin: 30px 0;">

        <h2>Kayıt Ol</h2>
        <?php
        if (!empty($registration_message)) {
            echo '<p class="' . ($registration_message_type === 'success' ? 'success-message' : 'error-message') . '">' . ($registration_message) . '</p>';
        }
        ?>
        <form action="login.php" method="post">
            <label for="reg_name">Ad Soyad:</label>
            <input type="text" id="reg_name" name="reg_name" required value="<?php echo isset($_POST['reg_name']) ? ($_POST['reg_name']) : ''; ?>">

            <label for="reg_username">Kullanıcı Adı:</label>
            <input type="text" id="reg_username" name="reg_username" required value="<?php echo isset($_POST['reg_username']) ? ($_POST['reg_username']) : ''; ?>">

            <label for="reg_email">E-posta:</label>
            <input type="email" id="reg_email" name="reg_email" required value="<?php echo isset($_POST['reg_email']) ? ($_POST['reg_email']) : ''; ?>">

            <label for="reg_password">Şifre:</label>
            <input type="password" id="reg_password" name="reg_password" required>

            <label for="reg_password_confirm">Şifre Tekrar:</label>
            <input type="password" id="reg_password_confirm" name="reg_password_confirm" required>
            
            <label for="reg_age">Yaş:</label>
            <input type="number" id="reg_age" name="reg_age" required min="1" value="<?php echo isset($_POST['reg_age']) ? ($_POST['reg_age']) : ''; ?>">

            <label for="reg_country_id">Ülke:</label>
            <select id="reg_country_id" name="reg_country_id" required>
                <option value="">Ülke Seçin...</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo ($country['country_id']); ?>" <?php echo (isset($_POST['reg_country_id']) && $_POST['reg_country_id'] == $country['country_id'] ? 'selected' : ''); ?>>
                        <?php echo ($country['country_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="register">Kayıt Ol</button>
        </form>

    </div>
    <?php
    if ($conn) { // Close connection if it was opened and not closed before (e.g. after POST)
        $conn->close();
    }
    ?>
</body>
</html>