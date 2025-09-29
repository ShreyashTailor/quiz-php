<?php
$host='localhost';
$user='root';
$pass='';
$db='test';

$conn= new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    die('Connection Failed : '.$conn->connect_error);
}
echo 'Connected Successfully'.'<br>';
/*
$sql='CREATE TABLE IF NOT EXISTS students(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    marks INT NOT NULL
)';
if($conn->query($sql)===TRUE){
    echo "\nTable students created successfully".'<br>';
}
else{
    echo "\nError creating table: ".$conn->error;
}

$sqlinsert="INSERT INTO students (name,email,marks) VALUES
('Shreyash','shreyash@gmail.com',85),
('Jay','jay@gmail.com',90),
('Yash','yash@gmail.com',95)";
if($conn->query($sqlinsert)===TRUE){
    echo "\nRecords inserted successfully".'<br>';
}
else{
    echo "\nError inserting records: ".$conn->error;
}*/
//display records in table format
$sqlselect="SELECT * FROM students";
$result=$conn->query($sqlselect);
if($result->num_rows>0){
    echo "\nData in students table:\n";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Marks</th></tr>";
    while($row=$result->fetch_assoc()){
        echo "<tr><td>".$row['id']."</td><td>".$row['name']."</td><td>".$row['email']."</td><td>".$row['marks']."</td></tr>";
    }
    echo "</table>";
}
else{
    echo "\nNo records found";
}

?>