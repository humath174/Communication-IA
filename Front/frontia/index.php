<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir cette page.");
}

// Récupérer les informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$nom = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Accueil</title>

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

<!-- Navbar -->
<nav class="bg-white shadow-md">
    <div class="max-w-screen-xl mx-auto p-4 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-blue-600">Mon Application</a>
        <ul class="flex space-x-6 text-gray-600">
            <li><a href="/index.php" class="text-blue-600">Dashboard</a></li>
            <li><a href="/boitemail/index.php">Email</a></li>
            <li><a href="/prompt/index.php">Prompt</a></li>
            <li><a href="/gestionenvoie/afficheetenvoie.php" >Envoie Mail</a></li>
            <li><a href="/activité/index.php">Activité</a></li>
        </ul>
    </div>
</nav>


<!-- Main Content -->
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Bienvenue, <?php echo htmlspecialchars($nom); ?>!</h1>
    <p class="text-lg text-gray-700">Vous êtes connecté avec l'email : <?php echo htmlspecialchars($email); ?></p>

    <!-- Card for additional content or actions -->
    <div class="mt-6 bg-white p-4 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-800 mb-3">Votre Tableau de Bord</h2>
        <p class="text-gray-600">Ici, vous pouvez gérer toutes vos activités et informations liées à votre compte.</p>
    </div>
</div>

</body>
</html>
