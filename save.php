<?php 
session_start();

try {
    $username = "root";
    $password = "";

    $conn = new PDO(
        "mysql:host=localhost;dbname=detect",
        $username,
        $password
    );

    if(isset($_POST["name"]) && isset($_POST["email"]) && isset($_POST["picture"])) {
        $name = $_POST["name"];
        $email = $_POST["email"];
        $picture = $_POST["picture"];

        $check = $conn->prepare(
            "SELECT * FROM users WHERE email=?"
        );

        $check->execute([$email]);

        if ($check->rowCount() == 0) {
            // INSERT ONLY IF NEW USER
            $sql = "INSERT INTO users(name,email,picture) VALUES(?,?,?)";
            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $name,
                $email,
                $picture
            ]);
        }

        $_SESSION["users"] = [
            "name" => $name,
            "email" => $email,
            "picture" => $picture
        ];

        echo json_encode([
            "status" => "success"
        ]);
    }

} catch(PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>