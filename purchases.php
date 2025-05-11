<?php
// Define the directory containing the CSV files
$directory = '/var/www/html/digitalcleavage.com/dashboard/csvs';

$allRows = [];

$total_spend = 0;
$num_transactions = 0;
$earliestdate = null;
$latestdate = null;

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
                    $header = fgetcsv($handle);

                    $monthly_spend = 0;

                    // Loop through each row in the CSV
                    while (($row = fgetcsv($handle)) !== false) {
                        // Combine the header with the row to create an associative array
                        $data = array_combine($header, $row);

                        // Boilerplate code to do something with each row
                        // For example: print the data
                        //print_r($data);

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

                            $md5 = md5($txdate . $cleardate . $desc . $merchant . $category . $type . $amount);
                            //print "tx: $txdate, clear: $cleardate, desc: $desc, merch: $merchant, cat: $category, type: $type, amount: $amount, md5: $md5\n";

                            $monthly_spend += $amount;
                            $total_spend += $amount;
                            $num_transactions++;

                            // Convert the date string to a timestamp for comparison
                            $txTimestamp = strtotime($txdate);

                            // Update the earliest date
                            if ($earliestdate === null || $txTimestamp < strtotime($earliestdate)) {
                                $earliestdate = $txdate;
                            }

                            // Update the latest date
                            if ($latestdate === null || $txTimestamp > strtotime($latestdate)) {
                                $latestdate = $txdate;
                            }

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

                            // Add the JSON object to the array
                            $allRows[] = $rowObject;
                        }
                    }

                    //print "Spend for the month: $monthly_spend\n";

                    // Close the file
                    fclose($handle);
                } else {
                    echo "Error opening file: $filePath\n";
                }
            }
        }

        // Close the directory
        closedir($dh);

        $total_spend = floor($total_spend * 100) / 100;

        $output = [
            "total-spend" => $total_spend,
            "total-transactions" => $num_transactions,
            "earliest-date" => $earliestdate,
            "latest-date" => $latestdate,
            "transactions" => $allRows // The array containing all transactions
        ];


        // output the JSON
        $jsonOutput = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json');
        // Output the JSON
        echo $jsonOutput;
    } else {
        echo "Error opening directory: $directory\n";
    }
} else {
    echo "$directory is not a valid directory.\n";
}
?>
