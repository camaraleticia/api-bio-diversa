<?php
/**
 * Arquivo de definição de rotas
 */

// Rotas da API
$router->post('/api/identify', ['ApiController', 'identify']);
$router->get('/api/species', ['ApiController', 'listSpecies']);
$router->get('/api/identifications', ['ApiController', 'listIdentifications']);
$router->get('/api/identifications/pending', ['ApiController', 'listPendingIdentifications']);
$router->post('/api/identifications/validate', ['ApiController', 'validateIdentification']);

// Rotas da interface web
$router->get('/', ['HomeController', 'index']);
$router->get('/dashboard', ['DashboardController', 'index']);
