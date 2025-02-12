<?php
session_start();

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

// Récupération des prompts liés à cette entreprise
$requetePrompts = $connexion->prepare("
    SELECT id, email, prompt 
    FROM Prompt_Email 
    WHERE company_id = ?
");
$requetePrompts->execute([$company_id]);
$prompts = $requetePrompts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des E-mails et des Prompts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Icônes Font Awesome pour les indicateurs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<!-- Navigation -->
<?php include "navbar.php"; ?>

<!-- Main Content -->
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestion des E-mails et des Prompts de votre entreprise</h1>

    <!-- Add New Email Button -->
    <div class="mb-4">
        <a href="add_email.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter un nouvel e-mail</a>
    </div>

    <!-- Table of Emails -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg mb-8">
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

    <!-- Add New Prompt Button -->
    <div class="mb-4">
        <a href="add_prompt.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Ajouter un nouveau prompt</a>
    </div>

    <!-- Table of Prompts -->
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full text-sm text-left text-gray-700">
            <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Prompt</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($prompts as $prompt): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2"><?php echo htmlspecialchars($prompt['email']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($prompt['prompt']); ?></td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="edit_prompt.php?id=<?php echo $prompt['id']; ?>" class="text-blue-600 hover:underline">Éditer</a> |
                        <a href="delete_prompt.php?id=<?php echo $prompt['id']; ?>" class="text-red-600 hover:underline">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($prompts)): ?>
            <p class="text-gray-600 text-center p-4">Aucun prompt trouvé pour cette entreprise.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
