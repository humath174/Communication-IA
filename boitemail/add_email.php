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

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $email = htmlspecialchars($_POST['email']);

    // Vérification que l'email n'existe pas déjà pour cette entreprise
    $requeteEmail = $connexion->prepare("SELECT email_address FROM emails WHERE company_id = ? AND email_address = ?");
    $requeteEmail->execute([$company_id, $email]);
    $emailExiste = $requeteEmail->fetch(PDO::FETCH_ASSOC);

    if ($emailExiste) {
        die("L'email est déjà associé à votre entreprise.");
    }

    // Insertion de la nouvelle adresse email
    $requeteInsert = $connexion->prepare("INSERT INTO emails (email_address, company_id, created_at) VALUES (?, ?, NOW())");
    $requeteInsert->execute([$email, $company_id]);

    echo "L'adresse email a été ajoutée avec succès.";
    header("Location: index.php"); // Redirection vers la page principale
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Adresse Email</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Ajouter une Nouvelle Adresse Email</h1>

<form action="add_email.php" method="POST">
    <div>
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>
    </div>
    <button type="submit">Ajouter l'Email</button>
</form>

<a href="index.php">Retour à la gestion des emails</a>

</body>
</html>