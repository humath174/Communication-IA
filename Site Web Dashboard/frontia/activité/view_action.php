<?php
session_start(); // Démarre la session pour récupérer l'utilisateur connecté

// Connexion à la base de données MySQL
$serveur = "192.168.1.200";
$utilisateur = "grafana";
$motDePasse = "grafana";
$baseDeDonnees = "botscommunication";

try {
    $connexion = new PDO("mysql:host=$serveur;dbname=$baseDeDonnees;charset=utf8", $utilisateur, $motDePasse);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir cette page.");
}

// Vérification si l'ID de l'action est passé dans l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Action non trouvée ou non autorisée.");
}

$action_id = $_GET['id']; // Récupère l'ID de l'action depuis l'URL

// Récupération du company_id de l'utilisateur connecté
$requeteUser = $connexion->prepare("SELECT company_id FROM users WHERE id = ?");
$requeteUser->execute([$_SESSION['user_id']]);
$user = $requeteUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur non trouvé.");
}

$company_id = $user['company_id']; // ID de l'entreprise associée

// Récupération des informations de l'action
$requeteAction = $connexion->prepare("
    SELECT ar.*, e.email_address AS email_to_address
    FROM actions_reponse ar
    JOIN emails e ON ar.email_to = e.email_address
    WHERE ar.id = ? AND e.company_id = ?
");

$requeteAction->execute([$action_id, $company_id]);
$action = $requeteAction->fetch(PDO::FETCH_ASSOC);

if (!$action) {
    die("Action non trouvée ou non autorisée.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Action</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="container mx-auto px-6 py-12">
    <div class="bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-6">Détails de l'Action</h1>

        <div class="mb-4">
            <p><strong class="text-gray-700">Email Expéditeur:</strong> <span class="text-blue-600"><?php echo htmlspecialchars($action['email_from']); ?></span></p>
        </div>

        <div class="mb-4">
            <p><strong class="text-gray-700">Email Destinataire:</strong> <span class="text-blue-600"><?php echo htmlspecialchars($action['email_to_address']); ?></span></p>
        </div>

        <div class="mb-4">
            <p><strong class="text-gray-700">Objet:</strong> <span class="text-blue-600"><?php echo htmlspecialchars($action['subject']); ?></span></p>
        </div>

        <div class="mb-6">
            <p><strong class="text-gray-700">Message Original:</strong></p>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-2">
                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($action['original_message'])); ?></p>
            </div>
        </div>

        <div class="mb-6">
            <p><strong class="text-gray-700">Réponse:</strong></p>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-2">
                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($action['reply_message'])); ?></p>
            </div>
        </div>

        <div class="mb-6">
            <p><strong class="text-gray-700">Date de l'Action:</strong> <span class="text-blue-600"><?php echo htmlspecialchars($action['action_timestamp']); ?></span></p>
        </div>

        <div class="text-center">
            <a href="index.php" class="text-white bg-blue-500 hover:bg-blue-600 px-6 py-2 rounded-lg">Retour à l'historique des actions</a>
        </div>
    </div>
</div>

</body>
</html>
