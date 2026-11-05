
<?php

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, full_name, email, password, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        
if ($_SESSION["role"] === "admin") {
    header("Location: admin/dashboard.php");
} elseif ($_SESSION["role"] === "collector") {
    header("Location: collector/dashboard.php");
} else {
    header("Location: dashboard.php");
}
exit;

    } else {

        $error = "Incorrect email or password. Please try again.";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | E-Waste Portal</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header class="navbar">

    <a href="index.php" class="logo">
        ♻ E-Waste Portal
    </a>

    <div class="nav-buttons">

        <span class="register-question">
            New to our portal?
        </span>

        <a href="register.php" class="register-btn">
            Create Account
        </a>

    </div>

</header>


<main class="login-page">

    <section class="login-shell">

        <!-- Left Image Section -->

        <div class="login-visual">

           <img src="assests/images/login-ewaste.png" alt="E-waste recycling">

            <div class="login-overlay"></div>

            <div class="login-visual-content">

                <span class="login-tag">
                    ♻ RECYCLE RESPONSIBLY
                </span>

                <h1>
                    Welcome Back!
                    <span>Let's make a difference together.</span>
                </h1>

                <p>
                    Your journey towards responsible
                    electronic waste management continues here.
                    Login to manage your pickups and track
                    your recycling requests.
                </p>

                <div class="login-points">
                    <p>✓ Easy pickup requests</p>
                    <p>✓ Track your request status</p>
                    <p>✓ Support responsible recycling</p>
                </div>

            </div>

        </div>


        <!-- Right Login Form -->

        <div class="login-form-section">

            <div class="login-form-box">

                <div class="login-icon">♻</div>

                <span class="form-welcome">
                    WELCOME BACK
                </span>

                <h2>Login to Your Account</h2>

                <p class="login-description">
                    Enter your registered details to continue.
                </p>


                <?php if (isset($_GET["registered"])): ?>

                    <div class="success">
                        Account created successfully!
                        Please login.
                    </div>

                <?php endif; ?>


                <?php if ($error !== ""): ?>

                    <div class="error">
                        <?php echo e($error); ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        autocomplete="email"
                        required
                        value="<?php echo e($_POST['email'] ?? ''); ?>"
                    >


                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <div class="login-options">

                        <label class="remember-option">

                            <input
                                type="checkbox"
                                id="showPassword"
                            >

                            Show Password

                        </label>

                    </div>


                    <button type="submit" class="login-submit">

                        Login to Continue →

                    </button>

                </form>


                <div class="login-divider">
                    <span>OR</span>
                </div>


                <p class="signup-text">

                    Don't have an account?

                    <a href="register.php">
                        Register Now
                    </a>

                </p>


                <a href="index.php" class="back-home">
                    ← Back to Homepage
                </a>

            </div>

        </div>

    </section>

</main>


<script>

const passwordInput = document.getElementById("password");

const showPassword = document.getElementById("showPassword");

showPassword.addEventListener("change", function () {

    passwordInput.type = this.checked ? "text" : "password";

});

</script>

</body>
</html>
