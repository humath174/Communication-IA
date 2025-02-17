<?php
session_start(); // Démarre la session pour récupérer l'utilisateur connecté

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$company_id = $user['company_id'];

// Récupération des emails en attente de réponse
$requeteReponses = $connexion->prepare("
    SELECT id, email_to, email_from, subject, original_message, reply_message, action_timestamp 
    FROM validation_response 
    WHERE sent = 0 AND company_id = :company_id
    ORDER BY id DESC
");
$requeteReponses->execute(['company_id' => $company_id]);
$reponses = $requeteReponses->fetchAll(PDO::FETCH_ASSOC);

// Récupération des détails d'une réponse spécifique si un ID est passé en paramètre
$reponseDetails = null;
if (isset($_GET['reponse_id'])) {
    $reponseId = htmlspecialchars($_GET['reponse_id']);
    $requeteDetails = $connexion->prepare("
        SELECT id, email_to, email_from, subject, original_message, reply_message, action_timestamp 
        FROM validation_response 
        WHERE id = :reponse_id AND company_id = :company_id
    ");
    $requeteDetails->execute(['reponse_id' => $reponseId, 'company_id' => $company_id]);
    $reponseDetails = $requeteDetails->fetch(PDO::FETCH_ASSOC);
}

// Traitement de l'envoi de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $reponseId = $_POST['reponse_id'];
    $replyMessage = $_POST['reply_message'];

    // Vérifier si l'ID de la réponse existe
    $checkExistence = $connexion->prepare("SELECT id FROM validation_response WHERE id = :reponse_id AND company_id = :company_id");
    $checkExistence->execute(['reponse_id' => $reponseId, 'company_id' => $company_id]);

    if ($checkExistence->rowCount() === 0) {
        die("Erreur : L'ID de la réponse n'existe pas.");
    }

    // Mettre à jour la réponse et marquer comme envoyée
    $requeteUpdate = $connexion->prepare("
        UPDATE validation_response 
        SET reply_message = :reply_message, sent = 1
        WHERE id = :reponse_id AND company_id = :company_id
    ");
    $requeteUpdate->execute(['reply_message' => $replyMessage, 'reponse_id' => $reponseId, 'company_id' => $company_id]);

    // Envoyer l'email
    $requeteEmail = $connexion->prepare("
        SELECT email_from, subject, reply_message 
        FROM validation_response 
        WHERE id = :reponse_id AND company_id = :company_id
    ");
    $requeteEmail->execute(['reponse_id' => $reponseId, 'company_id' => $company_id]);
    $emailData = $requeteEmail->fetch(PDO::FETCH_ASSOC);

    if ($emailData) {
        $to = $emailData['email_from'];
        $subject = "Re: " . $emailData['subject'];
        $message = $emailData['reply_message'];
        $headers = "From: ai@digitalweb-dynamics.com";

        mail($to, $subject, $message, $headers);
    }

    // Redirection pour éviter le rechargement du formulaire
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponses Suggérées</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="img.png" type="image/x-icon">
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-0HXKBBMW06');
    </script>
</head>
<body class="font-sans bg-gray-50">
<?php

include "navbar.php";

?>

<div class="flex h-screen">
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
                <form method="POST">
                    <textarea name="reply_message" class="w-full p-3 border border-gray-300 rounded-lg mb-4"><?= htmlspecialchars($reponseDetails['reply_message']) ?></textarea>
                    <input type="hidden" name="reponse_id" value="<?= $reponseDetails['id'] ?>">
                    <button type="submit" name="envoyer" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">Envoyer</button>
                </form>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Sélectionnez un mail.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
