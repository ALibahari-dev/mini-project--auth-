<?php 
$name = ["ali", "mmd", "reza", "medi"];
$num  = ["0901", "0917", "0938", "0937", "008"];
$result = [];
foreach ($name as $index => $value) {
    if (isset($num[$index])) {
        $result[] = [
            'name'  => $value,
            'phone' => $num[$index]
        ];
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>