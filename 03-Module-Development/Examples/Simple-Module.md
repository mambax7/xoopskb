---
title: Simple Module Example
description: Complete working module with all necessary files
tags:
  - examples
  - simple-module
  - complete-example
  - module-development
created: 2026-01-28
updated: 2026-01-28
---

# Simple Module Example - Blog

A complete, working simple module that demonstrates core XOOPS concepts.

## Module Structure

```
blog/
├── xoops_version.php
├── index.php
├── admin.php
├── class/
│   └── Handler/
│       └── BlogHandler.php
├── templates/
│   ├── blog_index.html
│   ├── blog_view.html
│   └── admin/blog_list.html
├── assets/
│   └── css/style.css
└── sql/mysql.sql
```

## xoops_version.php

```php
<?php
$modversion = [
    'name'           => 'Simple Blog',
    'version'        => '1.0.0',
    'description'    => 'A simple blog module',
    'author'         => 'Your Name',
    'dirname'        => 'blog',
    'sqlfile'        => ['mysql' => 'sql/mysql.sql'],
    'tables'         => ['blog_posts'],
    'hasAdmin'       => 1,
    'hasMain'        => 1,
];
?>
```

## Database Schema

```sql
CREATE TABLE `xoops_blog_posts` (
  `post_id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_title` VARCHAR(255) NOT NULL,
  `post_content` LONGTEXT NOT NULL,
  `post_author` INT NOT NULL,
  `post_created` INT NOT NULL,
  `post_published` TINYINT(1) DEFAULT 0,
  INDEX `post_author` (`post_author`),
  INDEX `post_published` (`post_published`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
```

## Handler Class

```php
<?php
class BlogHandler
{
    private $db;
    private $table;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->table = $this->db->prefix('blog_posts');
    }
    
    public function getAll($limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE post_published = 1 
                ORDER BY post_created DESC 
                LIMIT ?, ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $offset, $limit);
        $stmt->execute();
        
        $posts = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_object()) {
            $posts[] = $row;
        }
        
        return $posts;
    }
    
    public function get($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_object();
    }
    
    public function insert($post)
    {
        $sql = "INSERT INTO {$this->table} 
                (post_title, post_content, post_author, post_created, post_published)
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiii', 
            $post->post_title,
            $post->post_content,
            $post->post_author,
            $post->post_created,
            $post->post_published
        );
        
        return $stmt->execute() ? $this->db->insert_id : 0;
    }
    
    public function update($post)
    {
        $sql = "UPDATE {$this->table}
                SET post_title = ?, post_content = ?, post_published = ?
                WHERE post_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssii',
            $post->post_title,
            $post->post_content,
            $post->post_published,
            $post->post_id
        );
        
        return $stmt->execute();
    }
    
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }
}
?>
```

## Frontend (index.php)

```php
<?php
require_once __DIR__ . '/../../mainfile.php';

global $xoopsDB;

$op = $_GET['op'] ?? 'index';
$id = $_GET['id'] ?? 0;

$blogHandler = new BlogHandler($xoopsDB);
$xoopsTheme = \Xoops::getInstance()->getTheme();

switch ($op) {
    case 'view':
        $post = $blogHandler->get($id);
        $xoopsTheme->assign('post', $post);
        $template = 'blog_view.html';
        break;
        
    case 'index':
    default:
        $page = $_GET['page'] ?? 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $posts = $blogHandler->getAll($limit, $offset);
        $xoopsTheme->assign('posts', $posts);
        $xoopsTheme->assign('page', $page);
        $template = 'blog_index.html';
        break;
}

$xoopsTheme->display(
    \Xoops::getInstance()->getModulePath() . "/templates/$template"
);
?>
```

## Admin Interface (admin.php)

```php
<?php
require_once __DIR__ . '/../../mainfile.php';

if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
    exit('Access denied');
}

global $xoopsDB;

$op = $_GET['op'] ?? 'list';
$id = $_GET['id'] ?? 0;

$blogHandler = new BlogHandler($xoopsDB);

switch ($op) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = new \stdClass();
            $post->post_title = $_POST['title'];
            $post->post_content = $_POST['content'];
            $post->post_author = $xoopsUser->uid();
            $post->post_created = time();
            $post->post_published = $_POST['published'] ? 1 : 0;
            
            $blogHandler->insert($post);
        }
        break;
        
    case 'delete':
        $blogHandler->delete($id);
        break;
}

$posts = $blogHandler->getAll(999, 0);
?>

<h2>Manage Blog Posts</h2>
<a href="admin.php?op=create" class="btn btn-primary">New Post</a>

<table class="table">
    {foreach from=$posts item=post}
        <tr>
            <td>{$post->post_title}</td>
            <td>{if $post->post_published}Published{else}Draft{/if}</td>
            <td>
                <a href="admin.php?op=edit&id={$post->post_id}">Edit</a>
                <a href="admin.php?op=delete&id={$post->post_id}">Delete</a>
            </td>
        </tr>
    {/foreach}
</table>
```

## Frontend Templates

### blog_index.html

```smarty
<div class="blog-container">
    <h1>Blog</h1>
    
    {if $posts}
        <div class="posts-list">
            {foreach from=$posts item=post}
                <article class="post">
                    <h2><a href="?op=view&id={$post->post_id}">{$post->post_title|escape}</a></h2>
                    <small>Posted on {$post->post_created|date_format:"%Y-%m-%d"}</small>
                    <p>{$post->post_content|truncate:200|escape}</p>
                    <a href="?op=view&id={$post->post_id}">Read More</a>
                </article>
            {/foreach}
        </div>
    {else}
        <p>No posts available.</p>
    {/if}
</div>
```

### blog_view.html

```smarty
<article class="blog-post">
    <h1>{$post->post_title|escape}</h1>
    <small>Posted on {$post->post_created|date_format:"%Y-%m-%d %H:%M"}</small>
    
    <div class="post-content">
        {$post->post_content}
    </div>
    
    <a href="./">Back to Blog</a>
</article>
```

## CSS Styling

```css
/* assets/css/style.css */

.blog-container {
    padding: 20px 0;
}

.post {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.post h2 {
    margin-top: 0;
}

.blog-post {
    max-width: 800px;
    margin: 0 auto;
}

.post-content {
    line-height: 1.6;
    margin: 20px 0;
}
```

## Key Features

- Display published blog posts with pagination
- View individual posts
- Admin interface to create, edit, delete posts
- Draft/Published status
- Timestamps

## Testing

1. Install module via XOOPS admin
2. Access frontend at `modules/blog/`
3. Visit admin at `modules/blog/admin.php`
4. Create a test post and publish it

## Related Documentation

See also:
- [[../Patterns/MVC-Pattern]] for patterns
- [[../Patterns/Repository-Pattern]] for data access
- [[../Best-Practices/Code-Organization]] for structure

---

Tags: #examples #simple-module #complete-example #module-development
