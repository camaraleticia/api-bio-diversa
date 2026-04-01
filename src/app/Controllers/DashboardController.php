<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador para o dashboard de validação
 */
class DashboardController extends Controller
{
    /**
     * Exibe o dashboard de validação
     */
    public function index()
    {
        $this->render('dashboard/index', [
            'title' => 'Dashboard - Validação de Identificações'
        ]);
    }
}
