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

// Récupération des actions (réponses aux e-mails)
$requeteActions = $connexion->prepare("
    SELECT ar.id, ar.email_to, ar.email_from, ar.subject, ar.action_timestamp, e.email_address 
    FROM actions_reponse ar
    JOIN emails e ON ar.email_id = e.id
    WHERE e.company_id = ?
    ORDER BY ar.action_timestamp DESC
");
$requeteActions->execute([$company_id]);
$actions = $requeteActions->fetchAll(PDO::FETCH_ASSOC);

// Récupération des détails d'une action spécifique si un ID est passé en paramètre
$actionDetails = null;
if (isset($_GET['action_id'])) {
    $actionId = htmlspecialchars($_GET['action_id']);
    $requeteDetails = $connexion->prepare("
        SELECT ar.email_to, ar.email_from, ar.subject, ar.original_message, ar.reply_message, ar.action_timestamp 
        FROM actions_reponse ar
        JOIN emails e ON ar.email_id = e.id
        WHERE ar.id = ? AND e.company_id = ?
    ");
    $requeteDetails->execute([$actionId, $company_id]);
    $actionDetails = $requeteDetails->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actions Réponse</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .details {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<h1>Actions Réponse</h1>

<?php if (!empty($actions)): ?>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>À</th>
            <th>De</th>
            <th>Sujet</th>
            <th>Date et Heure</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($actions as $action): ?>
            <tr onclick="window.location.href='actions_reponse.php?action_id=<?= $action['id'] ?>'">
                <td><?= htmlspecialchars($action['id']) ?></td>
                <td><?= htmlspecialchars($action['email_to']) ?></td>
                <td><?= htmlspecialchars($action['email_from']) ?></td>
                <td><?= htmlspecialchars($action['subject']) ?></td>
                <td><?= htmlspecialchars($action['action_timestamp']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucune action trouvée.</p>
<?php endif; ?>

<?php if ($actionDetails): ?>
    <div class="details">
        <h2>Détails de l'Action</h2>
        <p><strong>À :</strong> <?= htmlspecialchars($actionDetails['email_to']) ?></p>
        <p><strong>De :</strong> <?= htmlspecialchars($actionDetails['email_from']) ?></p>
        <p><strong>Sujet :</strong> <?= htmlspecialchars($actionDetails['subject']) ?></p>
        <p><strong>Date et Heure :</strong> <?= htmlspecialchars($actionDetails['action_timestamp']) ?></p>
        <p><strong>Message Original :</strong></p>
        <pre><?= htmlspecialchars($actionDetails['original_message']) ?></pre>
        <p><strong>Réponse :</strong></p>
        <pre><?= htmlspecialchars($actionDetails['reply_message']) ?></pre>
    </div>
<?php endif; ?>

</body>
</html>