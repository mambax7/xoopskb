# xmfBlog Module — Feature Checklist

## Frontend Pages

### Post Listing (`index.php`)
- [ ] Default view shows all published posts (status=Published, row_flag='A')
- [ ] Respects XOOPS group permissions (xmfblog_read)
- [ ] Pagination with configurable posts_per_page
- [ ] Sort by date, title, views, weight
- [ ] Category filter via `?category_id=N`
- [ ] Featured image thumbnails from MediaManager
- [ ] Tags displayed per post with links to tag filter
- [ ] Search form and results (`?op=search&search=term`)

### My Posts (`index.php?op=myposts`)
- [ ] Shows only posts by the logged-in user (any status)
- [ ] Requires login (redirects to user.php if anonymous)
- [ ] Pagination

### Single Post View (`index.php?op=view&id=N`)
- [ ] Displays full post body with HTML sanitization
- [ ] Markdown formatting via MarkdownFormatterPlugin
- [ ] Featured image from MediaManager
- [ ] SEO meta tags via MetaGenerator
- [ ] View counter incremented atomically
- [ ] Tags and region breadcrumbs displayed
- [ ] Comments section (threaded, with depth indentation)
- [ ] Comment submission form with CSRF token
- [ ] Comment moderation based on module config

### Category View (`category.php?id=N`)
- [ ] Lists published posts in selected category
- [ ] Pagination, sorting

### Tag Filter (`index.php?op=tag&slug=X`)
- [ ] Shows posts tagged with given tag
- [ ] Pagination

### Region Filter (`index.php?op=region&term_id=N`)
- [ ] Shows posts in region + descendant regions
- [ ] Pagination

### RSS Feed (`index.php?op=rss`)
- [ ] Valid RSS 2.0 XML output
- [ ] Configurable item count

### Post Submission (`submit.php`)
- [ ] Frontend post submission for logged-in users
- [ ] Respects xmfblog_submit permission

## Admin Pages

### Dashboard (`admin/index.php`)
- [ ] Post statistics: total, published, pending, drafts
- [ ] Category count
- [ ] Comment statistics: total, awaiting moderation
- [ ] Notification badge (unread count)
- [ ] Test data load/clear buttons

### Posts (`admin/post.php`)
- [ ] List all posts with sortable table
- [ ] Title column links to **frontend** view (`index.php?op=view&id=N`)
- [ ] Thumbnail preview from MediaManager
- [ ] Status badges (Draft, Published, Pending, Rejected, Archived)
- [ ] Tags displayed as badges
- [ ] Inline status actions: Approve, Reject, Publish, Unpublish (POST forms with CSRF)
- [ ] Add/Edit form with FormBuilder + FormBinder
- [ ] Image upload with Cropper.js integration
- [ ] Tags input (comma-separated)
- [ ] Hierarchical taxonomy multi-selects
- [ ] Version history panel with compare and restore
- [ ] Bulk operations: publish, unpublish, delete
- [ ] Delete with confirmation dialog
- [ ] CSRF protection on all mutating operations

### Categories (`admin/category.php`)
- [ ] List with tree indentation
- [ ] Name column links to **frontend** category view
- [ ] Add/Edit with parent category selection
- [ ] Slug auto-generation
- [ ] Delete with confirmation
- [ ] CSRF protection on save

### Comments (`admin/comments.php`)
- [ ] Filter tabs: All, Pending (with count badge), Published
- [ ] Approve/Reject via POST forms with CSRF
- [ ] Delete with confirmation
- [ ] Post title lookup for each comment

### Taxonomy (`admin/taxonomy.php`)
- [ ] Vocabulary management (create, edit, delete)
- [ ] Term management within vocabularies
- [ ] Hierarchical term nesting (parent_id)
- [ ] CSRF on save operations

### Audit Log (`admin/audit.php`)
- [ ] Filter by entity type and action
- [ ] Actor column shows **username** with link to userinfo.php
- [ ] IP address displayed
- [ ] JSON change details decoded and displayed

### Reports (`admin/reports.php`)
- [ ] Posts by Status tab
- [ ] Posts by Category tab
- [ ] Recent Activity tab (latest 20 posts)
- [ ] CSV export for each report
- [ ] Status values displayed as labels

### Versions (`admin/versions.php`)
- [ ] Side-by-side diff of two versions
- [ ] Word-level diff highlighting (green=added, red/strikethrough=deleted)
- [ ] Full content displayed (no truncation)
- [ ] Unchanged fields in collapsible details section
- [ ] Restore to previous version with safety snapshot
- [ ] Compare any two versions or version vs current

### Permissions (`admin/permissions.php`)
- [ ] View, Submit, Auto-Approve, Admin permission tabs
- [ ] XoopsGroupPermForm for each permission type

### Import/Export (`admin/importexport.php`)
- [ ] CSV/JSON import with CSRF
- [ ] CSV/JSON export

### Blocks Admin (`admin/blocksadmin.php`)
- [ ] Block position and visibility management

### About (`admin/about.php`)
- [ ] Module info via Xmf\Module\Admin::renderAbout()

### Clone (`admin/clone.php`)
- [ ] Module cloning with namespace replacement

## Blocks

### Recent Posts Block
- [ ] Shows N most recent published posts
- [ ] Configurable post count
- [ ] Uses prefixed table name via repository query()

### Categories Block
- [ ] Lists all categories with post counts

## API (`api.php`)

- [ ] REST endpoints: index, show, store, update, destroy
- [ ] Rate limiting (30 req/min per IP)
- [ ] Authentication via XOOPS session ($GLOBALS['xoopsUser'])
- [ ] Permission middleware (xmfblog_submit for create/update, xmfblog_admin for delete)
- [ ] Configurable enable/disable via module preferences
- [ ] JSON response format via ApiResponse

## Security

- [ ] CSRF tokens on all mutating operations (POST forms)
- [ ] HTML sanitization on body content (strip_tags with allow-list)
- [ ] Markdown plugin rejects javascript: URLs
- [ ] Comment content escaped in templates
- [ ] XSS prevention via htmlspecialchars on all user-displayed data
