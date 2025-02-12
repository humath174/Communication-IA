<?php
$host = "192.168.1.200"; // Adresse du serveur MySQL
$user = "grafana"; // Nom d'utilisateur MySQL
$password = "grafana"; // Mot de passe MySQL
$dbname = "botscommunication"; // Remplace par le nom de ta base de données

// Création de la connexion
$conn = new mysqli($host, $user, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Définir l'encodage des caractères
$conn->set_charset("utf8mb4");
?>
