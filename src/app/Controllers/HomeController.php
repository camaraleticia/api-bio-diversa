<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador para a página inicial
 */
class HomeController extends Controller
{
    /**
     * Exibe a página inicial
     */
    public function index()
    {
        $this->render('home/index', [
            'title' => 'Carbonífera Biodiversa - Identificação de Espécies'
        ]);
    }
}
