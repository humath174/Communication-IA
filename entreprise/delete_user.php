<?php
session_start();
require 'config.php';

// Vérifier si l'utilisateur est connecté et autorisé
if (!isset($_SESSION['user_id']) || $_SESSION['autor'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'];

// Supprimer l'utilisateur
$stmt = $conn->prepare("UPDATE users SET company_id = NULL WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression.";
}

header("Location: company.php");
exit();
?>
