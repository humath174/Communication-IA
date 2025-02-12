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

// Récupération des e-mails liés à cette entreprise
$requeteEmails = $connexion->prepare("
    SELECT emails.id, emails.email_address, 
           MAX(users.full_name) AS full_name, 
           IF(email_accounts.id IS NULL, 0, 1) AS has_imap_config
    FROM emails 
    JOIN users ON emails.company_id = users.company_id
    LEFT JOIN email_accounts ON emails.email_address = email_accounts.email_address
    WHERE emails.company_id = ?
    GROUP BY emails.email_address
");
$requeteEmails->execute([$company_id]);


$donnees = $requeteEmails->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des E-mails</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Icônes Font Awesome pour les indicateurs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0HXKBBMW06"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-0HXKBBMW06');
    </script>

</head>
<body class="bg-gray-100">

<!-- Navigation -->
<nav class="bg-white shadow-md">
    <div class="max-w-screen-xl mx-auto p-4 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-blue-600">Mon Application</a>
        <ul class="flex space-x-6 text-gray-600">
            <li><a href="/index.php" >Dashboard</a></li>
            <li><a href="/boitemail/index.php" class="text-blue-600">Email</a></li>
            <li><a href="/prompt/index.php">Prompt</a></li>
            <li><a href="/gestionenvoie/afficheetenvoie.php" >Envoie Mail</a></li>
            <li><a href="/activité/index.php">Activité</a></li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestion des E-mails de votre entreprise</h1>

    <!-- Add New Email Button -->
    <div class="mb-4">
        <a href="add_email.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter un nouvel e-mail</a>
    </div>

    <!-- Table of Emails -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full text-sm text-left text-gray-700">
            <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Utilisateur</th>
                <th class="px-4 py-2">Configuration IMAP</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($donnees as $email): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2"><?php echo htmlspecialchars($email['email_address']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($email['full_name']); ?></td>
                    <td class="px-4 py-2">
                        <?php if ($email['has_imap_config']): ?>
                            <span class="text-green-500">
                                <i class="fas fa-check-circle"></i> Configuré
                            </span>
                        <?php else: ?>
                            <span class="text-red-500">
                                <i class="fas fa-exclamation-circle"></i> Non configuré
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="edit_email.php?id=<?php echo $email['id']; ?>" class="text-blue-600 hover:underline">Éditer</a> |
                        <a href="delete_email.php?id=<?php echo $email['id']; ?>" class="text-red-600 hover:underline">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>