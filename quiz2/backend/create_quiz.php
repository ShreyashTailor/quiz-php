<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);

$title = mysqli_real_escape_string($conn, $data['title']);
$desc = mysqli_real_escape_string($conn, $data['desc']);
$user_id = intval($data['user_id']);

mysqli_query($conn, "INSERT INTO quizzes (title, description, created_by) VALUES ('$title','$desc','$user_id')");
$quiz_id = mysqli_insert_id($conn);

echo json_encode(["status" => "success", "quiz_id" => $quiz_id]);
?>
