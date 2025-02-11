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

// Récupération des prompts liés à cette entreprise (avec le company_id)
$requetePrompts = $connexion->prepare("
    SELECT Prompt_Email.id, Prompt_Email.email, Prompt_Email.prompt 
    FROM Prompt_Email
    WHERE Prompt_Email.company_id = ?
");
$requetePrompts->execute([$company_id]);
$prompts = $requetePrompts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prompts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
<!-- Navigation -->
<nav class="bg-white shadow-md">
    <div class="max-w-screen-xl mx-auto p-4 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-blue-600">Mon Application</a>
        <ul class="flex space-x-6 text-gray-600">
            <li><a href="/index.php">Dashboard</a></li>
            <li><a href="/boitemail/index.php">Email</a></li>
            <li><a href="/prompt/index.php" class="text-blue-600">Prompt</a></li>
            <li><a href="/activité/index.php">Activité</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestion des Prompts</h1>

    <!-- Add New Prompt Link -->
    <div class="mb-4">
        <a href="add_prompt.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter un nouveau prompt</a>
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
    </div>
</div>
</body>

</html>
