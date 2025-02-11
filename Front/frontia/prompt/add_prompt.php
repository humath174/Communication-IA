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

// Récupération des adresses e-mail de l'entreprise
$requeteEmails = $connexion->prepare("SELECT email_address FROM emails WHERE company_id = ?");
$requeteEmails->execute([$company_id]);
$emails = $requeteEmails->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $email = htmlspecialchars($_POST['email']);
    $prompt = htmlspecialchars($_POST['prompt']);

    // Vérification que l'email appartient à l'entreprise de l'utilisateur
    $requeteEmail = $connexion->prepare("SELECT email_address FROM emails WHERE company_id = ? AND email_address = ?");
    $requeteEmail->execute([$company_id, $email]);
    $emailExiste = $requeteEmail->fetch(PDO::FETCH_ASSOC);

    if (!$emailExiste) {
        die("L'email n'est pas associé à votre entreprise.");
    }

    // Insertion du nouveau prompt
    $requeteInsert = $connexion->prepare("INSERT INTO Prompt_Email (email, prompt, company_id) VALUES (?, ?, ?)");
    $requeteInsert->execute([$email, $prompt, $company_id]);

    echo "Le prompt a été ajouté avec succès.";
    header("Location: index.php"); // Redirection vers la page principale
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Prompt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Ajouter un Nouveau Prompt</h1>

<form action="add_prompt.php" method="POST">
    <div>
        <label for="email">Email :</label>
        <select id="email" name="email" required>
            <option value="">Sélectionnez une adresse e-mail</option>
            <?php
            // Affichage des adresses e-mail disponibles
            foreach ($emails as $email) {
                echo "<option value='{$email['email_address']}'>{$email['email_address']}</option>";
            }
            ?>
        </select>
    </div>
    <div>
        <label for="prompt">Prompt :</label>
        <textarea id="prompt" name="prompt" required></textarea>
    </div>
    <button type="submit">Ajouter le Prompt</button>
</form>

<a href="index.php">Retour à la gestion des prompts</a>

</body>
</html>