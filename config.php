<?php


    try {
        $bdd = new PDO ('mysql:host=localhost;dbname=abc', 'root', "");
} catch (\Throwable $th) {
        try {
        $bdd = new PDO ('mysql:host=sql103.infinityfree.com;dbname=if0_40188599_abc', 'if0_40188599', "2GhroE0047U2o");
} catch (\Throwable $th) {
    throw $th;
}
}
