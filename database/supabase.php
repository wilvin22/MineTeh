<?php
// Supabase Configuration
class SupabaseClient {
    private $supabaseUrl;
    private $supabaseKey;
    private $headers;
    private $lastError;

    public function __construct() {
        // TODO: Replace with your actual Supabase credentials
        $this->supabaseUrl = 'https://didpavzminvohszuuowu.supabase.co'; // e.g., https://xxxxx.supabase.co
        $this->supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRpZHBhdnptaW52b2hzenV1b3d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzIwMTYwNDgsImV4cCI6MjA4NzU5MjA0OH0.iueZB9z5Z5YvKM98Gsy-ll--kLipCKXtmT0V7jHBA0Y';
        
        $this->headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    // SELECT query
    public function select($table, $columns = '*', $filters = [], $single = false) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?select=' . $columns;
        
        foreach ($filters as $key => $value) {
            $url .= '&' . $key . '=eq.' . urlencode($value);
        }
        
        if ($single) {
            $url .= '&limit=1';
        }
        
        $response = $this->makeRequest('GET', $url);
        
        if ($single && !empty($response)) {
            return $response[0];
        }
        
        return $response;
    }

    // INSERT query
    public function insert($table, $data) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table;
        $response = $this->makeRequest('POST', $url, $data);
        return $response;
    }

    // UPDATE query
    public function update($table, $data, $filters = []) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?';
        
        $filterParts = [];
        foreach ($filters as $key => $value) {
            $filterParts[] = $key . '=eq.' . urlencode($value);
        }
        $url .= implode('&', $filterParts);
        
        $response = $this->makeRequest('PATCH', $url, $data);
        return $response;
    }

    // DELETE query
    public function delete($table, $filters = []) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?';
        
        $filterParts = [];
        foreach ($filters as $key => $value) {
            $filterParts[] = $key . '=eq.' . urlencode($value);
        }
        $url .= implode('&', $filterParts);
        
        $response = $this->makeRequest('DELETE', $url);
        return $response;
    }

    // Custom query with filters
    public function query($table, $columns = '*', $queryParams = []) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?select=' . $columns;
        
        foreach ($queryParams as $key => $value) {
            $url .= '&' . $key . '=' . urlencode($value);
        }
        
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    // Execute raw query with custom filters
    public function customQuery($table, $columns = '*', $customFilter = '') {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?select=' . $columns;
        
        if (!empty($customFilter)) {
            $url .= '&' . $customFilter;
        }
        
        $response = $this->makeRequest('GET', $url);
        return $response;
    }

    // Make HTTP request
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            // Log detailed error information
            error_log("Supabase Error (HTTP $httpCode): " . $response);
            error_log("URL: " . $url);
            error_log("Method: " . $method);
            if ($data) {
                error_log("Data: " . json_encode($data));
            }
            
            // Also store last error for debugging
            $this->lastError = [
                'http_code' => $httpCode,
                'response' => $response,
                'url' => $url,
                'method' => $method
            ];
            
            return false;
        }
    }
    
    // Get last error
    public function getLastError() {
        return $this->lastError ?? null;
    }

    // Count rows
    public function count($table, $filters = []) {
        $url = $this->supabaseUrl . '/rest/v1/' . $table . '?select=count';
        
        foreach ($filters as $key => $value) {
            $url .= '&' . $key . '=eq.' . urlencode($value);
        }
        
        $headers = array_merge($this->headers, ['Prefer: count=exact']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        
        curl_close($ch);
        
        if (preg_match('/content-range: \d+-\d+\/(\d+)/i', $header, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
}

// Create global instance
$supabase = new SupabaseClient();
?>
