--TEST--
Test simple insert and update sql_variants using parameters of some different data categorys
--DESCRIPTION--
ORDER BY should work with sql_variants
--FILE--
﻿<?php

include 'pdo_tools.inc';

class Food
{
    function getFood()
    {
        return $this->food;
    }
    function getcategory()
    {
        return $this->category;
    }    
}

function CreateTable($conn, $tableName)
{
    try 
    {
        $stmt = $conn->exec("CREATE TABLE $tableName ([id] sql_variant, [food] sql_variant, [category] sql_variant)"); 
    }
    catch (Exception $e)
    {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }        
}

function InsertData($conn, $tableName, $id, $food, $category)
{
    try 
    {
        $query = "INSERT $tableName ([id], [food], [category]) VALUES (:id, :food, :category)";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':food', $food);
        $stmt->bindValue(':category', $category);
        
        $result = $stmt->execute();  
        if ($result)
            echo "\nAdded $food in $category with ID $id.";  
    }
    catch (Exception $e)
    {
        echo "Failed to insert food $food\n";
        echo $e->getMessage();
    }        
}

function UpdateID($conn, $tableName, $id, $food, $category)
{
    $query = "UPDATE $tableName SET id = ? WHERE food = ? AND category = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute(array($id, $food, $category));
    
    if ($result)
        echo "\nFood $food now updated with new id $id.";
    else
        echo "Failed to update ID.\n";        
}

function UpdateFood($conn, $tableName, $id, $food, $category)
{
    $query = "UPDATE $tableName SET food = ? WHERE id = ? AND category = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute(array($food, $id, $category));
    
    if ($result)
        echo "\nCategory $category now updated with $food.";
    else
        echo "Failed to update food.\n";     
}

function FetchRows($conn, $tableName)
{
    $query = "SELECT * FROM $tableName ORDER BY id";   
  
    $stmt = $conn->query($query);  

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'Food');
    while ($food = $stmt->fetch())
    {   
        echo "\nID: " . $food->id . " "; 
        echo $food->getFood() . ", ";
        echo $food->getcategory();
    }
    
    $stmt = null;  
}


function RunTest()
{
    StartTest("pdo_simple_update_variants");
    try
    {
        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        $tableName = GetTempTableName();   
        CreateTable($conn, $tableName);
        
        // Add three kinds of foods
        InsertData($conn, $tableName, 1, 'Milk', 'Diary Products');
        InsertData($conn, $tableName, 3, 'Chicken', 'Meat');
        InsertData($conn, $tableName, 5, 'Blueberry', 'Fruits');
        
        FetchRows($conn, $tableName);
        
        UpdateID($conn, $tableName, 4, 'Milk', 'Diary Products');

        FetchRows($conn, $tableName);
        
        UpdateFood($conn, $tableName, 4, 'Cheese', 'Diary Products');

        FetchRows($conn, $tableName);

        // Add three kinds of foods
        InsertData($conn, $tableName, 6, 'Salmon', 'Fish');
        InsertData($conn, $tableName, 2, 'Broccoli', 'Vegetables');
        
        FetchRows($conn, $tableName);
        
        $conn = null;
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_simple_update_variants");
}

RunTest();

?>
--EXPECT--
...Starting 'pdo_simple_update_variants' test...

Added Milk in Diary Products with ID 1.
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
Done
...Test 'pdo_simple_update_variants' completed successfully.
