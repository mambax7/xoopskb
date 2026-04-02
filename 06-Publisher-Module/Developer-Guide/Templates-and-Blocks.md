# Templates and Blocks

## Overview

Publisher provides customizable templates for displaying articles and blocks for sidebar/widget integration. This guide covers template customization and block configuration.

## Template Files

### Core Templates

| Template | Purpose |
|----------|---------|
| `publisher_index.tpl` | Module homepage |
| `publisher_item.tpl` | Single article view |
| `publisher_category.tpl` | Category listing |
| `publisher_archive.tpl` | Archive page |
| `publisher_search.tpl` | Search results |
| `publisher_submit.tpl` | Article submission form |
| `publisher_print.tpl` | Print-friendly view |

### Block Templates

| Template | Purpose |
|----------|---------|
| `publisher_block_latest.tpl` | Latest articles block |
| `publisher_block_spotlight.tpl` | Featured article block |
| `publisher_block_category.tpl` | Category list block |
| `publisher_block_author.tpl` | Author articles block |

## Template Variables

### Article Variables

```smarty
{* Available in publisher_item.tpl *}
<{$item.title}>           {* Article title *}
<{$item.body}>            {* Full content *}
<{$item.summary}>         {* Summary/excerpt *}
<{$item.author}>          {* Author name *}
<{$item.authorid}>        {* Author user ID *}
<{$item.datesub}>         {* Publication date *}
<{$item.datemodified}>    {* Last modified date *}
<{$item.counter}>         {* View count *}
<{$item.rating}>          {* Average rating *}
<{$item.votes}>           {* Number of votes *}
<{$item.categoryname}>    {* Category name *}
<{$item.categorylink}>    {* Category URL *}
<{$item.itemurl}>         {* Article URL *}
<{$item.image}>           {* Featured image *}
```

### Category Variables

```smarty
{* Available in publisher_category.tpl *}
<{$category.name}>        {* Category name *}
<{$category.description}> {* Category description *}
<{$category.image}>       {* Category image *}
<{$category.total}>       {* Article count *}
<{$category.link}>        {* Category URL *}
```

## Customizing Templates

### Override Location

Copy templates to your theme to customize:

```
themes/mytheme/modules/publisher/
├── publisher_index.tpl
├── publisher_item.tpl
└── blocks/
    └── publisher_block_latest.tpl
```

### Example: Custom Article Template

```smarty
{* themes/mytheme/modules/publisher/publisher_item.tpl *}
<article class="publisher-article">
    <header>
        <h1><{$item.title}></h1>
        <div class="meta">
            <span class="author">By <{$item.author}></span>
            <span class="date"><{$item.datesub}></span>
            <span class="category">
                <a href="<{$item.categorylink}>"><{$item.categoryname}></a>
            </span>
        </div>
    </header>

    <{if $item.image}>
    <figure class="featured-image">
        <img src="<{$item.image}>" alt="<{$item.title}>">
    </figure>
    <{/if}>

    <div class="content">
        <{$item.body}>
    </div>

    <footer>
        <{if $item.who_when}>
            <p class="attribution"><{$item.who_when}></p>
        <{/if}>

        <div class="actions">
            <{if $can_edit}>
                <a href="<{$xoops_url}>/modules/publisher/submit.php?itemid=<{$item.itemid}>">
                    Edit Article
                </a>
            <{/if}>
            <a href="<{$item.printlink}>" target="_blank">Print</a>
            <a href="<{$item.maillink}>">Email</a>
        </div>
    </footer>
</article>
```

## Blocks

### Available Blocks

| Block | Description |
|-------|-------------|
| Latest News | Shows recent articles |
| Spotlight | Featured article highlight |
| Category Menu | Category navigation |
| Archives | Archive links |
| Top Authors | Most active writers |
| Popular Items | Most viewed articles |

### Block Options

#### Latest News Block

| Option | Description |
|--------|-------------|
| Items to display | Number of articles |
| Category filter | Limit to specific categories |
| Show summary | Display article excerpt |
| Title length | Truncate titles |
| Template | Block template file |

### Custom Block Template

```smarty
{* themes/mytheme/modules/publisher/blocks/publisher_block_latest.tpl *}
<div class="publisher-latest-block">
    <{foreach item=item from=$block.items}>
    <article class="block-item">
        <h4>
            <a href="<{$item.link}>"><{$item.title}></a>
        </h4>
        <{if $block.show_summary}>
            <p><{$item.summary}></p>
        <{/if}>
        <div class="block-meta">
            <span class="date"><{$item.date}></span>
            <span class="views"><{$item.counter}> views</span>
        </div>
    </article>
    <{/foreach}>
</div>
```

## Template Tricks

### Conditional Display

```smarty
{* Show different content for different users *}
<{if $xoops_isadmin}>
    <a href="admin/item.php?op=edit&itemid=<{$item.itemid}>">Admin Edit</a>
<{elseif $item.uid == $xoops_userid}>
    <a href="submit.php?itemid=<{$item.itemid}>">Edit Your Article</a>
<{/if}>
```

### Custom CSS Class

```smarty
{* Add status-based styling *}
<article class="article <{$item.status}>">
    {* Content *}
</article>
```

### Date Formatting

```smarty
{* Format dates with Smarty *}
<time datetime="<{$item.datesub|date_format:'%Y-%m-%d'}>">
    <{$item.datesub|date_format:$xoops_config.dateformat}>
</time>
```

## Related Documentation

- [[../User-Guide/Basic-Configuration]] - Module settings
- [[../User-Guide/Creating-Articles]] - Content management
- [[../../04-API-Reference/Template/Template-System]] - XOOPS template engine
- [[../../02-Core-Concepts/Themes/Theme-Development]] - Theme customization
