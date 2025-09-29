<?php
include 'db.php';
$quiz_id = intval($_GET['quiz_id']);

$quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$quiz_id"));
$questions_result = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id");

$questions = [];
while($q = mysqli_fetch_assoc($questions_result)){
    $questions[] = $q;
}

echo json_encode([
    "quiz" => $quiz,
    "questions" => $questions
]);
?>
