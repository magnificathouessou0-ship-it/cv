<?php


    try {
        $bdd = new PDO ('mysql:host=localhost;dbname=abc', 'root', "");
} catch (\Throwable $th) {
        try {
        $bdd = new PDO ('mysql:host=sql205.infinityfree.com;dbname=if0_39638769_abc', 'if0_39638769', "39BzLkh2NDmYFXY");
} catch (\Throwable $th) {
    throw $th;
}
}
