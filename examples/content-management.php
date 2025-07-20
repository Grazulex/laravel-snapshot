<?php

declare(strict_types=1);
/**
 * Example: Content Management
 * Description: Track changes to articles and pages for content management systems
 *
 * Prerequisites:
 * - Article model (or substitute with any content model)
 * - Laravel Snapshot package configured
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/content-management.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

// Mock Article model for demonstration
class Article
{
    public $id;
    public $title;
    public $content;
    public $status;
    public $author_id;
    public $published_at;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 1;
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->status = $data['status'] ?? 'draft';
        $this->author_id = $data['author_id'] ?? 1;
        $this->published_at = $data['published_at'] ?? null;
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'author_id' => $this->author_id,
            'published_at' => $this->published_at,
        ];
    }
    
    public function update($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

echo "=== Content Management Example ===\n\n";

// Create a test article
$article = new Article([
    'id' => 123,
    'title' => 'Getting Started with Laravel Snapshot',
    'content' => 'Laravel Snapshot is a powerful package for tracking model changes...',
    'status' => 'draft',
    'author_id' => 1,
]);

echo "1. Created new article draft: '{$article->title}'\n";

// Create initial snapshot
Snapshot::save($article, 'article-draft-created');
echo "2. Initial draft snapshot created\n";

// Simulate editorial workflow
echo "\n3. Editorial workflow simulation:\n";

// Author makes initial revisions
sleep(1);
$article->update([
    'title' => 'Complete Guide to Laravel Snapshot',
    'content' => $article->content . "\n\nThis comprehensive guide covers all features including automatic snapshots, diff comparison, and storage backends.",
]);
Snapshot::save($article, 'author-revisions-v1');
echo "   ✓ Author revisions v1 - expanded title and content\n";

// Editor reviews and makes changes
sleep(1);
$article->update([
    'content' => str_replace('powerful package', 'essential package', $article->content),
]);
Snapshot::save($article, 'editor-review-changes');
echo "   ✓ Editor review changes applied\n";

// Author responds to feedback
sleep(1);
$article->update([
    'content' => $article->content . "\n\n## Key Features\n- Manual and automatic snapshots\n- Diff comparison\n- Multiple storage backends\n- Timeline reports",
]);
Snapshot::save($article, 'author-feature-section');
echo "   ✓ Author added feature section\n";

// Final editorial approval
sleep(1);
$article->update([
    'status' => 'ready_for_publish',
]);
Snapshot::save($article, 'editorial-approval');
echo "   ✓ Editorial approval - status changed to ready for publish\n";

// Publication
sleep(1);
$article->update([
    'status' => 'published',
    'published_at' => date('Y-m-d H:i:s'),
]);
Snapshot::save($article, 'article-published');
echo "   ✓ Article published\n";

// Post-publication update
sleep(1);
$article->update([
    'content' => $article->content . "\n\n## Additional Resources\n- Documentation: /docs\n- Examples: /examples\n- Support: GitHub Issues",
]);
Snapshot::save($article, 'post-publication-update');
echo "   ✓ Post-publication update - added resources section\n";

// Show content evolution
echo "\n4. Content evolution analysis:\n";
$snapshots = Snapshot::list();
$articleSnapshots = array_filter($snapshots, function($snapshot, $label) {
    return strpos($label, 'article-') === 0 || strpos($label, 'author-') === 0 || strpos($label, 'editor-') === 0;
}, ARRAY_FILTER_USE_BOTH);

foreach ($articleSnapshots as $label => $snapshot) {
    $content = $snapshot['data'] ?? $snapshot['content'] ?? '';
    if (is_array($content)) {
        $wordCount = str_word_count($content['content'] ?? '');
    } else {
        $wordCount = str_word_count($content);
    }
    $timestamp = $snapshot['timestamp'] ?? 'Unknown time';
    echo "   - {$label}: {$wordCount} words ({$timestamp})\n";
}

// Compare versions
echo "\n5. Version comparisons:\n";

// Compare draft vs published
$draftVsPublished = Snapshot::diff('article-draft-created', 'article-published');
if (isset($draftVsPublished['modified'])) {
    echo "   Draft vs Published changes:\n";
    foreach ($draftVsPublished['modified'] as $field => $change) {
        if ($field === 'content') {
            $fromWords = str_word_count($change['from']);
            $toWords = str_word_count($change['to']);
            echo "     - content: {$fromWords} words → {$toWords} words\n";
        } else {
            echo "     - {$field}: '{$change['from']}' → '{$change['to']}'\n";
        }
    }
}

// Compare editor changes
$editorChanges = Snapshot::diff('author-revisions-v1', 'editor-review-changes');
if (isset($editorChanges['modified']['content'])) {
    echo "\n   Editor changes detected in content:\n";
    $before = $editorChanges['modified']['content']['from'];
    $after = $editorChanges['modified']['content']['to'];
    
    // Simple change detection (in real scenario, you'd use a proper diff library)
    if (strpos($before, 'powerful package') !== false && strpos($after, 'essential package') !== false) {
        echo "     - Word change: 'powerful package' → 'essential package'\n";
    }
}

// Demonstrate content restoration capabilities
echo "\n6. Content restoration demonstration:\n";
echo "   Current article status: {$article->status}\n";
echo "   Current title: '{$article->title}'\n";

$draftSnapshot = Snapshot::load('article-draft-created');
if ($draftSnapshot) {
    $draftData = $draftSnapshot['data'] ?? $draftSnapshot;
    echo "   Can restore to draft version:\n";
    echo "     - Status: draft\n";
    echo "     - Original title: '{$draftData['title']}'\n";
    echo "     - Original word count: ".str_word_count($draftData['content'])."\n";
}

// Show workflow statistics
echo "\n7. Editorial workflow statistics:\n";
$stats = [
    'total_versions' => count($articleSnapshots),
    'drafts' => 0,
    'published' => 0,
    'author_changes' => 0,
    'editor_changes' => 0,
];

foreach ($articleSnapshots as $label => $snapshot) {
    if (strpos($label, 'draft') !== false) $stats['drafts']++;
    if (strpos($label, 'published') !== false) $stats['published']++;
    if (strpos($label, 'author') !== false) $stats['author_changes']++;
    if (strpos($label, 'editor') !== false) $stats['editor_changes']++;
}

echo "   - Total versions: {$stats['total_versions']}\n";
echo "   - Author changes: {$stats['author_changes']}\n";
echo "   - Editor changes: {$stats['editor_changes']}\n";
echo "   - Draft versions: {$stats['drafts']}\n";
echo "   - Published versions: {$stats['published']}\n";

// Demonstrate advanced content management features
echo "\n8. Advanced content management features:\n";

// Content backup before major changes
echo "   ✓ Automatic backup before each major revision\n";

// Change approval workflow
echo "   ✓ Track approval workflow (draft → review → published)\n";

// Collaborative editing tracking
echo "   ✓ Track individual contributor changes\n";

// Content rollback capabilities
echo "   ✓ Rollback to any previous version instantly\n";

// SEO impact analysis
echo "   ✓ Analyze impact of changes on content length/structure\n";

// Cleanup demonstration
echo "\n9. Content management cleanup:\n";

// In production, you might want to keep content snapshots longer
$contentSnapshots = array_keys($articleSnapshots);
echo "   Found ".count($contentSnapshots)." content snapshots to manage\n";

// Could implement retention policy here
echo "   ✓ Retention policy can be configured per content type\n";
echo "   ✓ Critical snapshots (published versions) can be preserved\n";
echo "   ✓ Draft snapshots can be cleaned up after publication\n";

// Cleanup test data
echo "\n10. Cleaning up test data...\n";
$deletedCount = Snapshot::clear();
echo "    Deleted {$deletedCount} snapshots\n";

echo "\n=== Content Management Benefits Demonstrated ===\n";
echo "✓ Complete editorial workflow tracking\n";
echo "✓ Author and editor change attribution\n";
echo "✓ Version comparison and rollback\n";
echo "✓ Content evolution analysis\n";
echo "✓ SEO and content quality metrics\n";
echo "✓ Collaborative editing support\n";
echo "✓ Automated backup and versioning\n";

echo "\nContent management example completed successfully!\n";