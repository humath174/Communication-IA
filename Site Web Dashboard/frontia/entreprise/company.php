<?php
session_start();
require 'config.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les infos de l'utilisateur
$stmt = $conn->prepare("SELECT company_id, autor FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Vérifier si l'utilisateur est autorisé
if ($user['autor'] != 1) {
    header("Location: index.php");
    exit();
}

$company_id = $user['company_id'];

// Récupérer les infos de l'entreprise
$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Mettre à jour les informations de l'entreprise
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_company'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    $update = $conn->prepare("UPDATE companies SET name = ?, description = ? WHERE id = ?");
    $update->bind_param("ssi", $name, $description, $company_id);

    if ($update->execute()) {
        $_SESSION['success'] = "Entreprise mise à jour avec succès.";
        header("Location: company.php");
        exit();
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour.";
    }
}

// Ajouter un membre à l'entreprise
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $new_email = trim($_POST['email']);

    // Vérifier si l'utilisateur existe
    $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_user->bind_param("s", $new_email);
    $check_user->execute();
    $user_result = $check_user->get_result();

    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_id_to_update = $user_data['id'];

        // Mettre à jour le company_id de l'utilisateur
        $update_user = $conn->prepare("UPDATE users SET company_id = ? WHERE id = ?");
        $update_user->bind_param("ii", $company_id, $user_id_to_update);

        if ($update_user->execute()) {
            $_SESSION['success'] = "Utilisateur ajouté avec succès.";
            header("Location: company.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout.";
        }
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
    }
}

// Supprimer un membre
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_user'])) {
    $user_id_to_delete = $_GET['delete_user'];

    // Vérifier si l'utilisateur appartient à l'entreprise
    $check_user = $conn->prepare("SELECT company_id FROM users WHERE id = ?");
    $check_user->bind_param("i", $user_id_to_delete);
    $check_user->execute();
    $user_result = $check_user->get_result();
    $user_data = $user_result->fetch_assoc();

    if ($user_data['company_id'] == $company_id) {
        $delete_user = $conn->prepare("UPDATE users SET company_id = NULL WHERE id = ?");
        $delete_user->bind_param("i", $user_id_to_delete);

        if ($delete_user->execute()) {
            $_SESSION['success'] = "Utilisateur supprimé avec succès.";
            header("Location: company.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression.";
        }
    } else {
        $_SESSION['error'] = "Cet utilisateur n'appartient pas à votre entreprise.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de l'entreprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>


<?php

include "navbar.php";

?>

<div class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white shadow-lg rounded-lg p-8 max-w-6xl w-full">
    <h2 class="text-2xl font-semibold text-gray-800 text-center mb-6">Gestion de l'entreprise</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 p-2 text-green-700 bg-green-100 border border-green-300 rounded">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 p-2 text-red-700 bg-red-100 border border-red-300 rounded">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire de mise à jour de l'entreprise -->
    <form action="company.php" method="POST" class="space-y-4 mb-6">
        <div>
            <label for="name" class="block text-gray-700 font-medium">Nom de l’entreprise</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" required
                   class="w-full p-2 border border-gray-300 rounded focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label for="description" class="block text-gray-700 font-medium">Description</label>
            <textarea id="description" name="description" required
                      class="w-full p-2 border border-gray-300 rounded focus:ring focus:ring-blue-300"><?php echo htmlspecialchars($company['description']); ?></textarea>
        </div>

        <button type="submit" name="update_company" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
            Mettre à jour
        </button>
    </form>

    <!-- Liste des membres de l'entreprise -->
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Liste des membres de l'entreprise</h3>
    <table class="min-w-full table-auto mb-4">
        <thead class="bg-gray-200">
        <tr>
            <th class="px-6 py-3 text-left">Nom</th>
            <th class="px-6 py-3 text-left">Email</th>
            <th class="px-6 py-3 text-left">Rôle</th>
            <th class="px-6 py-3 text-left">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // Récupérer les utilisateurs de l'entreprise
        $stmt = $conn->prepare("SELECT * FROM users WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($user = $result->fetch_assoc()) {
            echo "<tr class='border-b'>";
            echo "<td class='px-6 py-3'>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td class='px-6 py-3'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td class='px-6 py-3'>" . htmlspecialchars($user['autor'] == 1 ? 'Administrateur' : 'Utilisateur') . "</td>";
            echo "<td class='px-6 py-3'>
                            <a href='edit_user.php?id=" . $user['id'] . "' class='text-blue-600 hover:underline'>Modifier</a> | 
                            <a href='company.php?delete_user=" . $user['id'] . "' class='text-red-600 hover:underline' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce membre ?\")'>Supprimer</a>
                          </td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>

    <!-- Formulaire d'ajout d'un membre -->
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Ajouter un membre</h3>
    <form action="company.php" method="POST" class="space-y-4">
        <div>
            <label for="email" class="block text-gray-700 font-medium">Email de l'utilisateur</label>
            <input type="email" id="email" name="email" required
                   class="w-full p-2 border border-gray-300 rounded focus:ring focus:ring-blue-300">
        </div>

        <button type="submit" name="add_user" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
            Ajouter à l'entreprise
        </button>
    </form>

</div>
</div>
</body>
</html>
