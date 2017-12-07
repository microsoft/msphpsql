--TEST--
Test simple insert and update sql_variants using parameters of some different data categorys
--DESCRIPTION--
ORDER BY should work with sql_variants
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
﻿<?php
require_once("MsCommon_mid-refactor.inc");

class Food
{
    public function getFood()
    {
        return $this->food;
    }
    public function getcategory()
    {
        return $this->category;
    }
}

function createVariantTable($conn, $tableName)
{
    try {
        createTable($conn, $tableName, array("id" => "sql_variant", "food" => "sql_variant", "category" => "sql_variant"));
    } catch (Exception $e) {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }
}

function insertData($conn, $tableName, $id, $food, $category)
{
    try {
        $query = "INSERT $tableName ([id], [food], [category]) VALUES (:id, :food, :category)";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':food', $food);
        $stmt->bindValue(':category', $category);

        $result = $stmt->execute();
        if ($result) {
            echo "Added $food in $category with ID $id.\n";
        }
    } catch (Exception $e) {
        echo "Failed to insert food $food\n";
        echo $e->getMessage();
    }
}

function updateID($conn, $tableName, $id, $food, $category)
{
    $query = "UPDATE $tableName SET id = ? WHERE food = ? AND category = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute(array($id, $food, $category));

    if ($result) {
        echo "Food $food now updated with new id $id.\n";
    } else {
        echo "Failed to update ID.\n";
    }
}

function updateFood($conn, $tableName, $id, $food, $category)
{
    $query = "UPDATE $tableName SET food = ? WHERE id = ? AND category = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute(array($food, $id, $category));

    if ($result) {
        echo "Category $category now updated with $food.\n";
    } else {
        echo "Failed to update food.\n";
    }
}

function fetchRows($conn, $tableName)
{
    $query = "SELECT * FROM $tableName ORDER BY id";
    $stmt = $conn->query($query);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'Food');
    while ($food = $stmt->fetch()) {
        echo "ID: " . $food->id . " "; 
        echo $food->getFood() . ", ";
        echo $food->getcategory() . "\n";
    }
    unset($stmt);
}



try {
    // Connect
    $conn = connect();

    $tableName = getTableName();
    createVariantTable($conn, $tableName);

    // Add three kinds of foods
    insertData($conn, $tableName, 1, 'Milk', 'Diary Products');
    insertData($conn, $tableName, 3, 'Chicken', 'Meat');
    insertData($conn, $tableName, 5, 'Blueberry', 'Fruits');

    fetchRows($conn, $tableName);

    updateID($conn, $tableName, 4, 'Milk', 'Diary Products');

    fetchRows($conn, $tableName);

    updateFood($conn, $tableName, 4, 'Cheese', 'Diary Products');

    fetchRows($conn, $tableName);

    // Add three kinds of foods
    insertData($conn, $tableName, 6, 'Salmon', 'Fish');
    insertData($conn, $tableName, 2, 'Broccoli', 'Vegetables');

    fetchRows($conn, $tableName);

    dropTable($conn, $tableName);
    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
﻿Added Milk in Diary Products with ID 1.
Added Chicken in Meat with ID 3.
Added Blueberry in Fruits with ID 5.
ID: 1 Milk, Diary Products
ID: 3 Chicken, Meat
ID: 5 Blueberry, Fruits
Food Milk now updated with new id 4.
ID: 3 Chicken, Meat
ID: 4 Milk, Diary Products
ID: 5 Blueberry, Fruits
Category Diary Products now updated with Cheese.
ID: 3 Chicken, Meat
ID: 4 Cheese, Diary Products
ID: 5 Blueberry, Fruits
Added Salmon in Fish with ID 6.
Added Broccoli in Vegetables with ID 2.
ID: 2 Broccoli, Vegetables
ID: 3 Chicken, Meat
ID: 4 Cheese, Diary Products
ID: 5 Blueberry, Fruits
ID: 6 Salmon, Fish
