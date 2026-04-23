<?php

/**
 * Configuração centralizada do projeto IOT
 * Este arquivo gerencia todas as URLs e configurações dinâmicas
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuração de Ambiente
    |--------------------------------------------------------------------------
    | Detecta automaticamente o ambiente baseado no host atual
    */
    
    'environment' => function() {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        
        if (str_contains($host, '181.215.135.118')) {
            return 'production';
        } elseif (str_contains($host, '10.102.0.115')) {
            return 'development';
        } else {
            return 'local';
        }
    },
    
    /*
    |--------------------------------------------------------------------------
    | URLs por Ambiente
    |--------------------------------------------------------------------------
    */
    
    'urls' => [
        'production' => [
            'base' => 'http://181.215.135.118',
            'api_port' => 8000,
            'web_port' => 8001,
            'app_port' => 8002,
        ],
        'development' => [
            'base' => 'http://10.102.0.115',
            'api_port' => 8000,
            'web_port' => 8001,
            'app_port' => 8002,
        ],
        'local' => [
            'base' => 'http://127.0.0.1',
            'api_port' => 8000,
            'web_port' => 8001,
            'app_port' => 8002,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */
    
    'helpers' => [
        
        /**
         * Obter URL base do ambiente atual
         */
        'get_base_url' => function() {
            $config = include __FILE__;
            $env = $config['environment']();
            return $config['urls'][$env]['base'];
        },
        
        /**
         * Obter URL da API
         */
        'get_api_url' => function() {
            $config = include __FILE__;
            $env = $config['environment']();
            $urls = $config['urls'][$env];
            return $urls['base'] . ':' . $urls['api_port'] . '/api';
        },
        
        /**
         * Obter URL completa de um serviço
         */
        'get_service_url' => function($service) {
            $config = include __FILE__;
            $env = $config['environment']();
            $urls = $config['urls'][$env];
            
            $ports = [
                'api' => $urls['api_port'],
                'web' => $urls['web_port'],
                'app' => $urls['app_port'],
            ];
            
            return $urls['base'] . ':' . $ports[$service];
        },
        
    ],
    
]; 