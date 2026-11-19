<?php

session_start();

function exigirLogin($tipo)
{
    if (($_SESSION['tipo'] ?? '') !== $tipo)
    {
        header("Location: ../login.html");
        exit();
    }
}