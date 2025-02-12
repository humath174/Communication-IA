<?php
session_start();
require 'config.php'; // Connexion à la base de données

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir cette page.");
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$nom = $_SESSION['full_name'];

// Récupérer les derniers emails reçus
$queryEmails = "SELECT * FROM emails ORDER BY created_at DESC LIMIT 5";
$emails = $conn->query($queryEmails);

// Récupérer les dernières actions sur les emails
$queryActions = "SELECT * FROM actions_reponse ORDER BY action_timestamp DESC LIMIT 5";
$actions = $conn->query($queryActions);

// Récupérer les logs de connexion
$queryLogs = "SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 5";
$logs = $conn->query($queryLogs);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Dashboard</title>
</head>
<body class="bg-gray-100">

<?php

include "navbar.php";

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Bienvenue, <?php echo htmlspecialchars($nom); ?>!</h1>
    <p class="text-lg text-gray-700">Vous êtes connecté avec l'email : <?php echo htmlspecialchars($email); ?></p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Derniers emails -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Derniers Emails</h2>
            <ul>
                <?php while ($email = $emails->fetch_assoc()): ?>
                    <li class="border-b py-2">
                        <strong><?php echo htmlspecialchars($email['email_address']); ?></strong> - <?php echo htmlspecialchars($email['created_at']); ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Actions récentes -->
        <div class="bg-white p-4 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Actions Récentes</h2>
            <ul>
                <?php while ($action = $actions->fetch_assoc()): ?>
                    <li class="border-b py-2">
                        <strong><?php echo htmlspecialchars($action['email_to']); ?></strong> - <?php echo htmlspecialchars($action['action_timestamp']); ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Logs de connexion -->
    <div class="bg-white p-4 rounded-lg shadow-md mt-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-3">Dernières Connexions</h2>
        <ul>
            <?php while ($log = $logs->fetch_assoc()): ?>
                <li class="border-b py-2">
                    <strong><?php echo htmlspecialchars($log['email']); ?></strong> - <?php echo htmlspecialchars($log['login_time']); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>
</body>
</html>