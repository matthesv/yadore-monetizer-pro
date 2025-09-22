<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-media-document"></span>
        API Documentation & Monitoring
        <span class="version-badge">v2.9.20</span>
    </h1>

    <div class="yadore-api-container">
        <!-- API Status Dashboard -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-network"></span> API Status Dashboard</h2>
                <div class="card-actions">
                    <button class="button button-secondary" id="refresh-api-status">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="api-status-grid">
                    <div class="api-service">
                        <div class="service-header">
                            <h3><span class="dashicons dashicons-admin-network"></span> Yadore API</h3>
                            <span class="status-indicator <?php echo get_option('yadore_api_key') ? 'status-active' : 'status-inactive'; ?>"></span>
                        </div>
                        <div class="service-stats">
                            <div class="stat-item">
                                <span class="stat-label">Status:</span>
                                <span class="stat-value" id="yadore-api-status">Checking...</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Response Time:</span>
                                <span class="stat-value" id="yadore-api-latency">-</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Last Request:</span>
                                <span class="stat-value" id="yadore-api-last">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="api-service">
                        <div class="service-header">
                            <h3><span class="dashicons dashicons-admin-generic"></span> Gemini AI</h3>
                            <span class="status-indicator <?php echo get_option('yadore_gemini_api_key') ? 'status-active' : 'status-inactive'; ?>"></span>
                        </div>
                        <div class="service-stats">
                            <div class="stat-item">
                                <span class="stat-label">Status:</span>
                                <span class="stat-value" id="gemini-api-status">Checking...</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Response Time:</span>
                                <span class="stat-value" id="gemini-api-latency">-</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Last Request:</span>
                                <span class="stat-value" id="gemini-api-last">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="api-metrics">
                    <div class="metric-card">
                        <h4><span class="dashicons dashicons-chart-line"></span> Request Volume (24h)</h4>
                        <canvas id="requests-chart" width="400" height="200"></canvas>
                    </div>

                    <div class="metric-card">
                        <h4><span class="dashicons dashicons-performance"></span> Response Times</h4>
                        <div class="response-times">
                            <div class="time-stat">
                                <span class="time-label">Average:</span>
                                <span class="time-value" id="avg-response-time">Loading...</span>
                            </div>
                            <div class="time-stat">
                                <span class="time-label">Fastest:</span>
                                <span class="time-value" id="min-response-time">Loading...</span>
                            </div>
                            <div class="time-stat">
                                <span class="time-label">Slowest:</span>
                                <span class="time-value" id="max-response-time">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Documentation -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-media-document"></span> API Endpoints Documentation</h2>
            </div>
            <div class="card-content">
                <div class="api-docs-tabs">
                    <div class="tab-nav">
                        <button class="tab-button active" data-tab="yadore">Yadore API</button>
                        <button class="tab-button" data-tab="gemini">Gemini AI API</button>
                        <button class="tab-button" data-tab="internal">Internal Endpoints</button>
                    </div>

                    <!-- Yadore API Documentation -->
                    <div class="tab-content active" id="tab-yadore">
                        <div class="api-documentation">
                            <div class="api-spec-callout">
                                <p>
                                    <strong>Yadore Publisher API 2.0.0</strong> (OAS 3.0) &ndash; vollständige Spezifikation unter
                                    <code>https://api.yadore.com/openapi.yaml</code>. Alle Endpunkte verwenden den Server
                                    <code>https://api.yadore.com/</code>.
                                </p>
                            </div>
                            <div class="endpoint-section">
                                <h3>Offer Search Endpoint</h3>
                                <div class="endpoint-details">
                                    <div class="endpoint-url">
                                        <span class="method">GET</span>
                                        <code>https://api.yadore.com/v2/offer</code>
                                    </div>

                                    <div class="endpoint-description">
                                        <p>Search for affiliate-ready offers based on keywords and return enriched pricing and deeplink data.</p>
                                    </div>

                                    <div class="endpoint-parameters">
                                        <h4>Parameters</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Parameter</th>
                                                    <th>Type</th>
                                                    <th>Required</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>keyword</code></td>
                                                    <td>string</td>
                                                    <td>Yes</td>
                                                    <td>Product search keyword</td>
                                                </tr>
                                                <tr>
                                                    <td><code>limit</code></td>
                                                    <td>integer</td>
                                                    <td>No</td>
                                                    <td>Number of offers to return (default: 6, max: 50)</td>
                                                </tr>
                                                <tr>
                                                    <td><code>market</code></td>
                                                    <td>string</td>
                                                    <td>No</td>
                                                    <td>Market code (e.g. <code>DE</code>, <code>AT</code>) for localized results</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="endpoint-parameters">
                                        <h4>Headers</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Header</th>
                                                    <th>Required</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>API-Key</code></td>
                                                    <td>Yes</td>
                                                    <td>Your personal Yadore Publisher API key</td>
                                                </tr>
                                                <tr>
                                                    <td><code>Keyword</code></td>
                                                    <td>Yes</td>
                                                    <td>Active keyword transmitted from your plugin configuration</td>
                                                </tr>
                                                <tr>
                                                    <td><code>Limit</code></td>
                                                    <td>Yes</td>
                                                    <td>Number of offers requested based on your plugin settings</td>
                                                </tr>
                                                <tr>
                                                    <td><code>Accept</code></td>
                                                    <td>No</td>
                                                    <td>Use <code>application/json</code> to receive JSON responses</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="endpoint-example">
                                        <h4>Example Request</h4>
                                        <pre><code>GET /v2/offer?keyword=smartphone&amp;limit=6&amp;market=DE HTTP/1.1
Host: api.yadore.com
API-Key: YOUR_API_KEY
Keyword: smartphone
Limit: 6
Accept: application/json</code></pre>
                                    </div>

                                    <div class="endpoint-response">
                                        <h4>Example Response</h4>
                                        <pre><code>{
  "offers": [
    {
      "offerId": "offer_123",
      "name": "iPhone 15 Pro",
      "price": {
        "amount": "999.00",
        "currency": "EUR"
      },
      "deeplink": "https://affiliate.link/123",
      "imageUrl": "https://example.com/image.jpg",
      "merchantName": "Apple Store"
    }
  ],
  "total": 1,
  "requestId": "req_abc123"
}</code></pre>
                                    </div>

                                    <div class="endpoint-testing">
                                        <h4>Test This Endpoint</h4>
                                        <div class="test-form">
                                            <input type="text" id="test-keyword" placeholder="Enter keyword..." value="smartphone">
                                            <input type="number" id="test-limit" placeholder="Limit" value="3" min="1" max="10">
                                            <button class="button button-primary" id="test-yadore-endpoint">
                                                <span class="dashicons dashicons-admin-network"></span> Test Request
                                            </button>
                                        </div>
                                        <div class="test-results" id="yadore-test-results" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gemini AI Documentation -->
                    <div class="tab-content" id="tab-gemini">
                        <div class="api-documentation">
                            <div class="endpoint-section">
                                <h3>Content Analysis Endpoint</h3>
                                <div class="endpoint-details">
                                    <div class="endpoint-url">
                                        <span class="method">POST</span>
                                        <code>https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent</code>
                                    </div>

                                    <div class="endpoint-description">
                                        <p>Analyze content using Google's Gemini AI to extract relevant product keywords.</p>
                                    </div>

                                    <div class="endpoint-parameters">
                                        <h4>Available Models</h4>
                                        <div class="models-list">
                                            <div class="model-item">
                                                <h5>gemini-2.0-flash</h5>
                                                <p>Fastest real-time model for production workloads</p>
                                                <span class="model-badge recommended">Recommended</span>
                                            </div>
                                            <div class="model-item">
                                                <h5>gemini-2.0-pro-exp</h5>
                                                <p>Experimental pro model with the highest reasoning quality</p>
                                                <span class="model-badge premium">Premium</span>
                                            </div>
                                            <div class="model-item">
                                                <h5>gemini-2.0-flash-lite</h5>
                                                <p>Cost-efficient 2.0 model optimized for automation tasks</p>
                                                <span class="model-badge standard">Efficient</span>
                                            </div>
                                            <div class="model-item">
                                                <h5>gemini-1.5-flash-8b</h5>
                                                <p>Lightweight option for quick content tagging and metadata</p>
                                                <span class="model-badge standard">Lightweight</span>
                                            </div>
                                            <div class="model-item">
                                                <h5>gemini-1.5-pro</h5>
                                                <p>Highest accuracy for long-form editorial analysis</p>
                                                <span class="model-badge premium">Advanced</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="endpoint-example">
                                        <h4>Example Request</h4>
                                        <pre><code>{
  "contents": [
    {
      "parts": [
        {
          "text": "Analyze this content and identify the main product category: Best smartphones of 2024 review..."
        }
      ]
    }
  ],
  "generationConfig": {
    "temperature": 0.3,
    "maxOutputTokens": 50,
    "responseMimeType": "application/json",
    "responseSchema": {
      "type": "OBJECT",
      "properties": {
        "keyword": {
          "type": "STRING",
          "description": "Primary product keyword describing the best affiliate opportunity."
        },
        "alternate_keywords": {
          "type": "ARRAY",
          "description": "Up to three alternate keyword candidates for backup product searches.",
          "items": {
            "type": "STRING"
          }
        },
        "confidence": {
          "type": "NUMBER",
          "minimum": 0,
          "maximum": 1
        },
        "rationale": {
          "type": "STRING",
          "description": "Optional short explanation for the keyword choice."
        }
      },
      "required": ["keyword"],
      "propertyOrdering": ["keyword", "alternate_keywords", "confidence", "rationale"]
    }
  }
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Internal Endpoints -->
                    <div class="tab-content" id="tab-internal">
                        <div class="api-documentation">
                            <div class="internal-endpoints">
                                <h3>WordPress AJAX Endpoints</h3>
                                <div class="endpoints-list">
                                    <div class="endpoint-item">
                                        <h4>yadore_get_overlay_products</h4>
                                        <p>Get products for overlay display on frontend</p>
                                        <span class="endpoint-access">Public + Admin</span>
                                    </div>

                                    <div class="endpoint-item">
                                        <h4>yadore_test_gemini_api</h4>
                                        <p>Test Gemini AI API connection</p>
                                        <span class="endpoint-access">Admin Only</span>
                                    </div>

                                    <div class="endpoint-item">
                                        <h4>yadore_scan_posts</h4>
                                        <p>Start post scanning process</p>
                                        <span class="endpoint-access">Admin Only</span>
                                    </div>

                                    <div class="endpoint-item">
                                        <h4>yadore_get_api_logs</h4>
                                        <p>Retrieve API request logs</p>
                                        <span class="endpoint-access">Admin Only</span>
                                    </div>

                                    <div class="endpoint-item">
                                        <h4>yadore_get_debug_info</h4>
                                        <p>Get system debug information</p>
                                        <span class="endpoint-access">Admin Only</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Logs -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-list-view"></span> API Request Logs</h2>
                <div class="card-actions">
                    <select id="logs-filter">
                        <option value="all">All Requests</option>
                        <option value="yadore">Yadore API</option>
                        <option value="gemini">Gemini AI</option>
                        <option value="success">Successful Only</option>
                        <option value="error">Errors Only</option>
                    </select>
                    <button class="button button-secondary" id="clear-logs">
                        <span class="dashicons dashicons-trash"></span> Clear Logs
                    </button>
                    <button class="button button-secondary" id="export-logs">
                        <span class="dashicons dashicons-download"></span> Export
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="logs-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>API</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="api-logs-body">
                            <tr>
                                <td colspan="7" class="loading-row">
                                    <span class="dashicons dashicons-update-alt spinning"></span> Loading API logs...
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="table-pagination" id="logs-pagination">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize API documentation
    yadoreInitializeApiDocs();

    // Load API status
    yadoreLoadApiStatus();

    // Load API logs
    yadoreLoadApiLogs();
});

function yadoreInitializeApiDocs() {
    const $ = jQuery;

    // Tab switching
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');

        $('.tab-button').removeClass('active');
        $(this).addClass('active');

        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // API testing
    $('#test-yadore-endpoint').on('click', yadoreTestYadoreEndpoint);

    // Logs management
    $('#logs-filter').on('change', yadoreFilterLogs);
    $('#clear-logs').on('click', yadoreClearLogs);
    $('#export-logs').on('click', yadoreExportLogs);

    console.log('Yadore API Documentation v2.9.20 - Initialized');
}

function yadoreLoadApiStatus() {
    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_get_api_status',
        nonce: yadore_admin.nonce
    }, function(response) {
        if (response.success) {
            const data = response.data;

            // Update Yadore API status
            jQuery('#yadore-api-status').text(data.yadore.status);
            jQuery('#yadore-api-latency').text(data.yadore.latency + 'ms');
            jQuery('#yadore-api-last').text(data.yadore.last_request);

            // Update Gemini API status
            jQuery('#gemini-api-status').text(data.gemini.status);
            jQuery('#gemini-api-latency').text(data.gemini.latency + 'ms');
            jQuery('#gemini-api-last').text(data.gemini.last_request);

            // Update response times
            jQuery('#avg-response-time').text(data.metrics.avg_response_time + 'ms');
            jQuery('#min-response-time').text(data.metrics.min_response_time + 'ms');
            jQuery('#max-response-time').text(data.metrics.max_response_time + 'ms');
        }
    });
}

function yadoreTestYadoreEndpoint() {
    const $ = jQuery;
    const button = $('#test-yadore-endpoint');
    const resultsDiv = $('#yadore-test-results');

    const keyword = $('#test-keyword').val();
    const limit = parseInt($('#test-limit').val()) || 3;

    if (!keyword) {
        alert('Please enter a keyword to test.');
        return;
    }

    button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
    resultsDiv.show().html('<div class="testing-message"><span class="dashicons dashicons-update-alt spinning"></span> Testing Yadore API endpoint...</div>');

    $.post(yadore_admin.ajax_url, {
        action: 'yadore_test_api_endpoint',
        nonce: yadore_admin.nonce,
        keyword: keyword,
        limit: limit
    }, function(response) {
        if (response.success) {
            resultsDiv.html(`
                <div class="test-success">
                    <h5><span class="dashicons dashicons-yes-alt"></span> Request Successful</h5>
                    <div class="test-data">
                        <div class="test-row">
                            <span class="test-label">Products Found:</span>
                            <span class="test-value">${response.data.products.length}</span>
                        </div>
                        <div class="test-row">
                            <span class="test-label">Response Time:</span>
                            <span class="test-value">${response.data.response_time}ms</span>
                        </div>
                        <div class="test-row">
                            <span class="test-label">Request ID:</span>
                            <span class="test-value">${response.data.request_id}</span>
                        </div>
                    </div>
                    <details class="test-details">
                        <summary>View Response Data</summary>
                        <pre><code>${JSON.stringify(response.data, null, 2)}</code></pre>
                    </details>
                </div>
            `);
        } else {
            resultsDiv.html(`
                <div class="test-error">
                    <h5><span class="dashicons dashicons-dismiss"></span> Request Failed</h5>
                    <p>${response.data}</p>
                </div>
            `);
        }
    }).fail(function() {
        resultsDiv.html(`
            <div class="test-error">
                <h5><span class="dashicons dashicons-dismiss"></span> Connection Error</h5>
                <p>Unable to connect to API. Please check your connection and try again.</p>
            </div>
        `);
    }).always(function() {
        button.prop('disabled', false).html('<span class="dashicons dashicons-admin-network"></span> Test Request');
    });
}
</script>