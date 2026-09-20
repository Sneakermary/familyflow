<?php
include __DIR__ .'/../src/classes/Database.php';

function test_database_connection() {
    $db_test = new Database();
    echo "Connection works!";

}
test_database_connection();

?>