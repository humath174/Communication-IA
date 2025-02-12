<?php
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

// Vérification si un ID est passé en paramètre
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Récupération des données actuelles
    $requete = $connexion->prepare("SELECT * FROM Prompt_Email WHERE id = ?");
    $requete->execute([$id]);
    $prompt = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$prompt) {
        die("Aucune donnée trouvée pour cet ID.");
    }
} else {
    die("ID invalide.");
}

// Mise à jour après soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $promptText = $_POST['prompt'];

    $update = $connexion->prepare("UPDATE Prompt_Email SET email = ?, prompt = ? WHERE id = ?");
    $update->execute([$email, $promptText, $id]);

    // Redirection vers la page principale
    header("Location: index.php?message=modification_reussie");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Prompt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php

include "Component/navbar.php";

?>
<h2>Modifier un Prompt</h2>
<form method="POST">
    <label>Email :</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($prompt['email']); ?>" required>

    <label>Prompt :</label>
    <textarea name="prompt" required><?php echo htmlspecialchars($prompt['prompt']); ?></textarea>

    <button type="submit">Enregistrer les modifications</button>
</form>
<br>
<a href="index.php">Retour</a>
</body>
</html>
