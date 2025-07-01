<?php
// 5. RESTAURAR src/utils/Response.php (VERSÃO ORIGINAL)

class Response
{
    public static function success($data = [], $statusCode = 200, $headers = [])
    {
        return self::json([
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode, $headers);
    }
    
    public static function error($errors = [], $statusCode = 400, $headers = [])
    {
        return self::json([
            'success' => false,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode, $headers);
    }
    
    public static function json($data, $statusCode = 200, $headers = [])
    {
        http_response_code($statusCode);
        
        header('Content-Type: application/json; charset=utf-8');
        
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $data;
    }
    
    public static function xml($data, $statusCode = 200, $headers = [])
    {
        http_response_code($statusCode);
        
        header('Content-Type: application/xml; charset=utf-8');
        
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        $xml = new SimpleXMLElement('<response/>');
        self::arrayToXml($data, $xml);
        
        echo $xml->asXML();
        
        return $data;
    }
    
    public static function csv($data, $filename = 'export.csv', $headers = [])
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    public static function download($filePath, $filename = null, $mimeType = 'application/octet-stream')
    {
        if (!file_exists($filePath)) {
            return self::error(['message' => 'File not found'], 404);
        }
        
        $filename = $filename ?: basename($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
    
    public static function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
    
    public static function view($template, $data = [], $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        
        extract($data);
        
        ob_start();
        include $template;
        $content = ob_get_clean();
        
        echo $content;
        return $content;
    }
    
    public static function paginated($data, $total, $page, $limit, $statusCode = 200)
    {
        $pagination = [
            'current_page' => (int) $page,
            'per_page' => (int) $limit,
            'total' => (int) $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ];
        
        return self::json([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    public static function cached($data, $cacheTime = 3600, $statusCode = 200)
    {
        header('Cache-Control: public, max-age=' . $cacheTime);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        
        return self::json($data, $statusCode);
    }
    
    public static function noCache($data, $statusCode = 200)
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        return self::json($data, $statusCode);
    }
    
    private static function arrayToXml($data, $xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    public static function cors($allowedOrigins = ['*'], $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'], $allowedHeaders = ['Content-Type', 'Authorization'])
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}