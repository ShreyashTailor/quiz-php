<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data['user_id']);
$quiz_id = intval($data['quiz_id']);
$answers = $data['answers'];

$score = 0;
foreach($answers as $qid => $ans){
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT correct_option FROM questions WHERE id=$qid"));
    if($q['correct_option'] == $ans) $score++;
}

mysqli_query($conn, "INSERT INTO attempts (user_id, quiz_id, score) VALUES ($user_id, $quiz_id, $score)");
echo json_encode(["status"=>"success","score"=>$score]);
?>
