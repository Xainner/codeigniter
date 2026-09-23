<?php
/** Import the baseline only into an explicitly named disposable CI database. */
declare(strict_types=1);

$name = (string) getenv('DB_NAME');
if ($name === '' || !str_starts_with($name, 'ci_test_')) {
    fwrite(STDERR, "Refusing to import outside a ci_test_ database\n");
    exit(1);
}
$conn = new mysqli((string) getenv('DB_HOST'), (string) getenv('DB_USER'),
    (string) getenv('DB_PASS'), $name, (int) (getenv('DB_PORT') ?: 3306));
$sql = file_get_contents(__DIR__ . '/../database/database.sql');
if ($sql === false || !$conn->multi_query($sql)) {
    throw new RuntimeException($conn->error);
}
do {
    $result = $conn->store_result();
    if ($result !== false) { $result->free(); }
} while ($conn->more_results() && $conn->next_result());
if ($conn->errno) { throw new RuntimeException($conn->error); }
echo "Baseline imported\n";
