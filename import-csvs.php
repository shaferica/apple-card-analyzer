<?php
// Define the directory containing the CSV files
$directory = './csvs';

$host = 'localhost';
$port = '5432';
$dbname = 'mike';
$user = 'mike';
$password = '';
$table = 'apple_card';

/*

CREATE TABLE apple_card 
 ( hash CHAR(32), txn_date DATE, clearing_date DATE, description VARCHAR(255), 
   merchant VARCHAR(255), category VARCHAR(100), amount real, primary key(hash)
 );

*/

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
$pdo = new PDO($dsn, $user, $password, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);

// Open the directory
if (is_dir($directory)) {

    if ($dh = opendir($directory)) {

        // Loop through all files in the directory
        while (($file = readdir($dh)) !== false) {

            // Check if the file ends with .csv
            if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                //echo "Processing file: $file\n";

                // Open the CSV file
                $filePath = $directory . '/' . $file;
                if (($handle = fopen($filePath, 'r')) !== false) {
                    // Read the header row
                    $header = fgetcsv($handle, 0, ',', '"', '\\');


                    // Loop through each row in the CSV
                    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                   // while ( ($row = fgetcsv($handle)) !== false) {

                        // Combine the header with the row to create an associative array
                        $data = array_combine($header, $row);

                        // TODO: Add your logic here to process each row
                        $txdate    = $data["Transaction Date"];
                        $cleardate = $data["Clearing Date"];
                        $desc      = $data["Description"];
                        $merchant  = $data["Merchant"];
                        $category  = $data["Category"];
                        $type      = $data["Type"];
                        $amount    = (double)$data["Amount (USD)"];


                        // I only care about Purchases
                        if ($type == "Purchase") {

                            // create the hash
                            $md5 = md5($txdate . $cleardate . $desc . $merchant . $category . $type . $amount);

                           // print "INSERT: md5=$md5, txdate: $txdate, cleardate: $cleardate, category: $category, amount: $amount, desc: $desc\n";

                            // Create a JSON object for this row
                            $rowObject = [
                                "txn-date"         => $txdate,
                                "clearing-date"    => $cleardate,
                                "description"      => $desc,
                                "merchant"         => $merchant,
                                "category"         => $category,
                                "amount"           => $amount,
                                "hash"             => $md5
                            ];

                            // format the dates
                            $dateObj = DateTime::createFromFormat('m/d/Y', $txdate);
                            $txdate_formatted = $dateObj ? $dateObj->format('Y-m-d') : null;

                            $dateObj = DateTime::createFromFormat('m/d/Y', $cleardate);
                            $cleardate_formatted = $dateObj ? $dateObj->format('Y-m-d') : null;

                            // do the insert
                            $sql = "INSERT INTO apple_card (hash, txn_date, clearing_date, description, merchant, category, amount) 
                                    VALUES (:hash, :txn_date, :clearing_date, :description, :merchant, :category, :amount) ON CONFLICT (hash) DO NOTHING";

                            $stmt = $pdo->prepare($sql);

                            $stmt->execute([
                                ':hash' => $md5,
                                ':txn_date' => $txdate_formatted,          
                                ':clearing_date' => $cleardate_formatted, 
                                ':description' => $desc,
                                ':merchant' => $merchant,
                                ':category' => $category,
                                ':amount' => $amount
                            ]);
                            
                        }
                    }

                    // Close the file
                    fclose($handle);
                } else {
                    echo "Error opening file: $filePath\n";
                }
            }
        }

        // Close the directory
        closedir($dh);
    } else {
        echo "Error opening directory: $directory\n";
    }
} else {
    echo "$directory is not a valid directory.\n";
}
?>
