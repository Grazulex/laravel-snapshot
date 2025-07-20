<?php

declare(strict_types=1);
/**
 * Example: API Integration
 * Description: Snapshot API endpoints for external integrations
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Laravel API routes setup
 * - Authentication middleware
 *
 * Usage:
 * This demonstrates how to expose snapshot functionality via API endpoints
 */

echo "=== API Integration Example ===\n\n";

echo "1. API Controller implementation:\n";

// Example API controller
$controllerCode = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SnapshotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('can:manage-snapshots');
    }

    /**
     * List snapshots with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $snapshots = Snapshot::list();
            
            // Filter by model if specified
            if ($request->has('model')) {
                $modelClass = $request->get('model');
                $snapshots = array_filter($snapshots, function($snapshot) use ($modelClass) {
                    return ($snapshot['data']['class'] ?? '') === $modelClass;
                });
            }

            // Pagination
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $offset = ($page - 1) * $limit;
            $paginatedSnapshots = array_slice($snapshots, $offset, $limit, true);

            return response()->json([
                'data' => $paginatedSnapshots,
                'meta' => [
                    'total' => count($snapshots),
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil(count($snapshots) / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve snapshots'], 500);
        }
    }

    /**
     * Get a specific snapshot
     */
    public function show(string $label): JsonResponse
    {
        try {
            $snapshot = Snapshot::load($label);
            
            if (!$snapshot) {
                return response()->json(['error' => 'Snapshot not found'], 404);
            }

            return response()->json(['data' => $snapshot]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load snapshot'], 500);
        }
    }

    /**
     * Create a snapshot
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'required|string',
            'model_id' => 'required',
            'label' => 'required|string|max:255|unique:snapshots,label',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Create snapshot from provided data
            $snapshot = Snapshot::save($request->get('data'), $request->get('label'));
            
            return response()->json([
                'message' => 'Snapshot created successfully',
                'data' => $snapshot,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create snapshot'], 500);
        }
    }

    /**
     * Compare two snapshots
     */
    public function compare(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label_a' => 'required|string',
            'label_b' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $diff = Snapshot::diff($request->get('label_a'), $request->get('label_b'));
            
            return response()->json([
                'comparison' => [
                    'from' => $request->get('label_a'),
                    'to' => $request->get('label_b'),
                    'differences' => $diff,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to compare snapshots'], 500);
        }
    }

    /**
     * Delete a snapshot
     */
    public function destroy(string $label): JsonResponse
    {
        try {
            $deleted = Snapshot::delete($label);
            
            if (!$deleted) {
                return response()->json(['error' => 'Snapshot not found'], 404);
            }

            return response()->json(['message' => 'Snapshot deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete snapshot'], 500);
        }
    }

    /**
     * Get snapshot statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $modelClass = $request->get('model');
            $stats = Snapshot::stats($modelClass)->get();
            
            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve statistics'], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,export',
            'labels' => 'required|array|min:1',
            'labels.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $action = $request->get('action');
            $labels = $request->get('labels');
            $results = [];

            foreach ($labels as $label) {
                switch ($action) {
                    case 'delete':
                        $results[$label] = Snapshot::delete($label);
                        break;
                    case 'export':
                        $results[$label] = Snapshot::load($label);
                        break;
                }
            }

            return response()->json([
                'message' => "Bulk {$action} completed",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => "Failed to perform bulk {$action}"], 500);
        }
    }
}

PHP;

echo $controllerCode;

echo "\n2. API Routes (routes/api.php):\n";

$routesCode = <<<'PHP'
<?php

use App\Http\Controllers\Api\SnapshotController;

// Snapshot API routes
Route::middleware(['auth:api'])->prefix('snapshots')->group(function () {
    Route::get('/', [SnapshotController::class, 'index'])->name('api.snapshots.index');
    Route::post('/', [SnapshotController::class, 'store'])->name('api.snapshots.store');
    Route::get('/stats', [SnapshotController::class, 'stats'])->name('api.snapshots.stats');
    Route::post('/bulk', [SnapshotController::class, 'bulk'])->name('api.snapshots.bulk');
    Route::post('/compare', [SnapshotController::class, 'compare'])->name('api.snapshots.compare');
    Route::get('/{label}', [SnapshotController::class, 'show'])->name('api.snapshots.show');
    Route::delete('/{label}', [SnapshotController::class, 'destroy'])->name('api.snapshots.destroy');
});

PHP;

echo $routesCode;

echo "\n3. API client examples:\n";

// JavaScript client example
$jsClientCode = <<<'JAVASCRIPT'
// JavaScript API client
class SnapshotApiClient {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async request(method, endpoint, data = null) {
        const url = `${this.baseUrl}/api/snapshots${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`,
                'Accept': 'application/json',
            },
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'API request failed');
        }

        return response.json();
    }

    // List snapshots
    async list(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request('GET', `?${params}`);
    }

    // Get specific snapshot
    async get(label) {
        return this.request('GET', `/${label}`);
    }

    // Create snapshot
    async create(data) {
        return this.request('POST', '', data);
    }

    // Compare snapshots
    async compare(labelA, labelB) {
        return this.request('POST', '/compare', {
            label_a: labelA,
            label_b: labelB,
        });
    }

    // Delete snapshot
    async delete(label) {
        return this.request('DELETE', `/${label}`);
    }

    // Get statistics
    async stats(model = null) {
        const params = model ? `?model=${encodeURIComponent(model)}` : '';
        return this.request('GET', `/stats${params}`);
    }

    // Bulk operations
    async bulk(action, labels) {
        return this.request('POST', '/bulk', {
            action,
            labels,
        });
    }
}

// Usage example
const client = new SnapshotApiClient('https://your-app.com', 'your-api-token');

// List user snapshots
client.list({ model: 'App\\Models\\User', limit: 10 })
    .then(response => {
        console.log('Snapshots:', response.data);
    })
    .catch(error => {
        console.error('Error:', error.message);
    });

JAVASCRIPT;

echo $jsClientCode;

// Python client example
echo "\n4. Python API client:\n";

$pythonClientCode = <<<'PYTHON'
import requests
import json
from typing import Optional, Dict, List, Any

class SnapshotApiClient:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url.rstrip('/')
        self.token = token
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        })

    def _request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict[str, Any]:
        url = f"{self.base_url}/api/snapshots{endpoint}"
        
        response = self.session.request(
            method=method,
            url=url,
            json=data if data else None
        )
        
        if not response.ok:
            error_data = response.json() if response.content else {'error': 'API request failed'}
            raise Exception(error_data.get('error', 'Unknown error'))
        
        return response.json()

    def list_snapshots(self, model: Optional[str] = None, limit: int = 20, page: int = 1) -> Dict[str, Any]:
        """List snapshots with optional filtering"""
        params = {'limit': limit, 'page': page}
        if model:
            params['model'] = model
            
        query_string = '&'.join([f"{k}={v}" for k, v in params.items()])
        return self._request('GET', f"?{query_string}")

    def get_snapshot(self, label: str) -> Dict[str, Any]:
        """Get a specific snapshot"""
        return self._request('GET', f"/{label}")

    def create_snapshot(self, model_type: str, model_id: Any, label: str, data: Dict[str, Any]) -> Dict[str, Any]:
        """Create a new snapshot"""
        return self._request('POST', '', {
            'model_type': model_type,
            'model_id': model_id,
            'label': label,
            'data': data,
        })

    def compare_snapshots(self, label_a: str, label_b: str) -> Dict[str, Any]:
        """Compare two snapshots"""
        return self._request('POST', '/compare', {
            'label_a': label_a,
            'label_b': label_b,
        })

    def delete_snapshot(self, label: str) -> Dict[str, Any]:
        """Delete a snapshot"""
        return self._request('DELETE', f"/{label}")

    def get_stats(self, model: Optional[str] = None) -> Dict[str, Any]:
        """Get snapshot statistics"""
        endpoint = f"/stats?model={model}" if model else "/stats"
        return self._request('GET', endpoint)

    def bulk_operation(self, action: str, labels: List[str]) -> Dict[str, Any]:
        """Perform bulk operations on snapshots"""
        return self._request('POST', '/bulk', {
            'action': action,
            'labels': labels,
        })

# Usage example
client = SnapshotApiClient('https://your-app.com', 'your-api-token')

try:
    # List user snapshots
    response = client.list_snapshots(model='App\\Models\\User', limit=10)
    print(f"Found {response['meta']['total']} snapshots")
    
    # Get specific snapshot
    snapshot = client.get_snapshot('user-before-update')
    print(f"Snapshot data: {snapshot['data']}")
    
    # Compare snapshots
    comparison = client.compare_snapshots('before', 'after')
    print(f"Differences: {comparison['comparison']['differences']}")
    
except Exception as e:
    print(f"API Error: {e}")

PYTHON;

echo $pythonClientCode;

echo "\n5. API Authentication examples:\n";

$authCode = <<<'PHP'
<?php
// API Authentication middleware

class SnapshotApiMiddleware
{
    public function handle($request, Closure $next)
    {
        // Check API token
        $token = $request->bearerToken();
        if (!$token || !$this->validateToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permissions
        $user = auth()->user();
        if (!$user->can('manage-snapshots')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Rate limiting
        $key = 'api_rate_limit:' . $user->id;
        if (Cache::get($key, 0) >= 100) { // 100 requests per hour
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        Cache::increment($key, 1, 3600);

        return $next($request);
    }

    private function validateToken($token): bool
    {
        // Validate JWT token or API key
        return auth('api')->check();
    }
}

// Usage in routes
Route::middleware(['auth:api', SnapshotApiMiddleware::class])->group(function () {
    // Snapshot routes
});

PHP;

echo $authCode;

echo "\n6. Webhook integration:\n";

$webhookCode = <<<'PHP'
<?php
// Webhook notifications for snapshot events

class SnapshotWebhookService
{
    protected $webhookUrls;

    public function __construct()
    {
        $this->webhookUrls = config('snapshot.webhooks.urls', []);
    }

    public function notifySnapshotCreated($snapshot): void
    {
        $this->sendWebhook('snapshot.created', [
            'event' => 'snapshot.created',
            'snapshot' => [
                'label' => $snapshot['label'],
                'model_type' => $snapshot['data']['class'] ?? null,
                'created_at' => now()->toISOString(),
            ],
        ]);
    }

    public function notifySnapshotDeleted(string $label): void
    {
        $this->sendWebhook('snapshot.deleted', [
            'event' => 'snapshot.deleted',
            'label' => $label,
            'deleted_at' => now()->toISOString(),
        ]);
    }

    protected function sendWebhook(string $event, array $data): void
    {
        foreach ($this->webhookUrls as $url) {
            Http::post($url, array_merge($data, [
                'timestamp' => now()->toISOString(),
                'signature' => $this->generateSignature($data),
            ]));
        }
    }

    protected function generateSignature(array $data): string
    {
        $secret = config('snapshot.webhooks.secret');
        return hash_hmac('sha256', json_encode($data), $secret);
    }
}

// Event listeners
Event::listen(SnapshotCreated::class, function ($event) {
    app(SnapshotWebhookService::class)->notifySnapshotCreated($event->snapshot);
});

Event::listen(SnapshotDeleted::class, function ($event) {
    app(SnapshotWebhookService::class)->notifySnapshotDeleted($event->label);
});

PHP;

echo $webhookCode;

echo "\n7. API testing examples:\n";

$testCode = <<<'PHP'
<?php
// API endpoint tests

class SnapshotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_can_list_snapshots()
    {
        // Create test snapshots
        Snapshot::save(['id' => 1, 'name' => 'Test'], 'test-snapshot-1');
        Snapshot::save(['id' => 2, 'name' => 'Test 2'], 'test-snapshot-2');

        $response = $this->getJson('/api/snapshots');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'meta' => ['total', 'page', 'limit', 'pages'],
                ])
                ->assertJsonCount(2, 'data');
    }

    public function test_can_create_snapshot()
    {
        $data = [
            'model_type' => 'App\Models\User',
            'model_id' => 1,
            'label' => 'api-test-snapshot',
            'data' => ['id' => 1, 'name' => 'Test User'],
        ];

        $response = $this->postJson('/api/snapshots', $data);

        $response->assertStatus(201)
                ->assertJson(['message' => 'Snapshot created successfully']);

        $this->assertNotNull(Snapshot::load('api-test-snapshot'));
    }

    public function test_can_compare_snapshots()
    {
        Snapshot::save(['name' => 'Old Name'], 'snapshot-a');
        Snapshot::save(['name' => 'New Name'], 'snapshot-b');

        $response = $this->postJson('/api/snapshots/compare', [
            'label_a' => 'snapshot-a',
            'label_b' => 'snapshot-b',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'comparison' => [
                        'from',
                        'to', 
                        'differences',
                    ],
                ]);
    }

    public function test_requires_authentication()
    {
        auth()->logout();

        $response = $this->getJson('/api/snapshots');

        $response->assertStatus(401);
    }
}

PHP;

echo $testCode;

echo "\n=== API Integration Benefits Demonstrated ===\n";
echo "✓ RESTful API endpoints for all snapshot operations\n";
echo "✓ Proper authentication and authorization\n";
echo "✓ Rate limiting and security measures\n";
echo "✓ Client libraries for JavaScript and Python\n";
echo "✓ Webhook notifications for real-time integration\n";
echo "✓ Comprehensive API testing\n";
echo "✓ Bulk operations support\n";
echo "✓ Error handling and validation\n";

echo "\nAPI integration example completed successfully!\n";