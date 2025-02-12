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

// Vérification de l'ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Vérification si l'enregistrement existe
    $requete = $connexion->prepare("SELECT id FROM Prompt_Email WHERE id = ?");
    $requete->execute([$id]);
    $existe = $requete->fetch();

    if ($existe) {
        // Suppression de l'enregistrement
        $delete = $connexion->prepare("DELETE FROM Prompt_Email WHERE id = ?");
        $delete->execute([$id]);

        // Redirection après suppression
        header("Location: index.php?message=suppression_reussie");
        exit;
    } else {
        die("L'enregistrement n'existe pas.");
    }
} else {
    die("ID invalide.");
}
?>
