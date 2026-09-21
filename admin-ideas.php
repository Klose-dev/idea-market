<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();

$user = currentUser();

// Filter & pagination
$status  = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$search  = trim($_GET['q']   ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where  = ['1=1'];
$params = [];
if ($status)   { $where[] = 'i.status = ?';   $params[] = $status; }
if ($priority) { $where[] = 'i.priority = ?'; $params[] = $priority; }
if ($search)   { $where[] = '(i.title LIKE ? OR i.idea_id LIKE ? OR u.full_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM ideas i JOIN users u ON i.user_id=u.id $whereSQL");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = $pdo->prepare("
  SELECT i.*, c.name AS cat_name, u.full_name, a.full_name AS assigned_name
  FROM ideas i
  JOIN users u ON i.user_id = u.id
  JOIN categories c ON i.category_id = c.id
  LEFT JOIN users a ON i.assigned_to = a.id
  $whereSQL
  ORDER BY i.created_at DESC
  LIMIT $perPage OFFSET {$pg['offset']}
");
$stmt->execute($params);
$ideas = $stmt->fetchAll();

// Reviewers
$reviewers = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin') ORDER BY full_name")->fetchAll();
$stats = getAdminStats($pdo);

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $ideaId  = (int)$_POST['idea_id'];
    $action  = $_POST['action'];
    if ($action === 'update_status') {
        $newStatus = $_POST['status'];
        $pdo->prepare("UPDATE ideas SET status=?, updated_at=NOW() WHERE id=?")->execute([$newStatus, $ideaId]);
        $ideaTitle = $pdo->prepare("SELECT title FROM ideas WHERE id=?"); $ideaTitle->execute([$ideaId]);
        logActivity($pdo, $ideaId, "Status changed to $newStatus", $user['id'], $newStatus);
        jsonResponse(['success' => true, 'status' => $newStatus]);
    }
    if ($action === 'assign') {
        $assignTo = (int)$_POST['assigned_to'];
        $pdo->prepare("UPDATE ideas SET assigned_to=? WHERE id=?")->execute([$assignTo ?: null, $ideaId]);
        logActivity($pdo, $ideaId, "Assigned to reviewer", $user['id'], '');
        jsonResponse(['success' => true]);
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM ideas WHERE id=?")->execute([$ideaId]);
        jsonResponse(['success' => true]);
    }
    if ($action === 'toggle_feature') {
        $featured = (int)$_POST['featured'];
        $pdo->prepare("UPDATE ideas SET is_featured=? WHERE id=?")->execute([$featured, $ideaId]);
        jsonResponse(['success' => true]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Ideas — IdeaMarket Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<div class="layout-wrapper">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-crown" style="color:#f59e0b;"></i></div>
      <div class="brand-text"><h6>Admin Panel</h6><span>IdeaMarket</span></div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      <div class="user-info"><h6><?= e($user['name']) ?></h6><span style="color:var(--gold);font-size:.7rem;"><i class="fas fa-crown" style="font-size:.65rem;"></i> Administrator</span></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Management</span>
      <a href="admin.php"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="admin-ideas.php" class="active"><i class="fas fa-lightbulb"></i> Manage Ideas <span class="badge-count"><?= $stats['pending'] ?></span></a>
      <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
      <a href="admin-investments.php"><i class="fas fa-hand-holding-usd"></i> Investments</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Analytics</span>
      <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Manage Ideas</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>

    <div class="page-body fade-in">

      <!-- Status filter tabs -->
      <div style="margin-bottom:1.5rem;">
        <div class="tab-nav" style="border:none;background:var(--card);border-radius:var(--radius);padding:.5rem;gap:.3rem;flex-wrap:wrap;">
          <?php
            $statuses = [''=> "All ({$stats['total']})", 'pending'=> "Pending ({$stats['pending']})", 'review'=> "Under Review ({$stats['review']})", 'approved'=> "Approved ({$stats['approved']})", 'funded'=> "Funded ({$stats['funded']})", 'closed'=> "Closed ({$stats['closed']})"];
            foreach ($statuses as $val => $label):
          ?>
            <a href="admin-ideas.php?status=<?= $val ?>&q=<?= e($search) ?>"
               class="btn btn-sm <?= $status===$val ? 'btn-primary' : 'btn-outline' ?>"
               style="border-radius:8px;"><?= $label ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <form method="GET" style="display:contents;">
          <input type="hidden" name="status" value="<?= e($status) ?>">
          <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Search ideas, IDs, submitters..." value="<?= e($search) ?>">
          </div>
          <select name="priority" class="form-control" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <option value="high" <?= $priority==='high'?'selected':'' ?>>High Priority</option>
            <option value="medium" <?= $priority==='medium'?'selected':'' ?>>Medium Priority</option>
            <option value="low" <?= $priority==='low'?'selected':'' ?>>Low Priority</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
          <a href="admin-ideas.php" class="btn btn-outline">Reset</a>
        </form>
        <span style="margin-left:auto;font-size:.82rem;color:var(--text-muted);"><?= $total ?> results</span>
      </div>

      <!-- Ideas Table -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-list me-2" style="color:var(--orange)"></i>Ideas Queue</span>
          <a href="admin-reports.php" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export CSV</a>
        </div>
        <div class="table-wrapper">
          <table id="ideasTable">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAll" style="cursor:pointer;"></th>
                <th>Idea ID</th>
                <th>Title</th>
                <th>Submitter</th>
                <th>Category</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Votes</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($ideas): ?>
                <?php foreach ($ideas as $idea): ?>
                  <tr id="row-<?= $idea['id'] ?>">
                    <td><input type="checkbox" class="row-check" value="<?= $idea['id'] ?>"></td>
                    <td><code style="font-size:.72rem;background:var(--bg);padding:.2rem .4rem;border-radius:4px;"><?= e($idea['idea_id']) ?></code></td>
                    <td style="max-width:160px;">
                      <a href="idea.php?id=<?= $idea['id'] ?>" style="font-weight:600;color:var(--text);"><?= e(substr($idea['title'],0,30)) ?><?= strlen($idea['title'])>30?'…':'' ?></a>
                      <?php if ($idea['is_featured']): ?><br><span class="badge badge-premium" style="font-size:.65rem;margin-top:.2rem;"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;"><?= e($idea['full_name']) ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= e($idea['cat_name']) ?></td>
                    <td id="status-<?= $idea['id'] ?>"><?= statusBadge($idea['status']) ?></td>
                    <td><?= priorityBadge($idea['priority']) ?></td>
                    <td><i class="fas fa-heart" style="color:var(--red);font-size:.8rem;margin-right:.2rem;"></i><?= $idea['vote_count'] ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= formatDate($idea['created_at'],'M j') ?></td>
                    <td>
                      <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                        <a href="idea.php?id=<?= $idea['id'] ?>" class="btn btn-outline btn-sm" title="View">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-primary btn-sm" title="Update Status"
                                onclick="openStatusModal(<?= $idea['id'] ?>, '<?= $idea['status'] ?>', '<?= e(addslashes($idea['title'])) ?>')">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" title="Delete"
                                onclick="deleteIdea(<?= $idea['id'] ?>)">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="10">
                  <div class="empty-state"><i class="fas fa-lightbulb"></i><h5>No ideas found</h5><p>Try adjusting your filters.</p></div>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="pagination" style="padding:1rem;">
            <?php for ($p=1; $p<=$pg['total_pages']; $p++): ?>
              <a href="admin-ideas.php?page=<?= $p ?>&status=<?= e($status) ?>&q=<?= e($search) ?>"
                 class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Status Update Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal-box" style="width:420px;">
    <div class="modal-header">
      <h4><i class="fas fa-edit me-2" style="color:var(--blue)"></i>Update Idea Status</h4>
      <button class="modal-close" data-modal-close="statusModal"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p id="statusModalTitle" style="font-weight:600;margin-bottom:1.25rem;color:var(--text-muted);font-size:.875rem;"></p>
      <input type="hidden" id="statusIdeaId">
      <div class="form-group">
        <label class="form-label">New Status</label>
        <select id="newStatus" class="form-control">
          <option value="pending">⏳ Pending</option>
          <option value="review">🔍 Under Review</option>
          <option value="approved">✅ Approved</option>
          <option value="funded">🚀 Funded</option>
          <option value="closed">❌ Closed</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Reviewer (optional)</label>
        <select id="assignReviewer" class="form-control">
          <option value="">— Unassigned —</option>
          <?php foreach ($reviewers as $r): ?>
            <option value="<?= $r['id'] ?>"><?= e($r['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-modal-close="statusModal">Cancel</button>
      <button class="btn btn-primary" onclick="submitStatusUpdate()"><i class="fas fa-save"></i> Update</button>
    </div>
  </div>
</div>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>

<script src="assets/js/app.js"></script>
<script src="assets/js/realtime.js"></script>
<script>
// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});

function openStatusModal(id, currentStatus, title) {
  document.getElementById('statusIdeaId').value = id;
  document.getElementById('statusModalTitle').textContent = 'Idea: ' + title;
  document.getElementById('newStatus').value = currentStatus;
  Modal.open('statusModal');
}

async function submitStatusUpdate() {
  const ideaId   = document.getElementById('statusIdeaId').value;
  const newStatus = document.getElementById('newStatus').value;
  const assignTo  = document.getElementById('assignReviewer').value;
  Spinner.show();
  try {
    // Update status
    let res = await fetch('admin-ideas.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=update_status&idea_id=${ideaId}&status=${newStatus}` });
    let data = await res.json();
    if (data.success) {
      // Update badge in table
      const cell = document.getElementById('status-' + ideaId);
      if (cell) cell.innerHTML = getStatusBadge(newStatus);
      Toast.show('Status updated to ' + newStatus, 'success');
      Modal.close('statusModal');
    }
    // Assign reviewer if selected
    if (assignTo) {
      await fetch('admin-ideas.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=assign&idea_id=${ideaId}&assigned_to=${assignTo}` });
    }
  } catch(e) { Toast.show('Update failed', 'error'); }
  Spinner.hide();
}

async function deleteIdea(id) {
  if (!confirm('Delete this idea? This cannot be undone.')) return;
  Spinner.show();
  try {
    const res = await fetch('admin-ideas.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=delete&idea_id=${id}` });
    const data = await res.json();
    if (data.success) {
      const row = document.getElementById('row-' + id);
      if (row) { row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
      Toast.show('Idea deleted', 'success');
    }
  } catch(e) { Toast.show('Delete failed', 'error'); }
  Spinner.hide();
}

function getStatusBadge(status) {
  const map = {
    pending:  '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>',
    review:   '<span class="badge badge-review"><i class="fas fa-search"></i> Under Review</span>',
    approved: '<span class="badge badge-approved"><i class="fas fa-check-circle"></i> Approved</span>',
    funded:   '<span class="badge badge-funded"><i class="fas fa-rocket"></i> Funded</span>',
    closed:   '<span class="badge badge-closed"><i class="fas fa-times-circle"></i> Closed</span>',
  };
  return map[status] || status;
}

initLiveSearch('searchIdeas', 'ideasTable');
</script>
</body>
</html>
