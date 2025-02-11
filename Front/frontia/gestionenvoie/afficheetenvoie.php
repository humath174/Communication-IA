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

// Récupération du company_id de l'utilisateur
$requeteUser = $connexion->prepare("SELECT company_id FROM users WHERE id = ?");
$requeteUser->execute([$_SESSION['user_id']]);
$user = $requeteUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur non trouvé.");
}

$company_id = $user['company_id']; // ID de l'entreprise associée

// Récupération des réponses non envoyées
$requeteReponses = $connexion->prepare("
    SELECT id, email_to, email_from, subject, original_message, reply_message, action_timestamp 
    FROM responses 
    WHERE sent = 0 AND company_id = ?
    ORDER BY id DESC
");
$requeteReponses->execute([$company_id]);
$reponses = $requeteReponses->fetchAll(PDO::FETCH_ASSOC);

// Récupération des détails d'une réponse spécifique si un ID est passé en paramètre
$reponseDetails = null;
if (isset($_GET['reponse_id'])) {
    $reponseId = htmlspecialchars($_GET['reponse_id']);
    $requeteDetails = $connexion->prepare("
        SELECT email_to, email_from, subject, original_message, reply_message, action_timestamp 
        FROM responses 
        WHERE id = ? AND company_id = ?
    ");
    $requeteDetails->execute([$reponseId, $company_id]);
    $reponseDetails = $requeteDetails->fetch(PDO::FETCH_ASSOC);
}

// Traitement de l'envoi de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $reponseId = $_POST['reponse_id'];
    $replyMessage = $_POST['reply_message'];

    // Mettre à jour la réponse et marquer comme envoyée
    $requeteUpdate = $connexion->prepare("
        UPDATE responses 
        SET reply_message = ?, sent = 1 
        WHERE id = ?
    ");
    $requeteUpdate->execute([$replyMessage, $reponseId]);

    // Envoyer l'email
    $requeteEmail = $connexion->prepare("
        SELECT email_from, subject, reply_message 
        FROM responses 
        WHERE id = ?
    ");
    $requeteEmail->execute([$reponseId]);
    $emailData = $requeteEmail->fetch(PDO::FETCH_ASSOC);

    if ($emailData) {
        $to = $emailData['email_from'];
        $subject = "Re: " . $emailData['subject'];
        $message = $emailData['reply_message'];
        $headers = "From: ai@digitalweb-dynamics.com";

        if (mail($to, $subject, $message, $headers)) {
            echo "<script>alert('Email envoyé avec succès.');</script>";
        } else {
            echo "<script>alert('Erreur lors de l\'envoi de l\'email.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponses Suggérée</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans bg-gray-50">
<nav class="bg-white shadow-md">
    <div class="max-w-screen-xl mx-auto p-4 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-blue-600">Mon Application</a>
        <ul class="flex space-x-6 text-gray-600">
            <li><a href="/index.php">Dashboard</a></li>
            <li><a href="/boitemail/index.php">Email</a></li>
            <li><a href="/prompt/index.php">Prompt</a></li>
            <li><a href="/activité/index.php" class="text-blue-600">Activité</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
    </div>
</nav>

<div class="flex h-screen">
    <!-- Colonne latérale pour la liste des mails -->
    <div class="w-1/4 bg-gray-100 p-4 overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Liste des Mails</h2>
        <?php if (!empty($reponses)): ?>
            <ul>
                <?php foreach ($reponses as $reponse): ?>
                    <li class="mb-2">
                        <a href="?reponse_id=<?= $reponse['id'] ?>" class="block p-2 bg-white rounded-lg shadow hover:bg-gray-50">
                            <p class="font-semibold"><?= htmlspecialchars($reponse['subject']) ?></p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($reponse['email_from']) ?></p>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-600">Aucun mail à afficher.</p>
        <?php endif; ?>
    </div>

    <!-- Colonne principale pour les détails -->
    <div class="flex-1 p-8 overflow-y-auto">
        <?php if ($reponseDetails): ?>
            <h2 class="text-2xl font-bold mb-4">Détails de la Réponse</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p><strong>À :</strong> <?= htmlspecialchars($reponseDetails['email_to']) ?></p>
                <p><strong>De :</strong> <?= htmlspecialchars($reponseDetails['email_from']) ?></p>
                <p><strong>Sujet :</strong> <?= htmlspecialchars($reponseDetails['subject']) ?></p>
                <p><strong>Date et Heure :</strong> <?= htmlspecialchars($reponseDetails['action_timestamp']) ?></p>
                <p><strong>Message Original :</strong></p>
                <pre class="bg-gray-100 p-3 rounded-lg"><?= htmlspecialchars($reponseDetails['original_message']) ?></pre>
                <p><strong>Réponse Générée :</strong></p>
                <form method="POST" action="">
                    <textarea name="reply_message" class="w-full p-3 border border-gray-300 rounded-lg mb-4"><?= htmlspecialchars($reponseDetails['reply_message']) ?></textarea>
                    <input type="hidden" name="reponse_id" value="<?= $reponseDetails['id'] ?>">
                    <button type="submit" name="envoyer" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">Envoyer la Réponse</button>
                </form>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Sélectionnez un mail pour voir les détails.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>