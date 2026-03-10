<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require "includes/db_connection.php";
$user_id = $_SESSION['user_id'];


$where = "WHERE a.user_id = '$user_id'";

if (!empty($_GET['type'])) {
    $type = $_GET['type'];
    $where .= " AND a.result = '$type'";
}

if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    $where .= " AND DATE(a.created_at) = '$date'";
}


$query = "
SELECT 
    a.id, 
    a.result, 
    a.risk_level, 
    a.suspicious_keywords,
    a.created_at,
    u.filename,
    u.file_path
FROM analysis_history a
LEFT JOIN uploads u ON a.upload_id = u.id
$where
ORDER BY a.created_at DESC
";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Call Analysis History | Voice Shield</title>

    <style>
        body {
            background: #0D1117;
            color: #fff;
            font-family: "Tajawal", sans-serif;
            padding: 40px;
        }

        .container {
            width: 95%;
            margin: auto;
        }

        .title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .back-btn {
            background: #2563eb;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            font-size: 15px;
        }

        th {
            background: #161B22;
        }

        tr {
            background: #11151A;
            border-bottom: 1px solid #1F242B;
        }

        tr:hover {
            background: #1A1F27;
        }

        .btn {
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: #fff;
            margin-left: 4px;
            font-size: 14px;
        }

        .pdf-btn  { background: #FF8C00; }

        .risk-high { color: #ff4d4d; font-weight: 700; }
        .risk-mid  { color: #ffa31a; font-weight: 700; }
        .risk-low  { color: #1DB954; font-weight: 700; }

        .filters {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
        }

        select, input[type=date] {
            padding: 8px 12px;
            border-radius: 6px;
            background: #161B22;
            border: 1px solid #30363d;
            color: #fff;
            font-size: 14px;
        }

        .filter-btn {
            background: #238636;
            padding: 8px 14px;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }

    </style>
</head>

<body>
<div class="container">

    <div class="title">📁 Call Analysis History</div>

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

    <form method="GET" class="filters">
        <select name="type">
            <option value="">All Results</option>
            <option value="High Risk">High Risk</option>
            <option value="Suspicious">Suspicious</option>
            <option value="Low Risk">Low Risk</option>
            <option value="Safe">Safe</option>
        </select>

        <input type="date" name="date">

        <button class="filter-btn">Apply</button>
    </form>

    <table>
        <tr>
            <th>#</th>
            <th>File Name</th>
            <th>Result</th>
            <th>Risk %</th>
            <th>Keywords</th>
            <th>Date</th>
            <th>PDF</th>
        </tr>

        <?php
        $count = 1;
        while ($row = mysqli_fetch_assoc($result)) {

            if ($row['risk_level'] >= 75) {
                $riskClass = "risk-high";
            } elseif ($row['risk_level'] >= 40) {
                $riskClass = "risk-mid";
            } else {
                $riskClass = "risk-low";
            }

            $keywords = implode(", ", json_decode($row['suspicious_keywords'], true) ?? []);

            echo "
            <tr>
                <td>$count</td>
                <td>{$row['filename']}</td>
                <td>{$row['result']}</td>
                <td class='$riskClass'>{$row['risk_level']}%</td>
                <td>$keywords</td>
                <td>{$row['created_at']}</td>

                <td>
                    <a href='generate_pdf.php?id={$row['id']}' class='btn pdf-btn'>PDF</a>
                </td>
            </tr>
            ";

            $count++;
        }
        ?>

    </table>
</div>
</body>
</html>
