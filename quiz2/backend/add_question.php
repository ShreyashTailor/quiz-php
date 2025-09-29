<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);

$quiz_id = intval($data['quiz_id']);
$question = mysqli_real_escape_string($conn, $data['question']);
$options = $data['options'];
$correct = $data['correct'];

mysqli_query($conn, "INSERT INTO questions 
(quiz_id, question, option_a, option_b, option_c, option_d, correct_option) 
VALUES ($quiz_id,'{$question}','{$options[0]}','{$options[1]}','{$options[2]}','{$options[3]}','$correct')");

echo json_encode(["status" => "success"]);
?>
