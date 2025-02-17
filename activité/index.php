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

// Récupération du company_id de l'utilisateur connecté
$requeteUser = $connexion->prepare("SELECT company_id FROM users WHERE id = ?");
$requeteUser->execute([$_SESSION['user_id']]);
$user = $requeteUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur non trouvé.");
}

$company_id = $user['company_id']; // ID de l'entreprise associée

// Récupérer toutes les adresses emails associées à l'entreprise
$requeteEmails = $connexion->prepare("SELECT email_address FROM emails WHERE company_id = ?");
$requeteEmails->execute([$company_id]);
$emails = $requeteEmails->fetchAll(PDO::FETCH_ASSOC);

// Extraire toutes les adresses email
$emailAddresses = array_map(function($email) {
    return $email['email_address'];
}, $emails);

// Vérification si on a des emails associés à l'entreprise
if (count($emailAddresses) == 0) {
    die("Aucune adresse email associée à votre entreprise.");
}

// Récupération des actions où le champ email_to correspond aux adresses emails de l'entreprise
$placeholders = implode(',', array_fill(0, count($emailAddresses), '?'));
$query = "
    SELECT ar.id, ar.email_from, ar.email_to, ar.subject, ar.action_timestamp
    FROM actions_reponse ar
    WHERE ar.email_to IN ($placeholders)
    ORDER BY ar.action_timestamp DESC
";

$requeteActions = $connexion->prepare($query);
$requeteActions->execute($emailAddresses);
$actions = $requeteActions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Actions</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0HXKBBMW06"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-0HXKBBMW06');
    </script>

</head>
<body class="bg-gray-100 font-sans antialiased">
<?php

include "navbar.php";

?>

<div class="container mx-auto px-6 py-12">
    <div class="bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-6">Historique des Actions</h1>

        <?php if (count($actions) == 0): ?>
            <p class="text-center text-gray-600">Aucune action trouvée pour votre entreprise.</p>
        <?php else: ?>
            <table class="min-w-full table-auto">
                <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-4 py-2 text-sm font-medium text-gray-700">Email Expéditeur</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-700">Email Destinataire</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-700">Objet</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-700">Date</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-700">Détails</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($actions as $action): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-800"><?php echo htmlspecialchars($action['email_from']); ?></td>
                        <td class="px-4 py-2 text-sm text-gray-800"><?php echo htmlspecialchars($action['email_to']); ?></td>
                        <td class="px-4 py-2 text-sm text-gray-800"><?php echo htmlspecialchars($action['subject']); ?></td>
                        <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($action['action_timestamp']); ?></td>
                        <td class="px-4 py-2 text-sm text-blue-600"><a href="view_action.php?id=<?php echo $action['id']; ?>" class="hover:underline">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="index.php" class="text-white bg-blue-500 hover:bg-blue-600 px-6 py-2 rounded-lg">Retour à la page principale</a>
        </div>
    </div>
</div>

</body>
</html>
