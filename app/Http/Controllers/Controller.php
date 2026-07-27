<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    protected function created($data = null, string $message = 'Créé avec succès')
    {
        return $this->ok($data, $message, 201);
    }

    protected function error(string $message = 'Erreur', int $code = 400, $errors = null)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}
