-- Database Schema for Builder Bundle Storage
-- Supports hybrid approach: DB storage + CDN deployment

CREATE TABLE builder_bundles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'landing-page',
    -- Asset content stored as TEXT/CLOB
    css_content TEXT NOT NULL,
    html_content TEXT NOT NULL,
    js_content TEXT NOT NULL,
    -- Manifest metadata (JSON)
    manifest JSON,
    -- Hash for cache invalidation
    css_hash VARCHAR(32),
    html_hash VARCHAR(32),
    js_hash VARCHAR(32),
    -- File sizes
    css_size INT,
    html_size INT,
    js_size INT,
    -- CDN paths (for production)
    cdn_css_url VARCHAR(500) NULL,
    cdn_html_url VARCHAR(500) NULL,
    cdn_js_url VARCHAR(500) NULL,
    -- Status: draft, published, archived
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_version (version),
    INDEX idx_status (status),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example: Insert minified bundle from bundle.json
-- Note: In production, use prepared statements to prevent SQL injection

-- INSERT INTO builder_bundles (
--     version,
--     name,
--     css_content,
--     html_content,
--     js_content,
--     manifest,
--     css_hash,
--     html_hash,
--     js_hash,
--     css_size,
--     html_size,
--     js_size,
--     status
-- ) VALUES (
--     '1.0.0',
--     'landing-page',
--     '/* minified CSS content */',
--     '<!-- minified HTML content -->',
--     '/* minified JS content */',
--     '{"css": {...}, "html": {...}, "js": {...}}',
--     'md5hash1',
--     'md5hash2',
--     'md5hash3',
--     5000,
--     3116,
--     10890,
--     'draft'
-- );

-- Function to get bundle by version (for preview)
-- Function to get published bundle (for production - should use CDN)

