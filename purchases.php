<?php

$host = 'localhost';
$port = '5432';
$dbname = 'mike';
$user = 'mike';
$password = '';
$table = 'apple_card';

// connect to the database
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
$pdo = new PDO($dsn, $user, $password, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);
$sql = "SELECT * FROM apple_card ORDER BY txn_date DESC";


$total_spend = 0;
$num_transactions = 0;

$stmt = $pdo->query($sql);

// Iterate through results
foreach ($stmt as $row) {
    $hash = $row["hash"];
    $txdate = $row["txn_date"];
    $clearing_date = $row["clearing_date"];
    $description = $row["description"];
    $merchant = $row["merchant"];
    $category = $row["category"];
    $amount = (double)$row["amount"];


    $total_spend += $amount;
    $num_transactions++;

    $rowObject  = [
        "txn-date"         => $txdate,
        "clearing-date"    => $clearing_date,
        "description"      => $description,
        "merchant"         => $merchant,
        "category"         => $category,
        "amount"           => $amount,
        "hash"             => $hash
    ];

    // Add the JSON object to the array
    $allRows[] = $rowObject;
}

$total_spend = floor($total_spend * 100) / 100;

$output = [
    "total-spend" => $total_spend,
    "total-transactions" => $num_transactions,
    "transactions" => $allRows // The array containing all transactions
];


// output the JSON
$jsonOutput = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

header('Content-Type: application/json');
// Output the JSON
echo $jsonOutput;
?>
