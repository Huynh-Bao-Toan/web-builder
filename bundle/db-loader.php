<?php
/**
 * Database Loader for Builder Bundles
 * Loads minified assets from database (TEXT/CLOB fields)
 * Supports hybrid approach: DB for preview, CDN for production
 */

class BuilderBundleLoader {
    private $db;
    private $bundleName;
    private $mode; // 'preview' or 'production'
    private $cdnBaseUrl;

    public function __construct($dbConnection, $bundleName = 'landing-page', $mode = 'preview', $cdnBaseUrl = '') {
        $this->db = $dbConnection;
        $this->bundleName = $bundleName;
        $this->mode = $mode;
        $this->cdnBaseUrl = $cdnBaseUrl;
    }

    /**
     * Load bundle from database by version
     * Returns array with css, html, js content
     */
    public function loadBundle($version = null, $status = 'published') {
        try {
            $query = "SELECT 
                        css_content, 
                        html_content, 
                        js_content,
                        css_hash,
                        html_hash,
                        js_hash,
                        css_size,
                        html_size,
                        js_size,
                        cdn_css_url,
                        cdn_html_url,
                        cdn_js_url,
                        manifest,
                        version
                      FROM builder_bundles 
                      WHERE name = ?";
            
            $params = [$this->bundleName];
            
            if ($version) {
                $query .= " AND version = ?";
                $params[] = $version;
            } else {
                $query .= " AND status = ? ORDER BY published_at DESC LIMIT 1";
                $params[] = $status;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Bundle not found: {$this->bundleName}" . ($version ? " version {$version}" : ""));
            }

            // Production mode: prefer CDN URLs if available
            if ($this->mode === 'production' && !empty($result['cdn_css_url'])) {
                return [
                    'css' => $result['cdn_css_url'],
                    'html' => $result['cdn_html_url'],
                    'js' => $result['cdn_js_url'],
                    'mode' => 'cdn',
                    'version' => $result['version'],
                    'manifest' => json_decode($result['manifest'], true)
                ];
            }

            // Preview mode or no CDN: return content directly
            return [
                'css' => $result['css_content'],
                'html' => $result['html_content'],
                'js' => $result['js_content'],
                'mode' => 'db',
                'version' => $result['version'],
                'hashes' => [
                    'css' => $result['css_hash'],
                    'html' => $result['html_hash'],
                    'js' => $result['js_hash']
                ],
                'sizes' => [
                    'css' => $result['css_size'],
                    'html' => $result['html_size'],
                    'js' => $result['js_size']
                ],
                'manifest' => json_decode($result['manifest'], true)
            ];
        } catch (Exception $e) {
            error_log("BundleLoader Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert bundle into database from bundle.json
     */
    public function saveBundleFromJson($jsonPath, $version, $status = 'draft') {
        try {
            $bundleData = json_decode(file_get_contents($jsonPath), true);
            
            if (!$bundleData || !isset($bundleData['assets'])) {
                throw new Exception("Invalid bundle.json format");
            }

            $manifest = json_encode($bundleData['manifest']);

            $query = "INSERT INTO builder_bundles (
                        version, name, css_content, html_content, js_content,
                        manifest, css_hash, html_hash, js_hash,
                        css_size, html_size, js_size, status
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                        css_content = VALUES(css_content),
                        html_content = VALUES(html_content),
                        js_content = VALUES(js_content),
                        manifest = VALUES(manifest),
                        css_hash = VALUES(css_hash),
                        html_hash = VALUES(html_hash),
                        js_hash = VALUES(js_hash),
                        css_size = VALUES(css_size),
                        html_size = VALUES(html_size),
                        js_size = VALUES(js_size),
                        status = VALUES(status),
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $version,
                $this->bundleName,
                $bundleData['assets']['css'],
                $bundleData['assets']['html'],
                $bundleData['assets']['js'],
                $manifest,
                $bundleData['manifest']['css']['hash'],
                $bundleData['manifest']['html']['hash'],
                $bundleData['manifest']['js']['hash'],
                $bundleData['manifest']['css']['size'],
                $bundleData['manifest']['html']['size'],
                $bundleData['manifest']['js']['size'],
                $status
            ]);

            return $result;
        } catch (Exception $e) {
            error_log("BundleLoader Save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish bundle: update status and set published_at
     */
    public function publishBundle($version) {
        try {
            $query = "UPDATE builder_bundles 
                      SET status = 'published', published_at = CURRENT_TIMESTAMP
                      WHERE name = ? AND version = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$this->bundleName, $version]);
        } catch (Exception $e) {
            error_log("BundleLoader Publish Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update CDN URLs for a bundle version
     */
    public function updateCdnUrls($version, $cssUrl, $htmlUrl, $jsUrl) {
        try {
            $query = "UPDATE builder_bundles 
                      SET cdn_css_url = ?, cdn_html_url = ?, cdn_js_url = ?
                      WHERE name = ? AND version = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$cssUrl, $htmlUrl, $jsUrl, $this->bundleName, $version]);
        } catch (Exception $e) {
            error_log("BundleLoader CDN Update Error: " . $e->getMessage());
            return false;
        }
    }
}

