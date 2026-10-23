<?php
require_once __DIR__ . '/../security.php';

initSecureSession();
setSecurityHeaders();

requireAdmin('../login.php');

$db = db();

$quiz_id = sanitizeInt($_GET['id'] ?? 0, 1);

// Only allow POST requests for deletion
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_quizzes.php");
    exit;
}

if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    header("Location: manage_quizzes.php?error=invalid_token");
    exit;
}

try {
    $db->beginTransaction();
    
    // Delete questions first (foreign key constraint)
    $db->executeNonQuery(
        "DELETE FROM questions WHERE quiz_id = ?",
        [$quiz_id],
        'i'
    );
    
    // Delete leaderboard entries
    $db->executeNonQuery(
        "DELETE FROM leaderboard WHERE quiz_id = ?",
        [$quiz_id],
        'i'
    );
    
    // Delete quiz
    $result = $db->executeNonQuery(
        "DELETE FROM quizzes WHERE id = ?",
        [$quiz_id],
        'i'
    );
    
    if ($result > 0) {
        $db->commit();
        logSecurityEvent('quiz_deleted', "Quiz ID: $quiz_id, Admin: " . getCurrentUsername());
        header("Location: manage_quizzes.php?success=deleted");
    } else {
        $db->rollback();
        header("Location: manage_quizzes.php?error=not_found");
    }
} catch (Exception $e) {
    $db->rollback();
    logSecurityEvent('quiz_delete_error', "Error: " . $e->getMessage(), 'error');
    header("Location: manage_quizzes.php?error=server_error");
}
exit;