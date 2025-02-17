<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour accéder à cette page.");
}

// Connexion à la base de données
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

// Récupérer l'ID de l'e-mail à modifier depuis l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID d'e-mail invalide.");
}
$email_id = $_GET['id'];

// Récupérer les informations actuelles de l'e-mail
$requete = $connexion->prepare("
    SELECT * FROM email_accounts
    WHERE id = :id
");
$requete->execute(['id' => $email_id]);
$email = $requete->fetch(PDO::FETCH_ASSOC);

if (!$email) {
    die("E-mail non trouvé.");
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $email_address = $_POST['email_address'];
    $company_id = $_POST['company_id'];
    $imap_server = $_POST['imap_server'];
    $password = $_POST['password'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Mettre à jour les informations dans la base de données
    try {
        $requete = $connexion->prepare("
            UPDATE email_accounts
            SET email_address = :email_address,
                company_id = :company_id,
                imap_server = :imap_server,
                password = :password,
                is_active = :is_active
            WHERE id = :id
        ");
        $requete->execute([
            'email_address' => $email_address,
            'company_id' => $company_id,
            'imap_server' => $imap_server,
            'password' => $password,
            'is_active' => $is_active,
            'id' => $email_id
        ]);

        // Rediriger vers une page de confirmation ou afficher un message
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        die("Erreur lors de la mise à jour de l'e-mail : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'e-mail</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<?php

include "navbar.php";

?>
<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold text-center mb-8">Modifier l'e-mail</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Les informations de l'e-mail ont été mises à jour avec succès.
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white shadow-md rounded-lg p-6">
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="email_address">Adresse e-mail :</label>
            <input type="email" name="email_address" id="email_address" value="<?= htmlspecialchars($email['email_address']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="company_id">ID de l'entreprise :</label>
            <input type="number" name="company_id" id="company_id" value="<?= htmlspecialchars($email['company_id']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="imap_server">Serveur IMAP :</label>
            <input type="text" name="imap_server" id="imap_server" value="<?= htmlspecialchars($email['imap_server']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" value="<?= htmlspecialchars($email['password']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= $email['is_active'] ? 'checked' : '' ?>>
                Actif
            </label>
        </div>

        <div class="text-center">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Mettre à jour
            </button>
        </div>
    </form>
</div>

</body>
</html>