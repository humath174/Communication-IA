<?php
session_start(); // Démarre la session pour garder les informations

$host = "192.168.1.200";
$username = "grafana";
$password = "grafana";
$dbname = "botscommunication";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour récupérer l'adresse IP externe
function getClientIp() {
    $ip = '';

    // Vérifie l'en-tête HTTP_X_FORWARDED_FOR (utilisé par les proxies)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    // Vérifie l'en-tête HTTP_CLIENT_IP
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Utilise REMOTE_ADDR comme dernier recours
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Si plusieurs adresses IP sont présentes (cas des proxies), on prend la première
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }

    return trim($ip);
}

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Requête pour vérifier l'email et le mot de passe de l'utilisateur
    $stmt = $conn->prepare("SELECT u.id, u.email, u.full_name, u.company_id, c.name AS company_name, c.address AS company_address
                            FROM users u
                            JOIN companies c ON u.company_id = c.id
                            WHERE u.email = :email AND u.password = :password");
    $stmt->execute(['email' => $email, 'password' => md5($password)]); // Utiliser md5 pour la simplicité, en production utilisez un hashage plus sécurisé
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si l'utilisateur est trouvé
    if ($user) {
        // Sauvegarde les informations dans la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['company_name'] = $user['company_name'];
        $_SESSION['company_address'] = $user['company_address'];

        // Récupérer l'adresse IP externe
        $ip_address = getClientIp();

        // Récupérer le User-Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Enregistrer les informations de connexion dans la table login_logs
        $stmt = $conn->prepare("
            INSERT INTO login_logs (user_id, email, ip_address, user_agent)
            VALUES (:user_id, :email, :ip_address, :user_agent)
        ");
        $stmt->execute([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ]);

        // Redirige vers le dashboard
        header("Location: index.php");
        exit();
    } else {
        // Message d'erreur si l'email ou mot de passe est incorrect
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Page de connexion</h1>

<?php if (isset($error_message)): ?>
    <p style="color:red;"><?php echo $error_message; ?></p>
<?php endif; ?>

<form method="POST" action="login.php">
    <label for="email">Email :</label>
    <input type="email" name="email" id="email" required>
    <br>
    <label for="password">Mot de passe :</label>
    <input type="password" name="password" id="password" required>
    <br>
    <button type="submit">Se connecter</button>
</form>
</body>
</html>